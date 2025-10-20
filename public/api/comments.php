<?php
// GET /api/comments.php?postId=123  -> returns comments
// POST /api/comments.php -> create comment (JSON body: postId, content)
header('Content-Type: application/json');
session_start();
include __DIR__ . '/../../src/db.php';
$userId = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
// map expected column names to actual table columns
$colMap = [
    'post' => null,
    'user' => null,
    'content' => null,
    'id' => null,
    'created' => null,
];
$desc = mysqli_query($conn, "DESCRIBE comments");
$fields = [];
if ($desc) {
    while ($r = mysqli_fetch_assoc($desc)) {
        $fields[] = $r['Field'];
    }
}
// helpers to find column by candidates
$find = function ($candidates) use ($fields) {
    foreach ($candidates as $c) {
        if (in_array($c, $fields, true)) return $c;
    }
    return null;
};

$colMap['id'] = $find(['CommentID', 'comment_id', 'id', 'ID']);
$colMap['post'] = $find(['PostID', 'post_id', 'postId', 'postid']);
$colMap['user'] = $find(['UserID', 'user_id', 'userid', 'User_Id']);
$colMap['content'] = $find(['Content', 'content', 'Body', 'body', 'Comment', 'comment', 'Text', 'text']);
$colMap['created'] = $find(['Created_at', 'created_at', 'CreatedAt', 'createdAt', 'created', 'Created']);

// Ensure minimal columns exist
if (!$colMap['post'] || !$colMap['user'] || !$colMap['id']) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'comments table missing required columns']);
    exit;
}

// GET comments
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $postId = intval($_GET['postId'] ?? 0);
    if ($postId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'postId required']);
        exit;
    }
    $contentCol = $colMap['content'] ? "c.`" . $colMap['content'] . "` AS Content" : "'' AS Content";
    $createdCol = $colMap['created'] ? "c.`" . $colMap['created'] . "` AS Created_at" : "NOW() AS Created_at";
    $sql = "SELECT c.`" . $colMap['id'] . "` AS CommentID, " . $contentCol . ", " . $createdCol . ", a.username FROM comments c JOIN accounts a ON c.`" . $colMap['user'] . "` = a.UserID WHERE c.`" . $colMap['post'] . "` = ? ORDER BY " . ($colMap['created'] ? "c.`" . $colMap['created'] . "` ASC" : "1 ASC");
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $postId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $comments = [];
    while ($row = mysqli_fetch_assoc($res)) $comments[] = $row;
    echo json_encode(['success' => true, 'comments' => $comments]);
    exit;
}

// POST create comment
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
    if (!$colMap['content']) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'No content column in comments table']);
        exit;
    }

    // build insert SQL dynamically
    $insCols = ["`" . $colMap['post'] . "`", "`" . $colMap['user'] . "`", "`" . $colMap['content'] . "`"];
    $placeholders = ["?", "?", "?"];
    $types = 'iis';
    $sql = "INSERT INTO comments (" . implode(',', $insCols) . ") VALUES (" . implode(',', $placeholders) . ")";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . mysqli_error($conn)]);
        exit;
    }
    mysqli_stmt_bind_param($stmt, $types, $postId, $userId, $content);
    $ok = mysqli_stmt_execute($stmt);
    if (!$ok) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'DB insert failed', 'detail' => mysqli_error($conn)]);
        exit;
    }
    $commentId = mysqli_insert_id($conn);

    // fetch created comment with username
    $contentCol = $colMap['content'] ? "c.`" . $colMap['content'] . "` AS Content" : "'' AS Content";
    $createdCol = $colMap['created'] ? "c.`" . $colMap['created'] . "` AS Created_at" : "NOW() AS Created_at";
    $sql = "SELECT c.`" . $colMap['id'] . "` AS CommentID, " . $contentCol . ", " . $createdCol . ", a.username FROM comments c JOIN accounts a ON c.`" . $colMap['user'] . "` = a.UserID WHERE c.`" . $colMap['id'] . "` = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $commentId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $comment = mysqli_fetch_assoc($res);
    echo json_encode(['success' => true, 'comment' => $comment]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
