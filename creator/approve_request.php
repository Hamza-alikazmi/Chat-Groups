<?php
session_start();
require '../db.php';
if ($_SESSION['role']!=='creator') die;
$id = (int)$_POST['id'];
$conn->query("UPDATE group_members SET is_approved=1 WHERE id=$id");
header('Location: dashboard.php');
 
