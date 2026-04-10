<?php
require_once('../includes/functions.php');

// Assuming you have a session-based patient_id
// session_start();
// $logged_in_patient = $_SESSION['patient_id']; 

$appointment_id = $_GET['id'] ?? null;

if (!$appointment_id) {
    header("Location: index.php?cancel=invalid");
    exit();
}

$appointment = get_appointment_by_id($appointment_id);

if (!$appointment) {
    header("Location: index.php?cancel=notfound");
    exit();
}

// Logic check: Only 'Booked' or 'Upcoming' can be deleted as per your Screenshot
if ($appointment['status'] === 'Done') {
    header("Location: index.php?cancel=denied");
    exit();
}

// Perform the deletion
if (delete_appointment($appointment_id)) {
    header("Location: index.php?cancel=success");
} else {
    header("Location: index.php?cancel=error");
}
exit();
