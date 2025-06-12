<?php
header("Content-Type: application/json");
require_once '../config/db.php';

$room_id = intval($_GET['room_id'] ?? 0);
if ($room_id <= 0) {
    echo json_encode(["error" => "Invalid room ID."]);
    exit;
}

$stmt = $pdo->prepare("SELECT sender, content, created_at FROM messages WHERE room_id = :room_id ORDER BY created_at ASC");
$stmt->execute(['room_id' => $room_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($messages);
?>
