<?php
require_once '../config/database.php';
redirectIfNotLoggedIn();
if(!hasRole('doctor')) {
    header('Location: ../login.php');
    exit();
}

$record_id = $_GET['id'] ?? 0;

// Get doctor info
$stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$doctor = $stmt->fetch();

// Get record
$stmt = $pdo->prepare("
    SELECT * FROM medical_records 
    WHERE id = ? AND doctor_id = ?
");
$stmt->execute([$record_id, $doctor['id']]);
$record = $stmt->fetch();

if(!$record) {
    header('Location: records.php');
    exit();
}

// Get all patients
$patients = $pdo->query("
    SELECT p.*, u.full_name 
    FROM patients p 
    JOIN users u ON p.user_id = u.id
    ORDER BY u.full_name
")->fetchAll();

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'];
    $condition_name = sanitizeInput($_POST['condition_name']);
    $status = sanitizeInput($_POST['status']);
    $diagnosis = sanitizeInput($_POST['diagnosis']);
    $prescription = sanitizeInput($_POST['prescription']);
    $notes = sanitizeInput($_POST['notes']);
    $record_date = $_POST['record_date'];
    $next_appointment_date = $_POST['next_appointment_date'] ?: null;
    
    $stmt = $pdo->prepare("
        UPDATE medical_records 
        SET patient_id = ?, condition_name = ?, status = ?, diagnosis = ?, 
            prescription = ?, notes = ?, record_date = ?, next_appointment_date = ?
        WHERE id = ? AND doctor_id = ?
    ");
    
    if($stmt->execute([$patient_id, $condition_name, $status, $diagnosis, $prescription, $notes, $record_date, $next_appointment_date, $record_id, $doctor['id']])) {
        header('Location: records.php?msg=updated');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareClinic - Edit Medical Record</title>
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

    <div class="max-w-4xl mx-auto px-4 pt-20 pb-8">
        <div class="mb-8">
            <a href="records.php" class="text-indigo-600 hover:text-indigo-800 mb-2 inline-block">&larr; Back to Records</a>
            <h1 class="text-3xl font-bold text-gray-800">Edit Medical Record</h1>
        </div>
        
        <div class="bg-white rounded-xl shadow-md p-8">
            <form method="POST">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Patient *</label>
                        <select name="patient_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">Select Patient</option>
                            <?php foreach($patients as $patient): ?>
                            <option value="<?php echo $patient['id']; ?>" <?php echo $record['patient_id'] == $patient['id'] ? 'selected' : ''; ?>>
                                <?php echo $patient['full_name']; ?> (<?php echo $patient['patient_id']; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Condition *</label>
                        <input type="text" name="condition_name" value="<?php echo htmlspecialchars($record['condition_name']); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                        <select name="status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="stable" <?php echo $record['status'] == 'stable' ? 'selected' : ''; ?>>Stable</option>
                            <option value="recovering" <?php echo $record['status'] == 'recovering' ? 'selected' : ''; ?>>Recovering</option>
                            <option value="under_observation" <?php echo $record['status'] == 'under_observation' ? 'selected' : ''; ?>>Under Observation</option>
                            <option value="critical" <?php echo $record['status'] == 'critical' ? 'selected' : ''; ?>>Critical</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Record Date *</label>
                        <input type="date" name="record_date" value="<?php echo $record['record_date']; ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Diagnosis</label>
                        <textarea name="diagnosis" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"><?php echo htmlspecialchars($record['diagnosis']); ?></textarea>
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Prescription</label>
                        <textarea name="prescription" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"><?php echo htmlspecialchars($record['prescription']); ?></textarea>
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Additional Notes</label>
                        <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"><?php echo htmlspecialchars($record['notes']); ?></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Next Appointment Date</label>
                        <input type="date" name="next_appointment_date" value="<?php echo $record['next_appointment_date']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-8">
                    <a href="records.php" class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">Cancel</a>
                    <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">Update Record</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>