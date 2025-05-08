<?php
session_start();
require '../db.php';

if ($_SESSION['role'] !== 'creator') die;
$id = $_POST['id'];

$res = $conn->query("SELECT creator_id FROM chat_groups WHERE id=$id");
if ($res->fetch_assoc()['creator_id'] != $_SESSION['user_id']) die;

$name = $_POST['name'];
$desc = $_POST['description'];
$is_open = isset($_POST['is_open']) ? 1 : 0;

// Handle icon upload
$iconPath = null;
if (!empty($_FILES['icon']['name'])) {
    $targetDir = "group_icons/";
    $iconName = uniqid() . "_" . basename($_FILES["icon"]["name"]);
    $targetFile = $targetDir . $iconName;

    if (move_uploaded_file($_FILES["icon"]["tmp_name"], $targetFile)) {
        $iconPath = $iconName;
        $conn->query("UPDATE chat_groups SET icon='$iconPath' WHERE id=$id");
    }
}

$stmt = $conn->prepare("UPDATE chat_groups SET name=?, description=?, is_open=? WHERE id=?");
$stmt->bind_param("ssii", $name, $desc, $is_open, $id);
$stmt->execute();

header('Location: dashboard.php');
