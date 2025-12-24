<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$db = getDB();
$stmt = $db->query("SELECT * FROM genres ORDER BY name");
$genres = $stmt->fetchAll();

echo json_encode(['success' => true, 'data' => $genres]);
?>

