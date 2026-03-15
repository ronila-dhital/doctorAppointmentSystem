<?php
session_start();
include "db_connect.php";

if (!isset($_SESSION["doctor_id"])) {
    header("Location: doctorlogin.php");
    exit();
}

$doctor_id = $_SESSION["doctor_id"];

$stmt = $conn->prepare("SELECT status FROM doctor WHERE id=?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$doctor = $result->fetch_assoc();
$stmt->close();

$isApproved = $doctor && $doctor["status"] === "approved";

if (!$isApproved): ?>
<!DOCTYPE html>
<html>
<head>
    <title>Doctor Dashboard</title>
    <style>
        body{
            font-family:Arial;
            background:#f4f6f9;
            display:flex;
            justify-content:center;
            align-items:center;
            height:100vh;
        }
        .box{
            background:white;
            padding:30px;
            text-align:center;
            border-radius:10px;
            box-shadow:0 4px 10px rgba(0,0,0,0.1);
        }
        button{
            padding:8px 15px;
            background:#3498db;
            color:white;
            border:none;
            border-radius:5px;
            cursor:pointer;
        }
        button:hover{
            background:#1f6fa5;
        }
    </style>
</head>
<body>

<div class="box">
    <h2>⏳ Waiting for Admin Approval</h2>
    <p>Your account is not approved yet.</p>

    <form method="POST">
        <button type="submit">🔄 Recheck</button>
    </form>
</div>

</body>
</html>

<?php exit();endif;

// STOP PAGE HERE
?>


<!DOCTYPE html>
<html>
<head>
    <title>Doctor Dashboard</title>
    <style>
        body { font-family: Arial; background:#f4f6f9; padding:20px; }
        table { width:100%; border-collapse:collapse; background:white; margin-top:20px; }
        th, td { border:1px solid #ddd; padding:10px; text-align:center; }
        th { background:#3498db; color:white; }
        tr:nth-child(even){ background:#f9f9f9; }
        button { padding:5px 10px; margin:2px; }
        select, input { padding:5px; }
        .hidden { display:none; }
    </style>

    <script>
        function toggleReschedule(id){
            var row=document.getElementById("reschedule-"+id);
            row.classList.toggle("hidden");
        }
    </script>
</head>

<body>

<h2>Appointments</h2>

<table>
<tr>
    <th>ID</th>
    <th>Patient</th>
    <th>Email</th>
    <th>Date</th>
    <th>Time</th>
    <th>Description</th>
    <th>Status</th>
    <th>Actions</th>
</tr>

<?php
$stmt = $conn->prepare("
SELECT a.id, p.name, p.email, a.appointment_date,
       a.appointment_time, a.description, a.status
FROM appointment a
JOIN patient p ON a.patient_id=p.id
WHERE a.doctor_id=?
ORDER BY a.appointment_date ASC
");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

while ($row = $result->fetch_assoc()): ?>

<tr>
<form method="post">
    <td>
        <?= $row["id"] ?>
        <input type="hidden" name="id" value="<?= $row["id"] ?>">
    </td>

    <td><?= htmlspecialchars($row["name"]) ?></td>
    <td><?= htmlspecialchars($row["email"]) ?></td>
    <td><?= $row["appointment_date"] ?></td>
    <td><?= date("H:i", strtotime($row["appointment_time"])) ?></td>
    <td><?= htmlspecialchars($row["description"]) ?></td>

    <td>
        <select name="status">
            <option value="Pending" <?= $row["status"] == "Pending"
                ? "selected"
                : "" ?>>Pending</option>
            <option value="Approved" <?= $row["status"] == "Approved"
                ? "selected"
                : "" ?>>Approved</option>
            <option value="Completed" <?= $row["status"] == "Completed"
                ? "selected"
                : "" ?>>Completed</option>
        </select>
    </td>

    <td>
        <button type="submit" name="update">Update</button>
        <button type="button" onclick="toggleReschedule(<?= $row[
            "id"
        ] ?>)">Reschedule</button>
        <button type="submit" name="delete">Delete</button>
    </td>
</form>
</tr>

<!-- Reschedule Row -->
<tr id="reschedule-<?= $row["id"] ?>" class="hidden">
<form method="post">
<td colspan="8">
    <input type="hidden" name="id" value="<?= $row["id"] ?>">
    New Date:
    <input type="date" name="appointment_date" required>
    New Time:
    <input type="time" name="appointment_time" required>
    <button type="submit" name="reschedule">Confirm</button>
</td>
</form>
</tr>

<?php endwhile;
?>

</table>

</body>
</html>