<?php
require_once '../config/database.php';
redirectIfNotLoggedIn();
if(!hasRole('patient')) {
    header('Location: ../login.php');
    exit();
}

// Get patient info
$stmt = $pdo->prepare("
    SELECT p.* 
    FROM patients p 
    WHERE p.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$patient = $stmt->fetch();

// Get medical records
$stmt = $pdo->prepare("
    SELECT mr.*, d.full_name as doctor_name, doc.specialization
    FROM medical_records mr
    LEFT JOIN doctors doc ON mr.doctor_id = doc.id
    LEFT JOIN users d ON doc.user_id = d.id
    WHERE mr.patient_id = ?
    ORDER BY mr.record_date DESC
");
$stmt->execute([$patient['id']]);
$records = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareClinic - My Medical Records</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow-lg fixed w-full z-50 top-0">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <img src="../logo.png" alt="Logo" class="h-8 w-8 mr-2">
                    <span class="font-bold text-xl text-indigo-600">CareClinic Patient</span>
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
            <h1 class="text-3xl font-bold text-gray-800">My Medical Records</h1>
            <p class="text-gray-600 mt-2">View your complete medical history</p>
        </div>
        
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Condition</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Specialization</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Next Appointment</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($records as $record): ?>
                        <tr>
                            <td class="px-6 py-4"><?php echo date('M j, Y', strtotime($record['record_date'])); ?></td>
                            <td class="px-6 py-4 font-medium text-gray-900"><?php echo $record['condition_name']; ?></td>
                            <td class="px-6 py-4">Dr. <?php echo $record['doctor_name'] ?? 'N/A'; ?></td>
                            <td class="px-6 py-4"><?php echo $record['specialization'] ?? 'N/A'; ?></td>
                            <td class="px-6 py-4"><?php echo getStatusBadge($record['status']); ?></td>
                            <td class="px-6 py-4"><?php echo $record['next_appointment_date'] ? date('M j, Y', strtotime($record['next_appointment_date'])) : 'N/A'; ?></td>
                            <td class="px-6 py-4">
                                <a href="record-detail.php?id=<?php echo $record['id']; ?>" class="text-indigo-600 hover:text-indigo-900">View Details</a>
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