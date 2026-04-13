<?php
require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    $role = sanitizeInput($_POST['role'] ?? 'patient');
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = ?");
    $stmt->execute([$username, $role]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        
        switch($user['role']) {
            case 'admin':
                header('Location: admin/dashboard.php');
                break;
            case 'doctor':
                header('Location: doctor/dashboard.php');
                break;
            case 'patient':
                header('Location: patient/dashboard.php');
                break;
        }
        exit();
    } else {
        $error = 'Invalid username, password, or role!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareClinic - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-2xl p-8">
        <div class="text-center">
            <h2 class="mt-4 text-3xl font-extrabold text-gray-900">Welcome Back</h2>
            <p class="mt-2 text-sm text-gray-600">Sign in to your account</p>
        </div>
        
        <?php if($error): ?>
            <div class="mt-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form class="mt-8 space-y-6" method="POST">
            <div class="space-y-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                    <input id="username" name="username" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" placeholder="Enter your username">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input id="password" name="password" type="password" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" placeholder="Enter your password">
                </div>
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700">Login As</label>
                    <select id="role" name="role" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="patient">Patient</option>
                        <option value="doctor">Doctor</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
            </div>

            <div>
                <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Sign In
                </button>
            </div>
        </form>
        
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                Don't have an account?
                <a href="register.php" class="font-medium text-indigo-600 hover:text-indigo-500">Register here</a>
            </p>
        </div>
        
        <div class="mt-4 text-center">
            <a href="index.php" class="text-sm text-gray-500 hover:text-gray-700">← Back to Home</a>
        </div>
        
        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
            <p class="text-xs text-gray-500 text-center">Demo Credentials:</p>
            <div class="grid grid-cols-3 gap-2 text-xs text-gray-600 mt-2">
                <div>Admin: admin / admin123</div>
                <div>Doctor: fatin / arisya123</div>
                <div>Patient: ameera / ameera123</div>
            </div>
        </div>
    </div>
</body>
</html>