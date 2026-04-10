<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: system_login.html");
    exit();
}

$role = isset($_POST['role']) ? trim($_POST['role']) : '';
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

if ($role !== 'doctor') {
    header("Location: system_login.html?error=Only+Doctor+login+is+active+right+now.");
    exit();
}

if (empty($username) || empty($password)) {
    header("Location: system_login.html?error=Please+fill+in+username+and+password.");
    exit();
}

$sql = "SELECT * FROM doctors WHERE username = ? AND password = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    header("Location: system_login.html?error=Database+query+preparation+failed.");
    exit();
}

$stmt->bind_param("ss", $username, $password);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $doctor = $result->fetch_assoc();

    session_regenerate_id(true);
    $_SESSION['doctor_id'] = $doctor['doctor_id'];
    $_SESSION['doctor_name'] = $doctor['doctor_name'];
    $_SESSION['doctor_role'] = $doctor['doctor_role'];
    $_SESSION['username'] = $doctor['username'];
    $_SESSION['logged_in_role'] = 'doctor';

    header("Location: Doctor_Interfaces.html");
    exit();
} else {
    header("Location: system_login.html?error=Invalid+doctor+username+or+password.");
    exit();
}

$stmt->close();
$conn->close();
?>
