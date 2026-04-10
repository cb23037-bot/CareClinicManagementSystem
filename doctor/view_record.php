<?php
require_once('../includes/functions.php');

$patient_id = $_GET['id'] ?? null;
if (!$patient_id) {
    die("Invalid request.");
}

// Handle the update BEFORE fetching the record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_notes'])) {
    $new_notes = $_POST['notes'];

    // update_patient_notes needs to be defined in your functions.php
    if (update_patient_notes($patient_id, $new_notes)) {
        // Redirect to self with a success flag to prevent resubmission
        header("Location: view_record.php?id=$patient_id&update=success");
        exit();
    }
}

// Fetch the record (will show updated notes if redirect happened)
$patient_record = get_patient_record_by_id($patient_id);
if (!$patient_record) {
    die("Patient record not found.");
}

$page_title = "Appointment Details";
include('../includes/header.php');
?>

<main class="main-card view-details-card">
    <div class="back-nav">
        <a href="index.php"><i class="fa fa-arrow-left"></i> My Appointment</a>
    </div>

    <div class="view-logo">
        <img src="../images/logo.jpeg" alt="CareClinic">
    </div>

    <h2 class="view-title">Appointment Details</h2>

    <?php if (isset($_GET['update']) && $_GET['update'] == 'success'): ?>
        <div class="alert-box success">
            <i class="fa-solid fa-circle-check"></i>
            <span>Notes updated successfully!</span>
        </div>
    <?php endif; ?>

    <form action="" method="post">
        <div class="details-grid">
            <div class="detail-group">
                <label>Patient Name</label>
                <div class="read-only-box"><?php echo $patient_record['name']; ?></div>
            </div>

            <div class="detail-group">
                <label>Doctor</label>
                <div class="read-only-box">Dr. Ahmed</div>
            </div>

            <div class="detail-group">
                <label>Appointment Date</label>
                <div class="read-only-box"><?php echo $patient_record['date']; ?></div>
            </div>

            <div class="detail-group">
                <label>Appointment Time</label>
                <div class="read-only-box"><?php echo $patient_record['time']; ?></div>
            </div>
        </div>

        <div class="notes-section">
            <label>Notes</label>
            <textarea name="notes" class="notes-textarea" placeholder="Enter patient clinical notes here..."><?php echo $patient_record['notes']; ?></textarea>
        </div>

        <div class="view-actions">
            <button type="submit" name="save_notes" class="btn-done">Done</button>
        </div>
    </form>
</main>