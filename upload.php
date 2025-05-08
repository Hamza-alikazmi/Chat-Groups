<?php
session_start();
require 'db.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $link = trim($_POST['link']);
    $imagePath = 'books/default.jpg'; // default image

    if (empty($name) || empty($link)) {
        $error = "Name and link are required.";
    } else {
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'books/';
            $fileTmp = $_FILES['image']['tmp_name'];
            $fileName = basename($_FILES['image']['name']);
            $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
            $newName = uniqid() . '.' . $fileExt;
            $targetPath = $uploadDir . $newName;

            if (move_uploaded_file($fileTmp, $targetPath)) {
                $imagePath = $targetPath;
            } else {
                $error = "Image upload failed.";
            }
        }

        if (!isset($error)) {
            $stmt = $conn->prepare("INSERT INTO documents (name, image, link) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $imagePath, $link);
            if ($stmt->execute()) {
                $success = "Document uploaded successfully!";
            } else {
                $error = "Database error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Document</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f8;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
        }
        h2 {
            text-align: center;
            color: #333;
            margin-top: 40px;
            font-weight: 600;
        }
        form {
            background: #fff;
            max-width: 420px;
            width: 100%;
            margin: 30px auto 40px;
            padding: 30px 40px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            box-sizing: border-box;
        }
        input[type="text"],
        input[type="url"],
        input[type="file"] {
            width: 100%;
            padding: 12px 15px;
            margin-top: 15px;
            border: 1.8px solid #ccc;
            border-radius: 5px;
            font-size: 15px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        input[type="text"]:focus,
        input[type="url"]:focus,
        input[type="file"]:focus {
            border-color: #007BFF;
            outline: none;
        }
        button {
            width: 100%;
            margin-top: 20px;
            padding: 12px;
            background-color: #007BFF;
            border: none;
            border-radius: 5px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #0056b3;
        }
        #messageContainer {
            max-width: 420px;
            margin: 10px auto 0;
            padding: 12px 20px;
            border-radius: 5px;
            font-weight: 600;
            box-sizing: border-box;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1.5px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1.5px solid #f5c6cb;
        }
        #imagePreview {
            max-width: 420px;
            margin: 15px auto 30px;
            display: block;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        #imagePreview img {
            max-width: 100%;
            border-radius: 6px;
        }
    </style>
</head>
<body>

<h2>Upload Document</h2>

<?php if (isset($success)) echo "<div id='messageContainer' class='success'>$success</div>"; ?>
<?php if (isset($error)) echo "<div id='messageContainer' class='error'>$error</div>"; ?>

<form method="POST" enctype="multipart/form-data">
    <input type="text" name="name" placeholder="Document Name" required>
    <input type="url" name="link" placeholder="Document Link (e.g. https://...)" required>
    <input type="file" name="image" accept="image/*">
    <div id="imagePreview"></div>
    <button type="submit">Upload</button>
</form>

<script>
    document.querySelector('input[type="file"]').addEventListener('change', function(event) {
        const file = event.target.files[0];
        const preview = document.getElementById('imagePreview');
        preview.innerHTML = '';
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                preview.appendChild(img);
            };
            reader.readAsDataURL(file);
        }
    });
</script>

</body>
</html>
