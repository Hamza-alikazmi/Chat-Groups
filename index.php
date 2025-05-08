<?php
session_start();
require 'db.php';

$is_logged_in = false;
$is_creator = false;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $is_logged_in = true;

    // Get role from DB
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($role);
    if ($stmt->fetch()) {
        $is_creator = ($role === 'creator');
    }
    $stmt->close();
}

// Dummy course data (replace with DB logic later)
$courses = [
    ["title" => "Introduction to PHP", "description" => "Learn the basics of PHP programming."],
    ["title" => "Web Development with HTML/CSS", "description" => "Build beautiful and responsive websites."],
    ["title" => "JavaScript for Beginners", "description" => "Start scripting dynamic content on your pages."]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Landing Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        header {
            background-color: #0073e6;
            padding: 20px;
            color: white;
            text-align: center;
        }
        nav {
            background-color: #333;
            padding: 10px 0;
            text-align: center;
        }
        nav a {
            margin: 0 15px;
            color: white;
            text-decoration: none;
            font-weight: bold;
        }
        nav a:hover {
            text-decoration: underline;
        }
        main {
            padding: 20px;
        }
        .course {
            background-color: #fff;
            margin: 10px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .course h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .course p {
            color: #555;
        }
        .login-status {
            color: #28a745;
            font-size: 1.1em;
        }
    </style>
</head>
<body>

<header>
    <h1>Welcome to Our Course Platform</h1>
</header>

<nav>
    <a href="index.php">Home</a>
    <a href="about.php">About Us</a>
    <a href="contact.php">Contact Us</a>
    <?php if ($is_logged_in): ?>
        <a href="creator/dashboard.php">Dashboard</a>
        <?php if ($is_creator): ?>
            <a href="creator/tools.php">Creator Tools</a>
        <?php endif; ?>
        <a href="logout.php">Logout</a>
    <?php else: ?>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
    <?php endif; ?>
</nav>

<main>
    <h2>Available Courses</h2>

    <?php if ($is_logged_in): ?>
        <p class="login-status">You are logged in!</p>
    <?php else: ?>
        <p>Sign in to enroll in your favorite courses!</p>
    <?php endif; ?>

    <?php foreach ($courses as $course): ?>
        <div class="course">
            <h3><?= htmlspecialchars($course['title']) ?></h3>
            <p><?= htmlspecialchars($course['description']) ?></p>
        </div>
    <?php endforeach; ?>
</main>

</body>
</html>
