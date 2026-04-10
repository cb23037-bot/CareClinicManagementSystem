<?php
require 'functions.php';
require_login();

$user = current_user();
if ($user['role'] !== 'patient') {
    header('Location: profile.php');
    exit;
}

delete_user_by_id($user['id']);
session_unset();
session_destroy();
header('Location: index.php');
exit;
