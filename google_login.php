<?php
require 'functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

$credential = trim((string) ($_POST['credential'] ?? ''));

if ($credential === '') {
    set_flash('Google sign-in did not return a credential.');
    header('Location: register.php');
    exit;
}

try {
    $googlePayload = google_verify_id_token($credential);
    $user = find_or_create_google_user($googlePayload);
    $_SESSION['user_email'] = $user['email'];
    redirect_after_login($user);
} catch (Throwable $exception) {
    set_flash($exception->getMessage());
    header('Location: register.php');
    exit;
}
