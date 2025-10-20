<?php
// Create a request in `tradingrequests` and notify the owner
// POST: item_id (required). Meetup location now belongs to tradinglist, not tradingrequests.

declare(strict_types=1);
session_start();
header('Content-Type: application/json');

require __DIR__ . '/../../src/db.php';

function respond(int $code, array $data): void {
  http_response_code($code);
  echo json_encode($data);
  exit;
}

$me = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
if (!$me) {
  respond(401, ['ok' => false, 'error' => 'not_authenticated']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  respond(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

$itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
$location = isset($_POST['meetup_location']) ? trim($_POST['meetup_location']) : '';
$note = trim($_POST['note'] ?? '');

if ($itemId <= 0) {
  respond(400, ['ok' => false, 'error' => 'missing_fields']);
}

// Find item owner and details
$ownerId = null; $itemName = null; $itemMeetup = null;
if ($stmt = mysqli_prepare($conn, 'SELECT UserID, Name, MeetupLocation FROM tradinglist WHERE ItemID = ?')) {
  mysqli_stmt_bind_param($stmt, 'i', $itemId);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_bind_result($stmt, $ownerId, $itemName, $itemMeetup);
  $found = mysqli_stmt_fetch($stmt);
  mysqli_stmt_close($stmt);
  if (!$found) {
    respond(404, ['ok' => false, 'error' => 'item_not_found']);
  }
}
$ownerId = (int)$ownerId;
if ($ownerId === (int)$me) {
  respond(400, ['ok' => false, 'error' => 'cannot_request_own_item']);
}

// Ensure table exists (without MeetupLocation; it's now in tradinglist)
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tradingrequests (
  RequestID INT AUTO_INCREMENT PRIMARY KEY,
  ItemID INT NOT NULL,
  SenderID INT NOT NULL,
  ReceiverID INT NOT NULL,
  Status ENUM('Pending','Accepted','Rejected') DEFAULT 'Pending',
  RequestedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(ItemID), INDEX(SenderID), INDEX(ReceiverID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

// Insert request (no meetup location here)
if ($stmt = mysqli_prepare($conn, 'INSERT INTO tradingrequests (ItemID, SenderID, ReceiverID) VALUES (?, ?, ?)')) {
  mysqli_stmt_bind_param($stmt, 'iii', $itemId, $me, $ownerId);
  $ok = mysqli_stmt_execute($stmt);
  $requestId = $ok ? mysqli_insert_id($conn) : 0;
  $err = $ok ? null : mysqli_error($conn);
  mysqli_stmt_close($stmt);
  if (!$ok) {
    respond(500, ['ok' => false, 'error' => 'db_insert_failed', 'detail' => $err]);
  }
} else {
  respond(500, ['ok' => false, 'error' => 'prep_failed', 'detail' => mysqli_error($conn)]);
}

// Optional: notify owner
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS notifications (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type VARCHAR(50) NOT NULL,
  payload TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

$payload = json_encode([
  'kind' => 'trading_request',
  'request_id' => $requestId,
  'item_id' => $itemId,
  'item_name' => $itemName,
  'meetup_location' => $itemMeetup,
  'from_user' => (int)$me,
  'note' => $note,
], JSON_UNESCAPED_UNICODE);

if ($stmt = mysqli_prepare($conn, 'INSERT INTO notifications (user_id, type, payload) VALUES (?, ?, ?)')) {
  $type = 'trading_request';
  mysqli_stmt_bind_param($stmt, 'iss', $ownerId, $type, $payload);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
}

respond(200, ['ok' => true, 'request_id' => $requestId]);
