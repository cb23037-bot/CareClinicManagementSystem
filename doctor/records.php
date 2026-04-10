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

// Handle delete
if(isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM medical_records WHERE id = ? AND doctor_id = ?");
    $stmt->execute([$_GET['delete'], $doctor['id']]);
    header('Location: records.php?msg=deleted');
    exit();
}

// Get all medical records for this doctor's patients
$records = $pdo->prepare("
    SELECT mr.*, p.full_name as patient_name, pat.patient_id
    FROM medical_records mr
    JOIN patients pat ON mr.patient_id = pat.id
    JOIN users p ON pat.user_id = p.id
    WHERE mr.doctor_id = ?
    ORDER BY mr.record_date DESC
");
$records->execute([$doctor['id']]);
$records_list = $records->fetchAll();
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
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Medical Records</h1>
                <p class="text-gray-600 mt-2">Manage patient medical records</p>
            </div>
            <a href="add-record.php" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition">
                + Add New Record
            </a>
        </div>
        
        <?php if(isset($_GET['msg'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                Record <?php echo $_GET['msg']; ?> successfully!
            </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Record ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Condition</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Record Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Next Appointment</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($records_list as $record): ?>
                        <tr>
                            <td class="px-6 py-4"><?php echo $record['record_id']; ?></td>
                            <td class="px-6 py-4 font-medium text-gray-900"><?php echo $record['patient_name']; ?></td>
                            <td class="px-6 py-4"><?php echo $record['condition_name']; ?></td>
                            <td class="px-6 py-4"><?php echo date('M j, Y', strtotime($record['record_date'])); ?></td>
                            <td class="px-6 py-4">
                                <?php echo getStatusBadge($record['status']); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php echo $record['next_appointment_date'] ? date('M j, Y', strtotime($record['next_appointment_date'])) : 'N/A'; ?>
                            </td>
                            <td class="px-6 py-4">
                                <a href="edit-record.php?id=<?php echo $record['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                                <a href="?delete=<?php echo $record['id']; ?>" onclick="return confirm('Are you sure?')" class="text-red-600 hover:text-red-900">Delete</a>
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