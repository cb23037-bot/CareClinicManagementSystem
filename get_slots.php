<?php
header('Content-Type: application/json');
include 'db.php';

$sql = "SELECT ds.*, p.patient_name, p.phone_number
        FROM doctor_slots ds
        LEFT JOIN patients p ON ds.patient_id = p.patient_id
        ORDER BY ds.id DESC";
$result = $conn->query($sql);

$slots = [];

while ($row = $result->fetch_assoc()) {
    $dateObj = date_create($row['slot_date']);
    $displayDate = $dateObj ? date_format($dateObj, 'M j, Y') : $row['slot_date'];
    $patientName = isset($row['patient_name']) && trim($row['patient_name']) !== ''
        ? $row['patient_name']
        : null;
    $hasPatient = $row['patient_id'] !== null && $patientName !== null;
    $displayStatus = $hasPatient ? 'occupied' : 'available';

    $slots[] = [
        "slotId" => (int)$row['id'],
        "doctorId" => isset($row['doctor_id']) ? (int)$row['doctor_id'] : null,
        "patientId" => $row['patient_id'] !== null ? (int)$row['patient_id'] : null,
        "patient" => $patientName,
        "patientPhoneNumber" => $row['phone_number'],
        "hasPatient" => $hasPatient,
        "doctor" => $row['doctor_name'],
        "role" => $row['doctor_role'],
        "avatarClass" => $row['avatar_class'],
        "avatarSymbol" => $row['avatar_symbol'],
        "time" => str_replace(":", " : ", $row['slot_time']),
        "timeValue" => $row['slot_time'],
        "displayDate" => $displayDate,
        "rawDate" => $row['slot_date'],
        "notes" => $row['notes'],
        "status" => $displayStatus
    ];
}

echo json_encode($slots);
$conn->close();
?>
