<?php
// POST /api/like.php with JSON { type: 'post'|'comment', id: <int> }
header('Content-Type: application/json');
// avoid PHP warnings/notices printing HTML and breaking JSON responses
ini_set('display_errors', '0');
error_reporting(E_ALL);
ob_start();
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err) {
        http_response_code(500);
        // clear any buffered output and return JSON error
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'Fatal error: ' . ($err['message'] ?? 'unknown')]);
        return;
    }
    // flush any buffered output (should be JSON)
    $out = ob_get_clean();
    if ($out !== '') echo $out;
});
session_start();
include __DIR__ . '/../../src/db.php';
$userId = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}
$input = json_decode(file_get_contents('php://input'), true);
$type = $input['type'] ?? '';
$id = intval($input['id'] ?? 0);
if (!in_array($type, ['post','comment']) || $id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

if ($type === 'post') {
    // check existing
    // Use a constant select so we don't rely on a specific primary key column name
    $stmt = mysqli_prepare($conn, "SELECT 1 FROM postlikes WHERE PostID = ? AND UserID = ? LIMIT 1");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'DB prepare failed', 'detail' => mysqli_error($conn)]);
        exit;
    }
    mysqli_stmt_bind_param($stmt, 'ii', $id, $userId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res && mysqli_num_rows($res) > 0) {
        $del = mysqli_prepare($conn, "DELETE FROM postlikes WHERE PostID = ? AND UserID = ?");
        mysqli_stmt_bind_param($del, 'ii', $id, $userId);
        mysqli_stmt_execute($del);
        $action = 'unliked';
    } else {
        $ins = mysqli_prepare($conn, "INSERT IGNORE INTO postlikes (PostID, UserID) VALUES (?, ?)");
        mysqli_stmt_bind_param($ins, 'ii', $id, $userId);
        mysqli_stmt_execute($ins);
        $action = 'liked';
    }
    // return count
    $r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM postlikes WHERE PostID = " . intval($id));
    $row = mysqli_fetch_assoc($r);
    echo json_encode(['success' => true, 'action' => $action, 'count' => intval($row['cnt'] ?? 0)]);
    exit;
}

if ($type === 'comment') {
    // Schema-agnostic check for existing comment like
    $stmt = mysqli_prepare($conn, "SELECT 1 FROM commentlikes WHERE CommentID = ? AND UserID = ? LIMIT 1");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'DB prepare failed', 'detail' => mysqli_error($conn)]);
        exit;
    }
    mysqli_stmt_bind_param($stmt, 'ii', $id, $userId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res && mysqli_num_rows($res) > 0) {
        $del = mysqli_prepare($conn, "DELETE FROM commentlikes WHERE CommentID = ? AND UserID = ?");
        mysqli_stmt_bind_param($del, 'ii', $id, $userId);
        mysqli_stmt_execute($del);
        $action = 'unliked';
    } else {
        $ins = mysqli_prepare($conn, "INSERT IGNORE INTO commentlikes (CommentID, UserID) VALUES (?, ?)");
        mysqli_stmt_bind_param($ins, 'ii', $id, $userId);
        mysqli_stmt_execute($ins);
        $action = 'liked';
    }
    $r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM commentlikes WHERE CommentID = " . intval($id));
    $row = mysqli_fetch_assoc($r);
    echo json_encode(['success' => true, 'action' => $action, 'count' => intval($row['cnt'] ?? 0)]);
    exit;
}
