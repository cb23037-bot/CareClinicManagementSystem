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

if ($role !== 'admin') {
    header("Location: system_login.html?error=Please+select+the+Admin+role+to+continue.");
    exit();
}

if (empty($username) || empty($password)) {
    header("Location: system_login.html?error=Please+fill+in+username+and+password.");
    exit();
}

$sql = "SELECT * FROM admins WHERE username = ? AND password = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    header("Location: system_login.html?error=Database+query+preparation+failed.");
    exit();
}

$stmt->bind_param("ss", $username, $password);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();

    $_SESSION['admin_id'] = $admin['admin_id'];
    $_SESSION['admin_name'] = $admin['admin_name'];
    $_SESSION['username'] = $admin['username'];
    $_SESSION['logged_in_role'] = 'admin';

    header("Location: Admin_Interfaces.php");
    exit();
}

header("Location: system_login.html?error=Invalid+admin+username+or+password.");
exit();
?>
