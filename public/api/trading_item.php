<?php
/**
 * Create/Update/Delete trading list items
 * POST: name, category, description, meetup_location
 * PUT: item_id, name, category, description, meetup_location
 * DELETE: item_id
 */
session_start();
header('Content-Type: application/json');
include __DIR__ . '/../../src/db.php';
include __DIR__ . '/../../src/api_helpers.php';

$me = requireAuth();

// Handle PUT request (Update item)
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $itemId = intval($input['item_id'] ?? 0);
    $name = trim($input['name'] ?? '');
    $description = trim($input['description'] ?? '');
    $category = trim($input['category'] ?? '');
    $meetupLocation = trim($input['meetup_location'] ?? '');
    
    if (!$itemId) {
        jsonResponse(400, ['ok' => false, 'error' => 'Invalid input']);
    }
    
    if ($name === '' || $description === '' || $meetupLocation === '') {
        jsonResponse(400, ['ok' => false, 'error' => 'Name, description, and meetup location are required']);
    }
    
    // Check ownership (admins can edit any item)
    $isAdminUser = isAdmin($conn, $me);
    if (!$isAdminUser) {
        $stmt = mysqli_prepare($conn, "SELECT UserID FROM tradinglist WHERE ItemID = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'i', $itemId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $item = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
        
        if (!$item || intval($item['UserID']) !== intval($me)) {
            jsonResponse(403, ['ok' => false, 'error' => 'Not authorized']);
        }
    }
    
    // Update the item
    $stmt = mysqli_prepare($conn, "UPDATE tradinglist SET Name = ?, Description = ?, Category = ?, MeetupLocation = ? WHERE ItemID = ?");
    mysqli_stmt_bind_param($stmt, 'ssssi', $name, $description, $category, $meetupLocation, $itemId);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    if ($ok) {
        jsonResponse(200, ['ok' => true, 'message' => 'Item updated successfully']);
    } else {
        jsonResponse(500, ['ok' => false, 'error' => 'Failed to update item']);
    }
}

// Handle DELETE request
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $itemId = intval($input['item_id'] ?? 0);
    
    if (!$itemId) {
        jsonResponse(400, ['ok' => false, 'error' => 'Invalid input']);
    }
    
    // Check ownership (admins can delete any item)
    $isAdminUser = isAdmin($conn, $me);
    if (!$isAdminUser) {
        $stmt = mysqli_prepare($conn, "SELECT UserID FROM tradinglist WHERE ItemID = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'i', $itemId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $item = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
        
        if (!$item || intval($item['UserID']) !== intval($me)) {
            jsonResponse(403, ['ok' => false, 'error' => 'Not authorized']);
        }
    }
    
    // Delete images from disk and DB using prepared statements
    $imgStmt = mysqli_prepare($conn, "SELECT path FROM trading_images WHERE ItemID = ?");
    if ($imgStmt) {
        mysqli_stmt_bind_param($imgStmt, 'i', $itemId);
        mysqli_stmt_execute($imgStmt);
        $imgRes = mysqli_stmt_get_result($imgStmt);
        
        while ($img = mysqli_fetch_assoc($imgRes)) {
            $filePath = __DIR__ . '/../' . ltrim($img['path'], '/');
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
        mysqli_stmt_close($imgStmt);
        
        $delImgStmt = mysqli_prepare($conn, "DELETE FROM trading_images WHERE ItemID = ?");
        mysqli_stmt_bind_param($delImgStmt, 'i', $itemId);
        mysqli_stmt_execute($delImgStmt);
        mysqli_stmt_close($delImgStmt);
    }
    
    // Delete trading requests
    $delReqStmt = mysqli_prepare($conn, "DELETE FROM tradingrequests WHERE ItemID = ?");
    mysqli_stmt_bind_param($delReqStmt, 'i', $itemId);
    mysqli_stmt_execute($delReqStmt);
    mysqli_stmt_close($delReqStmt);
    
    // Delete the item
    $delItemStmt = mysqli_prepare($conn, "DELETE FROM tradinglist WHERE ItemID = ?");
    mysqli_stmt_bind_param($delItemStmt, 'i', $itemId);
    mysqli_stmt_execute($delItemStmt);
    mysqli_stmt_close($delItemStmt);
    
    jsonResponse(200, ['ok' => true]);
}

// Handle POST request (Create item)
$name = trim($_POST['name'] ?? '');
$category = trim($_POST['category'] ?? '');
$desc = trim($_POST['description'] ?? '');
$meetup = trim($_POST['meetup_location'] ?? '');

if ($name === '') {
    jsonResponse(400, ['ok' => false, 'error' => 'name_required']);
}

// Insert into tradinglist
$id = 0;
$stmt = mysqli_prepare($conn, "INSERT INTO tradinglist (UserID, Name, Category, Description, MeetupLocation) VALUES (?, ?, ?, ?, ?)");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'issss', $me, $name, $category, $desc, $meetup);
    if (!mysqli_stmt_execute($stmt)) {
        // Fallback: try without MeetupLocation
        mysqli_stmt_close($stmt);
        $stmt = mysqli_prepare($conn, "INSERT INTO tradinglist (UserID, Name, Category, Description) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'isss', $me, $name, $category, $desc);
            if (!mysqli_stmt_execute($stmt)) {
                // Fallback: minimal columns
                mysqli_stmt_close($stmt);
                $stmt = mysqli_prepare($conn, "INSERT INTO tradinglist (UserID, Name, Category) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($stmt, 'iss', $me, $name, $category);
                mysqli_stmt_execute($stmt);
            }
        }
    }
} else {
    // Minimal fallback
    $stmt = mysqli_prepare($conn, "INSERT INTO tradinglist (UserID, Name, Category) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'iss', $me, $name, $category);
    mysqli_stmt_execute($stmt);
}

$id = mysqli_insert_id($conn);
if ($stmt) mysqli_stmt_close($stmt);

// Create trading_images table if needed
$conn->query("CREATE TABLE IF NOT EXISTS trading_images (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ItemID INT NOT NULL,
    path VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (ItemID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

// Handle uploaded images
$uploaded = [];
if (!empty($_FILES)) {
    $files = $_FILES['image'] ?? $_FILES['image[]'] ?? null;
    if ($files) {
        $names = is_array($files['name']) ? $files['name'] : [$files['name']];
        $tmps = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
        for ($i = 0; $i < count($names); $i++) {
            $namef = $names[$i];
            $tmp = $tmps[$i];
            if (!is_uploaded_file($tmp)) continue;
            
            $ext = pathinfo($namef, PATHINFO_EXTENSION);
            $fn = 'uploads/trading/item_' . intval($id) . '_' . uniqid() . '.' . $ext;
            $dest = __DIR__ . '/../' . $fn;
            
            if (!is_dir(dirname($dest))) @mkdir(dirname($dest), 0755, true);
            
            if (move_uploaded_file($tmp, $dest)) {
                $pstmt = mysqli_prepare($conn, "INSERT INTO trading_images (ItemID, path) VALUES (?, ?)");
                if ($pstmt) {
                    mysqli_stmt_bind_param($pstmt, 'is', $id, $fn);
                    mysqli_stmt_execute($pstmt);
                    mysqli_stmt_close($pstmt);
                }
                $uploaded[] = $fn;
            }
        }
    }
}

echo json_encode(['ok' => true, 'item_id' => $id, 'images' => $uploaded]);

