<?php
require_once('../includes/functions.php');

$patient_id = $_GET['id'] ?? null;

if (!$patient_id) {
    header("Location: index.php?delete=error");
    exit();
}

// Attempt to delete
if (delete_patient_record($patient_id)) {
    // success flag
    header("Location: index.php?delete=success");
} else {
    // error flag
    header("Location: index.php?delete=error");
}
exit();
