<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

session_start();
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/api_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, ['ok' => false, 'error' => 'Method not allowed']);
}

// Only admins can edit events
requireAdmin($conn);

$eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
$name = trim($_POST['name'] ?? '');
$address = trim($_POST['address'] ?? '');
$date = trim($_POST['date'] ?? '');
$organizer = trim($_POST['organizer'] ?? '');

if ($eventId <= 0) {
    jsonResponse(400, ['ok' => false, 'error' => 'Invalid event id']);
}

if ($name === '' || $address === '' || $date === '' || $organizer === '') {
    jsonResponse(400, ['ok' => false, 'error' => 'All fields are required']);
}

// Update the event
$stmt = mysqli_prepare($conn, "UPDATE events SET Name = ?, Address = ?, Date = ?, Organizer = ? WHERE EventID = ?");
mysqli_stmt_bind_param($stmt, 'ssssi', $name, $address, $date, $organizer, $eventId);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($ok) {
    jsonResponse(200, ['ok' => true, 'message' => 'Event updated successfully']);
} else {
    jsonResponse(500, ['ok' => false, 'error' => 'Failed to update event']);
}

