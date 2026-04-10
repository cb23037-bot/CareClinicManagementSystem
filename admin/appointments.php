<?php
require_once '../config/database.php';
redirectIfNotLoggedIn();
if(!hasRole('admin')) {
    header('Location: ../login.php');
    exit();
}

// Handle status update
if(isset($_GET['update_status']) && isset($_GET['status'])) {
    $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
    $stmt->execute([$_GET['status'], $_GET['update_status']]);
    header('Location: appointments.php?msg=updated');
    exit();
}

// Get all appointments
$appointments = $pdo->query("
    SELECT a.*, 
           p.full_name as patient_name, pat.patient_id,
           d.full_name as doctor_name, doc.doctor_id, doc.specialization
    FROM appointments a
    JOIN patients pat ON a.patient_id = pat.id
    JOIN users p ON pat.user_id = p.id
    JOIN doctors doc ON a.doctor_id = doc.id
    JOIN users d ON doc.user_id = d.id
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareClinic - Manage Appointments</title>
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
            <h1 class="text-3xl font-bold text-gray-800">Manage Appointments</h1>
        </div>
        
        <?php if(isset($_GET['msg'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                Appointment status updated successfully!
            </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Appointment ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($appointments as $apt): ?>
                        <tr>
                            <td class="px-6 py-4"><?php echo $apt['appointment_id']; ?></td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900"><?php echo $apt['patient_name']; ?></div>
                                <div class="text-xs text-gray-500">ID: <?php echo $apt['patient_id']; ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">Dr. <?php echo $apt['doctor_name']; ?></div>
                                <div class="text-xs text-gray-500"><?php echo $apt['specialization']; ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <?php echo date('M j, Y', strtotime($apt['appointment_date'])); ?><br>
                                <span class="text-xs text-gray-500"><?php echo date('g:i A', strtotime($apt['appointment_time'])); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-xs font-bold uppercase 
                                    <?php echo $apt['status'] == 'completed' ? 'bg-green-100 text-green-700' : 
                                        ($apt['status'] == 'cancelled' ? 'bg-red-100 text-red-700' : 
                                        ($apt['status'] == 'confirmed' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700')); ?>">
                                    <?php echo $apt['status']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <select onchange="updateStatus(<?php echo $apt['id']; ?>, this.value)" class="text-sm border rounded-md px-2 py-1">
                                    <option value="">Change Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function updateStatus(id, status) {
            if(status) {
                if(confirm('Change appointment status?')) {
                    window.location.href = `?update_status=${id}&status=${status}`;
                }
            }
        }
    </script>
</body>
</html>