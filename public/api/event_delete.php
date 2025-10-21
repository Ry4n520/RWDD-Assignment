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

// Only admins can delete events
requireAdmin($conn);

$eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
if ($eventId <= 0) {
    jsonResponse(400, ['ok' => false, 'error' => 'Invalid event id']);
}

// Best-effort remove related images from disk if event_images exists
try {
    $tblRes = @mysqli_query($conn, "SHOW TABLES LIKE 'event_images'");
    if ($tblRes && mysqli_num_rows($tblRes) > 0) {
        $stmt = mysqli_prepare($conn, "SELECT path FROM event_images WHERE EventID = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $eventId);
            mysqli_stmt_execute($stmt);
            $rs = mysqli_stmt_get_result($stmt);
            
            while ($r = mysqli_fetch_assoc($rs)) {
                $rel = $r['path'] ?? '';
                if ($rel !== '') {
                    $abs = realpath(__DIR__ . '/..' . '/../' . $rel);
                    if (!$abs) {
                        $abs = __DIR__ . '/../../public/' . $rel;
                    }
                    if (is_file($abs)) @unlink($abs);
                }
            }
            mysqli_stmt_close($stmt);
        }
        
        $delStmt = mysqli_prepare($conn, "DELETE FROM event_images WHERE EventID = ?");
        if ($delStmt) {
            mysqli_stmt_bind_param($delStmt, 'i', $eventId);
            mysqli_stmt_execute($delStmt);
            mysqli_stmt_close($delStmt);
        }
    }
} catch (Throwable $e) {
    // ignore
}

// Remove participants first (if table exists)
$partStmt = mysqli_prepare($conn, "DELETE FROM eventparticipants WHERE EventID = ?");
if ($partStmt) {
    mysqli_stmt_bind_param($partStmt, 'i', $eventId);
    @mysqli_stmt_execute($partStmt);
    mysqli_stmt_close($partStmt);
}

// Finally delete event
$delEventStmt = mysqli_prepare($conn, "DELETE FROM events WHERE EventID = ?");
if ($delEventStmt) {
    mysqli_stmt_bind_param($delEventStmt, 'i', $eventId);
    if (mysqli_stmt_execute($delEventStmt)) {
        mysqli_stmt_close($delEventStmt);
        jsonResponse(200, ['ok' => true]);
    } else {
        mysqli_stmt_close($delEventStmt);
        jsonResponse(500, ['ok' => false, 'error' => 'Failed to delete event']);
    }
} else {
    jsonResponse(500, ['ok' => false, 'error' => 'Database error']);
}

