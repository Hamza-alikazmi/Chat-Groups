 <?php
session_start();
require '../db.php';
if ($_SESSION['role']!=='creator') {die; header('Location: groups.php'); exit;}
$id = (int)$_POST['id'];
// ensure ownership
$conn->query("DELETE FROM chat_groups WHERE id=$id AND creator_id=".$_SESSION['user_id']);
header('Location: dashboard.php');

