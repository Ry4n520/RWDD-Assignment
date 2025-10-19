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

// Only admins can delete events
if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Only admins can delete events']);
    exit;
}

$eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
if ($eventId <= 0) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid event id']);
  exit;
}

if (!isset($conn)) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'DB not available']);
  exit;
}

// Best-effort remove related images from disk if event_images exists
try {
  $tblRes = @mysqli_query($conn, "SHOW TABLES LIKE 'event_images'");
  if ($tblRes && mysqli_num_rows($tblRes) > 0) {
    if ($rs = @mysqli_query($conn, "SELECT path FROM event_images WHERE EventID = " . $eventId)) {
      while ($r = mysqli_fetch_assoc($rs)) {
        $rel = $r['path'] ?? '';
        if ($rel !== '') {
          $abs = realpath(__DIR__ . '/..' . '/../' . $rel);
          if (!$abs) {
            // try public root
            $abs = __DIR__ . '/../../public/' . $rel;
          }
          if (is_file($abs)) @unlink($abs);
        }
      }
    }
    @mysqli_query($conn, "DELETE FROM event_images WHERE EventID = " . $eventId);
  }
} catch (Throwable $e) {
  // ignore
}

// Remove participants first (if table exists)
@mysqli_query($conn, "DELETE FROM eventparticipants WHERE EventID = " . $eventId);

// Finally delete event
if (@mysqli_query($conn, "DELETE FROM events WHERE EventID = " . $eventId)) {
  echo json_encode(['ok' => true]);
} else {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Failed to delete event']);
}
?>
