<?php
header('Content-Type: application/json');
include 'db.php';

$sql = "SELECT * FROM doctor_slots ORDER BY id DESC";
$result = $conn->query($sql);

$slots = [];

while ($row = $result->fetch_assoc()) {
    $dateObj = date_create($row['slot_date']);
    $displayDate = $dateObj ? date_format($dateObj, 'M j, Y') : $row['slot_date'];

    $slots[] = [
        "slotId" => (int)$row['id'],
        "doctor" => $row['doctor_name'],
        "role" => $row['doctor_role'],
        "avatarClass" => $row['avatar_class'],
        "avatarSymbol" => $row['avatar_symbol'],
        "time" => str_replace(":", " : ", $row['slot_time']),
        "timeValue" => $row['slot_time'],
        "displayDate" => $displayDate,
        "rawDate" => $row['slot_date'],
        "notes" => $row['notes'],
        "status" => $row['status']
    ];
}

echo json_encode($slots);
$conn->close();
?>