<?php
require_once('../includes/functions.php');
$page_title = "CareClinic - View Appointment";
include('../includes/header.php');

$appointment_id = $_GET['id'] ?? null;
if (!$appointment_id) {
    die("<div class='main-card'>Invalid request.</div>");
}

// IMPORTANT: Ensure this function exists in functions.php
$app = get_appointment_by_id($appointment_id);

if (!$app) {
    die("<div class='main-card'>Appointment not found.</div>");
}
?>

<main class="main-card view-details-card">
    <div class="back-nav">
        <a href="index.php"><i class="fa-solid fa-arrow-left"></i> My Appointment</a>
    </div>

    <div class="view-logo">
        <img src="../images/logo.jpeg" alt="Logo">
    </div>
    <h1 class="view-title">Appointment Details</h1>

    <div class="details-grid">
        <div class="detail-group">
            <label>Patient Name</label>
            <div class="read-only-box">Sarah</div>
        </div>

        <div class="detail-group">
            <label>Doctor</label>
            <div class="read-only-box"><?php echo htmlspecialchars($app['doctor_name']); ?></div>
        </div>

        <div class="detail-group">
            <label>Date</label>
            <div class="read-only-box"><?php echo $app['app_date'] ?? $app['date']; ?></div>
        </div>

        <div class="detail-group">
            <label>Time</label>
            <div class="read-only-box"><?php echo date("h:i A", strtotime($app['app_time'] ?? $app['time'])); ?></div>
        </div>
    </div>

    <div class="notes-section">
        <label>Notes</label>
        <div class="notes-textarea">
            <?php echo htmlspecialchars($app['notes'] ?? 'No notes available for this appointment.'); ?>
        </div>
    </div>

    <div style="text-align: center;">
        <button class="btn-done" onclick="window.location.href='index.php'">Done</button>
    </div>

</main>