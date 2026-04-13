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
           d.full_name as doctor_name, doc.specialization,
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

function getStatusClass($status) {
    switch($status) {
        case 'stable': return 'success';
        case 'recovering': return 'info';
        case 'under_observation': return 'warning';
        case 'critical': return 'danger';
        default: return 'secondary';
    }
}

function getStatusText($status) {
    switch($status) {
        case 'stable': return 'Stable';
        case 'recovering': return 'Recovering';
        case 'under_observation': return 'Under Observation';
        case 'critical': return 'Critical';
        default: return ucfirst($status);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareClinic - Record Details</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="CareClinicLogo.jpeg">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f6fa;
        }

        .hero {
            background: linear-gradient(rgba(255,255,255,0.58), rgba(255,255,255,0.58)), url('background.jpg') center/cover no-repeat;
            min-height: 200px;
            border-bottom-left-radius: 46px;
            border-bottom-right-radius: 46px;
            padding: 14px 26px 30px;
            position: relative;
        }

        .nav {
            max-width: 1180px;
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

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 100px;
        }

        .logo {
            width: 44px;
            height: 44px;
            flex-shrink: 0;
            object-fit: contain;
        }

        .brand small {
            display: block;
            color: #0d6aa8;
            font-weight: 700;
            margin-top: 4px;
        }

        .menu {
            display: flex;
            align-items: center;
            gap: 34px;
            flex-wrap: wrap;
            justify-content: center;
            flex: 1;
        }

        .menu a {
            text-decoration: none;
            color: #5864c7;
            font-size: 14px;
        }

        .menu a.active {
            text-decoration: underline;
            text-underline-offset: 5px;
        }

        .logout-btn {
            border: none;
            background: #5864c7;
            color: #fff;
            padding: 8px 14px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .hero-title {
            text-align: center;
            color: rgba(0,0,0,0.75);
            font-size: 48px;
            font-style: italic;
            font-weight: 300;
            margin: 30px 0 0;
        }

        .page {
            max-width: 1000px;
            margin: -28px auto 60px;
            padding: 0 18px;
            position: relative;
            z-index: 2;
        }

        .card {
            background: white;
            border: 1px solid #bdbdbd;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 25px;
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
            background: white;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }

        .card-title i {
            margin-right: 8px;
            color: #5864c7;
        }

        .badge-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
        }

        .info-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #efefef;
        }

        .info-label {
            width: 140px;
            font-size: 13px;
            color: #6b7280;
        }

        .info-value {
            flex: 1;
            font-size: 14px;
            font-weight: 500;
            color: #1f2937;
        }

        .allergy-tag {
            display: inline-block;
            background: #fee;
            color: #e74c3c;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            margin: 3px;
        }

        .condition-tag {
            display: inline-block;
            background: #fff3e0;
            color: #f5a623;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            margin: 3px;
        }

        @media (max-width: 900px) {
            .menu { gap: 16px; }
            .hero-title { font-size: 36px; }
            .info-label { width: 110px; }
        }

        @media (max-width: 680px) {
            .nav { flex-direction: column; align-items: stretch; }
            .menu { justify-content: flex-start; }
            .hero-title { font-size: 28px; margin-top: 34px; }
            .info-row { flex-direction: column; }
            .info-label { width: 100%; margin-bottom: 5px; }
        }
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
                <a href="dashboard.php">Home</a>
                <a href="book-appointment.php">Book Appointment</a>
                <a href="index.php">My Appointment</a>
                <a href="records.php" class="active">My Medical Records</a>
                <a href="profile.php">Profile</a>
            </div>

            <button class="logout-btn" onclick="window.location.href='../logout.php'">Logout</button>
        </nav>

        <h1 class="hero-title">Record Details</h1>
    </section>

    <main class="page">
        <!-- Record Info Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-alt"></i> <?php echo htmlspecialchars($record['condition_name']); ?></h3>
                <span class="badge bg-<?php echo getStatusClass($record['status']); ?> badge-status">
                    <i class="fas <?php echo $record['status'] == 'stable' ? 'fa-check-circle' : 'fa-chart-line'; ?>"></i>
                    <?php echo getStatusText($record['status']); ?>
                </span>
            </div>
            <div class="p-4">
                <div class="info-row">
                    <div class="info-label"><i class="fas fa-id-card"></i> Record ID</div>
                    <div class="info-value"><?php echo htmlspecialchars($record['record_id']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label"><i class="fas fa-calendar-alt"></i> Date of Record</div>
                    <div class="info-value"><?php echo date('F j, Y', strtotime($record['record_date'])); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label"><i class="fas fa-user-md"></i> Doctor</div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($record['doctor_name'] ?? 'N/A'); ?>
                        <?php if($record['specialization']): ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars($record['specialization']); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label"><i class="fas fa-calendar-check"></i> Next Appointment</div>
                    <div class="info-value">
                        <?php if($record['next_appointment_date']): ?>
                            <i class="fas fa-calendar-day text-primary"></i> <?php echo date('F j, Y', strtotime($record['next_appointment_date'])); ?>
                        <?php else: ?>
                            <span class="text-muted"><i class="fas fa-check-circle text-success"></i> No follow-up scheduled - Patient stable</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Vital Signs Section -->
        <?php if($record['blood_pressure'] || $record['heart_rate'] || $record['temperature'] || $record['respiratory_rate'] || $record['oxygen_saturation']): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-heartbeat"></i> Vital Signs</h3>
            </div>
            <div class="p-4">
                <div class="row g-3">
                    <?php if($record['blood_pressure']): ?>
                    <div class="col-md-2 col-6">
                        <div class="vital-card">
                            <i class="fas fa-tachometer-alt fa-2x text-primary"></i>
                            <div class="vital-value"><?php echo htmlspecialchars($record['blood_pressure']); ?></div>
                            <div class="vital-label">Blood Pressure (mmHg)</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if($record['heart_rate']): ?>
                    <div class="col-md-2 col-6">
                        <div class="vital-card">
                            <i class="fas fa-heart fa-2x text-danger"></i>
                            <div class="vital-value"><?php echo htmlspecialchars($record['heart_rate']); ?></div>
                            <div class="vital-label">Heart Rate (bpm)</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if($record['temperature']): ?>
                    <div class="col-md-2 col-6">
                        <div class="vital-card">
                            <i class="fas fa-thermometer-half fa-2x text-warning"></i>
                            <div class="vital-value"><?php echo htmlspecialchars($record['temperature']); ?>°C</div>
                            <div class="vital-label">Temperature</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if($record['respiratory_rate']): ?>
                    <div class="col-md-2 col-6">
                        <div class="vital-card">
                            <i class="fas fa-lungs fa-2x text-info"></i>
                            <div class="vital-value"><?php echo htmlspecialchars($record['respiratory_rate']); ?></div>
                            <div class="vital-label">Respiratory Rate (/min)</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if($record['oxygen_saturation']): ?>
                    <div class="col-md-2 col-6">
                        <div class="vital-card">
                            <i class="fas fa-percent fa-2x text-success"></i>
                            <div class="vital-value"><?php echo htmlspecialchars($record['oxygen_saturation']); ?>%</div>
                            <div class="vital-label">Oxygen Saturation</div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Diagnosis & Prescription -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-stethoscope"></i> Diagnosis</h3>
                    </div>
                    <div class="p-4">
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($record['diagnosis'] ?: '<span class="text-muted"><i class="fas fa-info-circle"></i> No diagnosis recorded</span>')); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-prescription-bottle"></i> Prescription</h3>
                    </div>
                    <div class="p-4">
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($record['prescription'] ?: '<span class="text-muted"><i class="fas fa-info-circle"></i> No prescription recorded</span>')); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Additional Notes -->
        <?php if($record['notes']): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-pencil-alt"></i> Additional Notes</h3>
            </div>
            <div class="p-4">
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($record['notes'])); ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Patient Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-circle"></i> Personal Information</h3>
            </div>
            <div class="p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label"><i class="fas fa-id-card"></i> Patient ID</div>
                            <div class="info-value"><?php echo htmlspecialchars($record['patient_id']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label"><i class="fas fa-user"></i> Full Name</div>
                            <div class="info-value"><?php echo htmlspecialchars($record['patient_name']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label"><i class="fas fa-tint"></i> Blood Group</div>
                            <div class="info-value"><?php echo htmlspecialchars($record['blood_group'] ?? 'Not recorded'); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label"><i class="fas fa-chart-line"></i> BMI</div>
                            <div class="info-value">
                                <?php 
                                if($record['height'] && $record['weight']) {
                                    $bmi = $record['weight'] / (($record['height']/100) ** 2);
                                    echo number_format($bmi, 1);
                                    if($bmi < 18.5) echo ' (Underweight)';
                                    elseif($bmi < 25) echo ' (Normal)';
                                    elseif($bmi < 30) echo ' (Overweight)';
                                    else echo ' (Obese)';
                                } else {
                                    echo 'Not recorded';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if($record['allergies'] || $record['chronic_conditions']): ?>
                <div class="mt-4 pt-3 border-top">
                    <?php if($record['allergies']): ?>
                    <div class="mb-3">
                        <label class="fw-semibold mb-2"><i class="fas fa-allergies text-danger"></i> Allergies</label>
                        <div>
                            <?php foreach(explode(',', $record['allergies']) as $allergy): ?>
                                <span class="allergy-tag"><i class="fas fa-exclamation-circle"></i> <?php echo trim(htmlspecialchars($allergy)); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($record['chronic_conditions']): ?>
                    <div>
                        <label class="fw-semibold mb-2"><i class="fas fa-heartbeat text-warning"></i> Chronic Conditions</label>
                        <div>
                            <?php foreach(explode(',', $record['chronic_conditions']) as $condition): ?>
                                <span class="condition-tag"><i class="fas fa-chart-line"></i> <?php echo trim(htmlspecialchars($condition)); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if($record['emergency_contact_name']): ?>
                <div class="mt-4 pt-3 border-top">
                    <h5 class="fw-semibold mb-3"><i class="fas fa-phone-alt text-danger"></i> Emergency Contact</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="info-row">
                                <div class="info-label">Name</div>
                                <div class="info-value"><?php echo htmlspecialchars($record['emergency_contact_name']); ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-row">
                                <div class="info-label">Relationship</div>
                                <div class="info-value"><?php echo htmlspecialchars($record['emergency_contact_relationship']); ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-row">
                                <div class="info-label">Phone</div>
                                <div class="info-value"><?php echo htmlspecialchars($record['emergency_contact_phone']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Print/Download Buttons -->
        <div class="d-flex justify-content-end gap-3">
            <a href="records.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Records
            </a>
            <a href="print-record.php?id=<?php echo $record['id']; ?>" target="_blank" class="btn btn-primary">
                <i class="fas fa-file-pdf"></i> View Medical Report
            </a>
        </div>    
</main>
</body>
</html>