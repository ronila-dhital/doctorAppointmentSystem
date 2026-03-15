<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

include "../db_connection.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $specialization = trim($_POST["specialization"]);
    $otherSpec = trim($_POST["other_specialization"]);
    $experience = intval($_POST["experience"]);
    $available_from = $_POST["available_from"];
    $available_to = $_POST["available_to"];
    $status = "pending";

    $days = "";
    if (isset($_POST["available_days"])) {
        $days = implode(",", $_POST["available_days"]);
    }

    if (!empty($otherSpec)) {
        $specialization = $otherSpec;
    }

    if (!preg_match("/^[a-zA-Z]+(?:\s+[a-zA-Z]+)+$/", $name)) {
        $error = "Please enter a valid full name (First Last).";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // 📸 Upload Image
        $targetDir = "../uploads/doctors/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = basename($_FILES["profile_pic"]["name"]);
        $targetFilePath = $targetDir . time() . "_" . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        $allowedTypes = ["jpg", "jpeg", "png", "gif"];

        if (in_array($fileType, $allowedTypes)) {
            if (
                move_uploaded_file(
                    $_FILES["profile_pic"]["tmp_name"],
                    $targetFilePath,
                )
            ) {
                // ✅ INSERT WITH STATUS
                $stmt = $mysqli->prepare("INSERT INTO doctor
                (name, email, password, specialization, profile_pic, experience, available_from, available_to, available_days, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $stmt->bind_param(
                    "ssssssssss",
                    $name,
                    $email,
                    $hashedPassword,
                    $specialization,
                    $targetFilePath,
                    $experience,
                    $available_from,
                    $available_to,
                    $days,
                    $status,
                );

                if ($stmt->execute()) {
                    $_SESSION["doctor_id"] = $stmt->insert_id;
                    header("Location: doctordashboard.php");
                    exit();
                } else {
                    $error = "Registration failed: " . $stmt->error;
                }

                $stmt->close();
            } else {
                $error = "❌ Image upload failed.";
            }
        } else {
            $error = "❌ Only JPG, JPEG, PNG, GIF allowed.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Doctor Registration</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f4f4; }
        .form-box { max-width: 420px; margin: 60px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h2 { text-align: center; margin-bottom: 25px; color: #0b3d91; } /* Dark Blue Heading */
        label { display:block; margin:12px 0 6px; font-weight:bold; color:#2c3e50; }
        input[type=text], input[type=email], input[type=password], select, input[type=file], input[type=number] {
            width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 6px; font-size:14px;
        }
        input[type=file] {
            background:#f9f9f9; cursor:pointer;
        }
        input[type=submit] {
            background-color: #27ae60; color: white; padding: 12px; border: none; border-radius: 6px; width: 100%;
            font-weight: bold; cursor: pointer; font-size:15px;
        }
        input[type=submit]:hover { background-color: #1e8449; }
        .error { color: red; text-align: center; margin-bottom: 15px; }
        .login-link { text-align: center; margin-top: 20px; }
        .login-link a { color: #3498db; text-decoration: none; font-weight: bold; }
        .login-link a:hover { text-decoration: underline; }

          .days {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 6px;
        margin-bottom: 10px;
    }

    .days input[type="checkbox"] {
        display: none;
    }

    .days label {
        font-weight: normal;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border: 1px solid #ccc;
        border-radius: 50%;
        cursor: pointer;
        font-size: 12px;
        margin: 0;
        background: #f9f9f9;
        color: #444;
        transition: background 0.2s, color 0.2s, border-color 0.2s;
    }

    .days input[type="checkbox"]:checked+label {
        background: #27ae60;
        color: white;
        border-color: #27ae60;
    }

    .days label:hover {
        border-color: #27ae60;
        color: #27ae60;
    }
    </style>
</head>
<body>
    <div class="form-box">
        <h2>Doctor Registration</h2>
        <?php if (isset($error)) {
            echo "<p class='error'>$error</p>";
        } ?>
        <form method="POST" enctype="multipart/form-data">
            <label>Full Name</label>
            <input type="text" name="name" placeholder="Full Name" required>

            <label>Email Address</label>
            <input type="email" name="email" placeholder="Email Address" required>

            <label>Password</label>
<div style="position:relative;">
    <input type="password" id="password" name="password" placeholder="Password" required>
    <span onclick="togglePassword()"
          style="position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer; color:#0b3d91; font-weight:bold;">
        👁
    </span>
</div>

<script>
function togglePassword() {
    const passwordField = document.getElementById("password");
    if (passwordField.type === "password") {
        passwordField.type = "text";
    } else {
        passwordField.type = "password";
    }
}
</script>
            <label>Specialization</label>
            <select name="specialization">
                <option value="">-- Select Specialization --</option>
                <option value="Cardiologist">Cardiologist</option>
                <option value="Dermatologist">Dermatologist</option>
                <option value="Pediatrician">Pediatrician</option>
                <option value="Neurologist">Neurologist</option>
                <option value="Orthopedic">Orthopedic</option>
                <option value="General Physician">General Physician</option>
            </select>

            <input type="text" name="other_specialization" placeholder="Enter specialization if not listed">

            <label>Years of Experience</label>
            <input type="number" name="experience" placeholder="Years of Experience" min="0" required>

            <label>Add Profile Picture</label>
            <input type="file" name="profile_pic" accept="image/*" required>

             <label>Available From</label>
                <input type="time" name="available_from">

                <label>Available To</label>
                <input type="time" name="available_to">

                <label>Available Days</label>

                <div class="days">
                    <input type="checkbox" name="available_days[]" value="Sunday" id="day_sun">
                    <label for="day_sun">Sun</label>

                    <input type="checkbox" name="available_days[]" value="Monday" id="day_mon">
                    <label for="day_mon">Mon</label>

                    <input type="checkbox" name="available_days[]" value="Tuesday" id="day_tue">
                    <label for="day_tue">Tue</label>

                    <input type="checkbox" name="available_days[]" value="Wednesday" id="day_wed">
                    <label for="day_wed">Wed</label>
                    <input type="checkbox" name="available_days[]" value="Thursday" id="day_thu">
                    <label for="day_thu">Thu</label>

                    <input type="checkbox" name="available_days[]" value="Friday" id="day_fri">
                    <label for="day_fri">Fri</label>

                    <input type="checkbox" name="available_days[]" value="Saturday" id="day_sat">
                    <label for="day_sat">Sat</label>
                </div>

            <input type="submit" value="Register">
        </form>
        <div class="login-link">
            <p>Already registered? <a href="doctorlogin.php">Login here</a></p>
        </div>
    </div>
</body>
</html>