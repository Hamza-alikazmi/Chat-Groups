<?php
session_start();
require 'db.php';

if (!isset($_POST['msg_id'], $_SESSION['user_id'])) {
    die("Access denied");
}

$msg_id  = (int)$_POST['msg_id'];
$user_id = (int)$_SESSION['user_id'];

// Step 1: Fetch message details
$stmt = $conn->prepare("
    SELECT m.user_id, cg.creator_id, m.group_id
    FROM messages m
    JOIN chat_groups cg ON cg.id = m.group_id
    WHERE m.id = ?
");
$stmt->bind_param("i", $msg_id);
$stmt->execute();
$stmt->bind_result($msg_user_id, $creator_id, $group_id);

if (!$stmt->fetch()) {
    $stmt->close();
    die("Message not found");
}
$stmt->close();

// Step 2: Check the role of the current user in the group
$user_role = '';
$stmt = $conn->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->bind_param("ii", $group_id, $user_id);
$stmt->execute();
$stmt->bind_result($role);
if ($stmt->fetch()) {
    $user_role = $role;
}
$stmt->close();

// Step 3: Check permission based on roles
// Admin: Can delete messages from members, but not from leaders or creators
if ($user_role === 'admin') {
    if ($msg_user_id === $creator_id) {
        die("Admin cannot delete the creator's message");
    }
    // Admin can delete leader messages, so no need for extra check here
} elseif ($user_role === 'leader') {
    // Leader: Can delete messages from members, but not from creators
    if ($msg_user_id === $creator_id) {
        die("Leader cannot delete the creator's message");
    }
}

// Step 4: Only the message owner or the creator can delete the message
if ($user_id !== $msg_user_id && $user_id !== $creator_id) {
    die("Not allowed to delete this message");
}

// Step 5: Delete the message securely
$stmt = $conn->prepare("DELETE FROM messages WHERE id = ?");
$stmt->bind_param("i", $msg_id);
$stmt->execute();
$stmt->close();

// Step 6: Redirect back to the chat page
header("Location: chat.php?group_id=" . urlencode($group_id));
exit;
?>
