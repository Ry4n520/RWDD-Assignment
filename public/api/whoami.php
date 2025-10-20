<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
$out = [ 'ok' => true, 'time' => date('c'), 'session' => [] ];
foreach ($_SESSION as $k => $v) { $out['session'][$k] = $v; }
// also echo cookies for debugging
$out['cookies'] = $_COOKIE;
echo json_encode($out, JSON_PRETTY_PRINT);
?>
