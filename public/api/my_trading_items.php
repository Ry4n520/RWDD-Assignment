<?php
session_start();
header('Content-Type: application/json');
include __DIR__ . '/../../src/db.php';
include __DIR__ . '/../../src/api_helpers.php';

$me = requireAuth();

$stmt = mysqli_prepare($conn, "SELECT t.ItemID, t.Name, t.Category, 
    (SELECT path FROM trading_images ti WHERE ti.ItemID = t.ItemID ORDER BY ti.id ASC LIMIT 1) AS ImagePath 
    FROM tradinglist t 
    WHERE t.UserID = ? 
    ORDER BY t.DateAdded DESC");
mysqli_stmt_bind_param($stmt, 'i', $me);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$out = [];
while ($r = mysqli_fetch_assoc($res)) {
    $out[] = $r;
}
mysqli_stmt_close($stmt);

echo json_encode(['ok' => true, 'items' => $out]);

