<?php
session_start();
include "includes/db.php";

$user_id = $_SESSION['user_id'];

$sql = "SELECT id, name, email FROM users WHERE id != ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while($row = $result->fetch_assoc()){
    $users[] = $row;
}

echo json_encode($users);
?>
