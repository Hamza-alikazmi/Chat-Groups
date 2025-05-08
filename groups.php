

    <?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? null;

$stmt = $conn->prepare("
  SELECT cg.*,
    (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = cg.id AND gm.user_id = ?) AS requested,
    (SELECT gm.is_approved FROM group_members gm WHERE gm.group_id = cg.id AND gm.user_id = ?) AS approved
  FROM chat_groups cg
");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
$groups = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Groups</title>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
        * {
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', sans-serif;
      margin: 0;
      padding: 20px;
      background-color: #f1f3f6;
      color: #333;
    }

    h2 {
      margin-top: 0;
      color: #007bff;
    }

    a {
      color: #007bff;
      text-decoration: none;
      font-weight: 600;
    }

    a:hover {
      text-decoration: underline;
    }

    .group-card {
      background: white;
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 25px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      transition: box-shadow 0.3s ease;
    }

    .group-card:hover {
      box-shadow: 0 6px 18px rgba(0,0,0,0.08);
    }

    .group-card h3 {
      margin: 0 0 10px;
      font-size: 1.4rem;
      color: #333;
    }

    .group-card p {
      margin: 5px 0;
    }

    .status {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 6px;
      font-weight: 600;
      font-size: 0.9rem;
    }

    .status.open {
      background-color: #d4edda;
      color: #155724;
    }

    .status.closed {
      background-color: #f8d7da;
      color: #721c24;
    }

    form {
      margin-top: 15px;
    }

    input, textarea, select {
      width: 100%;
      margin-top: 8px;
      margin-bottom: 12px;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 1rem;
    }

    textarea {
      resize: vertical;
    }

    button {
      padding: 10px 18px;
      border: none;
      border-radius: 6px;
      font-weight: 600;
      cursor: pointer;
      transition: background-color 0.2s ease, transform 0.2s ease;
    }

    button:hover {
      transform: translateY(-1px);
    }

    .request {
      background-color: #007bff;
      color: white;
    }

    .save {
      background-color: #28a745;
      color: white;
    }

    .delete {
      background-color: #dc3545;
      color: white;
      margin-top: 10px;
    }

    .joined, .pending {
      font-weight: 600;
      margin-top: 8px;
    }

    .joined {
      color: #28a745;
    }

    .pending {
      color: #ffc107;
    }
    .group-icon {
      width: 60px;
      height: 60px;
      object-fit: cover;
     border-radius: 50%;
     border: 2px solid #007bff;
     margin-bottom: 10px;
}

    .edit-section {
      margin-top: 20px;
      border-top: 1px solid #eee;
      padding-top: 15px;
    }

    @media (max-width: 600px) {
      body {
        padding: 10px;
      }
    }
  </style>
</head>
<body>
  <h2>Available Groups</h2>
  <p><a href="logout.php">Logout</a></p>

  <?php foreach ($groups as $g): ?>
  <div class="group-card d-flex flex-row"
       data-group-id="<?= $g['id'] ?>"
       style="cursor: <?= $g['approved'] ? 'pointer' : 'not-allowed' ?>;"
       onclick="<?= $g['approved'] ? "window.location.href='chat.php?group_id={$g['id']}'" : "event.preventDefault();" ?>">

    <?php if (!empty($g['icon'])): ?>
      <img src="<?= htmlspecialchars($g['icon']) ?>" alt="Group Icon" class="group-icon">
    <?php else: ?>
      <img src="uploads/group_icons/default.png" alt="Default Icon" class="group-icon">
    <?php endif; ?>

    <div style="text-align: left; margin-left: 15px;">
      <h2><?= htmlspecialchars($g['name']) ?></h2>
      <p><?= htmlspecialchars($g['description']) ?></p>
      <p>
        Status:
        <span class="status <?= $g['is_open'] ? 'open' : 'closed' ?>">
          <?= $g['is_open'] ? 'Open' : 'Closed' ?>
        </span>
      </p>
    </div>
  </div>

  <?php if ($g['requested']): ?>
    <?php if ($g['approved']): ?>
      <p><a class="btn btn-primary me-4" href="chat.php?group_id=<?= $g['id'] ?>">Enter Chat</a></p>
    <?php else: ?>
      <p class="pending">⏳ Pending Approval</p>
    <?php endif; ?>
  <?php else: ?>
    <form method="POST" action="request_join.php">
      <input type="hidden" name="group_id" value="<?= $g['id'] ?>">
      <button class="request">Request to Join</button>
    </form>
  <?php endif; ?>

  <?php if (in_array($role, ['creator', 'admin', 'leader']) && $g['creator_id'] == $user_id): ?>      
    <div class="edit-section">
      <button class="toggle-edit" data-group-id="<?= $g['id'] ?>">✏️ Edit Group</button>
      <div class="edit-form" id="edit-form-<?= $g['id'] ?>" style="display: none;">
        <form class="edit-group-form" method="POST" action="creator/edit_group.php">
          <input type="hidden" name="group_id" value="<?= $g['id'] ?>">
          <select name="is_open" class="status-toggle">
            <option value="1" <?= $g['is_open'] ? 'selected' : '' ?>>Open</option>
            <option value="0" <?= !$g['is_open'] ? 'selected' : '' ?>>Closed</option>
          </select>
          <button type="submit" class="save">Save Changes</button>
        </form>

        <form method="POST" action="creator/delete_group.php" onsubmit="return confirm('Are you sure you want to delete this group?');">
          <input type="hidden" name="id" value="<?= $g['id'] ?>">
          <button class="delete">Delete Group</button>
        </form>
      </div>
    </div>
  <?php endif; ?>
<?php endforeach; ?>


  <script>
document.querySelectorAll('.edit-group-form').forEach(form => {
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    
    const groupId = this.getAttribute('data-group-id');
    const isOpen = this.querySelector('.status-toggle').value;

    fetch('creator/update_group_status.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: `group_id=${groupId}&is_open=${isOpen}`
    })
    .then(response => response.text())
    .then(result => {
      if (result.trim() === 'Success') {
        alert('Group status updated!');
        location.reload(); // Or you can update DOM without reloading
      } else {
        alert('Failed: ' + result);
      }
    });
  });
});
//check approval status

    document.querySelectorAll('.group-card').forEach(card => {
  card.addEventListener('click', function () {
    const groupId = this.getAttribute('data-group-id');

    if (this.style.cursor === 'not-allowed') return;

    fetch('creator/check_aprroval.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: 'group_id=' + groupId
    })
    .then(res => res.json())
    .then(data => {
      if (data.status === 'approved') {
        window.location.href = 'chat.php?group_id=' + groupId;
      } else if (data.status === 'pending') {
        alert('⏳ Your request to join this group is still pending approval.');
      } else if (data.status === 'not_requested') {
        alert('❌ You have not requested to join this group yet.');

// Toggle edit form visibility
    document.querySelectorAll('.toggle-edit').forEach(button => {
      button.addEventListener('click', () => {
        const groupId = button.getAttribute('data-group-id');
        const form = document.getElementById('edit-form-' + groupId);
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
      });
    });
  </script>
</body>
</html>
