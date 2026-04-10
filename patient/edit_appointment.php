<?php
require_once('../includes/functions.php');

$appointment_id = $_GET['id'] ?? null;

if (!$appointment_id) {
    die("Invalid request.");
}

$appointment = get_appointment_by_id($appointment_id);

if (!$appointment) {
    die("Appointment not found.");
}

// Ensure patients can only edit their own upcoming appointments
if ($appointment['status'] !== 'Booked' && $appointment['status'] !== 'Upcoming') {
    die("Cannot edit past appointments.");
}

$update_success = false;
$update_error = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_appointment'])) {
    $new_date = $_POST['date'];
    $new_time = $_POST['time'];

    // Validate that the new slot is available!

    if (update_appointment($appointment_id, $new_date, $new_time)) {
        $update_success = true;
        // Re-fetch record
        $appointment = get_appointment_by_id($appointment_id);
    } else {
        $update_error = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>CareClinic - Edit Appointment</title>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>
    <a href="index.php">&larr; Back to My Appointments</a>

    <main class="appointment-details-container">
        <h2>Edit Appointment Details</h2>

        <?php if ($update_success): ?>
            <div class="alert success">Appointment updated successfully!</div>
        <?php elseif ($update_error): ?>
            <div class="alert error">Error updating appointment.</div>
        <?php endif; ?>

        <form action="" method="post">
            <div class="details-grid">
                <div class="form-group">
                    <label>Doctor Name</label>
                    <input type="text" value="<?php echo $appointment['doctor_name']; ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Condition</label>
                    <input type="text" value="<?php echo $appointment['condition']; ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="date" value="<?php echo $appointment['date']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Time</label>
                    <input type="time" name="time" value="<?php echo date('H:i', strtotime($appointment['time'])); ?>" required>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" name="update_appointment" class="save-btn">Save Changes</button>
            </div>
        </form>

    </main>
</body>

</html>