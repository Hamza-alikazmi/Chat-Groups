<?php
session_start();
require 'db.php';

// Set header for JSON response
header('Content-Type: application/json');

// Check if necessary parameters are set
if (!isset($_SESSION['user_id'], $_POST['group_id'], $_POST['content'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$user_id = $_SESSION['user_id'];
$group_id = (int)$_POST['group_id'];
$content = trim($_POST['content']);
$reply_to = isset($_POST['reply_to']) ? (int)$_POST['reply_to'] : null; // Get reply_to if set

// Validate content
if (empty($content)) {
    echo json_encode(['status' => 'error', 'message' => 'Message content cannot be empty']);
    exit;
}


// Prepare the insert query for messages
if ($reply_to) {
    // If replying to another message, store the reply_to field
    $stmt = $conn->prepare("INSERT INTO messages (group_id, user_id, content, reply_to) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iisi", $group_id, $user_id, $content, $reply_to);
} else {
    // If not replying, just insert the message normally
    $stmt = $conn->prepare("INSERT INTO messages (group_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $group_id, $user_id, $content);
}

// Execute the statement
if ($stmt->execute()) {
    // Check if the row is affected
    if ($stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No rows affected']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Message not sent: ' . $stmt->error]);
}

// Close the prepared statements and DB connection
$stmt->close();
$conn->close();
?>
