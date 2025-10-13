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

// read JSON body
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$username = trim($input['username'] ?? '');
$bio = trim($input['bio'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');

// Build dynamic update
$fields = [];
$params = [];
$types = '';
if ($username !== '') { $fields[] = 'username = ?'; $types .= 's'; $params[] = $username; }
if ($bio !== '') { $fields[] = 'bio = ?'; $types .= 's'; $params[] = $bio; }
if ($email !== '') { $fields[] = 'email = ?'; $types .= 's'; $params[] = $email; }
if ($phone !== '') { $fields[] = 'phone = ?'; $types .= 's'; $params[] = $phone; }

if (empty($fields)) {
    echo json_encode(['success' => false, 'error' => 'No fields to update']);
    exit;
}

$sql = 'UPDATE accounts SET ' . implode(', ', $fields) . ' WHERE UserID = ? LIMIT 1';
$types .= 'i';
$params[] = intval($userId);

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Prepare failed', 'detail' => mysqli_error($conn)]);
    exit;
}

// bind params dynamically
$bind_names[] = $types;
for ($i=0;$i<count($params);$i++){
    $bind_name = 'bind' . $i;
    $$bind_name = $params[$i];
    $bind_names[] = &$$bind_name;
}
call_user_func_array(array($stmt,'bind_param'), $bind_names);

$ok = mysqli_stmt_execute($stmt);
if (!$ok) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Execute failed', 'detail' => mysqli_error($conn)]);
    exit;
}

echo json_encode(['success' => true]);

?>
