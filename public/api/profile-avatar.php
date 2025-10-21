<?php
/**
 * Upload user profile avatar/picture
 */
header('Content-Type: application/json; charset=utf-8');
session_start();
include __DIR__ . '/../../src/db.php';
include __DIR__ . '/../../src/api_helpers.php';

$userId = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, ['success' => false, 'error' => 'Method not allowed']);
}

if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(400, ['success' => false, 'error' => 'No file uploaded']);
}

$file = $_FILES['avatar'];
$allowed = ['image/png', 'image/jpeg', 'image/webp'];
if (!in_array($file['type'], $allowed)) {
    jsonResponse(400, ['success' => false, 'error' => 'Invalid file type']);
}

$uploadDir = __DIR__ . '/../uploads/avatars';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'avatar_' . intval($userId) . '_' . time() . '.' . $ext;
$dest = $uploadDir . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    jsonResponse(500, ['success' => false, 'error' => 'Failed to move uploaded file']);
}

// Update profile_picture in database
$url = 'uploads/avatars/' . $filename;
$stmt = mysqli_prepare($conn, 'UPDATE accounts SET profile_picture = ? WHERE UserID = ? LIMIT 1');
if (!$stmt) {
    jsonResponse(500, ['success' => false, 'error' => 'Prepare failed', 'detail' => mysqli_error($conn)]);
}

mysqli_stmt_bind_param($stmt, 'si', $url, $userId);
if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    jsonResponse(500, ['success' => false, 'error' => 'DB update failed', 'detail' => mysqli_error($conn)]);
}
mysqli_stmt_close($stmt);

echo json_encode(['success' => true, 'url' => $url]);

