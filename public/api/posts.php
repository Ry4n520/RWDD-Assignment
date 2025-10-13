<?php
// POST /api/posts.php -> create a post
header('Content-Type: application/json');
session_start();
include __DIR__ . '/../../src/db.php';
// allow anonymous posting (no login required)
$userId = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}
$input = json_decode(file_get_contents('php://input'), true);
$content = trim($input['content'] ?? '');
if ($content === '') {
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
$stmt = mysqli_prepare($conn, "INSERT INTO posts (UserID, Content) VALUES (?, ?)");
mysqli_stmt_bind_param($stmt, 'is', $userIdParam, $content);
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
// fetch created post with username
// fetch created post with username if available
$stmt = mysqli_prepare($conn, "SELECT p.PostID, p.Content, p.Created_at, a.username, p.UserID FROM posts p LEFT JOIN accounts a ON p.UserID = a.UserID WHERE p.PostID = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $postId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$post = mysqli_fetch_assoc($res);
if ($post && empty($post['username'])) $post['username'] = 'Guest';
echo json_encode(['success' => true, 'post' => $post]);
