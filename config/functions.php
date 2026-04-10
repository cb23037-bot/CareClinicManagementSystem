<?php
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateUniqueId($prefix) {
    return $prefix . date('Ymd') . rand(1000, 9999);
}

function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

function getStatusBadge($status) {
    $badges = [
        'stable' => '<span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-[11px] font-bold uppercase tracking-wider">stable</span>',
        'recovering' => '<span class="px-2 py-1 bg-orange-100 text-orange-700 rounded-full text-[11px] font-bold uppercase tracking-wider">recovering</span>',
        'critical' => '<span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-[11px] font-bold uppercase tracking-wider">critical</span>',
        'under_observation' => '<span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-[11px] font-bold uppercase tracking-wider">under observation</span>'
    ];
    return $badges[$status] ?? '<span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-[11px] font-bold uppercase tracking-wider">' . $status . '</span>';
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit();
    }
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}
?>