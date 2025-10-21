<?php
// Accept/Decline a trading request and notify the requester
// POST: request_id, response ('accept'|'decline')

declare(strict_types=1);
session_start();
header('Content-Type: application/json');

require __DIR__ . '/../../src/db.php';
require __DIR__ . '/../../src/api_helpers.php';

$me = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

$requestId = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
$resp = strtolower(trim($_POST['response'] ?? ''));

if ($requestId <= 0 || !in_array($resp, ['accept','decline'], true)) {
    jsonResponse(400, ['ok' => false, 'error' => 'invalid_params']);
}

// Load the request and ensure current user is the Receiver
$q = mysqli_prepare($conn, 'SELECT ItemID, SenderID, ReceiverID FROM tradingrequests WHERE RequestID = ?');
if (!$q) {
    jsonResponse(500, ['ok' => false, 'error' => 'prep_failed', 'detail' => mysqli_error($conn)]);
}

mysqli_stmt_bind_param($q, 'i', $requestId);
mysqli_stmt_execute($q);
mysqli_stmt_bind_result($q, $itemId, $senderId, $receiverId);
$found = mysqli_stmt_fetch($q);
mysqli_stmt_close($q);

if (!$found) {
    jsonResponse(404, ['ok' => false, 'error' => 'request_not_found']);
}

if ((int)$receiverId !== (int)$me) {
    jsonResponse(403, ['ok' => false, 'error' => 'forbidden']);
}

$newStatus = $resp === 'accept' ? 'Accepted' : 'Rejected';
$u = mysqli_prepare($conn, "UPDATE tradingrequests SET Status = ? WHERE RequestID = ?");
if (!$u) {
    jsonResponse(500, ['ok' => false, 'error' => 'prep_failed', 'detail' => mysqli_error($conn)]);
}

mysqli_stmt_bind_param($u, 'si', $newStatus, $requestId);
$ok = mysqli_stmt_execute($u);
mysqli_stmt_close($u);

if (!$ok) {
    jsonResponse(500, ['ok' => false, 'error' => 'update_failed', 'detail' => mysqli_error($conn)]);
}

// Create a notification for the sender
$conn->query("CREATE TABLE IF NOT EXISTS notifications (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type VARCHAR(50) NOT NULL,
  payload TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

// Load item details for nicer payload
$itemName = null;
$itemMeetup = null;
$stmt = mysqli_prepare($conn, 'SELECT Name, MeetupLocation FROM tradinglist WHERE ItemID = ?');
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $itemId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $itemName, $itemMeetup);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
}

$payload = json_encode([
    'kind' => $newStatus === 'Accepted' ? 'trading_accepted' : 'trading_declined',
    'request_id' => $requestId,
    'item_id' => (int)$itemId,
    'item_name' => $itemName,
    'meetup_location' => $itemMeetup,
], JSON_UNESCAPED_UNICODE);

$type = $newStatus === 'Accepted' ? 'trading_accepted' : 'trading_declined';
$p = mysqli_prepare($conn, 'INSERT INTO notifications (user_id, type, payload) VALUES (?, ?, ?)');
if ($p) {
    mysqli_stmt_bind_param($p, 'iss', $senderId, $type, $payload);
    mysqli_stmt_execute($p);
    mysqli_stmt_close($p);
}

jsonResponse(200, ['ok' => true, 'status' => $newStatus]);

