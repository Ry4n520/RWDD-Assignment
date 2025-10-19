<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

session_start();
require_once __DIR__ . '/../../src/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
  exit;
}

// Check authentication
$userId = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
if (!$userId) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'error' => 'Not authenticated']);
  exit;
}

// Check if user is admin
$isAdmin = false;
$userStmt = mysqli_prepare($conn, "SELECT usertype FROM accounts WHERE UserID = ? LIMIT 1");
mysqli_stmt_bind_param($userStmt, 'i', $userId);
mysqli_stmt_execute($userStmt);
$userRes = mysqli_stmt_get_result($userStmt);
if ($userRow = mysqli_fetch_assoc($userRes)) {
    if (isset($userRow['usertype']) && strtolower($userRow['usertype']) === 'admin') {
        $isAdmin = true;
    }
}

// Only admins can edit events
if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Only admins can edit events']);
    exit;
}

$eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
$name = trim($_POST['name'] ?? '');
$address = trim($_POST['address'] ?? '');
$date = trim($_POST['date'] ?? '');
$organizer = trim($_POST['organizer'] ?? '');

if ($eventId <= 0) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid event id']);
  exit;
}

if ($name === '' || $address === '' || $date === '' || $organizer === '') {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'All fields are required']);
  exit;
}

if (!isset($conn)) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'DB not available']);
  exit;
}

// Update the event
$stmt = mysqli_prepare($conn, "UPDATE events SET Name = ?, Address = ?, Date = ?, Organizer = ? WHERE EventID = ?");
mysqli_stmt_bind_param($stmt, 'ssssi', $name, $address, $date, $organizer, $eventId);
$ok = mysqli_stmt_execute($stmt);

if ($ok) {
  echo json_encode(['ok' => true, 'message' => 'Event updated successfully']);
} else {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Failed to update event']);
}
mysqli_stmt_close($stmt);
