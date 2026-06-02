<?php
session_start();
if(!isset($_SESSION['user_id'])) exit;

include "includes/db.php";

$sender_id = $_SESSION['user_id'];
$receiver_id = $_POST['receiver_id'] ?? 0;
$message = $_POST['message'] ?? '';

if($receiver_id && $message){
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $sender_id, $receiver_id, $message);
    $stmt->execute();
}
?>
