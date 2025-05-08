<?php
session_start();
require 'db.php';

if (!isset($_REQUEST['msg_id']) || !isset($_SESSION['user_id'])) {
    die("Access denied");
}

$msg_id = (int)$_REQUEST['msg_id'];
$user_id = $_SESSION['user_id'];

// Step 1: Fetch message details
$stmt = $conn->prepare("SELECT user_id, content, created_at, group_id FROM messages WHERE id=?");
$stmt->bind_param("i", $msg_id);
$stmt->execute();
$stmt->bind_result($uid, $content, $ctime, $group_id);
$stmt->fetch();
$stmt->close();

// Step 2: Check ownership and 1-minute window
if ($uid != $user_id || time() - strtotime($ctime) > 60) {
    die("Edit window closed or no permission to edit");
}

// Step 3: Process the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_content = trim($_POST['content']); // Remove unnecessary whitespace

    // Step 4: Check if new content is not empty
    if (empty($new_content)) {
        die("Message content cannot be empty.");
    }

    // Step 5: Update the message in the database
    $update_stmt = $conn->prepare("UPDATE messages SET content=? WHERE id=?");
    $update_stmt->bind_param("si", $new_content, $msg_id);

    if ($update_stmt->execute()) {
        header("Location: chat.php?group_id=$group_id");
        exit;
    } else {
        die("Error updating message.");
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Message</title>
</head>
<body>
    <h2>Edit Message</h2>
    <form method="POST">
        <textarea name="content" required><?= htmlspecialchars($content) ?></textarea><br>
        <button type="submit">Save</button>
    </form>
</body>
</html>
