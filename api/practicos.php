<?php
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/db.php';
$pdo = get_db();

$rows = $pdo->query("SELECT file, num, name, title, icon, description FROM practicos ORDER BY orden, id")->fetchAll();

echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
