<?php
session_start();

define('GOOGLE_CONFIG_FILE', __DIR__ . '/google_oauth_config.php');
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

    return find_user_by_id($newId);
}

function update_user(array $updatedUser)
{
    $db = database_connection();
    $id = (int) ($updatedUser['id'] ?? 0);
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

function google_oauth_config()
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $config = ['client_id' => ''];

    if (file_exists(GOOGLE_CONFIG_FILE)) {
        $loaded = require GOOGLE_CONFIG_FILE;
        if (is_array($loaded)) {
            $config = array_merge($config, $loaded);
        }
    }

    $config['client_id'] = trim((string) ($config['client_id'] ?? ''));
    return $config;
}

function google_client_id()
{
    $config = google_oauth_config();
    return $config['client_id'];
}

function google_sign_in_enabled()
{
    $clientId = google_client_id();
    return $clientId !== '' && stripos($clientId, 'YOUR_GOOGLE_CLIENT_ID') !== 0;
}

function base64url_decode_string($value)
{
    $remainder = strlen($value) % 4;
    if ($remainder > 0) {
        $value .= str_repeat('=', 4 - $remainder);
    }

    return base64_decode(strtr($value, '-_', '+/'));
}

function google_fetch_public_certificates()
{
    static $certificates = null;

    if ($certificates !== null) {
        return $certificates;
    }

    $ch = curl_init('https://www.googleapis.com/oauth2/v1/certs');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
    ]);

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($response === false || $status >= 400) {
        throw new RuntimeException('Could not contact Google to verify the sign-in token.');
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded) || $decoded === []) {
        throw new RuntimeException('Google returned an invalid set of signing certificates.');
    }

    $certificates = $decoded;
    return $certificates;
}

function google_verify_id_token($idToken)
{
    if (!google_sign_in_enabled()) {
        throw new RuntimeException('Google Sign-In is not configured yet.');
    }

    $parts = explode('.', $idToken);
    if (count($parts) !== 3) {
        throw new RuntimeException('Google returned an invalid token.');
    }

    [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
    $header = json_decode(base64url_decode_string($encodedHeader), true);
    $payload = json_decode(base64url_decode_string($encodedPayload), true);
    $signature = base64url_decode_string($encodedSignature);

    if (!is_array($header) || !is_array($payload) || $signature === false) {
        throw new RuntimeException('Google returned a malformed token.');
    }

    if (($header['alg'] ?? '') !== 'RS256' || empty($header['kid'])) {
        throw new RuntimeException('Google returned a token in an unsupported format.');
    }

    $certificates = google_fetch_public_certificates();
    $certificate = $certificates[$header['kid']] ?? null;

    if (!$certificate) {
        throw new RuntimeException('Google signing certificate could not be matched.');
    }

    $verified = openssl_verify(
        $encodedHeader . '.' . $encodedPayload,
        $signature,
        $certificate,
        OPENSSL_ALGO_SHA256
    );

    if ($verified !== 1) {
        throw new RuntimeException('Google sign-in verification failed.');
    }

    $audience = $payload['aud'] ?? '';
    $issuer = $payload['iss'] ?? '';
    $expiresAt = (int) ($payload['exp'] ?? 0);
    $issuedAt = (int) ($payload['iat'] ?? 0);

    if ($audience !== google_client_id()) {
        throw new RuntimeException('This Google token was not issued for this app.');
    }

    if (!in_array($issuer, ['accounts.google.com', 'https://accounts.google.com'], true)) {
        throw new RuntimeException('Google token issuer was invalid.');
    }

    if ($expiresAt < time() || ($issuedAt > 0 && $issuedAt > time() + 300)) {
        throw new RuntimeException('Google token has expired or is not valid yet.');
    }

    $email = trim((string) ($payload['email'] ?? ''));
    $subject = trim((string) ($payload['sub'] ?? ''));
    $emailVerified = filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

    if ($email === '' || $subject === '' || !$emailVerified) {
        throw new RuntimeException('Google did not return a verified email address.');
    }

    return $payload;
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

function find_or_create_google_user(array $googlePayload)
{
    $email = trim((string) ($googlePayload['email'] ?? ''));
    $name = trim((string) ($googlePayload['name'] ?? ''));
    $subject = trim((string) ($googlePayload['sub'] ?? ''));

    if ($email === '' || $subject === '') {
        throw new RuntimeException('Google account data was incomplete.');
    }

    $user = find_user_by_email($email);
    if ($user) {
        $user['google_sub'] = $subject;

        if (($user['name'] ?? '') === '' && $name !== '') {
            $user['name'] = $name;
        }

        update_user($user);
        return find_user_by_id($user['id']);
    }

    return create_user([
        'role' => 'patient',
        'name' => $name !== '' ? $name : 'Google User',
        'email' => $email,
        'phone' => '',
        'address' => '',
        'gender' => '',
        'dob' => '',
        'password' => '',
        'google_sub' => $subject,
    ]);
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
