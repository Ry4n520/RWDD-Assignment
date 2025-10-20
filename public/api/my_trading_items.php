<?php
session_start();
header('Content-Type: application/json');
include __DIR__ . '/../../src/db.php';

$me = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
if (!$me) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'not_authenticated']); exit; }

$res = mysqli_query($conn, "SELECT t.ItemID, t.Name, t.Category, (SELECT path FROM trading_images ti WHERE ti.ItemID = t.ItemID ORDER BY ti.id ASC LIMIT 1) AS ImagePath FROM tradinglist t WHERE t.UserID = " . intval($me) . " ORDER BY t.DateAdded DESC");
$out = [];
while ($r = mysqli_fetch_assoc($res)) $out[] = $r;
echo json_encode(['ok'=>true,'items'=>$out]);
?>
