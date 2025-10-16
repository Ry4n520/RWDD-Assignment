<?php
/**
 * Notifications API
 * GET: returns list of notifications for the authenticated user
 * POST action=mark_read {id}
 */
session_start();
header('Content-Type: application/json');
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
    $r['payload'] = json_decode($r['payload'], true) ?: $r['payload'];
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
