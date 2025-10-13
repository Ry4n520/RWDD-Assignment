<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
include __DIR__ . '/../../src/db.php';

$userId = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['avatar'];
$allowed = ['image/png','image/jpeg','image/webp'];
if (!in_array($file['type'], $allowed)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid file type']);
    exit;
}

$uploadDir = __DIR__ . '/../uploads/avatars';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'avatar_' . intval($userId) . '_' . time() . '.' . $ext;
$dest = $uploadDir . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file']);
    exit;
}

// store relative path in DB (for example: uploads/avatars/filename)

$url = 'uploads/avatars/' . $filename;
// update your accounts table column; use the existing 'profile_picture' column if present
$stmt = mysqli_prepare($conn, 'UPDATE accounts SET profile_picture = ? WHERE UserID = ? LIMIT 1');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Prepare failed', 'detail' => mysqli_error($conn)]);
    exit;
}
mysqli_stmt_bind_param($stmt, 'si', $url, $userId);
$ok = mysqli_stmt_execute($stmt);
if (!$ok) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB update failed', 'detail' => mysqli_error($conn)]);
    exit;
}

echo json_encode(['success' => true, 'url' => $url]);

?>
