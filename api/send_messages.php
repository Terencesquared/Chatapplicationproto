<?php
session_start();
header("Content-Type: application/json");
require_once '../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$user = $_SESSION['user'] ?? 'Anonymous';
$room_id = intval($data['room_id'] ?? 0);
$content = trim($data['message'] ?? '');

if ($room_id <= 0 || $content === '') {
    echo json_encode(["error" => "Invalid room or message."]);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO messages (room_id, sender, content) VALUES (:room_id, :sender, :content)");
$stmt->execute([
    'room_id' => $room_id,
    'sender' => $user,
    'content' => $content
]);

echo json_encode(["success" => true]);
?>
