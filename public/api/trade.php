<?php
/**
 * Minimal trade request API
 * POST actions:
 * - action=send {to_user_id, message, offer_post_id}
 * - action=respond {trade_id, response: accept|decline}
 *
 * This file is defensive and will create simple `trades` and `notifications` tables if absent.
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

// ensure notifications table exists (we keep notifications separate)
$conn->query("CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type VARCHAR(32) NOT NULL,
  payload TEXT,
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// make sure traderequests table exists (matching your DB schema)
$conn->query("CREATE TABLE IF NOT EXISTS traderequests (
  RequestID INT AUTO_INCREMENT PRIMARY KEY,
  Item1ID INT NOT NULL,
  Item2ID INT NOT NULL,
  SenderID INT NOT NULL,
  ReceiverID INT NOT NULL,
  Status ENUM('Pending','Accepted','Rejected') DEFAULT 'Pending',
  RequestedAt DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$action = $_POST['action'] ?? $_GET['action'] ?? null;
if ($action === 'send') {
  // expecting: to_user_id, target_item_id (Item2ID), offer_item_id (Item1ID)
  $to = intval($_POST['to_user_id'] ?? 0);
  $item2 = intval($_POST['target_item_id'] ?? 0);
  $item1 = intval($_POST['offer_item_id'] ?? 0);
  if (!$to || !$item2 || !$item1) { echo json_encode(['ok'=>false,'error'=>'missing_fields']); exit; }

  // validate ownership: item1 must belong to $me, item2 must belong to $to
  $r1 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT ItemID, UserID FROM tradinglist WHERE ItemID = " . intval($item1) . " LIMIT 1"));
  $r2 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT ItemID, UserID FROM tradinglist WHERE ItemID = " . intval($item2) . " LIMIT 1"));
  if (!$r1 || intval($r1['UserID']) !== intval($me)) { echo json_encode(['ok'=>false,'error'=>'offer_item_not_owned']); exit; }
  if (!$r2 || intval($r2['UserID']) !== intval($to)) { echo json_encode(['ok'=>false,'error'=>'target_item_invalid']); exit; }

  $stmt = mysqli_prepare($conn, "INSERT INTO traderequests (Item1ID,Item2ID,SenderID,ReceiverID) VALUES (?,?,?,?)");
  if (!$stmt) { echo json_encode(['ok'=>false,'error'=>'db_prepare']); exit; }
  mysqli_stmt_bind_param($stmt,'iiii',$item1,$item2,$me,$to);
  mysqli_stmt_execute($stmt);
  $requestId = mysqli_insert_id($conn);
  mysqli_stmt_close($stmt);

  // add notification for recipient
  $payload = json_encode(['request_id'=>$requestId,'from'=>$me,'item1'=>$item1,'item2'=>$item2]);
  $nstmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id,type,payload) VALUES (?,?,?)");
  if ($nstmt) {
    $type = 'trade_request';
    mysqli_stmt_bind_param($nstmt,'iss',$to,$type,$payload);
    mysqli_stmt_execute($nstmt);
    mysqli_stmt_close($nstmt);
  }

  echo json_encode(['ok'=>true,'request_id'=>$requestId]);
  exit;
}

if ($action === 'respond') {
  $requestId = intval($_POST['trade_id'] ?? 0);
  $resp = ($_POST['response'] ?? '') === 'accept' ? 'Accepted' : 'Rejected';
  if (!$requestId) { echo json_encode(['ok'=>false,'error'=>'invalid_trade']); exit; }
  // verify ownership (ReceiverID)
  $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM traderequests WHERE RequestID = " . intval($requestId) . " LIMIT 1"));
  if (!$r) { echo json_encode(['ok'=>false,'error'=>'not_found']); exit; }
  if (intval($r['ReceiverID']) !== intval($me)) { echo json_encode(['ok'=>false,'error'=>'not_permitted']); exit; }
  mysqli_query($conn, "UPDATE traderequests SET Status = '" . mysqli_real_escape_string($conn,$resp) . "' WHERE RequestID = " . intval($requestId));

  // notify the requester
  $payload = json_encode(['request_id'=>$requestId,'response'=>$resp,'by'=>$me]);
  $nstmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id,type,payload) VALUES (?,?,?)");
  if ($nstmt) {
    $type = $resp === 'Accepted' ? 'trade_accepted' : 'trade_rejected';
    $toUser = intval($r['SenderID']);
    mysqli_stmt_bind_param($nstmt,'iss',$toUser,$type,$payload);
    mysqli_stmt_execute($nstmt);
    mysqli_stmt_close($nstmt);
  }
  echo json_encode(['ok'=>true]);
  exit;
}

// not an allowed action
http_response_code(400);
echo json_encode(['ok'=>false,'error'=>'invalid_action']);

?>
