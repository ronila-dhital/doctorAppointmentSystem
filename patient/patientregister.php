<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../db_connection.php");
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $status   = 'pending';

    // Validate name: only letters and at least two words
    if (!preg_match("/^[a-zA-Z]+(?:\s+[a-zA-Z]+)+$/", $name)) {
        $error = "Please enter a valid full name using only letters (e.g. First Last).";
    } else {
        // Hash the password securely
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert into patient table
        $stmt = $mysqli->prepare("INSERT INTO patient (name, email, password,status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $hashedPassword, $status);
        if ($stmt === false) {
            $error = "Prepare failed: " . $mysqli->error;
        } else {
            $stmt->bind_param("ssss", $name, $email, $hashedPassword, $status);

            if ($stmt->execute()) {
                $_SESSION['patient_id'] = $stmt->insert_id;
                header("Location: patientdashboard.php");
                exit();
            } else {
                $error = "Registration failed: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Patient Registration</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        background: #f4f4f4;
    }

    .form-box {
        max-width: 400px;
        margin: 60px auto;
        background: #fff;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    h2 {
        text-align: center;
        margin-bottom: 20px;
    }

    input[type=text],
    input[type=email],
    input[type=password] {
        width: 100%;
        padding: 10px;
        margin: 8px 0;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    input[type=submit] {
        background-color: #27ae60;
        color: white;
        padding: 10px;
        border: none;
        border-radius: 4px;
        width: 100%;
        font-weight: bold;
        cursor: pointer;
    }

    input[type=submit]:hover {
        background-color: #1e8449;
    }

    .error {
        color: red;
        text-align: center;
        margin-bottom: 10px;
    }

    .login-link {
        text-align: center;
        margin-top: 15px;
    }

    .login-link a {
        color: #3498db;
        text-decoration: none;
        font-weight: bold;
    }

    .login-link a:hover {
        text-decoration: underline;
    }
    </style>
</head>

<body>
    <div class="form-box">
        <h2>Patient Registration</h2>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="submit" value="Register">
        </form>
        <div class="login-link">
            <p>Already registered? <a href="patientlogin.php">Login here</a></p>
        </div>
    </div>
</body>

</html>