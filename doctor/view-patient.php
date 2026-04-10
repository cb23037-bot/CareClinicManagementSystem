<?php
require_once '../config/database.php';
redirectIfNotLoggedIn();
if(!hasRole('doctor')) {
    header('Location: ../login.php');
    exit();
}

$patient_id = $_GET['id'] ?? 0;

// Get patient info
$stmt = $pdo->prepare("
    SELECT u.*, p.* 
    FROM users u 
    JOIN patients p ON u.id = p.user_id 
    WHERE p.patient_id = ?
");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch();

if(!$patient) {
    header('Location: patients.php');
    exit();
}

// Get medical records
$stmt = $pdo->prepare("
    SELECT mr.*, d.full_name as doctor_name
    FROM medical_records mr
    LEFT JOIN doctors doc ON mr.doctor_id = doc.id
    LEFT JOIN users d ON doc.user_id = d.id
    WHERE mr.patient_id = ?
    ORDER BY mr.record_date DESC
");
$stmt->execute([$patient['id']]);
$records = $stmt->fetchAll();

// Get appointments
$stmt = $pdo->prepare("
    SELECT a.*, d.full_name as doctor_name
    FROM appointments a
    JOIN doctors doc ON a.doctor_id = doc.id
    JOIN users d ON doc.user_id = d.id
    WHERE a.patient_id = ?
    ORDER BY a.appointment_date DESC
");
$stmt->execute([$patient['id']]);
$appointments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareClinic - Patient Details</title>
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
            <a href="patients.php" class="text-indigo-600 hover:text-indigo-800 mb-2 inline-block">&larr; Back to Patients</a>
            <h1 class="text-3xl font-bold text-gray-800">Patient Details</h1>
        </div>
        
        <!-- Patient Info Card -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
            <div class="flex items-start justify-between">
                <div class="flex items-center space-x-4">
                    <div class="bg-indigo-100 rounded-full w-16 h-16 flex items-center justify-center">
                        <span class="text-2xl font-bold text-indigo-600"><?php echo substr($patient['full_name'], 0, 2); ?></span>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800"><?php echo $patient['full_name']; ?></h2>
                        <p class="text-gray-600">Patient ID: <?php echo $patient['patient_id']; ?></p>
                        <p class="text-gray-600"><?php echo $patient['gender'] ?? 'Not specified'; ?> • Blood Group: <?php echo $patient['blood_group'] ?? 'N/A'; ?></p>
                    </div>
                </div>
                <a href="add-record.php?patient_id=<?php echo $patient['id']; ?>" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                    + Add Medical Record
                </a>
            </div>
            
            <div class="grid md:grid-cols-3 gap-4 mt-6 pt-6 border-t">
                <div>
                    <p class="text-sm text-gray-500">Email</p>
                    <p class="font-medium"><?php echo $patient['email']; ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Phone</p>
                    <p class="font-medium"><?php echo $patient['phone'] ?? 'N/A'; ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Address</p>
                    <p class="font-medium"><?php echo $patient['address'] ?? 'N/A'; ?></p>
                </div>
            </div>
        </div>
        
        <!-- Medical Records -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-800">Medical Records</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Condition</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Next Appointment</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($records as $record): ?>
                        <tr>
                            <td class="px-6 py-4"><?php echo date('M j, Y', strtotime($record['record_date'])); ?></td>
                            <td class="px-6 py-4 font-medium"><?php echo $record['condition_name']; ?></td>
                            <td class="px-6 py-4"><?php echo $record['doctor_name'] ?? 'N/A'; ?></td>
                            <td class="px-6 py-4"><?php echo getStatusBadge($record['status']); ?></td>
                            <td class="px-6 py-4"><?php echo $record['next_appointment_date'] ? date('M j, Y', strtotime($record['next_appointment_date'])) : 'N/A'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Appointment History -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-800">Appointment History</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($appointments as $apt): ?>
                        <tr>
                            <td class="px-6 py-4"><?php echo date('M j, Y', strtotime($apt['appointment_date'])); ?></td>
                            <td class="px-6 py-4"><?php echo date('g:i A', strtotime($apt['appointment_time'])); ?></td>
                            <td class="px-6 py-4"><?php echo $apt['doctor_name']; ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-xs font-bold uppercase 
                                    <?php echo $apt['status'] == 'completed' ? 'bg-green-100 text-green-700' : 
                                        ($apt['status'] == 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700'); ?>">
                                    <?php echo $apt['status']; ?>
                                </span>
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