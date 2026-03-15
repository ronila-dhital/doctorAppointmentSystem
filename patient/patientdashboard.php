<?php
session_start();
include "../db_connection.php";

// Redirect if not logged in
if (!isset($_SESSION["patient_id"])) {
    header("Location: patientlogin.php");
    exit();
}

$patient_id = $_SESSION["patient_id"];

// Fetch patient info
$stmt = $mysqli->prepare("SELECT name, email, status FROM patient WHERE id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();
$stmt->close();

// ✅ Define patient_status
$patient_status = $patient["status"];

// Fetch doctors (make sure doctor table has a 'status' column)
$stmt = $mysqli->prepare("SELECT id, name, specialization, experience, profile_pic, available_days, available_from, available_to, status 
                          FROM doctor ORDER BY name ASC");
$stmt->execute();
$doctors = $stmt->get_result();
$stmt->close();

$section = isset($_GET["section"]) ? $_GET["section"] : "dashboard";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Patient Dashboard</title>
    <style>
        body {font-family: 'Segoe UI', Arial; background:#f4f6f9; margin:0;}
        header {background:#2c3e50; color:white; padding:15px 30px; display:flex; justify-content:space-between; align-items:center;}
        nav a {color:white; margin:0 12px; text-decoration:none; font-weight:500;}
        nav a:hover {text-decoration:underline;}
        .status-badge {padding:4px 8px; border-radius:5px; font-size:14px; margin-left:10px;}
        .approved {background:#27ae60; color:white;}
        .pending {background:gray; color:white;}
        .notapproved {background:#c0392b; color:white;}
        .container {padding:30px;}
        .card {background:white; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.1); padding:25px; max-width:900px; margin:20px auto;}
        .doctor-grid {display:flex; flex-wrap:wrap; gap:20px; justify-content:center;}
        .doctor-card {background:#fff; padding:15px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.1); width:250px; text-align:center;}
        .doctor-card img {border-radius:50%; width:100px; height:100px; margin-bottom:10px;}
        .doctor-card h4 {margin:0; color:#0b3d91;}
        .doctor-card a {display:inline-block; margin-top:10px; padding:8px 12px; background:#27ae60; color:white; border-radius:6px; font-weight:bold;}
        .doctor-card a:hover {background:#1e8449;}
        .btn-disabled {margin-top:10px; padding:8px 12px; background:gray; color:white; border-radius:5px; font-weight:bold;}
        table {width:100%; border-collapse:collapse; margin-top:15px;}
        th, td {border:1px solid #ddd; padding:10px; text-align:center;}
        th {background:#3498db; color:white;}
        tr:nth-child(even){background:#f9f9f9;}
    </style>
</head>
<body>
<header>
    <h2>
        Welcome, <?php echo htmlspecialchars($patient["name"]); ?> 👋
        <span class="status-badge <?php 
            echo $patient_status === 'approved' ? 'approved' : 
                 ($patient_status === 'pending' ? 'pending' : 'notapproved'); ?>">
            <?php echo ucfirst($patient_status); ?>
        </span>
    </h2>
    <nav>
        <a href="?section=dashboard">Dashboard</a>
        <a href="?section=appointment">Appointments</a>
        <a href="?section=history">History</a>
        <a href="?section=details">Personal Details</a>
        <a href="patientlogout.php">Logout</a>
    </nav>
</header>
<div class="container">
<?php if ($section == "dashboard"): ?>
    <div class="card">
        <h3>Dashboard</h3>
        <p>Browse available doctors below and book your appointment.</p>
        <div class="doctor-grid">
            <?php while ($row = $doctors->fetch_assoc()): ?>
            <div class="doctor-card">
                <img src="<?php echo $row["profile_pic"]; ?>" alt="Doctor Picture">
                <h4><?php echo $row["name"]; ?></h4>
                <p><strong>Specialization:</strong> <?php echo $row["specialization"]; ?></p>
                <p><strong>Experience:</strong> <?php echo $row["experience"]; ?> years</p>
                <p><strong>Available Days:</strong> <?php echo $row["available_days"]; ?></p>
                <p><strong>Available Time:</strong> <?php echo $row["available_from"]; ?> to <?php echo $row["available_to"]; ?></p>
                <p><strong>Status:</strong> 
                    <span class="status-badge <?php 
                        echo $row["status"] === 'approved' ? 'approved' : 
                             ($row["status"] === 'pending' ? 'pending' : 'notapproved'); ?>">
                        <?php echo ucfirst($row["status"]); ?>
                    </span>
                </p>

                <!-- ✅ Only approved patients AND approved doctors can book -->
                <?php if ($patient_status === "approved" && $row["status"] === "approved"): ?>
                    <a href="patientappointment.php?doctor_id=<?php echo $row["id"]; ?>">Book Appointment</a>
                <?php elseif ($row["status"] !== "approved"): ?>
                    <span class="btn-disabled">Doctor Not Approved</span>
                <?php else: ?>
                    <span class="btn-disabled">Pending Patient Approval</span>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

<?php elseif ($section == "appointment"): ?>
    <div class="card">
        <h3>Your Upcoming Appointments</h3>
        <table>
            <tr><th>Doctor</th><th>Date</th><th>Time</th><th>Description</th><th>Status</th></tr>
            <?php
            $stmt = $mysqli->prepare("SELECT d.name AS doctor_name, a.appointment_date, a.appointment_time, a.description, a.status
                                      FROM appointment a JOIN doctor d ON a.doctor_id = d.id
                                      WHERE a.patient_id = ? AND a.appointment_date >= CURDATE()
                                      ORDER BY a.appointment_date ASC, a.appointment_time ASC");
            $stmt->bind_param("i", $patient_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                echo "<tr><td>{$row["doctor_name"]}</td><td>{$row["appointment_date"]}</td><td>{$row["appointment_time"]}</td><td>{$row["description"]}</td><td>{$row["status"]}</td></tr>";
            }
            $stmt->close();
            ?>
        </table>
    </div>

<?php elseif ($section == "history"): ?>
    <div class="card">
        <h3>Your Appointment History</h3>
        <table>
            <tr><th>Doctor</th><th>Date</th><th>Time</th><th>Description</th><th>Status</th></tr>
            <?php
            $stmt = $mysqli->prepare("SELECT d.name AS doctor_name, a.appointment_date, a.appointment_time, a.description, a.status
                                      FROM appointment a JOIN doctor d ON a.doctor_id = d.id
                                      WHERE a.patient_id = ? AND a.appointment_date < CURDATE()
                                      ORDER BY a.appointment_date DESC, a.appointment_time DESC");
            $stmt->bind_param("i", $patient_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                echo "<tr><td>{$row["doctor_name"]}</td><td>{$row["appointment_date"]}</td><td>{$row["appointment_time"]}</td><td>{$row["description"]}</td><td>{$row["status"]}</td></tr>";
            }
            $stmt->close();
            ?>
        </table>
    </div>

<?php elseif ($section == "details"): ?>
    <div class="card">
        <h3>Your Personal Details</h3>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($patient["name"]); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($patient["email"]); ?></p>
        <p><strong>Status:</strong> 
            <span class="status-badge <?php 
                echo $patient_status === 'approved' ? 'approved' : 
                     ($patient_status === 'pending' ? 'pending' : 'notapproved'); ?>">
                                <?php echo ucfirst($patient_status); ?>
            </span>
        </p>
    </div>
<?php endif; ?>
</div>
</body>
</html>