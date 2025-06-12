<?php
header("Content-Type: application/json");
require_once '../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);
$name = trim($data['name'] ?? '');

if ($name === '') {
    echo json_encode(["error" => "Room name is required."]);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO chat_rooms (name) VALUES (:name)");
$stmt->execute(['name' => $name]);

echo json_encode(["success" => true, "room_id" => $pdo->lastInsertId()]);
?>
