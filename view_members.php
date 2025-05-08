<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'], $_GET['group_id'])) {
    header("Location: index.php");
    exit;
}

$group_id = (int)$_GET['group_id'];
$user_id = $_SESSION['user_id'];

// Check if user is leader or creator
$stmt = $conn->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->bind_param("ii", $group_id, $user_id);
$stmt->execute();
$stmt->bind_result($user_role);
$stmt->fetch();
$stmt->close();
if (!in_array($user_role, ['leader', 'creator'])) {
    echo '<p style="display:flex; flex-wrap:wrap; padding:10px; background:#fff3cd; color:#856404; border:1px solid #ffeeba; border-radius:6px; font-family:Arial;">
            &#9888; Only Leaders and Creators can view group members.
          </p>';
    exit;
}



// Actions
function handle_action($conn, $group_id, $param, $sql) {
    $id = (int)$_GET[$param];
    global $user_id;
    if ($id !== $user_id) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $group_id, $id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: view_members.php?group_id=$group_id");
    exit;
}

if (isset($_GET['approve'])) handle_action($conn, $group_id, 'approve', "UPDATE group_members SET is_approved = 1 WHERE group_id = ? AND user_id = ?");
if (isset($_GET['kick']))    handle_action($conn, $group_id, 'kick', "DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
if (isset($_GET['ban']))     handle_action($conn, $group_id, 'ban', "UPDATE group_members SET banned = 1 WHERE group_id = ? AND user_id = ?");
if (isset($_GET['promote'])) handle_action($conn, $group_id, 'promote', "UPDATE group_members SET role = 'leader' WHERE group_id = ? AND user_id = ?");
if (isset($_GET['demote']))  handle_action($conn, $group_id, 'demote', "UPDATE group_members SET role = 'member' WHERE group_id = ? AND user_id = ?");

// Fetch members
$stmt = $conn->prepare("
    SELECT u.id, u.username, gm.role, gm.is_approved, gm.banned 
    FROM group_members gm 
    JOIN users u ON gm.user_id = u.id 
    WHERE gm.group_id = ?
");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Group Members</title>
    <style>
body {
    font-family: Arial, sans-serif;
    padding: 20px;
    background: #fafafa;
    overflow-x: hidden;
}

h2 {
    margin-bottom: 20px;
}

/* Member list layout */
ul.member-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

li.member-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 14px;
    margin-bottom: 8px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    transition: background 0.3s ease;
    position: relative;
    max-width: 100%; /* Ensure the item stays within the container */
    flex-wrap: wrap;
    gap: 10px; /* Space between items */
}

li.member-item:hover {
    background: #f5f5f5;
}

/* Member info layout */
.member-info {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
}

.member-info span {
    margin-right: 10px;
}

/* Role labels */
.role-label {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.8em;
    color: #fff;
    text-transform: capitalize;
}

.role-label.creator {
    background-color: #4CAF50;
}

.role-label.leader {
    background-color: #2196F3;
}

.role-label.member {
    background-color: #9E9E9E;
}

/* Status indicators */
.pending {
    color: #999;
    font-style: italic;
}

.banned {
    color: #c62828;
    font-weight: bold;
    margin-left: 5px;
}

/* Actions - hidden by default, shown on hover */
.actions {
    display: none;
    gap: 6px;
}

li.member-item:hover .actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end; /* Align actions to the right */
}

/* Action buttons */
.actions a {
    text-decoration: none;
    padding: 4px 8px;
    background: #e0e0e0;
    border-radius: 4px;
    font-size: 0.85em;
    color: #333;
    transition: background 0.2s;
    margin: 2px;
}

.actions a:hover {
    background: #ccc;
}


    </style>
</head>
<body>
    <h2>Group Members</h2>
    <ul class="member-list">
        <?php while ($row = $result->fetch_assoc()): ?>
            <?php
                $role = $row['role'];
                $isPending = !$row['is_approved'];
                $isBanned = $row['banned'];
                $targetId = $row['id'];
            ?>
            <li class="member-item<?= $isPending ? ' pending' : '' ?>">
                <div class="member-info">
                    <span><strong><?= htmlspecialchars($row['username']) ?></strong></span>
                    <span class="role-label <?= $role ?>"><?= ucfirst($role) ?></span>
                    <?php if ($isPending): ?>
                        <span class="pending">(Pending)</span>
                    <?php endif; ?>
                    <?php if ($isBanned): ?>
                        <span class="banned">(Banned)</span>
                    <?php endif; ?>
                </div>
                <div class="actions">
                    <?php if ($isPending): ?>
                        <a href="?group_id=<?= $group_id ?>&approve=<?= $targetId ?>">Approve</a>
                    <?php endif; ?>
                    <?php if ($targetId === $user_id): ?>
                        <span style="font-style: italic; color: #555;">(You)</span>
                    <?php elseif ($role !== 'creator'): ?>
                        <a href="?group_id=<?= $group_id ?>&kick=<?= $targetId ?>" onclick="return confirm('Kick this user?')">Kick</a>
                        <a href="?group_id=<?= $group_id ?>&ban=<?= $targetId ?>" onclick="return confirm('Ban this user?')">Ban</a>
                        <?php if ($role == 'member'): ?>
                            <a href="?group_id=<?= $group_id ?>&promote=<?= $targetId ?>">Promote</a>
                        <?php elseif ($role == 'leader'): ?>
                            <a href="?group_id=<?= $group_id ?>&demote=<?= $targetId ?>">Demote</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </li>
        <?php endwhile; ?>
    </ul>
</body>
</html>
