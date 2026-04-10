<?php
require_once '../config/database.php';
redirectIfNotLoggedIn();
if(!hasRole('patient')) {
    header('Location: ../login.php');
    exit();
}

// Get patient info
$stmt = $pdo->prepare("
    SELECT u.*, p.* 
    FROM users u 
    JOIN patients p ON u.id = p.user_id 
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$patient = $stmt->fetch();

// Get total appointments count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total FROM appointments WHERE patient_id = ?
");
$stmt->execute([$patient['id']]);
$total_appointments = $stmt->fetch()['total'];

// Get upcoming appointments
$stmt = $pdo->prepare("
    SELECT a.*, d.full_name as doctor_name, doc.specialization
    FROM appointments a
    JOIN doctors doc ON a.doctor_id = doc.id
    JOIN users d ON doc.user_id = d.id
    WHERE a.patient_id = ? AND a.appointment_date >= CURDATE()
    ORDER BY a.appointment_date ASC LIMIT 5
");
$stmt->execute([$patient['id']]);
$upcoming_appointments = $stmt->fetchAll();

// Get recent medical records
$stmt = $pdo->prepare("
    SELECT mr.*, d.full_name as doctor_name 
    FROM medical_records mr
    LEFT JOIN doctors doc ON mr.doctor_id = doc.id
    LEFT JOIN users d ON doc.user_id = d.id
    WHERE mr.patient_id = ?
    ORDER BY mr.record_date DESC LIMIT 5
");
$stmt->execute([$patient['id']]);
$recent_records = $stmt->fetchAll();

// Get recent vitals if available
$stmt = $pdo->prepare("
    SELECT * FROM vitals 
    WHERE patient_id = ? 
    ORDER BY recorded_date DESC LIMIT 1
");
$stmt->execute([$patient['id']]);
$latest_vitals = $stmt->fetch();

// Calculate BMI if height and weight available
$bmi = null;
if($patient['height'] && $patient['weight']) {
    $height_m = $patient['height'] / 100;
    $bmi = $patient['weight'] / ($height_m * $height_m);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareClinic - Patient Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f0f2f5; }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body>
    <nav class="bg-white shadow-lg fixed w-full z-50 top-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <img src="../logo.png" alt="Logo" class="h-8 w-8 mr-2">
                    <span class="font-bold text-xl text-indigo-600">CareClinic Patient Portal</span>
                </div>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="dashboard.php" class="text-indigo-600 border-b-2 border-indigo-600 pb-1">Dashboard</a>
                    <a href="records.php" class="text-gray-600 hover:text-indigo-600 transition">My Records</a>
                    <a href="book-appointment.php" class="text-gray-600 hover:text-indigo-600 transition">Book Appointment</a>
                    <a href="profile.php" class="text-gray-600 hover:text-indigo-600 transition">Profile</a>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700 hidden md:inline">Welcome, <?php echo $_SESSION['full_name']; ?></span>
                    <a href="../logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition text-sm">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-8">
        <!-- Welcome Banner -->
        <div class="gradient-bg rounded-2xl shadow-lg p-8 mb-8 text-white">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold mb-2">Welcome back, <?php echo $_SESSION['full_name']; ?>!</h1>
                    <p class="text-white/80">We're here to take care of your health. Your next appointment is just a click away.</p>
                </div>
                <a href="book-appointment.php" class="mt-4 md:mt-0 bg-white text-indigo-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition shadow-lg">
                    + Book New Appointment
                </a>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Total Appointments</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo $total_appointments; ?></p>
                    </div>
                    <div class="bg-indigo-100 p-3 rounded-full">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Medical Records</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo count($recent_records); ?></p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Upcoming</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo count($upcoming_appointments); ?></p>
                    </div>
                    <div class="bg-yellow-100 p-3 rounded-full">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Health Score</p>
                        <p class="text-3xl font-bold text-gray-800">
                            <?php 
                            if($bmi) {
                                if($bmi < 18.5) echo 'Good';
                                elseif($bmi < 25) echo 'Excellent';
                                elseif($bmi < 30) echo 'Fair';
                                else echo 'Needs Care';
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Upcoming Appointments -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800">Upcoming Appointments</h3>
                    <a href="appointments.php" class="text-sm text-indigo-600 hover:text-indigo-800">View All →</a>
                </div>
                <div class="p-6">
                    <?php if(count($upcoming_appointments) > 0): ?>
                        <div class="space-y-4">
                            <?php foreach($upcoming_appointments as $apt): ?>
                            <div class="flex items-start space-x-4 p-3 bg-gray-50 rounded-lg">
                                <div class="bg-indigo-100 rounded-lg p-3 text-center min-w-[60px]">
                                    <div class="text-xs text-indigo-600 font-bold"><?php echo date('M', strtotime($apt['appointment_date'])); ?></div>
                                    <div class="text-xl font-bold text-indigo-700"><?php echo date('d', strtotime($apt['appointment_date'])); ?></div>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-800">Dr. <?php echo $apt['doctor_name']; ?></h4>
                                    <p class="text-sm text-gray-500"><?php echo $apt['specialization']; ?></p>
                                    <p class="text-xs text-gray-400 mt-1"><?php echo date('g:i A', strtotime($apt['appointment_time'])); ?></p>
                                </div>
                                <div>
                                    <span class="px-2 py-1 rounded-full text-xs font-bold uppercase 
                                        <?php echo $apt['status'] == 'confirmed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'; ?>">
                                        <?php echo $apt['status']; ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <svg class="w-16 h-16 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <p class="text-gray-500">No upcoming appointments</p>
                            <a href="book-appointment.php" class="mt-3 inline-block text-indigo-600 hover:text-indigo-800 text-sm">Book one now →</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Medical Records -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800">Recent Medical Records</h3>
                    <a href="records.php" class="text-sm text-indigo-600 hover:text-indigo-800">View All →</a>
                </div>
                <div class="p-6">
                    <?php if(count($recent_records) > 0): ?>
                        <div class="space-y-4">
                            <?php foreach($recent_records as $record): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <div class="bg-blue-100 rounded-lg p-2">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-800"><?php echo $record['condition_name']; ?></h4>
                                        <p class="text-xs text-gray-500">Dr. <?php echo $record['doctor_name'] ?? 'N/A'; ?> • <?php echo date('M j, Y', strtotime($record['record_date'])); ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <?php echo getStatusBadge($record['status']); ?>
                                    <a href="record-detail.php?id=<?php echo $record['id']; ?>" class="text-indigo-600 hover:text-indigo-800 text-sm">View</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <svg class="w-16 h-16 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-gray-500">No medical records yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions Row -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <a href="book-appointment.php" class="bg-white rounded-xl shadow-md p-4 text-center hover:shadow-lg transition group">
                <div class="bg-indigo-100 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-3 group-hover:bg-indigo-200 transition">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </div>
                <p class="font-semibold text-gray-700 text-sm">Book Appointment</p>
            </a>
            
            <a href="records.php" class="bg-white rounded-xl shadow-md p-4 text-center hover:shadow-lg transition group">
                <div class="bg-green-100 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-3 group-hover:bg-green-200 transition">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <p class="font-semibold text-gray-700 text-sm">Medical Records</p>
            </a>
            
            <a href="prescriptions.php" class="bg-white rounded-xl shadow-md p-4 text-center hover:shadow-lg transition group">
                <div class="bg-yellow-100 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-3 group-hover:bg-yellow-200 transition">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <p class="font-semibold text-gray-700 text-sm">Prescriptions</p>
            </a>
            
            <a href="profile.php" class="bg-white rounded-xl shadow-md p-4 text-center hover:shadow-lg transition group">
                <div class="bg-purple-100 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-3 group-hover:bg-purple-200 transition">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <p class="font-semibold text-gray-700 text-sm">My Profile</p>
            </a>
        </div>
        
        <!-- Health Tips Section -->
        <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-start space-x-4">
                <div class="bg-white/20 rounded-full p-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-xl font-bold mb-2">Health Tip of the Day</h3>
                    <p class="text-white/90">Stay hydrated! Drink at least 8 glasses of water daily to maintain optimal health and energy levels.</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-white/80"><?php echo date('l, F j, Y'); ?></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>