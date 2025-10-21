<?php
// API to toggle admin status for users (admin-only)
session_start();
header('Content-Type: application/json');
include __DIR__ . '/../../src/db.php';
include __DIR__ . '/../../src/api_helpers.php';

// Require admin access
$currentUserId = requireAdmin($conn);

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$targetUserId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
$makeAdmin = isset($input['is_admin']) ? (bool)$input['is_admin'] : false;

if (!$targetUserId) {
    jsonResponse(400, ['ok' => false, 'error' => 'Invalid user ID']);
}

// Prevent admin from demoting themselves
if ($targetUserId == $currentUserId && !$makeAdmin) {
    jsonResponse(400, ['ok' => false, 'error' => 'Cannot remove your own admin privileges']);
}

// Update user type
$newUserType = $makeAdmin ? 'admin' : 'user';
$updateStmt = mysqli_prepare($conn, "UPDATE accounts SET usertype = ? WHERE UserID = ?");
if ($updateStmt) {
    mysqli_stmt_bind_param($updateStmt, 'si', $newUserType, $targetUserId);
    $success = mysqli_stmt_execute($updateStmt);
    mysqli_stmt_close($updateStmt);
    
    if ($success) {
        jsonResponse(200, ['ok' => true, 'message' => 'Admin status updated successfully']);
    } else {
        jsonResponse(500, ['ok' => false, 'error' => 'Failed to update admin status']);
    }
} else {
    jsonResponse(500, ['ok' => false, 'error' => 'Database error']);
}

