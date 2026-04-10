<?php
header('Content-Type: text/plain; charset=utf-8');

require 'db.php';

function fail(string $message): void
{
    http_response_code(500);
    exit($message);
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
        fail("Failed to prepare column check: " . $conn->error);
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
        fail("Failed to prepare index check: " . $conn->error);
    }

    $stmt->bind_param("sss", $database, $table, $index);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();

    return $exists;
}

function tableExists(mysqli $conn, string $database, string $table): bool
{
    $stmt = $conn->prepare("
        SELECT 1
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = ?
          AND TABLE_NAME = ?
        LIMIT 1
    ");

    if (!$stmt) {
        fail("Failed to prepare table check: " . $conn->error);
    }

    $stmt->bind_param("ss", $database, $table);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();

    return $exists;
}

function ensureQuery(mysqli $conn, string $sql, string $errorPrefix): void
{
    if (!$conn->query($sql)) {
        fail($errorPrefix . ": " . $conn->error);
    }
}

function generatePatientCode(int $patientId): string
{
    return 'PAT' . str_pad((string)$patientId, 4, '0', STR_PAD_LEFT);
}

$database = 'careclinic_db';

$queries = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        role VARCHAR(50) NOT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) DEFAULT NULL,
        phone VARCHAR(50) DEFAULT NULL,
        address TEXT DEFAULT NULL,
        gender VARCHAR(20) DEFAULT NULL,
        dob DATE DEFAULT NULL,
        password VARCHAR(255) DEFAULT NULL,
        google_sub VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_users_role_email (role, email),
        UNIQUE KEY uniq_users_google_sub (google_sub)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS doctor_profiles (
        user_id INT(10) UNSIGNED NOT NULL,
        specialization VARCHAR(120) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id),
        CONSTRAINT fk_doctor_profiles_user FOREIGN KEY (user_id)
            REFERENCES users (id)
            ON DELETE CASCADE
            ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS admin_profiles (
        user_id INT(10) UNSIGNED NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id),
        CONSTRAINT fk_admin_profiles_user FOREIGN KEY (user_id)
            REFERENCES users (id)
            ON DELETE CASCADE
            ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS patient_profiles (
        user_id INT(10) UNSIGNED NOT NULL,
        patient_code VARCHAR(20) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id),
        UNIQUE KEY uniq_patient_profiles_patient_code (patient_code),
        CONSTRAINT fk_patient_profiles_user FOREIGN KEY (user_id)
            REFERENCES users (id)
            ON DELETE CASCADE
            ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

foreach ($queries as $query) {
    ensureQuery($conn, $query, 'Migration failed while creating tables');
}

if (!columnExists($conn, $database, 'admins', 'user_id')) {
    ensureQuery($conn, "ALTER TABLE admins ADD COLUMN user_id INT(10) UNSIGNED NULL AFTER admin_id", 'Migration failed while adding admins.user_id');
}

if (!columnExists($conn, $database, 'doctors', 'user_id')) {
    ensureQuery($conn, "ALTER TABLE doctors ADD COLUMN user_id INT(10) UNSIGNED NULL AFTER doctor_id", 'Migration failed while adding doctors.user_id');
}

if (!columnExists($conn, $database, 'patients', 'user_id')) {
    ensureQuery($conn, "ALTER TABLE patients ADD COLUMN user_id INT(10) UNSIGNED NULL AFTER patient_id", 'Migration failed while adding patients.user_id');
}

if (!indexExists($conn, $database, 'admins', 'uniq_admins_user_id')) {
    ensureQuery($conn, "ALTER TABLE admins ADD UNIQUE KEY uniq_admins_user_id (user_id)", 'Migration failed while adding admins.user_id index');
}

if (!indexExists($conn, $database, 'doctors', 'uniq_doctors_user_id')) {
    ensureQuery($conn, "ALTER TABLE doctors ADD UNIQUE KEY uniq_doctors_user_id (user_id)", 'Migration failed while adding doctors.user_id index');
}

if (!indexExists($conn, $database, 'patients', 'uniq_patients_user_id')) {
    ensureQuery($conn, "ALTER TABLE patients ADD UNIQUE KEY uniq_patients_user_id (user_id)", 'Migration failed while adding patients.user_id index');
}

$conn->begin_transaction();

try {
    $insertUserStmt = $conn->prepare("
        INSERT INTO users (role, name, email, phone, password)
        VALUES (?, ?, ?, ?, ?)
    ");
    if (!$insertUserStmt) {
        throw new RuntimeException("Failed to prepare user insert statement: " . $conn->error);
    }

    $updateAdminStmt = $conn->prepare("UPDATE admins SET user_id = ? WHERE admin_id = ?");
    $updateDoctorStmt = $conn->prepare("UPDATE doctors SET user_id = ? WHERE doctor_id = ?");
    $updatePatientStmt = $conn->prepare("UPDATE patients SET user_id = ? WHERE patient_id = ?");
    $upsertAdminProfileStmt = $conn->prepare("
        INSERT INTO admin_profiles (user_id)
        VALUES (?)
        ON DUPLICATE KEY UPDATE user_id = VALUES(user_id)
    ");
    $upsertDoctorProfileStmt = $conn->prepare("
        INSERT INTO doctor_profiles (user_id, specialization)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE specialization = VALUES(specialization)
    ");
    $upsertPatientProfileStmt = $conn->prepare("
        INSERT INTO patient_profiles (user_id, patient_code)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE patient_code = VALUES(patient_code)
    ");

    foreach ([$updateAdminStmt, $updateDoctorStmt, $updatePatientStmt, $upsertAdminProfileStmt, $upsertDoctorProfileStmt, $upsertPatientProfileStmt] as $stmt) {
        if (!$stmt) {
            throw new RuntimeException("Failed to prepare migration statement: " . $conn->error);
        }
    }

    $adminResult = $conn->query("SELECT admin_id, admin_name, username, password, user_id FROM admins ORDER BY admin_id ASC");
    if (!$adminResult) {
        throw new RuntimeException("Failed to fetch admins: " . $conn->error);
    }

    $migratedAdmins = 0;
    while ($admin = $adminResult->fetch_assoc()) {
        $userId = $admin['user_id'] !== null ? (int)$admin['user_id'] : null;

        if ($userId === null) {
            $role = 'admin';
            $name = $admin['admin_name'];
            $email = null;
            $phone = null;
            $password = $admin['password'];
            $insertUserStmt->bind_param("sssss", $role, $name, $email, $phone, $password);
            if (!$insertUserStmt->execute()) {
                throw new RuntimeException("Failed to create admin user for {$admin['admin_name']}: " . $insertUserStmt->error);
            }

            $userId = (int)$conn->insert_id;
            $updateAdminStmt->bind_param("ii", $userId, $admin['admin_id']);
            if (!$updateAdminStmt->execute()) {
                throw new RuntimeException("Failed to link admin {$admin['admin_name']} to user: " . $updateAdminStmt->error);
            }
        }

        $upsertAdminProfileStmt->bind_param("i", $userId);
        if (!$upsertAdminProfileStmt->execute()) {
            throw new RuntimeException("Failed to upsert admin profile for {$admin['admin_name']}: " . $upsertAdminProfileStmt->error);
        }

        $migratedAdmins++;
    }
    $adminResult->close();

    $doctorResult = $conn->query("SELECT doctor_id, doctor_name, doctor_role, username, password, user_id FROM doctors ORDER BY doctor_id ASC");
    if (!$doctorResult) {
        throw new RuntimeException("Failed to fetch doctors: " . $conn->error);
    }

    $migratedDoctors = 0;
    while ($doctor = $doctorResult->fetch_assoc()) {
        $userId = $doctor['user_id'] !== null ? (int)$doctor['user_id'] : null;

        if ($userId === null) {
            $role = 'doctor';
            $name = $doctor['doctor_name'];
            $email = null;
            $phone = null;
            $password = $doctor['password'];
            $insertUserStmt->bind_param("sssss", $role, $name, $email, $phone, $password);
            if (!$insertUserStmt->execute()) {
                throw new RuntimeException("Failed to create doctor user for {$doctor['doctor_name']}: " . $insertUserStmt->error);
            }

            $userId = (int)$conn->insert_id;
            $updateDoctorStmt->bind_param("ii", $userId, $doctor['doctor_id']);
            if (!$updateDoctorStmt->execute()) {
                throw new RuntimeException("Failed to link doctor {$doctor['doctor_name']} to user: " . $updateDoctorStmt->error);
            }
        }

        $specialization = $doctor['doctor_role'];
        $upsertDoctorProfileStmt->bind_param("is", $userId, $specialization);
        if (!$upsertDoctorProfileStmt->execute()) {
            throw new RuntimeException("Failed to upsert doctor profile for {$doctor['doctor_name']}: " . $upsertDoctorProfileStmt->error);
        }

        $migratedDoctors++;
    }
    $doctorResult->close();

    $patientResult = $conn->query("SELECT patient_id, patient_name, phone_number, user_id FROM patients ORDER BY patient_id ASC");
    if (!$patientResult) {
        throw new RuntimeException("Failed to fetch patients: " . $conn->error);
    }

    $migratedPatients = 0;
    while ($patient = $patientResult->fetch_assoc()) {
        $userId = $patient['user_id'] !== null ? (int)$patient['user_id'] : null;

        if ($userId === null) {
            $role = 'patient';
            $name = $patient['patient_name'];
            $email = null;
            $phone = $patient['phone_number'];
            $password = null;
            $insertUserStmt->bind_param("sssss", $role, $name, $email, $phone, $password);
            if (!$insertUserStmt->execute()) {
                throw new RuntimeException("Failed to create patient user for {$patient['patient_name']}: " . $insertUserStmt->error);
            }

            $userId = (int)$conn->insert_id;
            $updatePatientStmt->bind_param("ii", $userId, $patient['patient_id']);
            if (!$updatePatientStmt->execute()) {
                throw new RuntimeException("Failed to link patient {$patient['patient_name']} to user: " . $updatePatientStmt->error);
            }
        }

        $patientCode = generatePatientCode((int)$patient['patient_id']);
        $upsertPatientProfileStmt->bind_param("is", $userId, $patientCode);
        if (!$upsertPatientProfileStmt->execute()) {
            throw new RuntimeException("Failed to upsert patient profile for {$patient['patient_name']}: " . $upsertPatientProfileStmt->error);
        }

        $migratedPatients++;
    }
    $patientResult->close();

    $insertUserStmt->close();
    $updateAdminStmt->close();
    $updateDoctorStmt->close();
    $updatePatientStmt->close();
    $upsertAdminProfileStmt->close();
    $upsertDoctorProfileStmt->close();
    $upsertPatientProfileStmt->close();

    $conn->commit();

    echo "Phase 1 migration completed successfully.\n";
    echo "Admins mapped: {$migratedAdmins}\n";
    echo "Doctors mapped: {$migratedDoctors}\n";
    echo "Patients mapped: {$migratedPatients}\n";
} catch (Throwable $e) {
    $conn->rollback();
    fail("Phase 1 migration failed: " . $e->getMessage());
}

$conn->close();
?>
