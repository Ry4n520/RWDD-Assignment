<?php
// GET /api/comments.php?postId=123  -> returns comments
// POST /api/comments.php -> create comment (JSON body: postId, content)
header('Content-Type: application/json');
session_start();
include __DIR__ . '/../../src/db.php';
$userId = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $postId = intval($_GET['postId'] ?? 0);
    if ($postId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'postId required']);
        exit;
    }
    $stmt = mysqli_prepare($conn, "SELECT c.CommentID, c.Content, c.Created_at, a.username FROM comments c JOIN accounts a ON c.UserID = a.UserID WHERE c.PostID = ? ORDER BY c.Created_at ASC");
    mysqli_stmt_bind_param($stmt, 'i', $postId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $comments = [];
    while ($row = mysqli_fetch_assoc($res)) $comments[] = $row;
    echo json_encode(['success' => true, 'comments' => $comments]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }
    $input = json_decode(file_get_contents('php://input'), true);
    $postId = intval($input['postId'] ?? 0);
    $content = trim($input['content'] ?? '');
    if ($postId <= 0 || $content === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid input']);
        exit;
    }
    $stmt = mysqli_prepare($conn, "INSERT INTO comments (PostID, UserID, Content) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'iis', $postId, $userId, $content);
    $ok = mysqli_stmt_execute($stmt);
    if (!$ok) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'DB insert failed']);
        exit;
    }
    $commentId = mysqli_insert_id($conn);
    $stmt = mysqli_prepare($conn, "SELECT c.CommentID, c.Content, c.Created_at, a.username FROM comments c JOIN accounts a ON c.UserID = a.UserID WHERE c.CommentID = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $commentId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $comment = mysqli_fetch_assoc($res);
    echo json_encode(['success' => true, 'comment' => $comment]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
