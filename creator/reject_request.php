<?php
session_start();
require '../db.php';
if ($_SESSION['role']!=='creator') die;
$id = (int)$_POST['id'];
$conn->query("DELETE FROM group_members WHERE id=$id");
header('Location: dashboard.php');
 
