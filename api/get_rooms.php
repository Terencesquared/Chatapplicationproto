<?php
header("Content-Type: application/json");
require_once '../config/db.php';

$stmt = $pdo->query("SELECT id, name FROM chat_rooms ORDER BY id DESC");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($rooms);
?>
