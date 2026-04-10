<?php
header('Content-Type: text/plain; charset=utf-8');

$host = "localhost";
$username = "root";
$password = "";
$database = "careclinic_db";

$conn = new mysqli($host, $username, $password);

if ($conn->connect_error) {
    http_response_code(500);
    exit("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$queries = [
    "CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci",
    "USE `$database`",
    "CREATE TABLE IF NOT EXISTS doctors (
        doctor_id INT(11) NOT NULL AUTO_INCREMENT,
        doctor_name VARCHAR(100) NOT NULL,
        doctor_role VARCHAR(100) NOT NULL,
        username VARCHAR(50) NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (doctor_id),
        UNIQUE KEY username (username)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
    "CREATE TABLE IF NOT EXISTS admins (
        admin_id INT(11) NOT NULL AUTO_INCREMENT,
        admin_name VARCHAR(100) NOT NULL,
        username VARCHAR(50) NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (admin_id),
        UNIQUE KEY username (username)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
    "CREATE TABLE IF NOT EXISTS patients (
        patient_id INT(11) NOT NULL AUTO_INCREMENT,
        patient_name VARCHAR(100) NOT NULL,
        phone_number VARCHAR(30) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (patient_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
    "CREATE TABLE IF NOT EXISTS doctor_slots (
        id INT(11) NOT NULL AUTO_INCREMENT,
        doctor_id INT(11) NOT NULL,
        patient_id INT(11) DEFAULT NULL,
        doctor_name VARCHAR(100) NOT NULL,
        doctor_role VARCHAR(100) NOT NULL,
        avatar_class VARCHAR(50) NOT NULL,
        avatar_symbol VARCHAR(20) NOT NULL,
        slot_date DATE NOT NULL,
        slot_time VARCHAR(20) NOT NULL,
        notes TEXT DEFAULT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'available',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY fk_doctor_slots_doctor (doctor_id),
        KEY fk_doctor_slots_patient (patient_id),
        CONSTRAINT fk_doctor_slots_doctor FOREIGN KEY (doctor_id)
            REFERENCES doctors (doctor_id)
            ON DELETE CASCADE
            ON UPDATE CASCADE,
        CONSTRAINT fk_doctor_slots_patient FOREIGN KEY (patient_id)
            REFERENCES patients (patient_id)
            ON DELETE SET NULL
            ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
];

foreach ($queries as $query) {
    if (!$conn->query($query)) {
        http_response_code(500);
        exit("Setup failed: " . $conn->error);
    }
}

function columnExists(mysqli $conn, string $database, string $table, string $column): bool
{
    $stmt = $conn->prepare("
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = ?
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
        LIMIT 1
    ");

    if (!$stmt) {
        http_response_code(500);
        exit("Failed to prepare column check: " . $conn->error);
    }

    $stmt->bind_param("sss", $database, $table, $column);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();

    return $exists;
}

function indexExists(mysqli $conn, string $database, string $table, string $index): bool
{
    $stmt = $conn->prepare("
        SELECT 1
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = ?
          AND TABLE_NAME = ?
          AND INDEX_NAME = ?
        LIMIT 1
    ");

    if (!$stmt) {
        http_response_code(500);
        exit("Failed to prepare index check: " . $conn->error);
    }

    $stmt->bind_param("sss", $database, $table, $index);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();

    return $exists;
}

function foreignKeyExists(mysqli $conn, string $database, string $table, string $constraint): bool
{
    $stmt = $conn->prepare("
        SELECT 1
        FROM information_schema.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = ?
          AND TABLE_NAME = ?
          AND CONSTRAINT_NAME = ?
          AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        LIMIT 1
    ");

    if (!$stmt) {
        http_response_code(500);
        exit("Failed to prepare foreign key check: " . $conn->error);
    }

    $stmt->bind_param("sss", $database, $table, $constraint);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();

    return $exists;
}

if (!columnExists($conn, $database, 'doctor_slots', 'patient_id')) {
    if (!$conn->query("ALTER TABLE doctor_slots ADD COLUMN patient_id INT(11) DEFAULT NULL AFTER doctor_id")) {
        http_response_code(500);
        exit("Migration failed while adding patient_id: " . $conn->error);
    }
}

if (!indexExists($conn, $database, 'doctor_slots', 'fk_doctor_slots_patient')) {
    if (!$conn->query("ALTER TABLE doctor_slots ADD INDEX fk_doctor_slots_patient (patient_id)")) {
        http_response_code(500);
        exit("Migration failed while adding patient index: " . $conn->error);
    }
}

if (!foreignKeyExists($conn, $database, 'doctor_slots', 'fk_doctor_slots_patient')) {
    if (!$conn->query("
        ALTER TABLE doctor_slots
        ADD CONSTRAINT fk_doctor_slots_patient
        FOREIGN KEY (patient_id)
        REFERENCES patients (patient_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
    ")) {
        http_response_code(500);
        exit("Migration failed while adding patient foreign key: " . $conn->error);
    }
}

$seedDoctors = [
    ['Dr. Sarah', 'Family Medicine Doctor', 'drsarah', '123456'],
    ['Dr. Ammar', 'General Practitioner (GP)', 'drammar', '123456'],
    ['Dr. Ahmed', 'General Practitioner (GP)', 'drahmed', '123456'],
    ['Dr. Fatima', 'Gynecologist', 'drfatima', '123456'],
    ['Dr. Aiman', 'Pediatrician', 'draiman', '123456'],
    ['Dr. Hasan', 'General Practitioner (GP)', 'drhasan', '123456']
];

$seedAdmins = [
    ['System Admin', 'admin', 'admin123']
];

$seedStmt = $conn->prepare("
    INSERT INTO doctors (doctor_name, doctor_role, username, password)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        doctor_name = VALUES(doctor_name),
        doctor_role = VALUES(doctor_role),
        password = VALUES(password)
");

if (!$seedStmt) {
    http_response_code(500);
    exit("Failed to prepare seed statement: " . $conn->error);
}

foreach ($seedDoctors as $doctor) {
    $seedStmt->bind_param("ssss", $doctor[0], $doctor[1], $doctor[2], $doctor[3]);
    if (!$seedStmt->execute()) {
        http_response_code(500);
        exit("Failed to seed doctor data: " . $seedStmt->error);
    }
}

$seedStmt->close();

$adminSeedStmt = $conn->prepare("
    INSERT INTO admins (admin_name, username, password)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE
        admin_name = VALUES(admin_name),
        password = VALUES(password)
");

if (!$adminSeedStmt) {
    http_response_code(500);
    exit("Failed to prepare admin seed statement: " . $conn->error);
}

foreach ($seedAdmins as $admin) {
    $adminSeedStmt->bind_param("sss", $admin[0], $admin[1], $admin[2]);
    if (!$adminSeedStmt->execute()) {
        http_response_code(500);
        exit("Failed to seed admin data: " . $adminSeedStmt->error);
    }
}

$adminSeedStmt->close();
$conn->close();

echo "Database setup completed successfully.";
?>
