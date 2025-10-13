<?php
// POST /api/like.php with JSON { type: 'post'|'comment', id: <int> }
header('Content-Type: application/json');
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
    $stmt = mysqli_prepare($conn, "SELECT LikeID FROM postlikes WHERE PostID = ? AND UserID = ? LIMIT 1");
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
    $stmt = mysqli_prepare($conn, "SELECT LikeID FROM commentlikes WHERE CommentID = ? AND UserID = ? LIMIT 1");
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
