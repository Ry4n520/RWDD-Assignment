<?php
/**
 * Notifications API
 * GET: returns list of notifications for the authenticated user
 * POST action=mark_read {id}
 * POST action=mark_unread {id}
 * POST action=mark_all_read
 */
session_start();
header('Content-Type: application/json');
// Prevent caching to ensure latest request_status enrichment is always fetched
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
include __DIR__ . '/../../src/db.php';
include __DIR__ . '/../../src/api_helpers.php';

$me = requireAuth();

// Ensure table exists (no-op if already present)
$conn->query("CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type VARCHAR(32) NOT NULL,
  payload TEXT,
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $limit = intval($_GET['limit'] ?? 50);
    $limit = max(1, $limit);
    
    $stmt = mysqli_prepare($conn, "SELECT id, type, payload, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    mysqli_stmt_bind_param($stmt, 'ii', $me, $limit);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    $out = [];
    while ($r = mysqli_fetch_assoc($res)) {
        $payload = json_decode($r['payload'], true);
        if (!is_array($payload)) {
            $payload = [];
        }
        
        // Enrich trading request notifications with current request status
        if (($r['type'] === 'trading_request' || $r['type'] === 'trade_request') && isset($payload['request_id'])) {
            $reqId = intval($payload['request_id']);
            if ($reqId > 0) {
                $reqStmt = mysqli_prepare($conn, 'SELECT Status FROM tradingrequests WHERE RequestID = ? LIMIT 1');
                mysqli_stmt_bind_param($reqStmt, 'i', $reqId);
                mysqli_stmt_execute($reqStmt);
                mysqli_stmt_bind_result($reqStmt, $status);
                if (mysqli_stmt_fetch($reqStmt)) {
                    $payload['request_status'] = $status; // 'Pending' | 'Accepted' | 'Rejected'
                }
                mysqli_stmt_close($reqStmt);
            }
        }
        
        $r['payload'] = $payload;
        $r['is_read'] = intval($r['is_read']) === 1;
        $out[] = $r;
    }
    mysqli_stmt_close($stmt);
    
    echo json_encode(['ok' => true, 'notifications' => $out]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    
    if ($action === 'mark_read') {
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            jsonResponse(400, ['ok' => false, 'error' => 'invalid_id']);
        }
        
        $stmt = mysqli_prepare($conn, "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, 'ii', $id, $me);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        echo json_encode(['ok' => true]);
        exit;
    }
    
    if ($action === 'mark_unread') {
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            jsonResponse(400, ['ok' => false, 'error' => 'invalid_id']);
        }
        
        $stmt = mysqli_prepare($conn, "UPDATE notifications SET is_read = 0 WHERE id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, 'ii', $id, $me);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        echo json_encode(['ok' => true]);
        exit;
    }
    
    if ($action === 'mark_all_read') {
        $stmt = mysqli_prepare($conn, "UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $me);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        echo json_encode(['ok' => true]);
        exit;
    }
}

jsonResponse(400, ['ok' => false, 'error' => 'invalid_request']);

