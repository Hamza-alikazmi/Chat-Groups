<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo 'Unauthorized';
  exit;
}

$user_id = $_SESSION['user_id'];
$group_id = $_POST['group_id'] ?? null;
$is_open = $_POST['is_open'] ?? null;

if ($group_id === null || $is_open === null) {
  http_response_code(400);
  echo 'Invalid input';
  exit;
}

// Check ownership
$stmt = $conn->prepare("SELECT * FROM chat_groups WHERE id = ? AND creator_id = ?");
$stmt->bind_param("ii", $group_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
  http_response_code(403);
  echo 'Not allowed';
  exit;
}
$stmt->close();

// Update status
$update = $conn->prepare("UPDATE chat_groups SET is_open = ? WHERE id = ?");
$update->bind_param("ii", $is_open, $group_id);
$update->execute();
$update->close();

echo 'Success';
?>
