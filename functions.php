<?php
session_start();

define('MYSQL_CONFIG_FILE', __DIR__ . '/mysql_config.php');

function mysql_app_config()
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $defaults = [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'database' => getenv('DB_NAME') ?: 'careclinic_app',
        'username' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
    ];

    if (file_exists(MYSQL_CONFIG_FILE)) {
        $loaded = require MYSQL_CONFIG_FILE;
        if (is_array($loaded)) {
            $defaults = array_merge($defaults, $loaded);
        }
    }

    $defaults['port'] = (int) $defaults['port'];
    return $config = $defaults;
}

function mysql_database_name()
{
    $name = (string) mysql_app_config()['database'];
    if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
        throw new RuntimeException('The configured MySQL database name is invalid.');
    }

    return $name;
}

function database_connection()
{
    static $connection = null;

    if ($connection instanceof mysqli) {
        return $connection;
    }

    $config = mysql_app_config();
    mysqli_report(MYSQLI_REPORT_OFF);

    $connection = @new mysqli(
        $config['host'],
        $config['username'],
        $config['password'],
        '',
        $config['port']
    );

    if ($connection->connect_errno) {
        throw new RuntimeException('Could not connect to MySQL. Start XAMPP MySQL and check mysql_config.php.');
    }

    $connection->set_charset('utf8mb4');
    $databaseName = mysql_database_name();
    $connection->query("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $connection->select_db($databaseName);

    ensure_database_schema($connection);

    return $connection;
}

function schema_table_exists(mysqli $db, $tableName)
{
    $statement = $db->prepare(
        "SELECT 1
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = ?
           AND TABLE_NAME = ?
         LIMIT 1"
    );

    $databaseName = mysql_database_name();
    $statement->bind_param('ss', $databaseName, $tableName);
    $statement->execute();
    $statement->store_result();
    $exists = $statement->num_rows > 0;
    $statement->close();

    return $exists;
}

function schema_column_exists(mysqli $db, $tableName, $columnName)
{
    $statement = $db->prepare(
        "SELECT 1
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = ?
           AND TABLE_NAME = ?
           AND COLUMN_NAME = ?
         LIMIT 1"
    );

    $databaseName = mysql_database_name();
    $statement->bind_param('sss', $databaseName, $tableName, $columnName);
    $statement->execute();
    $statement->store_result();
    $exists = $statement->num_rows > 0;
    $statement->close();

    return $exists;
}

function schema_index_exists(mysqli $db, $tableName, $indexName)
{
    $statement = $db->prepare(
        "SELECT 1
         FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = ?
           AND TABLE_NAME = ?
           AND INDEX_NAME = ?
         LIMIT 1"
    );

    $databaseName = mysql_database_name();
    $statement->bind_param('sss', $databaseName, $tableName, $indexName);
    $statement->execute();
    $statement->store_result();
    $exists = $statement->num_rows > 0;
    $statement->close();

    return $exists;
}

function schema_foreign_key_exists(mysqli $db, $tableName, $constraintName)
{
    $statement = $db->prepare(
        "SELECT 1
         FROM information_schema.TABLE_CONSTRAINTS
         WHERE TABLE_SCHEMA = ?
           AND TABLE_NAME = ?
           AND CONSTRAINT_NAME = ?
           AND CONSTRAINT_TYPE = 'FOREIGN KEY'
         LIMIT 1"
    );

    $databaseName = mysql_database_name();
    $statement->bind_param('sss', $databaseName, $tableName, $constraintName);
    $statement->execute();
    $statement->store_result();
    $exists = $statement->num_rows > 0;
    $statement->close();

    return $exists;
}

function ensure_database_schema(mysqli $db)
{
    $db->query(
        "CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            role VARCHAR(50) NOT NULL DEFAULT 'patient',
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(50) NOT NULL DEFAULT '',
            address TEXT NOT NULL,
            gender VARCHAR(20) NOT NULL DEFAULT '',
            dob DATE NULL,
            password VARCHAR(255) NOT NULL DEFAULT '',
            google_sub VARCHAR(255) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_users_email (email),
            UNIQUE KEY uniq_users_google_sub (google_sub)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $db->query(
        "CREATE TABLE IF NOT EXISTS admin_profiles (
            user_id INT UNSIGNED NOT NULL PRIMARY KEY,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_admin_profiles_user
                FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $db->query(
        "CREATE TABLE IF NOT EXISTS patient_profiles (
            user_id INT UNSIGNED NOT NULL PRIMARY KEY,
            patient_code VARCHAR(20) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_patient_profiles_code (patient_code),
            CONSTRAINT fk_patient_profiles_user
                FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $db->query(
        "CREATE TABLE IF NOT EXISTS doctor_profiles (
            user_id INT UNSIGNED NOT NULL PRIMARY KEY,
            specialization VARCHAR(120) NOT NULL DEFAULT '',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_doctor_profiles_user
                FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $db->query(
        "CREATE TABLE IF NOT EXISTS doctors (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NULL,
            name VARCHAR(120) NOT NULL,
            specialization VARCHAR(120) NOT NULL DEFAULT '',
            email VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_doctors_email (email),
            UNIQUE KEY uniq_doctors_user_id (user_id),
            CONSTRAINT fk_doctors_user
                FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    if (schema_table_exists($db, 'doctors') && !schema_column_exists($db, 'doctors', 'user_id')) {
        $db->query("ALTER TABLE doctors ADD COLUMN user_id INT UNSIGNED NULL AFTER id");
    }

    if (schema_table_exists($db, 'doctors')) {
        $db->query(
            "UPDATE doctors d
             LEFT JOIN users u ON u.id = d.user_id
             SET d.user_id = NULL
             WHERE d.user_id IS NOT NULL
               AND u.id IS NULL"
        );
    }

    if (schema_table_exists($db, 'doctors') && !schema_index_exists($db, 'doctors', 'uniq_doctors_user_id')) {
        $db->query("ALTER TABLE doctors ADD UNIQUE KEY uniq_doctors_user_id (user_id)");
    }

    if (schema_table_exists($db, 'doctors') && !schema_foreign_key_exists($db, 'doctors', 'fk_doctors_user')) {
        $db->query(
            "ALTER TABLE doctors
             ADD CONSTRAINT fk_doctors_user
             FOREIGN KEY (user_id) REFERENCES users(id)
             ON DELETE SET NULL ON UPDATE CASCADE"
        );
    }

    $db->query("INSERT IGNORE INTO admin_profiles (user_id) SELECT id FROM users WHERE role = 'admin'");
    $db->query("INSERT IGNORE INTO patient_profiles (user_id) SELECT id FROM users WHERE role = 'patient'");
    $db->query("INSERT IGNORE INTO doctor_profiles (user_id, specialization) SELECT id, '' FROM users WHERE role = 'doctor'");

    if (
        schema_table_exists($db, 'doctors')
        && schema_column_exists($db, 'doctors', 'email')
        && schema_column_exists($db, 'doctors', 'specialization')
        && schema_column_exists($db, 'doctors', 'user_id')
    ) {
        $db->query(
            "UPDATE doctors d
             INNER JOIN users u ON u.email = d.email AND u.role = 'doctor'
             SET d.user_id = u.id
             WHERE d.user_id IS NULL"
        );

        $db->query(
            "INSERT INTO doctor_profiles (user_id, specialization)
             SELECT u.id, d.specialization
             FROM doctors d
             INNER JOIN users u ON u.email = d.email AND u.role = 'doctor'
             ON DUPLICATE KEY UPDATE specialization = VALUES(specialization)"
        );
    }
}

function ensure_role_profile_for_user(mysqli $db, $userId, $role, array $attributes = [])
{
    $userId = (int) $userId;
    if ($userId <= 0) {
        return;
    }

    if ($role === 'admin') {
        $statement = $db->prepare('INSERT IGNORE INTO admin_profiles (user_id) VALUES (?)');
        $statement->bind_param('i', $userId);
        $statement->execute();
        $statement->close();
        return;
    }

    if ($role === 'doctor') {
        $specialization = trim((string) ($attributes['specialization'] ?? ''));
        $statement = $db->prepare(
            "INSERT INTO doctor_profiles (user_id, specialization)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE
                specialization = CASE
                    WHEN VALUES(specialization) = '' THEN specialization
                    ELSE VALUES(specialization)
                END"
        );
        $statement->bind_param('is', $userId, $specialization);
        $statement->execute();
        $statement->close();
        return;
    }

    $statement = $db->prepare('INSERT IGNORE INTO patient_profiles (user_id) VALUES (?)');
    $statement->bind_param('i', $userId);
    $statement->execute();
    $statement->close();
}

function remove_role_profile_for_user(mysqli $db, $userId, $role)
{
    $tableName = '';
    if ($role === 'admin') {
        $tableName = 'admin_profiles';
    } elseif ($role === 'doctor') {
        $tableName = 'doctor_profiles';
    } elseif ($role === 'patient') {
        $tableName = 'patient_profiles';
    }

    if ($tableName === '') {
        return;
    }

    $statement = $db->prepare("DELETE FROM {$tableName} WHERE user_id = ?");
    $statement->bind_param('i', $userId);
    $statement->execute();
    $statement->close();
}

function normalize_user_row(array $row)
{
    $row['id'] = (int) ($row['id'] ?? 0);
    $row['role'] = (string) ($row['role'] ?? 'patient');
    $row['name'] = (string) ($row['name'] ?? '');
    $row['email'] = (string) ($row['email'] ?? '');
    $row['phone'] = (string) ($row['phone'] ?? '');
    $row['address'] = (string) ($row['address'] ?? '');
    $row['gender'] = (string) ($row['gender'] ?? '');
    $row['dob'] = (string) ($row['dob'] ?? '');
    $row['password'] = (string) ($row['password'] ?? '');
    $row['google_sub'] = (string) ($row['google_sub'] ?? '');
    return $row;
}

function load_users()
{
    $result = database_connection()->query(
        "SELECT id, role, name, email, phone, address, gender,
                COALESCE(DATE_FORMAT(dob, '%Y-%m-%d'), '') AS dob,
                password, COALESCE(google_sub, '') AS google_sub
         FROM users
         ORDER BY id"
    );

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = normalize_user_row($row);
    }

    return $users;
}

function fetch_user_by_query($sql, $types, ...$params)
{
    $db = database_connection();
    $statement = $db->prepare($sql);
    $statement->bind_param($types, ...$params);
    $statement->execute();
    $result = $statement->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $statement->close();

    return $row ? normalize_user_row($row) : null;
}

function find_user_by_email($email)
{
    return fetch_user_by_query(
        "SELECT id, role, name, email, phone, address, gender,
                COALESCE(DATE_FORMAT(dob, '%Y-%m-%d'), '') AS dob,
                password, COALESCE(google_sub, '') AS google_sub
         FROM users
         WHERE email = ?
         LIMIT 1",
        's',
        $email
    );
}

function find_user_by_id($id)
{
    return fetch_user_by_query(
        "SELECT id, role, name, email, phone, address, gender,
                COALESCE(DATE_FORMAT(dob, '%Y-%m-%d'), '') AS dob,
                password, COALESCE(google_sub, '') AS google_sub
         FROM users
         WHERE id = ?
         LIMIT 1",
        'i',
        $id
    );
}

function create_user(array $attributes)
{
    $db = database_connection();
    $role = (string) ($attributes['role'] ?? 'patient');
    $name = (string) ($attributes['name'] ?? '');
    $email = (string) ($attributes['email'] ?? '');
    $phone = (string) ($attributes['phone'] ?? '');
    $address = (string) ($attributes['address'] ?? '');
    $gender = (string) ($attributes['gender'] ?? '');
    $dob = trim((string) ($attributes['dob'] ?? ''));
    $dob = $dob !== '' ? $dob : null;
    $password = (string) ($attributes['password'] ?? '');
    $googleSub = trim((string) ($attributes['google_sub'] ?? ''));
    $googleSub = $googleSub !== '' ? $googleSub : null;

    $statement = $db->prepare(
        'INSERT INTO users (role, name, email, phone, address, gender, dob, password, google_sub)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $statement->bind_param(
        'sssssssss',
        $role,
        $name,
        $email,
        $phone,
        $address,
        $gender,
        $dob,
        $password,
        $googleSub
    );
    $statement->execute();
    $newId = (int) $db->insert_id;
    $statement->close();
    ensure_role_profile_for_user($db, $newId, $role, $attributes);

    return find_user_by_id($newId);
}

function update_user(array $updatedUser)
{
    $db = database_connection();
    $id = (int) ($updatedUser['id'] ?? 0);
    $existingUser = $id > 0 ? find_user_by_id($id) : null;
    $role = (string) ($updatedUser['role'] ?? 'patient');
    $name = (string) ($updatedUser['name'] ?? '');
    $email = (string) ($updatedUser['email'] ?? '');
    $phone = (string) ($updatedUser['phone'] ?? '');
    $address = (string) ($updatedUser['address'] ?? '');
    $gender = (string) ($updatedUser['gender'] ?? '');
    $dob = trim((string) ($updatedUser['dob'] ?? ''));
    $dob = $dob !== '' ? $dob : null;
    $password = (string) ($updatedUser['password'] ?? '');
    $googleSub = trim((string) ($updatedUser['google_sub'] ?? ''));
    $googleSub = $googleSub !== '' ? $googleSub : null;

    $statement = $db->prepare(
        'UPDATE users
         SET role = ?, name = ?, email = ?, phone = ?, address = ?, gender = ?, dob = ?, password = ?, google_sub = ?
         WHERE id = ?'
    );
    $statement->bind_param(
        'sssssssssi',
        $role,
        $name,
        $email,
        $phone,
        $address,
        $gender,
        $dob,
        $password,
        $googleSub,
        $id
    );
    $statement->execute();
    $updated = $statement->affected_rows >= 0;
    $statement->close();

    if ($updated && $id > 0) {
        if ($existingUser && ($existingUser['role'] ?? '') !== $role) {
            remove_role_profile_for_user($db, $id, $existingUser['role']);
        }

        ensure_role_profile_for_user($db, $id, $role, $updatedUser);
    }

    return $updated;
}

function delete_user_by_id($id)
{
    $db = database_connection();
    $statement = $db->prepare('DELETE FROM users WHERE id = ?');
    $statement->bind_param('i', $id);
    $statement->execute();
    $deleted = $statement->affected_rows > 0;
    $statement->close();

    return $deleted;
}

function current_user()
{
    if (empty($_SESSION['user_email'])) {
        return null;
    }

    return find_user_by_email($_SESSION['user_email']);
}

function require_login()
{
    if (!current_user()) {
        header('Location: index.php');
        exit;
    }
}

function require_role($role)
{
    require_login();
    $user = current_user();
    if (!$user || $user['role'] !== $role) {
        header('Location: ' . home_path_for_role($user['role'] ?? 'patient'));
        exit;
    }
}

function sanitize($value)
{
    return htmlspecialchars(trim($value));
}

function redirect_after_login(array $user)
{
    header('Location: ' . home_path_for_role($user['role'] ?? 'patient'));
    exit;
}

function home_path_for_role($role)
{
    if ($role === 'admin') {
        return 'admin_home.php';
    }

    if ($role === 'doctor') {
        return 'doctor_home.php';
    }

    return 'patient_home.php';
}

function flash_message()
{
    if (!empty($_SESSION['flash'])) {
        $message = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $message;
    }

    return null;
}

function set_flash($message)
{
    $_SESSION['flash'] = $message;
}
