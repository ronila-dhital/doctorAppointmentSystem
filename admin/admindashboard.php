<?php
include "../db_connection.php";
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: adminlogin.php");
    exit();
}

$message = "";

if (isset($_POST["approve_doctor"])) {
    $stmt = $mysqli->prepare("UPDATE doctor SET status='approved' WHERE id=?");
    $stmt->bind_param("i", $_POST["id"]);
    $stmt->execute();
    $stmt->close();
    $message = "Doctor Approved!";
}

if (isset($_POST["reject_doctor"])) {
    $stmt = $mysqli->prepare("DELETE FROM doctor WHERE id=?");
    $stmt->bind_param("i", $_POST["id"]);
    $stmt->execute();
    $stmt->close();
    $message = "Doctor Rejected & Deleted!";
}

if (isset($_POST["approve_patient"])) {
    $stmt = $mysqli->prepare("UPDATE patient SET status='approved' WHERE id=?");
    $stmt->bind_param("i", $_POST["id"]);
    $stmt->execute();
    $stmt->close();
    $message = "Patient Approved!";
}

if (isset($_POST["reject_patient"])) {
    $stmt = $mysqli->prepare("DELETE FROM patient WHERE id=?");
    $stmt->bind_param("i", $_POST["id"]);
    $stmt->execute();
    $stmt->close();
    $message = "Patient Rejected & Deleted!";
}

/* Doctor CRUD */
if (isset($_POST["delete_doctor"])) {
    $stmt = $mysqli->prepare("DELETE FROM doctor WHERE id=?");
    $stmt->bind_param("i", $_POST["id"]);
    $stmt->execute();
    $stmt->close();
    $message = "✅ Doctor deleted!";
}

/* Patient CRUD */
if (isset($_POST["delete_patient"])) {
    $stmt = $mysqli->prepare("DELETE FROM patient WHERE id=?");
    $stmt->bind_param("i", $_POST["id"]);
    $stmt->execute();
    $stmt->close();
    $message = "✅ Patient deleted!";
}

/* Appointment CRUD */
if (isset($_POST["delete_appointment"])) {
    $stmt = $mysqli->prepare("DELETE FROM appointment WHERE id=?");
    $stmt->bind_param("i", $_POST["id"]);
    $stmt->execute();
    $stmt->close();
    $message = "✅ Appointment deleted!";
}

/* Counts */
$doctorCount = $mysqli
    ->query("SELECT COUNT(*) AS c FROM doctor")
    ->fetch_assoc()["c"];
$patientCount = $mysqli
    ->query("SELECT COUNT(*) AS c FROM patient")
    ->fetch_assoc()["c"];
$appointmentCount = $mysqli
    ->query("SELECT COUNT(*) AS c FROM appointment")
    ->fetch_assoc()["c"];
$pendingDoctorCount = $mysqli
    ->query("SELECT COUNT(*) AS c FROM doctor WHERE status='pending'")
    ->fetch_assoc()["c"];
$pendingPatientCount = $mysqli
    ->query("SELECT COUNT(*) AS c FROM patient WHERE status='pending'")
    ->fetch_assoc()["c"];

$pendingCount = $pendingDoctorCount + $pendingPatientCount;

/* Data */
$doctors = $mysqli->query("SELECT * FROM doctor ORDER BY name ASC");
$patients = $mysqli->query("SELECT * FROM patient ORDER BY name ASC");
$appointments = $mysqli->query("SELECT a.id, p.name AS patient_name, d.name AS doctor_name, a.appointment_date, a.appointment_time, a.description, a.status
                                FROM appointment a
                                JOIN patient p ON a.patient_id = p.id
                                JOIN doctor d ON a.doctor_id = d.id
                                ORDER BY a.appointment_date DESC, a.appointment_time DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        body { font-family: 'Segoe UI', Arial; background:#f4f4f9; margin:0; }
        header { background:linear-gradient(90deg,#ff6a00,#ee0979); color:white; padding:15px 30px; display:flex; justify-content:space-between; align-items:center; }
        .logout { background:#c0392b; color:white; padding:8px 12px; border-radius:6px; text-decoration:none; }
        .container { padding:30px; }
        .summary { display:flex; gap:20px; margin-bottom:30px; }
        .card { flex:1; background:white; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.1); padding:20px; text-align:center; }
        h3 { color:#0b3d91; margin-top:0; }
        table { width:100%; border-collapse:collapse; margin-top:15px; }
        th, td { border:1px solid #ddd; padding:10px; text-align:center; }
        th { background:#3498db; color:white; }
        tr:nth-child(even) { background:#f9f9f9; }
        button { background:#27ae60; color:white; border:none; padding:6px 10px; cursor:pointer; border-radius:6px; }
        button:hover { background:#1e8449; }
        .message { margin:10px 0; padding:10px; border-radius:6px; font-weight:bold; background:#d4edda; color:#155724; }
    </style>
</head>
<body>
    <header>
        <h2>Welcome, <?php echo $_SESSION["admin_name"]; ?> 👑</h2>
        <a href="adminlogout.php" class="logout">Logout</a>
    </header>

    <div class="container">
        <?php if (
            !empty($message)
        ): ?><div class="message"><?php echo $message; ?></div><?php endif; ?>

        <!-- Summary Cards -->
        <div class="summary">
            <div class="card"><h3>Doctors</h3><p><?php echo $doctorCount; ?></p></div>
            <div class="card"><h3>Patients</h3><p><?php echo $patientCount; ?></p></div>
            <div class="card"><h3>Appointments</h3><p><?php echo $appointmentCount; ?></p></div>
            <div class="card"><h3>Pending</h3><p><?php echo $pendingCount; ?></p></div>

        </div>

        <!-- Doctor Management (View + Delete only) -->
        <div class="card">
            <h3>Doctors</h3>
            <table>
                <tr><th>ID</th><th>Name</th><th>Specialization</th><th>Experience</th><th>Profile Pic</th><th>Actions</th></tr>
                <?php while ($row = $doctors->fetch_assoc()): ?>
                <tr>
                    <td><?= $row["id"] ?></td>
                    <td><?= $row["name"] ?></td>
                    <td><?= $row["specialization"] ?></td>
                    <td><?= $row["experience"] ?> years</td>
                    <td><img src="<?= $row["profile_pic"] ?>" width="50"></td>
                    <td>
                        <?php if ($row["status"] == "pending"): ?>

                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $row[
                                    "id"
                                ] ?>">
                                <button type="submit" name="approve_doctor">Approve</button>
                            </form>

                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $row[
                                    "id"
                                ] ?>">
                                <button type="submit" name="reject_doctor" style="background:red;">Reject</button>
                            </form>

                        <?php else: ?>

                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $row[
                                    "id"
                                ] ?>">
                                <button type="submit" name="delete_doctor">Delete</button>
                            </form>

                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>

        <!-- Patient Management -->
        <div class="card">
            <h3>Patients</h3>
            <table>
                <tr><th>ID</th><th>Name</th><th>Email</th><th>Actions</th></tr>
                <?php while ($row = $patients->fetch_assoc()): ?>
                <tr>
                    <td><?= $row["id"] ?></td>
                    <td><?= $row["name"] ?></td>
                    <td><?= $row["email"] ?></td>
                    <td>
                        <?php if ($row["status"] == "pending"): ?>

                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $row[
                                    "id"
                                ] ?>">
                                <button type="submit" name="approve_patient">Approve</button>
                            </form>

                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $row[
                                    "id"
                                ] ?>">
                                <button type="submit" name="reject_patient" style="background:red;">Reject</button>
                            </form>

                        <?php else: ?>

                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $row[
                                    "id"
                                ] ?>">
                                <button type="submit" name="delete_patient">Delete</button>
                            </form>

                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>

        <!-- Appointment Management -->
        <div class="card">
            <h3>Appointments</h3>
            <table>
                <tr><th>ID</th><th>Patient</th><th>Doctor</th><th>Date</th><th>Time</th><th>Description</th><th>Status</th><th>Actions</th></tr>
                <?php while ($row = $appointments->fetch_assoc()): ?>
                <tr>
                    <td><?= $row["id"] ?></td>
                    <td><?= $row["patient_name"] ?></td>
                    <td><?= $row["doctor_name"] ?></td>
                    <td><?= $row["appointment_date"] ?></td>
                    <td><?= $row["appointment_time"] ?></td>
                    <td><?= $row["description"] ?></td>
                    <td><?= $row["status"] ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $row[
                                "id"
                            ] ?>">
                            <button type="submit" name="delete_appointment">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
</body>
</html>