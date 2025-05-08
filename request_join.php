<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['group_id'])) {
    die("Unauthorized access.");
}

$user_id = $_SESSION['user_id'];
$group_id = $_POST['group_id'];

// Prevent duplicate request
$stmt = $conn->prepare("SELECT * FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->bind_param("ii", $group_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt = $conn->prepare("INSERT INTO group_members (group_id, user_id, is_approved) VALUES (?, ?, 0)");
    $stmt->bind_param("ii", $group_id, $user_id);
    $stmt->execute();
}

header("Location: groups.php");
exit;
?>
 
