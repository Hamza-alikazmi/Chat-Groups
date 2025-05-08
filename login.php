<?php
session_start();
require 'db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $res = $conn->query("SELECT * FROM users WHERE username = '$username'");
    if ($res->num_rows === 1) {
        $user = $res->fetch_assoc();
        if (password_verify($_POST['password'], $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role']    = $user['role'];
            header('Location: groups.php');
            exit;
        }
    }
    $error = 'Invalid credentials';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fancy Login</title>
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
            width: 100%;
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
        <h1>Login</h1>
        <?php if ($error): ?>
            <p style="color:red;"><?= $error ?></p>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div style="position: relative;">
                    <input type="password" id="password" name="password" required>
                    <span id="togglePassword" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #fff;">
                    👁️
                     </span><script>
                                const togglePassword = document.getElementById('togglePassword');
                                const passwordInput = document.getElementById('password');

                                togglePassword.addEventListener('click', () => {
                                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                                    passwordInput.setAttribute('type', type);
                                    togglePassword.textContent = type === 'password' ? '👁️' : '🙈';
                                });
                            </script>
                 </div>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
        <div class="footer">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
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
</body>
</html>
