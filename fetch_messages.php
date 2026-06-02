<?php
session_start();
include "includes/db.php";

$user_id = $_SESSION['user_id'];
$receiver_id = $_GET['receiver_id'];

$stmt = $conn->prepare("
    SELECT m.*, u.name as sender_name 
    FROM messages m 
    JOIN users u ON m.sender_id = u.id 
    WHERE (m.sender_id = ? AND m.receiver_id = ?) 
       OR (m.sender_id = ? AND m.receiver_id = ?)
    ORDER BY m.created_at ASC
");
$stmt->bind_param("iiii", $user_id, $receiver_id, $receiver_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while($row = $result->fetch_assoc()){
    $messages[] = $row;
}
echo json_encode($messages);
?>
