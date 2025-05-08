<?php
session_start();
require 'db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $role = trim($_POST['role'] ?? 'member');
  $password_raw = $_POST['password'] ?? '';
  $password = password_hash($password_raw, PASSWORD_BCRYPT);

  // Validation checks
  if (strlen($name) < 3) {
    $error = 'Full name must be at least 3 characters.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Invalid email format.';
  } elseif (strlen($username) < 3) {
    $error = 'Username must be at least 3 characters.';
  } elseif (strlen($password_raw) < 6) {
    $error = 'Password must be at least 6 characters.';
  } elseif (!in_array($role, ['Professor', 'Student'])) {
    $error = 'Invalid role selected.';
  }
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$error = $error ?? '';
  // Handle avatar upload
  $avatar = 'default.png';
  if (!$error && isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
    $fileName = uniqid() . "_" . basename($_FILES["avatar"]["name"]);
    $targetFile = $targetDir . $fileName;
    if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $targetFile)) {
      $avatar = $fileName;
    }
  }

  if (!$error) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
      $error = 'Username or email already taken.';
    } else {
      $stmt = $conn->prepare("
        INSERT INTO users (username, password, name, email, avatar, role)
        VALUES (?, ?, ?, ?, ?, ?)
      ");
      if ($stmt) {
        $stmt->bind_param("ssssss", $username, $password, $name, $email, $avatar, $role);
        if ($stmt->execute()) {
          $stmt->close();
          header('Location: login.php');
          exit;
        } else {
          $error = 'Error during registration. Please try again.';
        }
      } else {
        $error = 'Database error. Please contact support.';
      }
    }
    $stmt->close();
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registration</title>
  <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-attachment: fixed;
            background-color: #000;
            overflow: hidden;
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        .container {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.37);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            width: 90%;
            max-width: 400px;
            padding: 20px;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .container h1 {
            margin-bottom: 20px;
            font-size: 24px;
        }

        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .form-group input {
            width: 95%;
            padding: 10px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 5px;
            font-size: 14px;
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
        }

        .form-group input:focus {
            border-color: #4CAF50;
            outline: none;
            background: rgba(255, 255, 255, 0.3);
        }

        .btn {
            background: rgba(76, 175, 80, 0.8);
            color: #fff;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
        }

        .btn:hover {
            background: rgba(69, 160, 73, 0.9);
        }

        .footer {
            margin-top: 15px;
            font-size: 12px;
        }

        .footer a {
            color: #4CAF50;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }
  </style>
</head>
<body>
  <canvas id="backgroundCanvas"></canvas>
  <div class="container">
  <h1>Register</h1>
  <?php if ($error): ?><p style="color:red;"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="POST" action="register.php" enctype="multipart/form-data">
  
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <div class="form-group">
    <label for="name">Full Name</label>
    <input type="text" id="name" name="name" placeholder="Enter your full name" required>
  </div>
  <div class="form-group">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" placeholder="Enter your email" required>
  </div>
  <div class="form-group">
    <label for="avatar">Avatar</label>
    <input type="file" id="avatar" name="avatar" accept="image/*">
  </div>
  <div class="form-group">
    <label for="role">Role In University</label>
    <select name="role" id="role" required>
      <option value="Professor">Professor</option>
      <option value="Student">Student</option>
    </select>
  </div>
  <div class="form-group">
    <label for="username">Username</label>
    <input type="text" id="username" name="username" placeholder="Enter your username" required>
  </div>
  <div class="form-group">
    <label for="password">Password</label>
    <div style="position: relative;">
      <input type="password" id="password" name="password" placeholder="Create a password" required>
      <span id="togglePassword" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #fff;">👁️</span>
    </div>
  </div>
  <button type="submit" class="btn">Sign Up</button>
</form>

  <div class="footer">
    Already have an account? <a href="login.php">Log in</a>
  </div>
  </div>
  <script>
  const togglePassword = document.getElementById('togglePassword');
  const passwordInput = document.getElementById('password');

  togglePassword.addEventListener('click', () => {
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);
    togglePassword.textContent = type === 'password' ? '👁️' : '🙈';
  });
  </script>
  <script>
    const canvas = document.getElementById('backgroundCanvas');
    const ctx = canvas.getContext('2d');

    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;

    const particles = [];

    class Particle {
      constructor(x, y, radius, color, velocity) {
        this.x = x;
        this.y = y;
        this.radius = radius;
        this.color = color;
        this.velocity = velocity;
      }

      draw() {
        ctx.beginPath();
        ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2, false);
        ctx.fillStyle = this.color;
        ctx.fill();
        ctx.closePath();
      }

      update() {
        this.x += this.velocity.x;
        this.y += this.velocity.y;

        if (this.x - this.radius < 0 || this.x + this.radius > canvas.width) {
          this.velocity.x = -this.velocity.x;
        }

        if (this.y - this.radius < 0 || this.y + this.radius > canvas.height) {
          this.velocity.y = -this.velocity.y;
        }

        this.draw();
      }
    }

    function initParticles() {
      for (let i = 0; i < 50; i++) {
        const radius = Math.random() * 3 + 1;
        const x = Math.random() * canvas.width;
        const y = Math.random() * canvas.height;
        const color = `rgba(255, 255, 255, ${Math.random()})`;
        const velocity = {
          x: (Math.random() - 0.5) * 2,
          y: (Math.random() - 0.5) * 2
        };

        particles.push(new Particle(x, y, radius, color, velocity));
      }
    }

    function animate() {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      particles.forEach(particle => particle.update());
      requestAnimationFrame(animate);
    }

    initParticles();
    animate();

    window.addEventListener('resize', () => {
      canvas.width = window.innerWidth;
      canvas.height = window.innerHeight;
      particles.length = 0;
      initParticles();
    });
  </script>
  <script>
document.querySelector("form").addEventListener("submit", function (e) {
  const name = document.getElementById("name").value.trim();
  const email = document.getElementById("email").value.trim();
  const username = document.getElementById("username").value.trim();
  const password = document.getElementById("password").value;
  const role = document.getElementById("role").value;

  let errorMessages = [];

  if (name.length < 3) {
    errorMessages.push("Name must be at least 3 characters.");
  }

  const emailPattern = /^[^ ]+@[^ ]+\.[a-z]{2,}$/i;
  if (!emailPattern.test(email)) {
    errorMessages.push("Invalid email format.");
  }

  if (username.length < 3) {
    errorMessages.push("Username must be at least 3 characters.");
  }

  if (password.length < 6) {
    errorMessages.push("Password must be at least 6 characters.");
  }

  if (!["Professor", "Student"].includes(role)) {
    errorMessages.push("Please select a valid role.");
  }

  if (errorMessages.length > 0) {
    e.preventDefault();
    alert(errorMessages.join("\n"));
  }
});

// Password toggle
document.getElementById("togglePassword").addEventListener("click", function () {
  const passwordField = document.getElementById("password");
  const type = passwordField.getAttribute("type") === "password" ? "text" : "password";
  passwordField.setAttribute("type", type);
  this.textContent = type === "password" ? "👁️" : "🙈";
});
</script>

</body>
</html>
