<?php
require 'functions.php';
require_login();

$user = current_user();
header('Location: ' . home_path_for_role($user['role'] ?? 'patient'));
exit;
