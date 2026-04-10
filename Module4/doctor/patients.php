<?php
require_once '../config/database.php';
redirectIfNotLoggedIn();
if(!hasRole('doctor')) {
    header('Location: ../login.php');
    exit();
}

// Get doctor info
$stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$doctor = $stmt->fetch();

// Get all patients with appointments to this doctor
$patients = $pdo->prepare("
    SELECT DISTINCT u.*, p.* 
    FROM users u 
    JOIN patients p ON u.id = p.user_id
    JOIN appointments a ON p.id = a.patient_id
    WHERE a.doctor_id = ?
    ORDER BY u.full_name
");
$patients->execute([$doctor['id']]);
$patients_list = $patients->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareClinic - My Patients</title>
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
                        <span class="font-bold text-xl text-indigo-600">CareClinic Doctor</span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700"><?php echo $_SESSION['full_name']; ?></span>
                    <a href="../logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 pt-20 pb-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">My Patients</h1>
            <p class="text-gray-600 mt-2">List of all patients under your care</p>
        </div>
        
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Blood Group</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($patients_list as $patient): ?>
                        <tr>
                            <td class="px-6 py-4"><?php echo $patient['patient_id']; ?></td>
                            <td class="px-6 py-4 font-medium text-gray-900"><?php echo $patient['full_name']; ?></td>
                            <td class="px-6 py-4"><?php echo $patient['email']; ?></td>
                            <td class="px-6 py-4"><?php echo $patient['phone']; ?></td>
                            <td class="px-6 py-4"><?php echo $patient['blood_group'] ?? 'N/A'; ?></td>
                            <td class="px-6 py-4">
                                <a href="view-patient.php?id=<?php echo $patient['patient_id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">View Records</a>
                                <a href="add-record.php?patient_id=<?php echo $patient['id']; ?>" class="text-green-600 hover:text-green-900">Add Record</a>
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