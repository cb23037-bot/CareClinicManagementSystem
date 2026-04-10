<?php
session_start();
header('Content-Type: application/json');
include 'db.php';

if (!isset($_SESSION['doctor_id'])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Doctor session not found. Please log in first."
    ]);
    exit;
}

$doctorId = (int)$_SESSION['doctor_id'];
$doctorName = isset($_SESSION['doctor_name']) ? $_SESSION['doctor_name'] : '';

$sql = "SELECT ds.id, ds.doctor_id, ds.patient_id, ds.doctor_name, ds.doctor_role, ds.avatar_class, ds.avatar_symbol,
               ds.slot_date, ds.slot_time, ds.notes, ds.status, p.patient_name, p.phone_number
        FROM doctor_slots ds
        LEFT JOIN patients p ON ds.patient_id = p.patient_id
        WHERE ds.doctor_id = ?
        ORDER BY ds.slot_date ASC, ds.slot_time ASC, ds.id DESC";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to prepare doctor slots query."
    ]);
    exit;
}

$stmt->bind_param("i", $doctorId);
$stmt->execute();
$result = $stmt->get_result();

$slots = [];

while ($row = $result->fetch_assoc()) {
    $dateObj = date_create($row['slot_date']);
    $displayDate = $dateObj ? date_format($dateObj, 'M j, Y') : $row['slot_date'];
    $patientName = isset($row['patient_name']) && trim($row['patient_name']) !== ''
        ? $row['patient_name']
        : 'Available';
    $hasPatient = !empty($row['patient_id']) && $patientName !== 'Available';
    $displayStatus = $hasPatient ? 'occupied' : 'available';

    $slots[] = [
        "slotId" => (int)$row['id'],
        "doctorId" => (int)$row['doctor_id'],
        "doctor" => $row['doctor_name'],
        "patientId" => $row['patient_id'] !== null ? (int)$row['patient_id'] : null,
        "patient" => $patientName,
        "patientPhoneNumber" => $row['phone_number'],
        "hasPatient" => $hasPatient,
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

echo json_encode([
    "success" => true,
    "doctor" => $doctorName,
    "slots" => $slots
]);

$stmt->close();
$conn->close();
?>
