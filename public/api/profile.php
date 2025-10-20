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

// inspect accounts table columns so we only update columns that exist
$cols_res = mysqli_query($conn, 'DESCRIBE accounts');
$availableCols = [];
if ($cols_res) {
    while ($c = mysqli_fetch_assoc($cols_res)) {
        $availableCols[] = $c['Field'];
    }
}

// mapping from logical field to candidate account column names (in order)
$candidates = [
    'username' => ['username','user_name','name'],
    'bio' => ['bio','about','description'],
    'email' => ['email','email_address','user_email'],
    'phone' => ['phone','contact','phone_number','telephone'],
];

function pick_col($candidates, $availableCols) {
    foreach ($candidates as $cand) {
        if (in_array($cand, $availableCols)) return $cand;
    }
    return null;
}

// resolve which actual columns we'll update
$col_map = [];
$col_map['username'] = pick_col($candidates['username'], $availableCols);
$col_map['bio'] = pick_col($candidates['bio'], $availableCols);
$col_map['email'] = pick_col($candidates['email'], $availableCols);
$col_map['phone'] = pick_col($candidates['phone'], $availableCols);

// Build dynamic update
$fields = [];
$params = [];
$types = '';
if ($username !== '' && $col_map['username']) { $fields[] = $col_map['username'] . ' = ?'; $types .= 's'; $params[] = $username; }
if ($bio !== '' && $col_map['bio']) { $fields[] = $col_map['bio'] . ' = ?'; $types .= 's'; $params[] = $bio; }
if ($email !== '' && $col_map['email']) { $fields[] = $col_map['email'] . ' = ?'; $types .= 's'; $params[] = $email; }
if ($phone !== '' && $col_map['phone']) { $fields[] = $col_map['phone'] . ' = ?'; $types .= 's'; $params[] = $phone; }

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

$updated = [];
$id = intval($userId);
$selectCols = ['UserID'];
// only select columns that exist
foreach (['username','bio','email','phone','profile_picture','avatar','picture'] as $k) {
    if (in_array($k, $availableCols)) $selectCols[] = $k;
}
$sqlSel = 'SELECT ' . implode(', ', array_unique($selectCols)) . ' FROM accounts WHERE UserID = ? LIMIT 1';
$s2 = mysqli_prepare($conn, $sqlSel);
if ($s2) {
    mysqli_stmt_bind_param($s2, 'i', $id);
    mysqli_stmt_execute($s2);
    $res2 = mysqli_stmt_get_result($s2);
    $updated = $res2 ? mysqli_fetch_assoc($res2) : [];
}

echo json_encode(['success' => true, 'user' => $updated, 'profile_picture' => $updated['profile_picture'] ?? null]);

?>
