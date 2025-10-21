<?php
// api/event_join.php
// Create a participant row for the current user and given event_id

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

// Prevent duplicate join
$chk = mysqli_prepare($conn, 'SELECT ParticipantID FROM eventparticipants WHERE EventID = ? AND UserID = ? LIMIT 1');
if ($chk) {
    mysqli_stmt_bind_param($chk, 'ii', $eventId, $userId);
    mysqli_stmt_execute($chk);
    $res = mysqli_stmt_get_result($chk);
    if ($res && mysqli_fetch_assoc($res)) {
        mysqli_stmt_close($chk);
        jsonResponse(200, ['ok' => true, 'already' => true]);
    }
    mysqli_stmt_close($chk);
}

$stmt = mysqli_prepare($conn, 'INSERT INTO eventparticipants (EventID, UserID, Joined_at) VALUES (?, ?, NOW())');
if (!$stmt) {
    jsonResponse(500, ['ok' => false, 'error' => 'prepare_failed']);
}

mysqli_stmt_bind_param($stmt, 'ii', $eventId, $userId);
$ok = mysqli_stmt_execute($stmt);
$err = mysqli_error($conn);
mysqli_stmt_close($stmt);

if (!$ok) {
    jsonResponse(500, ['ok' => false, 'error' => $err ?: 'insert_failed']);
}

jsonResponse(200, ['ok' => true]);

