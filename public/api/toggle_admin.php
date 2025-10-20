<?php
// API to toggle admin status for users (admin-only)
session_start();
header('Content-Type: application/json');
include __DIR__ . '/../../src/db.php';

// Check if user is logged in
$currentUserId = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
if (!$currentUserId) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Not logged in']);
    exit;
}

// Check if current user is admin
$isAdmin = false;
$stmt = mysqli_prepare($conn, "SELECT usertype FROM accounts WHERE UserID = ? LIMIT 1");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $currentUserId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        if (isset($row['usertype']) && strtolower($row['usertype']) === 'admin') {
            $isAdmin = true;
        }
    }
    mysqli_stmt_close($stmt);
}

if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Admin access required']);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$targetUserId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
$makeAdmin = isset($input['is_admin']) ? (bool)$input['is_admin'] : false;

if (!$targetUserId) {
    echo json_encode(['ok' => false, 'error' => 'Invalid user ID']);
    exit;
}

// Prevent admin from demoting themselves
if ($targetUserId == $currentUserId && !$makeAdmin) {
    echo json_encode(['ok' => false, 'error' => 'Cannot remove your own admin privileges']);
    exit;
}

// Update user type
$newUserType = $makeAdmin ? 'admin' : 'user';
$updateStmt = mysqli_prepare($conn, "UPDATE accounts SET usertype = ? WHERE UserID = ?");
if ($updateStmt) {
    mysqli_stmt_bind_param($updateStmt, 'si', $newUserType, $targetUserId);
    $success = mysqli_stmt_execute($updateStmt);
    mysqli_stmt_close($updateStmt);
    
    if ($success) {
        echo json_encode(['ok' => true, 'message' => 'Admin status updated successfully']);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Failed to update admin status']);
    }
} else {
    echo json_encode(['ok' => false, 'error' => 'Database error']);
}
?>
