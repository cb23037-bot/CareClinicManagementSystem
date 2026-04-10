<?php
header('Content-Type: application/json');
include 'db.php';

$sql = "SELECT doctor_id, doctor_name, doctor_role FROM doctors ORDER BY doctor_name ASC";
$result = $conn->query($sql);

$doctors = [];
while ($row = $result->fetch_assoc()) {
    $doctors[] = [
        "doctorId" => (int)$row['doctor_id'],
        "doctorName" => $row['doctor_name'],
        "doctorRole" => $row['doctor_role']
    ];
}

echo json_encode($doctors);
$conn->close();
?>