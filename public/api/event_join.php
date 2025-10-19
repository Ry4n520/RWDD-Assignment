<?php
// api/event_join.php
// Create a participant row for the current user and given event_id

declare(strict_types=1);
session_start();
header('Content-Type: application/json');

require __DIR__ . '/../../src/db.php';

function json_out(int $code, array $payload) {
  http_response_code($code);
  echo json_encode($payload);
  exit;
}

$userId = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
if (!$userId) json_out(401, ['ok' => false, 'error' => 'not_authenticated']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  json_out(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

$eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
if ($eventId <= 0) json_out(400, ['ok' => false, 'error' => 'invalid_event']);

// Prevent duplicate join: check if exists
$chk = mysqli_prepare($conn, 'SELECT ParticipantID FROM eventparticipants WHERE EventID = ? AND UserID = ? LIMIT 1');
if ($chk) {
  mysqli_stmt_bind_param($chk, 'ii', $eventId, $userId);
  mysqli_stmt_execute($chk);
  $res = mysqli_stmt_get_result($chk);
  if ($res && mysqli_fetch_assoc($res)) {
    mysqli_stmt_close($chk);
    json_out(200, ['ok' => true, 'already' => true]);
  }
  mysqli_stmt_close($chk);
}

$stmt = mysqli_prepare($conn, 'INSERT INTO eventparticipants (EventID, UserID, Joined_at) VALUES (?, ?, NOW())');
if (!$stmt) json_out(500, ['ok' => false, 'error' => 'prepare_failed']);
mysqli_stmt_bind_param($stmt, 'ii', $eventId, $userId);
$ok = mysqli_stmt_execute($stmt);
$err = mysqli_error($conn);
mysqli_stmt_close($stmt);

if (!$ok) json_out(500, ['ok' => false, 'error' => $err ?: 'insert_failed']);

json_out(200, ['ok' => true]);
