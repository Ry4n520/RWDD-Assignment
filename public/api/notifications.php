<?php
/**
 * Notifications API
 * GET: returns list of notifications for the authenticated user
 * POST action=mark_read {id}
 */
session_start();
header('Content-Type: application/json');
// Prevent caching to ensure latest request_status enrichment is always fetched
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
include __DIR__ . '/../../src/db.php';

$me = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
if (!$me) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'error'=>'not_authenticated']);
  exit;
}

// ensure table exists (no-op if already present)
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
  $res = mysqli_query($conn, "SELECT id, type, payload, is_read, created_at FROM notifications WHERE user_id = " . intval($me) . " ORDER BY created_at DESC LIMIT " . max(1,$limit));
  $out = [];
  while ($r = mysqli_fetch_assoc($res)) {
    $payload = json_decode($r['payload'], true);
    if (!is_array($payload)) { $payload = []; }
    // Enrich trading request notifications with current request status so UI can persist state after refresh
    if (($r['type'] === 'trading_request' || $r['type'] === 'trade_request') && isset($payload['request_id'])) {
      $reqId = intval($payload['request_id']);
      if ($reqId > 0) {
        if ($st = mysqli_prepare($conn, 'SELECT Status FROM tradingrequests WHERE RequestID = ? LIMIT 1')) {
          mysqli_stmt_bind_param($st, 'i', $reqId);
          mysqli_stmt_execute($st);
          mysqli_stmt_bind_result($st, $status);
          if (mysqli_stmt_fetch($st)) {
            $payload['request_status'] = $status; // 'Pending' | 'Accepted' | 'Rejected'
          }
          mysqli_stmt_close($st);
        }
      }
    }
    $r['payload'] = $payload;
    $r['is_read'] = intval($r['is_read']) === 1;
    $out[] = $r;
  }
  echo json_encode(['ok'=>true,'notifications'=>$out]);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? null;
  if ($action === 'mark_read') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['ok'=>false,'error'=>'invalid_id']); exit; }
    mysqli_query($conn, "UPDATE notifications SET is_read=1 WHERE id=" . intval($id) . " AND user_id=" . intval($me));
    echo json_encode(['ok'=>true]);
    exit;
  }
  if ($action === 'mark_all_read') {
    mysqli_query($conn, "UPDATE notifications SET is_read=1 WHERE user_id=" . intval($me));
    echo json_encode(['ok'=>true]);
    exit;
  }
}

http_response_code(400);
echo json_encode(['ok'=>false,'error'=>'invalid_request']);

?>
