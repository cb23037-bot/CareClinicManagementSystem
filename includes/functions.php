<?php
// Database connection parameters - Make sure these match your config.php
function get_db_connection()
{
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "careclinic_db";

    $conn = mysqli_connect($host, $user, $pass, $db);

    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    return $conn;
}

// --- DOCTOR FUNCTIONS ---

// Get all patient records for the doctor's dashboard
function get_all_patient_records($doctor_id)
{
    $conn = get_db_connection();

    // SQL joins the patients and appointments tables
    $sql = "SELECT p.name, p.patient_id_code AS patient_id, p.phone, p.email, 
                   a.app_condition AS 'condition', a.app_date AS 'date', 
                   a.app_time AS 'time', a.status 
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            WHERE a.doctor_id = ?
            ORDER BY a.app_date DESC";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $doctor_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_close($conn);
    return $data;
}

// Get a SINGLE record for view_record.php
function get_patient_record_by_id($patient_id_code)
{
    $conn = get_db_connection();

    $sql = "SELECT p.name, p.patient_id_code AS patient_id, a.app_date AS 'date', 
                   a.app_time AS 'time', a.notes, a.app_condition AS 'condition'
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            WHERE p.patient_id_code = ? 
            LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $patient_id_code);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $data = mysqli_fetch_assoc($result);
    mysqli_close($conn);
    return $data;
}

// Update ONLY the notes in the database
function update_patient_notes($patient_id_code, $notes)
{
    $conn = get_db_connection();

    // We find the internal patient ID based on the PT001 code
    $sql = "UPDATE appointments a 
            JOIN patients p ON a.patient_id = p.id 
            SET a.notes = ? 
            WHERE p.patient_id_code = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $notes, $patient_id_code);
    $success = mysqli_stmt_execute($stmt);

    mysqli_close($conn);
    return $success;
}

// Delete a record
function delete_patient_record($patient_id_code)
{
    $conn = get_db_connection();

    // Find the patient by code and delete their appointments
    $sql = "DELETE a FROM appointments a 
            JOIN patients p ON a.patient_id = p.id 
            WHERE p.patient_id_code = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $patient_id_code);
    $success = mysqli_stmt_execute($stmt);

    mysqli_close($conn);
    return $success;
}

// --- PATIENT FUNCTIONS ---

// Get all appointments for a specific patient (for patient/index.php)
function get_patient_appointments($patient_id_code)
{
    $conn = get_db_connection();

    $sql = "SELECT a.id AS appointment_id, d.name AS doctor_name, 
                   a.app_condition AS 'condition', a.app_date AS 'date', 
                   a.app_time AS 'time', a.status, a.notes 
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN doctors d ON a.doctor_id = d.id
            WHERE p.patient_id_code = ?
            ORDER BY a.app_date ASC";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $patient_id_code);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_close($conn);
    return $data;
}

// Create a NEW appointment
function create_appointment($patient_id_code, $doctor_id, $service_name, $date, $time)
{
    $conn = get_db_connection();

    // 1. Get the internal numeric ID for the patient code (PT001)
    $p_sql = "SELECT id FROM patients WHERE patient_id_code = ?";
    $p_stmt = mysqli_prepare($conn, $p_sql);
    mysqli_stmt_bind_param($p_stmt, "s", $patient_id_code);
    mysqli_stmt_execute($p_stmt);
    $p_res = mysqli_stmt_get_result($p_stmt);
    $patient = mysqli_fetch_assoc($p_res);

    if (!$patient) return false;
    $p_id = $patient['id'];

    // 2. Insert the appointment 
    // UPDATED: Changed 'service_type' to 'app_condition' to match your DB
    $sql = "INSERT INTO appointments (patient_id, doctor_id, app_condition, app_date, app_time, status) 
            VALUES (?, ?, ?, ?, ?, 'Booked')";

    $stmt = mysqli_prepare($conn, $sql);

    // Bind parameters: i = integer, s = string
    // Order: p_id (i), doctor_id (i), service_name (s), date (s), time (s)
    mysqli_stmt_bind_param($stmt, "iisss", $p_id, $doctor_id, $service_name, $date, $time);

    $success = mysqli_stmt_execute($stmt);
    $new_id = mysqli_insert_id($conn);

    mysqli_close($conn);
    return $success ? $new_id : false;
}

function get_appointment_by_id($id)
{
    $conn = get_db_connection();

    // Use a JOIN to get the doctor's name from the doctors table
    $sql = "SELECT a.*, d.name AS doctor_name 
            FROM appointments a
            JOIN doctors d ON a.doctor_id = d.id
            WHERE a.id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $data = mysqli_fetch_assoc($result);
    mysqli_close($conn);
    return $data;
}

function delete_appointment($id)
{
    global $conn;
    $conn = get_db_connection();

    $sql = "DELETE FROM appointments WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    return false;
}
