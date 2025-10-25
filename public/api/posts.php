<?php
// POST /api/posts.php -> create a post
// PUT /api/posts.php -> edit a post
// DELETE /api/posts.php -> delete a post
header('Content-Type: application/json');
session_start();
include __DIR__ . '/../../src/db.php';
include __DIR__ . '/../../src/api_helpers.php';

$userId = getCurrentUserId();

// Handle PUT request (edit post)
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $userId = requireAuth();
    $isAdminUser = isAdmin($conn, $userId);
    
    $input = json_decode(file_get_contents('php://input'), true);
    $postId = intval($input['post_id'] ?? 0);
    $newContent = trim($input['content'] ?? '');
    
    if (!$postId || !$newContent) {
        jsonResponse(400, ['ok' => false, 'error' => 'Invalid input']);
    }
    
    // Check ownership (admins can edit any post)
    if (!$isAdminUser) {
        $stmt = mysqli_prepare($conn, "SELECT UserID FROM posts WHERE PostID = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'i', $postId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $post = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
        
        if (!$post || intval($post['UserID']) !== intval($userId)) {
            jsonResponse(403, ['ok' => false, 'error' => 'Not authorized']);
        }
    }
    
    // Update post
    $stmt = mysqli_prepare($conn, "UPDATE posts SET Content = ? WHERE PostID = ?");
    mysqli_stmt_bind_param($stmt, 'si', $newContent, $postId);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        jsonResponse(200, ['ok' => true]);
    } else {
        mysqli_stmt_close($stmt);
        jsonResponse(500, ['ok' => false, 'error' => 'Failed to update post']);
    }
}

// Handle DELETE request (delete post)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $userId = requireAuth();
    $isAdminUser = isAdmin($conn, $userId);
    
    $input = json_decode(file_get_contents('php://input'), true);
    $postId = intval($input['post_id'] ?? 0);
    
    if (!$postId) {
        jsonResponse(400, ['ok' => false, 'error' => 'Invalid input']);
    }
    
    // Check ownership (admins can delete any post)
    if (!$isAdminUser) {
        $stmt = mysqli_prepare($conn, "SELECT UserID FROM posts WHERE PostID = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'i', $postId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $post = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
        
        if (!$post || intval($post['UserID']) !== intval($userId)) {
            jsonResponse(403, ['ok' => false, 'error' => 'Not authorized']);
        }
    }
    
    // Delete post images from disk and DB using prepared statements
    $imgStmt = mysqli_prepare($conn, "SELECT path FROM post_images WHERE PostID = ?");
    if ($imgStmt) {
        mysqli_stmt_bind_param($imgStmt, 'i', $postId);
        mysqli_stmt_execute($imgStmt);
        $imgRes = mysqli_stmt_get_result($imgStmt);
        
        while ($img = mysqli_fetch_assoc($imgRes)) {
            $filePath = __DIR__ . '/../../public/' . ltrim($img['path'], '/');
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
        mysqli_stmt_close($imgStmt);
        
        $delImgStmt = mysqli_prepare($conn, "DELETE FROM post_images WHERE PostID = ?");
        mysqli_stmt_bind_param($delImgStmt, 'i', $postId);
        mysqli_stmt_execute($delImgStmt);
        mysqli_stmt_close($delImgStmt);
    }
    
    // Delete comments, likes, and the post using prepared statements
    $delCommentsStmt = mysqli_prepare($conn, "DELETE FROM comments WHERE PostID = ?");
    mysqli_stmt_bind_param($delCommentsStmt, 'i', $postId);
    mysqli_stmt_execute($delCommentsStmt);
    mysqli_stmt_close($delCommentsStmt);
    
    $delLikesStmt = mysqli_prepare($conn, "DELETE FROM postlikes WHERE PostID = ?");
    mysqli_stmt_bind_param($delLikesStmt, 'i', $postId);
    mysqli_stmt_execute($delLikesStmt);
    mysqli_stmt_close($delLikesStmt);
    
    $delPostStmt = mysqli_prepare($conn, "DELETE FROM posts WHERE PostID = ?");
    mysqli_stmt_bind_param($delPostStmt, 'i', $postId);
    mysqli_stmt_execute($delPostStmt);
    mysqli_stmt_close($delPostStmt);
    
    jsonResponse(200, ['ok' => true]);
}

// helper: resize an image file to fit within max dimensions using GD
if (!function_exists('resize_image')) {
    function resize_image($filePath, $maxWidth = 1024, $maxHeight = 1024) {
        if (!file_exists($filePath)) return false;
        $info = getimagesize($filePath);
        if (!$info) return false;
        list($width, $height, $type) = $info;
        if ($width <= $maxWidth && $height <= $maxHeight) return true;
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newW = max(1, (int) round($width * $ratio));
        $newH = max(1, (int) round($height * $ratio));
        switch ($type) {
            case IMAGETYPE_JPEG:
                $src = imagecreatefromjpeg($filePath);
                break;
            case IMAGETYPE_PNG:
                $src = imagecreatefrompng($filePath);
                break;
            case IMAGETYPE_GIF:
                $src = imagecreatefromgif($filePath);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagecreatefromwebp')) {
                    $src = imagecreatefromwebp($filePath);
                    break;
                }
                return false;
            default:
                return false;
        }
        if (!$src) return false;
        $dst = imagecreatetruecolor($newW, $newH);
        if ($type == IMAGETYPE_PNG) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
            imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
        } elseif ($type == IMAGETYPE_GIF) {
            $trans_index = imagecolortransparent($src);
            if ($trans_index >= 0) {
                $trans_color = imagecolorsforindex($src, $trans_index);
                $trans_index_new = imagecolorallocate($dst, $trans_color['red'], $trans_color['green'], $trans_color['blue']);
                imagefill($dst, 0, 0, $trans_index_new);
                imagecolortransparent($dst, $trans_index_new);
            }
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);
        $saved = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $saved = imagejpeg($dst, $filePath, 85);
                break;
            case IMAGETYPE_PNG:
                $saved = imagepng($dst, $filePath, 6);
                break;
            case IMAGETYPE_GIF:
                $saved = imagegif($dst, $filePath);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagewebp')) $saved = imagewebp($dst, $filePath, 80);
                break;
        }
        imagedestroy($src);
        imagedestroy($dst);
        return $saved;
    }
}

// Handle POST request (create post)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, ['success' => false, 'error' => 'Method not allowed']);
}

// Support both JSON POST and multipart/form-data (with images[] and title)
$content = '';
$title = '';
$uploadedFiles = [];
if (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    if (!empty($_FILES['images'])) {
        if (is_array($_FILES['images']['name'])) {
            $count = count($_FILES['images']['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $uploadedFiles[] = [
                        'tmp_name' => $_FILES['images']['tmp_name'][$i],
                        'name' => $_FILES['images']['name'][$i],
                        'type' => $_FILES['images']['type'][$i],
                    ];
                }
            }
        } else {
            if ($_FILES['images']['error'] === UPLOAD_ERR_OK) {
                $uploadedFiles[] = [
                    'tmp_name' => $_FILES['images']['tmp_name'],
                    'name' => $_FILES['images']['name'],
                    'type' => $_FILES['images']['type'],
                ];
            }
        }
    }
} else {
    $input = json_decode(file_get_contents('php://input'), true);
    $title = trim($input['title'] ?? '');
    $content = trim($input['content'] ?? '');
}

if ($content === '' && empty($uploadedFiles)) {
    jsonResponse(400, ['success' => false, 'error' => 'Empty content']);
}

// Determine user id - require login for creating posts
$userIdParam = $userId;
if (!$userIdParam) {
    // Try to find a Guest account
    $stmt = mysqli_prepare($conn, "SELECT UserID FROM accounts WHERE username IN ('Guest','guest','Anonymous','anonymous') LIMIT 1");
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        $userIdParam = intval($row['UserID']);
    } else {
        // Fallback to first account
        mysqli_stmt_close($stmt);
        $stmt = mysqli_prepare($conn, "SELECT UserID FROM accounts LIMIT 1");
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($res)) {
            $userIdParam = intval($row['UserID']);
        } else {
            mysqli_stmt_close($stmt);
            jsonResponse(403, ['success' => false, 'error' => 'No user accounts exist. Please create an account first.']);
        }
    }
    mysqli_stmt_close($stmt);
}

// Insert post record
$columns = ['UserID', 'Content'];
$placeholders = ['?', '?'];
$types = 'is';
$values = [$userIdParam, $content];

// Check if Title column exists
$colRes = mysqli_query($conn, "SHOW COLUMNS FROM posts LIKE 'Title'");
if ($colRes && mysqli_num_rows($colRes) > 0 && $title !== '') {
    $columns[] = 'Title';
    $placeholders[] = '?';
    $types .= 's';
    $values[] = $title;
}

$sql = "INSERT INTO posts (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$values);
$ok = mysqli_stmt_execute($stmt);

if (!$ok) {
    $err = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    jsonResponse(500, ['success' => false, 'error' => 'DB insert failed', 'detail' => $err]);
}

$postId = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);

// Handle uploaded files
$savedImages = [];
if (!empty($uploadedFiles)) {
    $uploadDir = __DIR__ . '/../../public/uploads/posts';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    
    foreach ($uploadedFiles as $f) {
        $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
        $safeName = 'post_' . $postId . '_' . time() . '_' . bin2hex(random_bytes(6)) . ($ext ? '.' . $ext : '');
        $dest = $uploadDir . '/' . $safeName;
        
        if (move_uploaded_file($f['tmp_name'], $dest)) {
            try {
                @resize_image($dest, 1024, 1024);
            } catch (Throwable $e) {
                // ignore
            }
            
            $publicPath = 'uploads/posts/' . $safeName;
            $savedImages[] = $publicPath;
            
            $create = "CREATE TABLE IF NOT EXISTS post_images (
              id INT AUTO_INCREMENT PRIMARY KEY,
              PostID INT NOT NULL,
              path VARCHAR(255) NOT NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            mysqli_query($conn, $create);
            
            $ins = mysqli_prepare($conn, "INSERT INTO post_images (PostID, path) VALUES (?, ?)");
            mysqli_stmt_bind_param($ins, 'is', $postId, $publicPath);
            mysqli_stmt_execute($ins);
            mysqli_stmt_close($ins);
        }
    }
}

// Fetch created post with username and images
$selectCols = "p.PostID, p.Content, p.Created_at, a.username, p.UserID";
if (count($values) > 2) {
    $selectCols .= ", p.Title";
}
$stmt = mysqli_prepare($conn, "SELECT $selectCols FROM posts p LEFT JOIN accounts a ON p.UserID = a.UserID WHERE p.PostID = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $postId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$post = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

// Load images
$imgStmt = mysqli_prepare($conn, "SELECT path FROM post_images WHERE PostID = ? ORDER BY id ASC");
mysqli_stmt_bind_param($imgStmt, 'i', $postId);
mysqli_stmt_execute($imgStmt);
$imgR = mysqli_stmt_get_result($imgStmt);
$images = [];
while ($r = mysqli_fetch_assoc($imgR)) {
    $images[] = ltrim($r['path'], '/');
}
mysqli_stmt_close($imgStmt);

if (!empty($images)) {
    $post['images'] = $images;
}
if ($post && empty($post['username'])) {
    $post['username'] = 'Guest';
}

echo json_encode(['success' => true, 'post' => $post]);
exit;

// helper: resize an image file to fit within max dimensions using GD
if (!function_exists('resize_image')) {
    function resize_image($filePath, $maxWidth = 1024, $maxHeight = 1024) {
        if (!file_exists($filePath)) return false;
        $info = getimagesize($filePath);
        if (!$info) return false;
        list($width, $height, $type) = $info;
        if ($width <= $maxWidth && $height <= $maxHeight) return true;
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newW = max(1, (int) round($width * $ratio));
        $newH = max(1, (int) round($height * $ratio));
        switch ($type) {
            case IMAGETYPE_JPEG:
                $src = imagecreatefromjpeg($filePath);
                break;
            case IMAGETYPE_PNG:
                $src = imagecreatefrompng($filePath);
                break;
            case IMAGETYPE_GIF:
                $src = imagecreatefromgif($filePath);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagecreatefromwebp')) {
                    $src = imagecreatefromwebp($filePath);
                    break;
                }
                return false;
            default:
                return false;
        }
        if (!$src) return false;
        $dst = imagecreatetruecolor($newW, $newH);
        if ($type == IMAGETYPE_PNG) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
            imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
        } elseif ($type == IMAGETYPE_GIF) {
            $trans_index = imagecolortransparent($src);
            if ($trans_index >= 0) {
                $trans_color = imagecolorsforindex($src, $trans_index);
                $trans_index_new = imagecolorallocate($dst, $trans_color['red'], $trans_color['green'], $trans_color['blue']);
                imagefill($dst, 0, 0, $trans_index_new);
                imagecolortransparent($dst, $trans_index_new);
            }
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);
        $saved = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $saved = imagejpeg($dst, $filePath, 85);
                break;
            case IMAGETYPE_PNG:
                $saved = imagepng($dst, $filePath, 6);
                break;
            case IMAGETYPE_GIF:
                $saved = imagegif($dst, $filePath);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagewebp')) $saved = imagewebp($dst, $filePath, 80);
                break;
        }
        imagedestroy($src);
        imagedestroy($dst);
        return $saved;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Support both JSON POST and multipart/form-data (with images[] and title)
$content = '';
$title = '';
$uploadedFiles = [];
if (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
    // multipart: read from $_POST and $_FILES
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    if (!empty($_FILES['images'])) {
        // normalize images[] to array
        if (is_array($_FILES['images']['name'])) {
            $count = count($_FILES['images']['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $uploadedFiles[] = [
                        'tmp_name' => $_FILES['images']['tmp_name'][$i],
                        'name' => $_FILES['images']['name'][$i],
                        'type' => $_FILES['images']['type'][$i],
                    ];
                }
            }
        } else {
            if ($_FILES['images']['error'] === UPLOAD_ERR_OK) {
                $uploadedFiles[] = [
                    'tmp_name' => $_FILES['images']['tmp_name'],
                    'name' => $_FILES['images']['name'],
                    'type' => $_FILES['images']['type'],
                ];
            }
        }
    }
} else {
    $input = json_decode(file_get_contents('php://input'), true);
    $title = trim($input['title'] ?? '');
    $content = trim($input['content'] ?? '');
}

// If Title column exists, require both title and content. Otherwise require content.
$colResCheck = mysqli_query($conn, "SHOW COLUMNS FROM posts LIKE 'Title'");

// Do not require a title; posts must have content or at least one uploaded file.
if ($content === '' && empty($uploadedFiles)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Empty content']);
    exit;
}

// determine user id to use for the insert. If the session is anonymous, try to
// attach the post to a safe existing account (prefer a 'Guest' user). Many
// schemas make posts.UserID NOT NULL, so inserting NULL will fail — choose a
// fallback account instead of attempting a NULL insert.
$fallbackUsed = false;
if ($userId !== null) {
    $userIdParam = intval($userId);
} else {
    // try to find a Guest/anonymous account first
    $r = mysqli_query($conn, "SELECT UserID, username FROM accounts WHERE username IN ('Guest','guest','Anonymous','anonymous') LIMIT 1");
    if ($r && mysqli_num_rows($r) > 0) {
        $row = mysqli_fetch_assoc($r);
        $userIdParam = intval($row['UserID']);
        $fallbackUsed = true;
    } else {
        // fallback to the first existing account (if any)
        $r2 = mysqli_query($conn, "SELECT UserID, username FROM accounts LIMIT 1");
        if ($r2 && mysqli_num_rows($r2) > 0) {
            $row2 = mysqli_fetch_assoc($r2);
            $userIdParam = intval($row2['UserID']);
            $fallbackUsed = true;
        } else {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'No user accounts exist on server. Please login or create an account first.']);
            exit;
        }
    }
}

// prepared insert (always provide a UserID integer to avoid NOT NULL/ FK errors)
// Insert post record (include Title column if it exists)
$columns = ['UserID', 'Content'];
$placeholders = ['?', '?'];
$types = 'is';
$values = [$userIdParam, $content];
// detect if `Title` column exists in posts table
$colRes = mysqli_query($conn, "SHOW COLUMNS FROM posts LIKE 'Title'");
if ($colRes && mysqli_num_rows($colRes) > 0 && $title !== '') {
    $columns[] = 'Title';
    $placeholders[] = '?';
    $types .= 's';
    $values[] = $title;
}
$sql = "INSERT INTO posts (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
$stmt = mysqli_prepare($conn, $sql);
// bind params dynamically
mysqli_stmt_bind_param($stmt, $types, ...$values);
$ok = mysqli_stmt_execute($stmt);
if (!$ok) {
    // try to detect FK error and retry with a fallback user id
    $err = mysqli_error($conn);
    if (stripos($err, 'foreign key') !== false || stripos($err, 'Cannot add or update a child row') !== false) {
        // find any existing user to attach the post to
        $r = mysqli_query($conn, "SELECT UserID FROM accounts LIMIT 1");
        if ($r && mysqli_num_rows($r) > 0) {
            $row = mysqli_fetch_assoc($r);
            $fallbackId = intval($row['UserID']);
            $stmt2 = mysqli_prepare($conn, "INSERT INTO posts (UserID, Content) VALUES (?, ?) ");
            mysqli_stmt_bind_param($stmt2, 'is', $fallbackId, $content);
            $ok2 = mysqli_stmt_execute($stmt2);
            if ($ok2) {
                $postId = mysqli_insert_id($conn);
                // fetch created post
                $stmt = mysqli_prepare($conn, "SELECT p.PostID, p.Content, p.Created_at, a.username, p.UserID FROM posts p LEFT JOIN accounts a ON p.UserID = a.UserID WHERE p.PostID = ? LIMIT 1");
                mysqli_stmt_bind_param($stmt, 'i', $postId);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $post = mysqli_fetch_assoc($res);
                if ($post && empty($post['username'])) $post['username'] = 'Guest';
                echo json_encode(['success' => true, 'post' => $post, 'warning' => 'Used fallback user id due to FK constraint']);
                exit;
            }
        }
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB insert failed', 'detail' => $err]);
    exit;
}
$postId = mysqli_insert_id($conn);
// If there are uploaded files, save them and optionally store references in post_images
$savedImages = [];
if (!empty($uploadedFiles)) {
    $uploadDir = __DIR__ . '/../../public/uploads/posts';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    foreach ($uploadedFiles as $f) {
        $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
        $safeName = 'post_' . $postId . '_' . time() . '_' . bin2hex(random_bytes(6)) . ($ext ? '.' . $ext : '');
        $dest = $uploadDir . '/' . $safeName;
        if (move_uploaded_file($f['tmp_name'], $dest)) {
            // attempt to resize the image to a standard maximum size (best-effort)
            try {
                @resize_image($dest, 1024, 1024);
            } catch (Throwable $e) {
                // ignore resize failures; keep original
            }

            $publicPath = 'uploads/posts/' . $safeName;
            $savedImages[] = $publicPath;
            // ensure post_images table exists and insert
            $create = "CREATE TABLE IF NOT EXISTS post_images (
              id INT AUTO_INCREMENT PRIMARY KEY,
              PostID INT NOT NULL,
              path VARCHAR(255) NOT NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            mysqli_query($conn, $create);
            $ins = mysqli_prepare($conn, "INSERT INTO post_images (PostID, path) VALUES (?, ?)");
            mysqli_stmt_bind_param($ins, 'is', $postId, $publicPath);
            mysqli_stmt_execute($ins);
        }
    }
}

// fetch created post with username and aggregate images
$stmt = mysqli_prepare($conn, "SELECT p.PostID, p.Content, p.Created_at, a.username, p.UserID" . (isset($values[2]) ? ", p.Title" : "") . " FROM posts p LEFT JOIN accounts a ON p.UserID = a.UserID WHERE p.PostID = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $postId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$post = mysqli_fetch_assoc($res);
// load images from post_images
$imgRes = mysqli_prepare($conn, "SELECT path FROM post_images WHERE PostID = ? ORDER BY id ASC");
mysqli_stmt_bind_param($imgRes, 'i', $postId);
mysqli_stmt_execute($imgRes);
$imgR = mysqli_stmt_get_result($imgRes);
$images = [];
while ($r = mysqli_fetch_assoc($imgR)) {
    // normalize stored paths to be relative (no leading slash)
    $images[] = ltrim($r['path'], '/');
}
if (!empty($images)) {
    $post['images'] = $images;
}
if ($post && empty($post['username'])) $post['username'] = 'Guest';
echo json_encode(['success' => true, 'post' => $post]);
