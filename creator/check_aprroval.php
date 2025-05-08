<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
  exit;
}

$user_id = $_SESSION['user_id'];
$group_id = $_POST['group_id'] ?? null;

if (!$group_id) {
  echo json_encode(['status' => 'error', 'message' => 'Invalid group ID']);
  exit;
}

$stmt = $conn->prepare("SELECT approved FROM group_members WHERE user_id = ? AND group_id = ?");
$stmt->bind_param("ii", $user_id, $group_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
  if ($row['approved']) {
    echo json_encode(['status' => 'approved']);
  } else {
    echo json_encode(['status' => 'pending']);
  }
} else {
  echo json_encode(['status' => 'not_requested']);
}

$stmt->close();
?>
