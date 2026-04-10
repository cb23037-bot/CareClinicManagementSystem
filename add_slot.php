<?php
header('Content-Type: application/json');
include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (
    !isset($data['doctor']) ||
    !isset($data['role']) ||
    !isset($data['avatarClass']) ||
    !isset($data['avatarSymbol']) ||
    !isset($data['rawDate']) ||
    !isset($data['timeValue'])
) {
    echo json_encode(["success" => false, "message" => "Incomplete data"]);
    exit;
}

$doctor = trim($data['doctor']);
$role = trim($data['role']);
$avatarClass = trim($data['avatarClass']);
$avatarSymbol = trim($data['avatarSymbol']);
$rawDate = trim($data['rawDate']);
$timeValue = trim($data['timeValue']);
$notes = isset($data['notes']) ? trim($data['notes']) : '';
$status = "available";

$doctorStmt = $conn->prepare("SELECT doctor_id, doctor_name, doctor_role FROM doctors WHERE doctor_name = ? LIMIT 1");

if (!$doctorStmt) {
    echo json_encode(["success" => false, "message" => "Failed to prepare doctor lookup query"]);
    exit;
}

$doctorStmt->bind_param("s", $doctor);
$doctorStmt->execute();
$doctorResult = $doctorStmt->get_result();
$doctorRecord = $doctorResult->fetch_assoc();
$doctorStmt->close();

if (!$doctorRecord) {
    echo json_encode(["success" => false, "message" => "Selected doctor was not found in the database"]);
    exit;
}

$doctorId = (int)$doctorRecord['doctor_id'];
$doctorName = $doctorRecord['doctor_name'];
$doctorRole = $doctorRecord['doctor_role'];

$sql = "INSERT INTO doctor_slots
(doctor_id, doctor_name, doctor_role, avatar_class, avatar_symbol, slot_date, slot_time, notes, status)
VALUES
(?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Failed to prepare slot insert query"]);
    exit;
}

$stmt->bind_param(
    "issssssss",
    $doctorId,
    $doctorName,
    $doctorRole,
    $avatarClass,
    $avatarSymbol,
    $rawDate,
    $timeValue,
    $notes,
    $status
);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Slot added successfully"]);
} else {
    echo json_encode(["success" => false, "message" => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
