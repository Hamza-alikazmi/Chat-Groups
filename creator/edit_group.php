<?php
session_start();
require '../db.php';

if (!isset($_GET['group_id']) || $_SESSION['role'] !== 'creator') {
    die('Unauthorized access');
}

$group_id = (int)$_GET['group_id'];

// Fetch the group details
$res = $conn->query("SELECT * FROM chat_groups WHERE id=$group_id");
$group = $res->fetch_assoc();

if ($group['creator_id'] != $_SESSION['user_id']) {
    die('Access denied');
}

// If the form is submitted, update the group
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $is_open = isset($_POST['is_open']) ? 1 : 0;

    // Handle icon upload
    $iconPath = $group['icon']; // Default to existing icon
    if (!empty($_FILES['icon']['name'])) {
        $targetDir = "../group_icons/";  // Ensure this directory exists and is writable
        $iconName = uniqid() . "_" . basename($_FILES["icon"]["name"]);
        $targetFile = $targetDir . $iconName;

        // Debugging: Check if file is actually uploaded
        echo "File name: " . $_FILES["icon"]["name"] . "<br>";
        echo "File type: " . $_FILES["icon"]["type"] . "<br>";
        echo "Temp file path: " . $_FILES["icon"]["tmp_name"] . "<br>";

        // Check if the upload directory is writable
        if (!is_writable($targetDir)) {
            echo "Error: Upload directory is not writable. Please check folder permissions.<br>";
        } else {
            // Validate the file type
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = mime_content_type($_FILES["icon"]["tmp_name"]);

            // Check if the file type is allowed
            if (in_array($fileType, $allowedTypes)) {
                // Check if move_uploaded_file() works
                if (move_uploaded_file($_FILES["icon"]["tmp_name"], $targetFile)) {
                    // If the icon upload is successful, update the icon path
                    $iconPath = 'group_icons/' . $iconName; // Store relative path
                    echo "Icon uploaded successfully.<br>";
                } else {
                    echo "Error: Failed to upload the icon. Please check folder permissions.<br>";
                }
            } else {
                echo "Error: Invalid file type. Please upload a valid image.<br>";
            }
        }
    }

    // Update group details in the database
    $stmt = $conn->prepare("UPDATE chat_groups SET name=?, description=?, is_open=?, icon=? WHERE id=?");
    $stmt->bind_param("ssisi", $name, $desc, $is_open, $iconPath, $id);
    $stmt->execute();

    // Debugging: Check if update was successful
    if ($stmt->affected_rows > 0) {
        echo "Group updated successfully.<br>";
        header("Location: ../chat.php?group_id={$group_id}");
        exit;
    } else {
        echo "Error: Failed to update group. Check database connection or changes.<br>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Group</title>
</head>
<body>
  <h2>Edit Group</h2>

  <form action="edit_group.php?group_id=<?= $group['id'] ?>" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= $group['id'] ?>">

    <label for="name">Group Name:</label>
    <input type="text" name="name" value="<?= htmlspecialchars($group['name']) ?>" required><br>

    <label for="description">Description:</label>
    <textarea name="description"><?= htmlspecialchars($group['description']) ?></textarea><br>

    <label for="is_open">Open Group:</label>
    <input type="checkbox" name="is_open" value="1" <?= $group['is_open'] ? 'checked' : '' ?>><br>

    <label for="icon">Group Icon:</label>
    <input type="file" name="icon"><br>
    <?php if ($group['icon'] && $group['icon'] !== 'default.jpg'): ?>
        <img src="../<?= $group['icon'] ?>" alt="Group Icon" style="max-width: 100px; max-height: 100px;"><br>
    <?php endif; ?>

    <button type="submit">Update Group</button>
  </form>
</body>
</html>
