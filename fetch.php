<?php
session_start();
require 'db.php';

if (!isset($_GET['group_id'], $_SESSION['user_id'])) {
    die("Access denied");
}

$user_id = $_SESSION['user_id'];
$group_id = (int)$_GET['group_id'];

// Get group creator
$stmt = $conn->prepare("SELECT creator_id FROM chat_groups WHERE id = ?");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$stmt->bind_result($creator_id);
$stmt->fetch();
$stmt->close();

// Get user's role in the group
$stmt_role = $conn->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt_role->bind_param("ii", $group_id, $user_id);
$stmt_role->execute();
$stmt_role->bind_result($role);
$stmt_role->fetch();
$stmt_role->close();

// Fetch messages
$stmt = $conn->prepare("
    SELECT m.id, m.user_id, m.content, m.created_at, m.reply_to, u.username, u.avatar
    FROM messages m
    JOIN users u ON u.id = m.user_id
    WHERE m.group_id = ?
    ORDER BY m.created_at ASC
");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$res = $stmt->get_result();

$html = '';
while ($m = $res->fetch_assoc()) {
    $is_owner = $m['user_id'] == $user_id;
    $is_creator = $user_id == $creator_id;
    $is_admin = in_array($role, ['admin', 'leader']);
    $can_delete = $is_owner || $is_creator || $is_admin;
    $can_edit = $is_owner && (time() - strtotime($m['created_at']) < 3600);

    $cls = $is_owner ? 'sent' : 'recv';

    // Prepare reply content if applicable
    $reply_html = '';
    if (!empty($m['reply_to'])) {
        $stmt_reply = $conn->prepare("
            SELECT m2.content, u2.username
            FROM messages m2
            JOIN users u2 ON u2.id = m2.user_id
            WHERE m2.id = ?
        ");
        $stmt_reply->bind_param("i", $m['reply_to']);
        $stmt_reply->execute();
        $stmt_reply->bind_result($reply_content, $reply_username);
        if ($stmt_reply->fetch()) {
            $reply_html = '<div class="reply-to reply-preview">
                <strong>Replying to ' . htmlspecialchars($reply_username) . ':</strong><br>
                ' . htmlspecialchars($reply_content) . '
            </div>';
        }
        $stmt_reply->close();
    }

    // Avatar path
    $avatar = (!empty($m['avatar']) && file_exists('avatars/' . $m['avatar']))
        ? htmlspecialchars($m['avatar'])
        : 'default.jpg';

    // Build message HTML
    $html .= '<div class="msg ' . $cls . '">
        <div class="msg-content" style="position:relative; display:flex; gap:10px; align-items:flex-start;">
            <img src="avatars/' . $avatar . '" alt="Avatar" class="avatar-img">
            <div>
                <strong>' . htmlspecialchars($m['username']) . '</strong><br>
                ' . $reply_html . '
                <span>' . htmlspecialchars($m['content']) . '</span>
                <div class="time">' . date('h:i A', strtotime($m['created_at'])) . '</div>
            </div>
        </div>
        <div class="msg-actions" style="position:absolute; top:5px; right:5px; display:flex; gap:5px;">';

    // Reply
    $html .= '<button type="button" onclick="setReply(' . $m['id'] . ', \'' . addslashes(htmlspecialchars($m['content'])) . '\')" title="Reply">
                <i class="fa fa-reply"></i>
              </button>';

    // Edit
    if ($can_edit) {
        $html .= '<form method="GET" action="edit_message.php" style="display:inline;">
                    <input type="hidden" name="msg_id" value="' . $m['id'] . '">
                    <button title="Edit"><i class="fa fa-pencil-alt"></i></button>
                  </form>';
    }

    // Delete
    if ($can_delete) {
        $html .= '<form method="POST" action="delete_message.php" style="display:inline;">
                    <input type="hidden" name="msg_id" value="' . $m['id'] . '">
                    <button title="Delete"><i class="fa fa-trash-alt"></i></button>
                  </form>';
    }

    $html .= '</div></div>'; // Close msg-actions and msg
}

echo $html;
?>
