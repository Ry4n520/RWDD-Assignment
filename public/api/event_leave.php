<?php
// api/event_leave.php
// Delete a participant row for the current user and given event_id

declare(strict_types=1);
session_start();
header('Content-Type: application/json');

require __DIR__ . '/../../src/db.php';
require __DIR__ . '/../../src/api_helpers.php';

$userId = requireAuth();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    jsonResponse(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

$eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
if ($eventId <= 0) {
    jsonResponse(400, ['ok' => false, 'error' => 'invalid_event']);
}

$stmt = mysqli_prepare($conn, 'DELETE FROM eventparticipants WHERE EventID = ? AND UserID = ? LIMIT 1');
if (!$stmt) {
    jsonResponse(500, ['ok' => false, 'error' => 'prepare_failed']);
}

mysqli_stmt_bind_param($stmt, 'ii', $eventId, $userId);
$ok = mysqli_stmt_execute($stmt);
$aff = mysqli_stmt_affected_rows($stmt);
$err = mysqli_error($conn);
mysqli_stmt_close($stmt);

if (!$ok) {
    jsonResponse(500, ['ok' => false, 'error' => $err ?: 'delete_failed']);
}

jsonResponse(200, ['ok' => true, 'removed' => $aff > 0]);

