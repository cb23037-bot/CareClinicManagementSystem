<?php
header('Content-Type: application/json');
include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['slotId'])) {
    echo json_encode(["success" => false, "message" => "Slot ID missing"]);
    exit;
}

$id = (int)$data['slotId'];

$sql = "DELETE FROM doctor_slots WHERE id = $id";

if ($conn->query($sql) === TRUE) {
    echo json_encode(["success" => true, "message" => "Slot deleted successfully"]);
} else {
    echo json_encode(["success" => false, "message" => $conn->error]);
}

$conn->close();
?>