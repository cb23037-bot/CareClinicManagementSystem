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
    
    // Vitals fields
    $blood_pressure = sanitizeInput($_POST['blood_pressure'] ?? null);
    $heart_rate = sanitizeInput($_POST['heart_rate'] ?? null);
    $temperature = sanitizeInput($_POST['temperature'] ?? null);
    $respiratory_rate = sanitizeInput($_POST['respiratory_rate'] ?? null);
    $oxygen_saturation = sanitizeInput($_POST['oxygen_saturation'] ?? null);
    
    $stmt = $pdo->prepare("
        UPDATE medical_records 
        SET patient_id = ?, condition_name = ?, status = ?, diagnosis = ?, 
            prescription = ?, notes = ?, record_date = ?, next_appointment_date = ?,
            blood_pressure = ?, heart_rate = ?, temperature = ?, respiratory_rate = ?, 
            oxygen_saturation = ?
        WHERE id = ? AND doctor_id = ?
    ");
    
    $stmt->execute([
        $patient_id, $condition_name, $status, $diagnosis, 
        $prescription, $notes, $record_date, $next_appointment_date,
        $blood_pressure, $heart_rate, $temperature, $respiratory_rate,
        $oxygen_saturation, $record_id, $doctor['id']
    ]);
    
    header('Location: records.php?msg=updated');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareClinic - Edit Medical Record</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="CareClinicLogo.jpeg">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; background: #ffffff; }
        .hero {
            background: linear-gradient(rgba(255,255,255,0.58), rgba(255,255,255,0.58)), url('background.jpg') center/cover no-repeat;
            min-height: 200px;
            border-bottom-left-radius: 46px;
            border-bottom-right-radius: 46px;
            padding: 14px 26px 30px;
        }
        .nav {
            max-width: 1280px;
            margin: 0 auto;
            background: rgba(255,255,255,0.62);
            backdrop-filter: blur(5px);
            border-radius: 26px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 22px;
            gap: 18px;
        }
        .brand { display: flex; align-items: center; gap: 10px; }
        .logo { width: 44px; height: 44px; object-fit: contain; }
        .brand small { color: #0d6aa8; font-weight: 700; font-size: 18px; }
        .menu { display: flex; align-items: center; gap: 34px; flex-wrap: wrap; justify-content: center; flex: 1; }
        .menu a { text-decoration: none; color: #5864c7; font-size: 14px; }
        .menu a.active { text-decoration: underline; text-underline-offset: 5px; }
        .logout-btn { border: none; background: #5864c7; color: #fff; padding: 8px 14px; border-radius: 12px; font-weight: 700; cursor: pointer; }
        .hero-title { text-align: center; color: rgba(0,0,0,0.75); font-size: 42px; font-style: italic; font-weight: 300; margin: 30px 0 0; }
        .page { max-width: 1000px; margin: -28px auto 60px; padding: 0 18px; position: relative; z-index: 2; }
        .card { background: white; border: 1px solid #bdbdbd; box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08); border-radius: 14px; overflow: hidden; padding: 30px; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #5864c7; text-decoration: none; }
        .form-section { background: #f9fafb; border-radius: 12px; padding: 20px; margin-bottom: 25px; }
        .form-section-title { font-size: 16px; font-weight: 600; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #e5e7eb; }
        .form-section-title i { margin-right: 8px; color: #5864c7; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 6px; color: #1f2937; }
        .required { color: #e74c3c; }
        input, select, textarea { width: 100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; font-family: inherit; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #5864c7; }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .vitals-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 15px; }
        .btn-submit { background: #5864c7; color: white; padding: 12px 24px; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; width: 100%; }
        .btn-cancel { background: #9ca3af; color: white; padding: 12px 24px; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; text-align: center; }
        .button-group { display: flex; gap: 15px; margin-top: 30px; }
        @media (max-width: 768px) { .form-row, .vitals-grid { grid-template-columns: 1fr; } .menu { gap: 16px; } .nav { flex-direction: column; } .hero-title { font-size: 32px; } }
    </style>
</head>
<body>
    <section class="hero">
        <nav class="nav">
            <div class="brand">
                <img src="CareClinicLogo.jpeg" alt="CareClinic" class="logo">
                <small>CareClinic</small>
            </div>
            <div class="menu">
                <a href="dashboard.php">Dashboard</a>
                <a href="patients.php">My Patients</a>
                <a href="appointments.php">My Appointments</a>
                <a href="schedule.php">Schedule Availability</a>
                <a href="records.php" class="active">Medical Records</a>
                <a href="profile.php">Profile</a>
            </div>
            <button class="logout-btn" onclick="window.location.href='../logout.php'">Logout</button>
        </nav>
        <h1 class="hero-title">Edit Medical Record</h1>
    </section>

    <main class="page">
        <div class="card">
            <a href="records.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Records</a>
            
            <form method="POST">
                <!-- Patient Information -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-user"></i> Patient Information</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Patient <span class="required">*</span></label>
                            <select name="patient_id" required>
                                <option value="">Select Patient</option>
                                <?php foreach($patients as $patient): ?>
                                <option value="<?php echo $patient['id']; ?>" <?php echo $record['patient_id'] == $patient['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($patient['full_name']); ?> (<?php echo $patient['patient_id']; ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Record Date <span class="required">*</span></label>
                            <input type="date" name="record_date" value="<?php echo $record['record_date']; ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Vital Signs -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-heartbeat"></i> Vital Signs</div>
                    <div class="vitals-grid">
                        <div class="form-group">
                            <label><i class="fas fa-tachometer-alt"></i> Blood Pressure (mmHg)</label>
                            <input type="text" name="blood_pressure" value="<?php echo htmlspecialchars($record['blood_pressure'] ?? ''); ?>" placeholder="e.g., 120/80">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-heart"></i> Heart Rate (bpm)</label>
                            <input type="number" name="heart_rate" value="<?php echo htmlspecialchars($record['heart_rate'] ?? ''); ?>" placeholder="60-100">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-thermometer-half"></i> Temperature (°C)</label>
                            <input type="text" name="temperature" value="<?php echo htmlspecialchars($record['temperature'] ?? ''); ?>" step="0.1" placeholder="36.5 - 37.2">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-lungs"></i> Respiratory Rate (/min)</label>
                            <input type="number" name="respiratory_rate" value="<?php echo htmlspecialchars($record['respiratory_rate'] ?? ''); ?>" placeholder="12-20">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-percent"></i> Oxygen Saturation (%)</label>
                            <input type="number" name="oxygen_saturation" value="<?php echo htmlspecialchars($record['oxygen_saturation'] ?? ''); ?>" placeholder="95-100" min="0" max="100">
                        </div>
                    </div>
                </div>

                <!-- Diagnosis -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-stethoscope"></i> Diagnosis & Condition</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Condition <span class="required">*</span></label>
                            <input type="text" name="condition_name" value="<?php echo htmlspecialchars($record['condition_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Status <span class="required">*</span></label>
                            <select name="status" required>
                                <option value="stable" <?php echo $record['status'] == 'stable' ? 'selected' : ''; ?>>Stable</option>
                                <option value="recovering" <?php echo $record['status'] == 'recovering' ? 'selected' : ''; ?>>Recovering</option>
                                <option value="under_observation" <?php echo $record['status'] == 'under_observation' ? 'selected' : ''; ?>>Under Observation</option>
                                <option value="critical" <?php echo $record['status'] == 'critical' ? 'selected' : ''; ?>>Critical</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Diagnosis</label>
                        <textarea name="diagnosis" rows="3"><?php echo htmlspecialchars($record['diagnosis']); ?></textarea>
                    </div>
                </div>

                <!-- Prescription -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-prescription-bottle"></i> Prescription</div>
                    <div class="form-group">
                        <textarea name="prescription" rows="4"><?php echo htmlspecialchars($record['prescription']); ?></textarea>
                    </div>
                </div>

                <!-- Follow-up -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-calendar-check"></i> Follow-up Schedule</div>
                    <div class="form-group">
                        <label>Next Appointment Date</label>
                        <input type="date" name="next_appointment_date" value="<?php echo $record['next_appointment_date']; ?>" min="<?php echo date('Y-m-d'); ?>">
                        <small class="text-muted">Leave empty if no follow-up needed</small>
                    </div>
                </div>

                <!-- Additional Notes -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-pencil-alt"></i> Additional Notes</div>
                    <div class="form-group">
                        <textarea name="notes" rows="3"><?php echo htmlspecialchars($record['notes']); ?></textarea>
                    </div>
                </div>
                
                <div class="button-group">
                    <a href="records.php" class="btn-cancel" style="flex: 1;">Cancel</a>
                    <button type="submit" class="btn-submit" style="flex: 1;"><i class="fas fa-save"></i> Update Record</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>