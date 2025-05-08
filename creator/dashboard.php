<?php
session_start();
require '../db.php';
if ($_SESSION['role'] !== 'creator') die("Unauthorized");

$user_id = $_SESSION['user_id'];

// Fetch creator's groups
$res = $conn->query("
  SELECT * FROM chat_groups WHERE creator_id = $user_id
");

// Fetch pending group joins
$jr = $conn->query("
  SELECT gm.id, u.username, u.id as user_id, cg.name
  FROM group_members gm
  JOIN users u ON u.id = gm.user_id
  JOIN chat_groups cg ON cg.id = gm.group_id
  WHERE cg.creator_id = $user_id AND gm.is_approved = 0
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Creator Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; }

    body {
      font-family: 'Inter', sans-serif;
      margin: 0;
      padding: 0;
      background-color: #f0f2f5;
      color: #333;
    }

    header {
      background: linear-gradient(to right, #007bff, #0056b3);
      color: white;
      padding: 1rem 2rem;
      text-align: center;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

    header h1 { margin: 0; font-size: 1.8rem; }
    header a {
      color: white;
      margin: 0 10px;
      text-decoration: none;
      font-weight: 600;
    }

    header a:hover { text-decoration: underline; }

    main {
      max-width: 900px;
      margin: 30px auto;
      padding: 20px;
      background: white;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.05);
    }

    h2, h3 { color: #007bff; margin-bottom: 10px; }

    form {
      margin-bottom: 20px;
    }

    input, textarea, select, button {
      display: block;
      width: 100%;
      margin: 10px 0;
      padding: 12px 14px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 1rem;
    }

    input[type="file"] {
      padding: 6px;
    }

    button {
      background-color: #007bff;
      color: white;
      border: none;
      transition: background-color 0.3s, transform 0.2s;
    }

    button:hover {
      background-color: #0056b3;
      transform: scale(1.02);
    }

    .group-card, .request-card {
      border: 1px solid #ddd;
      border-radius: 10px;
      padding: 15px 20px;
      margin: 15px 0;
      background-color: #fdfdfd;
      transition: box-shadow 0.3s ease;
    }

    .group-card:hover, .request-card:hover {
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .request-card {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
    }

    .request-card span {
      font-weight: 500;
      flex: 1 1 60%;
    }

    .request-card form {
      display: inline;
      margin-left: 10px;
    }

    .group-card img {
      max-width: 80px;
      max-height: 80px;
      border-radius: 8px;
      float: right;
      margin-left: 10px;
    }

    @media (max-width: 600px) {
      .group-card form,
      .request-card {
        flex-direction: column;
        gap: 10px;
      }

      .request-card span {
        flex: 1 1 100%;
      }

      .request-card form {
        width: 100%;
      }
    }
  </style>
</head>
<body>
<header>
  <h1>Creator Dashboard</h1>
  <a href="../groups.php">Back to Groups</a> |
  <a href="../logout.php">Logout</a>
</header>
<main>
  <h2>Your Groups</h2>

  <h3>Create New Group</h3>
  <form method="POST" action="create_group.php" enctype="multipart/form-data">
    <input name="name" placeholder="Group Name" required>
    <textarea name="description" placeholder="Group Description"></textarea>
    <input type="file" name="icon" accept="image/*">
    <button>Create Group</button>
  </form>

  <?php while ($g = $res->fetch_assoc()): ?>
    <div class="group-card">
      <?php if (!empty($g['icon'])): ?>
        <img src="../<?= htmlspecialchars($g['icon']) ?>" alt="Group Icon">
      <?php endif; ?>
      <h4><?= htmlspecialchars($g['name']) ?></h4>
      <p><?= nl2br(htmlspecialchars($g['description'])) ?></p>
    </div>
  <?php endwhile; ?>

  <h3>Pending Requests</h3>
  <?php while ($r = $jr->fetch_assoc()): ?>
    <?php
      // Count how many groups this user is already approved in
      $check = $conn->query("SELECT COUNT(*) AS total FROM group_members WHERE user_id = " . $r['user_id'] . " AND is_approved = 1");
      $memberData = $check->fetch_assoc();
      $groupCount = $memberData['total'];
    ?>
    <div class="request-card">
      <span>
        <?= htmlspecialchars($r['username']) ?> → <?= htmlspecialchars($r['name']) ?>
        (<?= $groupCount == 0 ? 'New Member' : "$groupCount Groups" ?>)
      </span>
      <div style="display: flex; gap: 10px;">
        <form method="POST" action="approve_request.php">
          <input name="id" value="<?= $r['id'] ?>" hidden>
          <button>Approve</button>
        </form>
        <form method="POST" action="reject_request.php">
          <input name="id" value="<?= $r['id'] ?>" hidden>
          <button style="background-color: #dc3545;">Reject</button>
        </form>
      </div>
    </div>
  <?php endwhile; ?>
</main>
</body>
</html>
