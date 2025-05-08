<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'], $_GET['group_id'])) {
    die("Access denied");
}

$user_id = $_SESSION['user_id'];
$group_id = (int)$_GET['group_id'];

// Check membership & open status
$stmt = $conn->prepare("
  SELECT cg.is_open, gm.is_approved, cg.name, cg.creator_id, cg.icon, cg.description
  FROM chat_groups cg
  LEFT JOIN group_members gm ON gm.group_id = cg.id AND gm.user_id = ?
  WHERE cg.id = ?
");
$stmt->bind_param("ii", $user_id, $group_id);
$stmt->execute();
$stmt->bind_result($is_open, $is_approved, $group_name, $creator_id, $group_icon, $group_description);
$stmt->fetch();
$stmt->close();

if ($is_open !== 1 && !$is_approved) {
    die("You can't access this chat");
}

// Get user role
$stmt_role = $conn->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt_role->bind_param("ii", $group_id, $user_id);
$stmt_role->execute();
$stmt_role->bind_result($role);
$stmt_role->fetch();
$stmt_role->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Chat: <?= htmlspecialchars($group_name) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
  <link rel="stylesheet" href="chat.css">

</head>
<body>

<header>
  <h2>Group: <?= htmlspecialchars($group_name) ?></h2>

  <!-- Display the group icon -->
  <div class="group-info d-flex align-items-center">
    <?php if (!empty($group_icon) && $group_icon != 'default.jpg'): ?>
      <img src="<?= htmlspecialchars($group_icon) ?>" alt="Group Icon" class="group-icon me-2">
    <?php else: ?>
      <img src="" alt="Default Icon" class="group-icon me-2">
    <?php endif; ?>
    <span class="group-description"><?= htmlspecialchars($group_description) ?></span>


          <div class="dropdown ms-auto">
            <button class="btn btn-primary dropdown-toggle" type="button" id="groupActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              Actions
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="groupActionsDropdown">
              <li><a class="dropdown-item" href="./creator/edit_group.php?group_id=<?= $group_id ?>">Edit Group</a></li>
              <li><a class="dropdown-item" href="groups.php">Back to Groups</a></li>
              <li><a class="dropdown-item" href="logout.php">Logout</a></li>
            </ul>
          </div>
  </div>
  <script>
    function toggleDarkMode() {
      document.body.classList.toggle('dark-mode');
      const elementsToToggle = document.querySelectorAll('.container, .chat-box, .sidebar, header, .msg, form#send-message-form, .modal-content, .reply-preview');
      elementsToToggle.forEach(el => el.classList.toggle('dark-mode'));
    }
  </script>
</header>

<div class="container">
  <div class="chat-section">
    <div class="chat-box d-flex flex-column max-height:400;" id="chat">
      <!-- Messages will be loaded here via AJAX -->
    </div>
    <div class="sidebar" id="memberSidebar">
      <h4>Group Members</h4>
      <ul id="sidebarMembersList"></ul>
    </div>
  </div>

  <form id="send-message-form">
    <input type="hidden" name="group_id" value="<?= $group_id ?>">
    <input type="hidden" id="reply_to" name="reply_to" value="">
    <div id="replyingToBox" style="display:none;" class="reply-preview"></div>
    <input id="message-content" name="content" type="text" placeholder="Type a message…" required>
    <button type="submit">Send</button>
  </form>

  <button id="viewMembersBtn" onclick="openGroupMembersModal()">View Members</button>
</div>

<div id="groupMembersModal" class="modal">
  <div class="modal-content">
    <h3>Group Members</h3>
    <ul id="modalMembersList"></ul>
    <button onclick="closeGroupMembersModal()">Close</button>
  </div>
</div>

<script>
  function openGroupMembersModal() {
    $('#groupMembersModal').fadeIn();
  }

  function closeGroupMembersModal() {
    $('#groupMembersModal').fadeOut();
  }

  function fetchMessages() {
    $.get('fetch.php', { group_id: <?= $group_id ?> }, function(response) {
      $('#chat').html(response);
      $('#chat').scrollTop($('#chat')[0].scrollHeight);
    });
  }

  $('#send-message-form').on('submit', function(e) {
    e.preventDefault();
    let content = $('#message-content').val().trim();
    if (content) {
      $.post('send.php', $(this).serialize(), function(resp) {
        try {
          let data = JSON.parse(resp);
          if (data.status === 'success') {
            $('#message-content').val('');
            $('#reply_to').val('');
            $('#replyingToBox').hide().text('');
            fetchMessages();
          } else {
            alert("Error: " + (data.message || "Unknown error."));
          }
        } catch (err) {
          console.error('JSON parse error', err);
        }
      });
    }
  });

  function loadGroupMembers() {
    $.get('view_members.php', { group_id: <?= $group_id ?> }, function(data) {
      $('#sidebarMembersList').html(data);
      $('#modalMembersList').html(data);
    });
  }

  function setReply(msgId, text) {
    $('#reply_to').val(msgId);
    $('#replyingToBox').show().text("Replying to: " + text);
    $('#message-content').focus();
  }

  // Make replyTo function globally available (called from fetch.php)
  window.setReply = setReply;

  fetchMessages();
  loadGroupMembers();
  setInterval(fetchMessages, 2000);
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
</body>
</html>
