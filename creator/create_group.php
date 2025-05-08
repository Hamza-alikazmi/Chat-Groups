<?php
session_start();
require '../db.php';
if ($_SESSION['role'] !== 'creator') die('Access denied');

// CSRF protection would be important to add here for security
// You can implement a simple token-based protection (recommend this in forms)

$name = $_POST['name'] ?? '';
$desc = $_POST['description'] ?? '';
$iconPath = null;

// Sanitize inputs to avoid XSS
$name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$desc = htmlspecialchars($desc, ENT_QUOTES, 'UTF-8');

// Handle file upload
if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = mime_content_type($_FILES['icon']['tmp_name']);

    if (in_array($fileType, $allowedTypes)) {
        $ext = pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION);
        $newName = uniqid('icon_', true) . '.' . $ext;
        $uploadDir = '../group_icons/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $targetPath = $uploadDir . $newName;

        if (move_uploaded_file($_FILES['icon']['tmp_name'], $targetPath)) {
            $iconPath = '../group_icons/' . $newName;
        } else {
            // Handle error if file move fails
            die('Error uploading the image.');
        }
    } else {
        // Handle invalid file type
        die('Invalid file type. Allowed types: JPEG, PNG, GIF, WEBP.');
    }
}

// Prepare SQL query using prepared statement
$stmt = $conn->prepare("
    INSERT INTO chat_groups (name, description, icon, creator_id)
    VALUES (?, ?, ?, ?)
");

if ($stmt === false) {
    die('Error preparing statement: ' . $conn->error);
}

$stmt->bind_param("sssi", $name, $desc, $iconPath, $_SESSION['user_id']);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    // Success
    header('Location: dashboard.php');
    exit;
} else {
    // Handle error if insert fails
    die('Error creating group: ' . $stmt->error);
}

$stmt->close();
?>
