<?php
/**
 * Minimal trade request API
 * POST actions:
 * - action=send {to_user_id, message, offer_post_id}
 * - action=respond {trade_id, response: accept|decline}
 */
session_start();
header('Content-Type: application/json');
include __DIR__ . '/../../src/db.php';
include __DIR__ . '/../../src/api_helpers.php';

$me = requireAuth();

// ensure notifications table exists
$conn->query("CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type VARCHAR(32) NOT NULL,
  payload TEXT,
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// make sure traderequests table exists
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
    $to = intval($_POST['to_user_id'] ?? 0);
    $item2 = intval($_POST['target_item_id'] ?? 0);
    $item1 = intval($_POST['offer_item_id'] ?? 0);
    
    if (!$to || !$item2 || !$item1) {
        jsonResponse(400, ['ok' => false, 'error' => 'missing_fields']);
    }

    // Validate ownership using prepared statements
    $stmt1 = mysqli_prepare($conn, "SELECT ItemID, UserID FROM tradinglist WHERE ItemID = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt1, 'i', $item1);
    mysqli_stmt_execute($stmt1);
    $res1 = mysqli_stmt_get_result($stmt1);
    $r1 = mysqli_fetch_assoc($res1);
    mysqli_stmt_close($stmt1);
    
    $stmt2 = mysqli_prepare($conn, "SELECT ItemID, UserID FROM tradinglist WHERE ItemID = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt2, 'i', $item2);
    mysqli_stmt_execute($stmt2);
    $res2 = mysqli_stmt_get_result($stmt2);
    $r2 = mysqli_fetch_assoc($res2);
    mysqli_stmt_close($stmt2);
    
    if (!$r1 || intval($r1['UserID']) !== intval($me)) {
        jsonResponse(400, ['ok' => false, 'error' => 'offer_item_not_owned']);
    }
    
    if (!$r2 || intval($r2['UserID']) !== intval($to)) {
        jsonResponse(400, ['ok' => false, 'error' => 'target_item_invalid']);
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO traderequests (Item1ID, Item2ID, SenderID, ReceiverID) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        jsonResponse(500, ['ok' => false, 'error' => 'db_prepare']);
    }
    
    mysqli_stmt_bind_param($stmt, 'iiii', $item1, $item2, $me, $to);
    mysqli_stmt_execute($stmt);
    $requestId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    // Add notification for recipient
    $payload = json_encode(['request_id' => $requestId, 'from' => $me, 'item1' => $item1, 'item2' => $item2]);
    $nstmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, type, payload) VALUES (?, ?, ?)");
    if ($nstmt) {
        $type = 'trade_request';
        mysqli_stmt_bind_param($nstmt, 'iss', $to, $type, $payload);
        mysqli_stmt_execute($nstmt);
        mysqli_stmt_close($nstmt);
    }

    jsonResponse(200, ['ok' => true, 'request_id' => $requestId]);
}

if ($action === 'respond') {
    $requestId = intval($_POST['trade_id'] ?? 0);
    $resp = ($_POST['response'] ?? '') === 'accept' ? 'Accepted' : 'Rejected';
    
    if (!$requestId) {
        jsonResponse(400, ['ok' => false, 'error' => 'invalid_trade']);
    }
    
    // Verify ownership using prepared statement
    $stmt = mysqli_prepare($conn, "SELECT RequestID, Item1ID, Item2ID, SenderID, ReceiverID FROM traderequests WHERE RequestID = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $requestId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $r = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    
    if (!$r) {
        jsonResponse(404, ['ok' => false, 'error' => 'not_found']);
    }
    
    if (intval($r['ReceiverID']) !== intval($me)) {
        jsonResponse(403, ['ok' => false, 'error' => 'not_permitted']);
    }
    
    // Update status using prepared statement
    $updateStmt = mysqli_prepare($conn, "UPDATE traderequests SET Status = ? WHERE RequestID = ?");
    mysqli_stmt_bind_param($updateStmt, 'si', $resp, $requestId);
    mysqli_stmt_execute($updateStmt);
    mysqli_stmt_close($updateStmt);

    // Notify the requester
    $payload = json_encode(['request_id' => $requestId, 'response' => $resp, 'by' => $me]);
    $nstmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, type, payload) VALUES (?, ?, ?)");
    if ($nstmt) {
        $type = $resp === 'Accepted' ? 'trade_accepted' : 'trade_rejected';
        $toUser = intval($r['SenderID']);
        mysqli_stmt_bind_param($nstmt, 'iss', $toUser, $type, $payload);
        mysqli_stmt_execute($nstmt);
        mysqli_stmt_close($nstmt);
    }
    
    jsonResponse(200, ['ok' => true]);
}

// Invalid action
jsonResponse(400, ['ok' => false, 'error' => 'invalid_action']);

