<?php
/**
 * Update user profile (username, bio, email, phone)
 */
header('Content-Type: application/json; charset=utf-8');
session_start();
include __DIR__ . '/../../src/db.php';
include __DIR__ . '/../../src/api_helpers.php';

$userId = requireAuth();

// Read JSON body
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$username = trim($input['username'] ?? '');
$bio = trim($input['bio'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');

// Build dynamic update query
$fields = [];
$params = [];
$types = '';

if ($username !== '') { 
    $fields[] = 'username = ?'; 
    $types .= 's'; 
    $params[] = $username; 
}
if ($bio !== '') { 
    $fields[] = 'bio = ?'; 
    $types .= 's'; 
    $params[] = $bio; 
}
if ($email !== '') { 
    $fields[] = 'email = ?'; 
    $types .= 's'; 
    $params[] = $email; 
}
if ($phone !== '') { 
    $fields[] = 'phone = ?'; 
    $types .= 's'; 
    $params[] = $phone; 
}

if (empty($fields)) {
    jsonResponse(400, ['success' => false, 'error' => 'No fields to update']);
}

$sql = 'UPDATE accounts SET ' . implode(', ', $fields) . ' WHERE UserID = ? LIMIT 1';
$types .= 'i';
$params[] = intval($userId);

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    jsonResponse(500, ['success' => false, 'error' => 'Prepare failed', 'detail' => mysqli_error($conn)]);
}

// Bind params dynamically
$bind_names[] = $types;
for ($i = 0; $i < count($params); $i++) {
    $bind_name = 'bind' . $i;
    $$bind_name = $params[$i];
    $bind_names[] = &$$bind_name;
}
call_user_func_array(array($stmt, 'bind_param'), $bind_names);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    jsonResponse(500, ['success' => false, 'error' => 'Execute failed', 'detail' => mysqli_error($conn)]);
}
mysqli_stmt_close($stmt);

// Fetch updated profile
$stmt2 = mysqli_prepare($conn, "SELECT UserID, username, bio, email, phone, profile_picture FROM accounts WHERE UserID = ? LIMIT 1");
mysqli_stmt_bind_param($stmt2, 'i', $userId);
mysqli_stmt_execute($stmt2);
$result = mysqli_stmt_get_result($stmt2);
$updated = mysqli_fetch_assoc($result) ?? [];
mysqli_stmt_close($stmt2);

echo json_encode([
    'success' => true, 
    'user' => $updated, 
    'profile_picture' => $updated['profile_picture'] ?? null
]);

