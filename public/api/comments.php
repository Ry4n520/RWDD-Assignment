<?php
// GET /api/comments.php?postId=123  -> returns comments
// POST /api/comments.php -> create comment (JSON body: postId, content)
header('Content-Type: application/json');
session_start();
include __DIR__ . '/../../src/db.php';
include __DIR__ . '/../../src/api_helpers.php';

$userId = getCurrentUserId();

// GET comments
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $postId = intval($_GET['postId'] ?? 0);
    if ($postId <= 0) {
        jsonResponse(400, ['success' => false, 'error' => 'postId required']);
    }
    
    $sql = "SELECT c.CommentID, c.Comment AS Content, c.Created_at, a.username 
            FROM comments c 
            JOIN accounts a ON c.UserID = a.UserID 
            WHERE c.PostID = ? 
            ORDER BY c.Created_at ASC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $postId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $comments = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $comments[] = $row;
    }
    mysqli_stmt_close($stmt);
    
    echo json_encode(['success' => true, 'comments' => $comments]);
    exit;
}

// POST create comment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$userId) {
        jsonResponse(401, ['success' => false, 'error' => 'Authentication required']);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $postId = intval($input['postId'] ?? 0);
    $content = trim($input['content'] ?? '');
    
    if ($postId <= 0 || $content === '') {
        jsonResponse(400, ['success' => false, 'error' => 'Invalid input']);
    }
    
    // Insert comment
    $sql = "INSERT INTO comments (PostID, UserID, Comment) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        jsonResponse(500, ['success' => false, 'error' => 'Prepare failed: ' . mysqli_error($conn)]);
    }
    
    mysqli_stmt_bind_param($stmt, 'iis', $postId, $userId, $content);
    $ok = mysqli_stmt_execute($stmt);
    
    if (!$ok) {
        mysqli_stmt_close($stmt);
        jsonResponse(500, ['success' => false, 'error' => 'DB insert failed', 'detail' => mysqli_error($conn)]);
    }
    
    $commentId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    
    // Fetch created comment with username
    $sql = "SELECT c.CommentID, c.Comment AS Content, c.Created_at, a.username 
            FROM comments c 
            JOIN accounts a ON c.UserID = a.UserID 
            WHERE c.CommentID = ? 
            LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $commentId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $comment = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    
    // Add like information for newly created comment (will be 0 since it's new)
    $comment['liked'] = false;
    $comment['like_count'] = 0;
    
    echo json_encode(['success' => true, 'comment' => $comment]);
    exit;
}

jsonResponse(405, ['success' => false, 'error' => 'Method not allowed']);

