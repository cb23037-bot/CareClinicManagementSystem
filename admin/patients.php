<?php
require_once '../config/database.php';
redirectIfNotLoggedIn();
if(!hasRole('admin')) {
    header('Location: ../login.php');
    exit();
}

// Handle delete
if(isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'patient'");
    $stmt->execute([$_GET['delete']]);
    header('Location: patients.php?msg=deleted');
    exit();
}

// Get all patients
$patients = $pdo->query("
    SELECT u.*, p.* 
    FROM users u 
    JOIN patients p ON u.id = p.user_id 
    WHERE u.role = 'patient'
    ORDER BY u.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareClinic - Manage Patients</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow-lg fixed w-full z-50 top-0">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="flex items-center">
                        <img src="../logo.png" alt="Logo" class="h-8 w-8 mr-2">
                        <span class="font-bold text-xl text-indigo-600">CareClinic Admin</span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Welcome, <?php echo $_SESSION['full_name']; ?></span>
                    <a href="../logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 pt-20 pb-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Manage Patients</h1>
            <button onclick="openAddModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition">
                + Add New Patient
            </button>
        </div>
        
        <?php if(isset($_GET['msg'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                Operation completed successfully!
            </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Blood Group</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($patients as $patient): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $patient['patient_id']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900"><?php echo $patient['full_name']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $patient['email']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $patient['phone']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $patient['blood_group']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="view-patient.php?id=<?php echo $patient['user_id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                                <a href="?delete=<?php echo $patient['user_id']; ?>" onclick="return confirm('Are you sure?')" class="text-red-600 hover:text-red-900">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>