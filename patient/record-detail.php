<?php
require_once '../config/database.php';
redirectIfNotLoggedIn();
if(!hasRole('patient')) {
    header('Location: ../login.php');
    exit();
}

$record_id = $_GET['id'] ?? 0;

// Get patient info
$stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$patient = $stmt->fetch();

// Get record details
$stmt = $pdo->prepare("
    SELECT mr.*, 
           d.full_name as doctor_name, doc.specialization, doc.qualification,
           p.full_name as patient_name, pat.patient_id, pat.blood_group, 
           pat.height, pat.weight, pat.allergies, pat.chronic_conditions,
           pat.emergency_contact_name, pat.emergency_contact_phone, pat.emergency_contact_relationship
    FROM medical_records mr
    JOIN patients pat ON mr.patient_id = pat.id
    JOIN users p ON pat.user_id = p.id
    LEFT JOIN doctors doc ON mr.doctor_id = doc.id
    LEFT JOIN users d ON doc.user_id = d.id
    WHERE mr.id = ? AND mr.patient_id = ?
");
$stmt->execute([$record_id, $patient['id']]);
$record = $stmt->fetch();

if(!$record) {
    header('Location: records.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareClinic - Record Details</title>
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

    <div class="max-w-5xl mx-auto px-4 pt-20 pb-8">
        <div class="mb-8">
            <a href="records.php" class="text-indigo-600 hover:text-indigo-800 mb-2 inline-block">&larr; Back to Records</a>
            <h1 class="text-3xl font-bold text-gray-800">Medical Record Details</h1>
        </div>
        
        <!-- Record Info -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800"><?php echo $record['condition_name']; ?></h2>
                    <p class="text-gray-600">Record ID: <?php echo $record['record_id']; ?></p>
                </div>
                <div><?php echo getStatusBadge($record['status']); ?></div>
            </div>
            
            <div class="grid md:grid-cols-2 gap-4 pt-4 border-t">
                <div>
                    <p class="text-sm text-gray-500">Date of Record</p>
                    <p class="font-medium"><?php echo date('F j, Y', strtotime($record['record_date'])); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Doctor</p>
                    <p class="font-medium">Dr. <?php echo $record['doctor_name'] ?? 'N/A'; ?></p>
                    <p class="text-sm text-gray-500"><?php echo $record['specialization'] ?? ''; ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Next Appointment</p>
                    <p class="font-medium"><?php echo $record['next_appointment_date'] ? date('F j, Y', strtotime($record['next_appointment_date'])) : 'Not scheduled'; ?></p>
                </div>
            </div>
        </div>
        
        <!-- Diagnosis & Prescription -->
        <div class="grid md:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Diagnosis</h3>
                <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($record['diagnosis'] ?: 'No diagnosis recorded')); ?></p>
            </div>
            
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Prescription</h3>
                <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($record['prescription'] ?: 'No prescription recorded')); ?></p>
            </div>
        </div>
        
        <!-- Additional Notes -->
        <?php if($record['notes']): ?>
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Additional Notes</h3>
            <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($record['notes'])); ?></p>
        </div>
        <?php endif; ?>
        
        <!-- Patient Information -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Personal Information</h3>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Patient ID</p>
                    <p class="font-medium"><?php echo $record['patient_id']; ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Full Name</p>
                    <p class="font-medium"><?php echo $record['patient_name']; ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Blood Group</p>
                    <p class="font-medium"><?php echo $record['blood_group'] ?? 'Not recorded'; ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">BMI</p>
                    <p class="font-medium">
                        <?php 
                        if($record['height'] && $record['weight']) {
                            $bmi = $record['weight'] / (($record['height']/100) ** 2);
                            echo number_format($bmi, 1);
                        } else {
                            echo 'Not recorded';
                        }
                        ?>
                    </p>
                </div>
            </div>
            
            <?php if($record['allergies'] || $record['chronic_conditions']): ?>
            <div class="mt-4 pt-4 border-t">
                <?php if($record['allergies']): ?>
                <div class="mb-3">
                    <p class="text-sm text-gray-500">Allergies</p>
                    <div class="flex flex-wrap gap-2 mt-1">
                        <?php foreach(explode(',', $record['allergies']) as $allergy): ?>
                        <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs"><?php echo trim($allergy); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if($record['chronic_conditions']): ?>
                <div>
                    <p class="text-sm text-gray-500">Chronic Conditions</p>
                    <div class="flex flex-wrap gap-2 mt-1">
                        <?php foreach(explode(',', $record['chronic_conditions']) as $condition): ?>
                        <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs"><?php echo trim($condition); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if($record['emergency_contact_name']): ?>
            <div class="mt-4 pt-4 border-t">
                <h4 class="font-semibold text-gray-800 mb-2">Emergency Contact</h4>
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-xs text-gray-500">Name</p>
                        <p class="text-sm font-medium"><?php echo $record['emergency_contact_name']; ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Relationship</p>
                        <p class="text-sm font-medium"><?php echo $record['emergency_contact_relationship']; ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Phone</p>
                        <p class="text-sm font-medium"><?php echo $record['emergency_contact_phone']; ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Print/Download Button -->
        <div class="mt-6 flex justify-end">
            <button onclick="window.print()" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                Print / Download Record
            </button>
        </div>
    </div>
</body>
</html>