<?php
// POST /api/like.php with JSON { type: 'post'|'comment', id: <int> }
header('Content-Type: application/json');
ini_set('display_errors', '0');
error_reporting(E_ALL);
ob_start();
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err) {
        http_response_code(500);
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'Fatal error: ' . ($err['message'] ?? 'unknown')]);
        return;
    }
    $out = ob_get_clean();
    if ($out !== '') echo $out;
});

session_start();
include __DIR__ . '/../../src/db.php';
include __DIR__ . '/../../src/api_helpers.php';

$userId = getCurrentUserId();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, ['success' => false, 'error' => 'Method not allowed']);
}

if (!$userId) {
    jsonResponse(401, ['success' => false, 'error' => 'Authentication required']);
}

$input = json_decode(file_get_contents('php://input'), true);
$type = $input['type'] ?? '';
$id = intval($input['id'] ?? 0);

if (!in_array($type, ['post','comment']) || $id <= 0) {
    jsonResponse(400, ['success' => false, 'error' => 'Invalid input']);
}

if ($type === 'post') {
    // Check if already liked
    $stmt = mysqli_prepare($conn, "SELECT 1 FROM postlikes WHERE PostID = ? AND UserID = ? LIMIT 1");
    if (!$stmt) {
        jsonResponse(500, ['success' => false, 'error' => 'DB prepare failed', 'detail' => mysqli_error($conn)]);
    }
    mysqli_stmt_bind_param($stmt, 'ii', $id, $userId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $action = '';
    
    if ($res && mysqli_num_rows($res) > 0) {
        mysqli_stmt_close($stmt);
        $del = mysqli_prepare($conn, "DELETE FROM postlikes WHERE PostID = ? AND UserID = ?");
        mysqli_stmt_bind_param($del, 'ii', $id, $userId);
        mysqli_stmt_execute($del);
        mysqli_stmt_close($del);
        $action = 'unliked';
    } else {
        mysqli_stmt_close($stmt);
        $ins = mysqli_prepare($conn, "INSERT IGNORE INTO postlikes (PostID, UserID) VALUES (?, ?)");
        mysqli_stmt_bind_param($ins, 'ii', $id, $userId);
        mysqli_stmt_execute($ins);
        mysqli_stmt_close($ins);
        $action = 'liked';
    }
    
    // Get count using prepared statement
    $countStmt = mysqli_prepare($conn, "SELECT COUNT(*) AS cnt FROM postlikes WHERE PostID = ?");
    mysqli_stmt_bind_param($countStmt, 'i', $id);
    mysqli_stmt_execute($countStmt);
    $countRes = mysqli_stmt_get_result($countStmt);
    $row = mysqli_fetch_assoc($countRes);
    mysqli_stmt_close($countStmt);
    
    echo json_encode(['success' => true, 'action' => $action, 'count' => intval($row['cnt'] ?? 0)]);
    exit;
}

if ($type === 'comment') {
    // Check if already liked
    $stmt = mysqli_prepare($conn, "SELECT 1 FROM commentlikes WHERE CommentID = ? AND UserID = ? LIMIT 1");
    if (!$stmt) {
        jsonResponse(500, ['success' => false, 'error' => 'DB prepare failed', 'detail' => mysqli_error($conn)]);
    }
    mysqli_stmt_bind_param($stmt, 'ii', $id, $userId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $action = '';
    
    if ($res && mysqli_num_rows($res) > 0) {
        mysqli_stmt_close($stmt);
        $del = mysqli_prepare($conn, "DELETE FROM commentlikes WHERE CommentID = ? AND UserID = ?");
        mysqli_stmt_bind_param($del, 'ii', $id, $userId);
        mysqli_stmt_execute($del);
        mysqli_stmt_close($del);
        $action = 'unliked';
    } else {
        mysqli_stmt_close($stmt);
        $ins = mysqli_prepare($conn, "INSERT IGNORE INTO commentlikes (CommentID, UserID) VALUES (?, ?)");
        mysqli_stmt_bind_param($ins, 'ii', $id, $userId);
        mysqli_stmt_execute($ins);
        mysqli_stmt_close($ins);
        $action = 'liked';
    }
    
    // Get count using prepared statement
    $countStmt = mysqli_prepare($conn, "SELECT COUNT(*) AS cnt FROM commentlikes WHERE CommentID = ?");
    mysqli_stmt_bind_param($countStmt, 'i', $id);
    mysqli_stmt_execute($countStmt);
    $countRes = mysqli_stmt_get_result($countStmt);
    $row = mysqli_fetch_assoc($countRes);
    mysqli_stmt_close($countStmt);
    
    echo json_encode(['success' => true, 'action' => $action, 'count' => intval($row['cnt'] ?? 0)]);
    exit;
}
