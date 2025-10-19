<?php
session_start();
header('Content-Type: application/json');
include __DIR__ . '/../../src/db.php';

$currentUserId = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
if (!$currentUserId) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'error' => 'Not logged in']);
  exit;
}

// Fetch all trade requests made by the current user
$sql = "SELECT 
    tr.RequestID,
    tr.ItemID,
    tr.Status,
    t.Name AS ItemName,
    t.Category,
    t.Description,
    a.username AS OwnerName,
    (SELECT path FROM trading_images ti WHERE ti.ItemID = t.ItemID ORDER BY ti.id ASC LIMIT 1) AS ImagePath
  FROM tradingrequests tr
  JOIN tradinglist t ON tr.ItemID = t.ItemID
  JOIN accounts a ON t.UserID = a.UserID
  WHERE tr.SenderID = ?
  ORDER BY tr.RequestID DESC";

$trades = [];
if (isset($conn)) {
  if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, 'i', $currentUserId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
      $trades[] = $row;
    }
    mysqli_stmt_close($stmt);
  }
}

echo json_encode(['ok' => true, 'trades' => $trades]);
