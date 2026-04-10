<?php
require_once '../config/database.php';
redirectIfNotLoggedIn();
if(!hasRole('admin')) {
    header('Location: ../login.php');
    exit();
}

// Get all medical records
$records = $pdo->query("
    SELECT mr.*, 
           p.full_name as patient_name, pat.patient_id,
           d.full_name as doctor_name
    FROM medical_records mr
    JOIN patients pat ON mr.patient_id = pat.id
    JOIN users p ON pat.user_id = p.id
    LEFT JOIN doctors doc ON mr.doctor_id = doc.id
    LEFT JOIN users d ON doc.user_id = d.id
    ORDER BY mr.record_date DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareClinic - Medical Records</title>
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
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Medical Records</h1>
            <p class="text-gray-600 mt-2">View all patient medical records</p>
        </div>
        
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Record ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Condition</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Record Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Next Appointment</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($records as $record): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4"><?php echo $record['record_id']; ?></td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900"><?php echo $record['patient_name']; ?></div>
                                <div class="text-xs text-gray-500">ID: <?php echo $record['patient_id']; ?></div>
                            </td>
                            <td class="px-6 py-4 font-medium"><?php echo $record['condition_name']; ?></td>
                            <td class="px-6 py-4">Dr. <?php echo $record['doctor_name'] ?? 'N/A'; ?></td>
                            <td class="px-6 py-4"><?php echo date('M j, Y', strtotime($record['record_date'])); ?></td>
                            <td class="px-6 py-4">
                                <?php echo getStatusBadge($record['status']); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php echo $record['next_appointment_date'] ? date('M j, Y', strtotime($record['next_appointment_date'])) : 'N/A'; ?>
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