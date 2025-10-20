<?php
// Simple DB inspection endpoint for local debugging
header('Content-Type: application/json; charset=utf-8');
include __DIR__ . '/../../src/db.php';

$out = [ 'ok' => true, 'time' => date('c') ];

function safe_query($conn, $sql){
    $r = mysqli_query($conn, $sql);
    if ($r === false) return ['error' => mysqli_error($conn)];
    $rows = [];
    while ($row = mysqli_fetch_assoc($r)) $rows[] = $row;
    return $rows;
}

$out['posts_describe'] = safe_query($conn, 'DESCRIBE posts');
$out['accounts_describe'] = safe_query($conn, 'DESCRIBE accounts');
$out['posts_count'] = safe_query($conn, 'SELECT COUNT(*) AS cnt FROM posts');
$out['accounts_count'] = safe_query($conn, 'SELECT COUNT(*) AS cnt FROM accounts');
// sample rows
$out['posts_sample'] = safe_query($conn, 'SELECT * FROM posts ORDER BY PostID DESC LIMIT 5');
$out['accounts_sample'] = safe_query($conn, 'SELECT * FROM accounts ORDER BY UserID DESC LIMIT 5');

echo json_encode($out, JSON_PRETTY_PRINT);
