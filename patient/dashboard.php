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
    WHERE a.patient_id = ? AND a.app_date >= CURDATE()
    ORDER BY a.app_date ASC LIMIT 5
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

// Calculate BMI if height and weight available
$bmi = null;
$bmi_category = 'N/A';
if($patient['height'] && $patient['weight']) {
    $height_m = $patient['height'] / 100;
    $bmi = $patient['weight'] / ($height_m * $height_m);
    if($bmi < 18.5) $bmi_category = 'Underweight';
    elseif($bmi < 25) $bmi_category = 'Normal';
    elseif($bmi < 30) $bmi_category = 'Overweight';
    else $bmi_category = 'Obese';
}

function getStatusClass($status) {
    switch($status) {
        case 'confirmed': return 'success';
        case 'completed': return 'info';
        case 'cancelled': return 'danger';
        case 'scheduled': return 'warning';
        default: return 'secondary';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareClinic - Patient Dashboard</title>
    
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
            background: #ffffff;
        }

        .hero {
            background: linear-gradient(rgba(255,255,255,0.58), rgba(255,255,255,0.58)), url('background.jpg') center/cover no-repeat;
            min-height: 260px;
            border-bottom-left-radius: 46px;
            border-bottom-right-radius: 46px;
            padding: 14px 26px 36px;
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
            font-size: 66px;
            font-style: italic;
            font-weight: 300;
            margin: 48px 0 0;
        }

        .page {
            max-width: 1280px;
            margin: -28px auto 60px;
            padding: 0 18px;
            position: relative;
            z-index: 2;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
        }

        .stat-info h3 {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #1f2937;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            background: #eaf4ff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #5864c7;
        }

        .card {
            background: white;
            border: 1px solid #bdbdbd;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 30px;
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .action-card {
            background: white;
            border-radius: 14px;
            padding: 20px;
            text-align: center;
            border: 1px solid #bdbdbd;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            text-decoration: none;
            display: block;
        }

        .action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
        }

        .action-icon {
            width: 56px;
            height: 56px;
            background: #eaf4ff;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 28px;
            color: #5864c7;
        }

        .action-title {
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 6px;
        }

        .action-desc {
            font-size: 12px;
            color: #6b7280;
        }

        .appointment-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            border-bottom: 1px solid #efefef;
        }

        .appointment-item:last-child {
            border-bottom: none;
        }

        .appointment-date {
            background: #eaf4ff;
            border-radius: 12px;
            padding: 10px 15px;
            text-align: center;
            min-width: 70px;
        }

        .appointment-date .month {
            font-size: 11px;
            color: #5864c7;
            font-weight: 600;
        }

        .appointment-date .day {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
        }

        .badge-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
        }

        .health-tip {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 14px;
            padding: 25px;
            color: white;
        }

        @media (max-width: 900px) {
            .menu { gap: 16px; }
            .hero-title { font-size: 48px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .quick-actions { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 680px) {
            .nav { flex-direction: column; align-items: stretch; }
            .menu { justify-content: flex-start; }
            .hero-title { font-size: 38px; margin-top: 34px; }
            .stats-grid { grid-template-columns: 1fr; }
            .quick-actions { grid-template-columns: 1fr; }
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
                <a href="dashboard.php" class="active">Home</a>
                <a href="book-appointment.php">Book Appointment</a>
                <a href="index.php">My Appointment</a>
                <a href="records.php">My Medical Records</a>
                <a href="profile.php">Profile</a>
            </div>

            <button class="logout-btn" onclick="window.location.href='../logout.php'">Logout</button>
        </nav>

        <h1 class="hero-title">Patient Dashboard</h1>
    </section>

    <main class="page">
        <!-- Welcome Banner -->
        <div class="health-tip mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2 class="fs-3 fw-bold mb-2">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
                    <p class="mb-0 opacity-75">We're here to take care of your health. Your health is our priority.</p>
                </div>
                <a href="book-appointment.php" class="btn btn-light text-primary fw-semibold px-4 py-2 rounded-pill">
                    <i class="fas fa-plus-circle"></i> Book Appointment
                </a>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h3><i class="fas fa-calendar-alt"></i> Total Appointments</h3>
                    <div class="stat-number"><?php echo $total_appointments; ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-info">
                    <h3><i class="fas fa-notes-medical"></i> Medical Records</h3>
                    <div class="stat-number"><?php echo count($recent_records); ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-folder-open"></i></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-info">
                    <h3><i class="fas fa-clock"></i> Upcoming</h3>
                    <div class="stat-number"><?php echo count($upcoming_appointments); ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-info">
                    <h3><i class="fas fa-heartbeat"></i> Health Status</h3>
                    <div class="stat-number" style="font-size: 24px;"><?php echo $bmi_category; ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            </div>
        </div>
        
        <!-- Two Column Layout -->
        <div class="row g-4 mb-4">
            <!-- Upcoming Appointments -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-calendar-week"></i> Upcoming Appointments</h3>
                        <a href="appointments.php" class="btn btn-sm btn-outline-primary">View All <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="p-0">
                        <?php if(count($upcoming_appointments) > 0): ?>
                            <?php foreach($upcoming_appointments as $apt): ?>
                            <div class="appointment-item">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="appointment-date">
                                        <div class="month"><?php echo date('M', strtotime($apt['app_date'])); ?></div>
                                        <div class="day"><?php echo date('d', strtotime($apt['app_date'])); ?></div>
                                    </div>
                                    <div>
                                        <h5 class="fw-semibold mb-1">nbsp;<?php echo htmlspecialchars($apt['doctor_name']); ?></h5>
                                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($apt['specialization']); ?></p>
                                        <p class="text-muted small mt-1"><i class="far fa-clock"></i> <?php echo date('g:i A', strtotime($apt['app_time'])); ?></p>
                                        <?php if(!empty($apt['app_condition'])): ?>
                                            <p class="text-muted small mt-1"><i class="fas fa-stethoscope"></i> <strong>Reason:</strong> <?php echo htmlspecialchars(substr($apt['app_condition'], 0, 60)); ?></p>
                                        <?php endif; ?>                                    
                                    </div>
                                </div>
                                <div>
                                    <span class="badge bg-<?php echo getStatusClass($apt['status']); ?> badge-status">
                                        <i class="fas <?php echo $apt['status'] == 'confirmed' ? 'fa-check-circle' : 'fa-clock'; ?>"></i>
                                        <?php echo ucfirst($apt['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No upcoming appointments</p>
                                <a href="book-appointment.php" class="btn btn-sm btn-primary">Book one now <i class="fas fa-arrow-right"></i></a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Recent Medical Records -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-notes-medical"></i> Recent Medical Records</h3>
                        <a href="records.php" class="btn btn-sm btn-outline-primary">View All <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="p-0">
                        <?php if(count($recent_records) > 0): ?>
                            <?php foreach($recent_records as $record): ?>
                            <div class="appointment-item">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                                        <i class="fas fa-file-alt text-primary"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-semibold mb-1"><?php echo htmlspecialchars($record['condition_name']); ?></h5>
                                        <p class="text-muted small mb-0">
                                            <i class="fas fa-user-md">&nbsp;</i><?php echo htmlspecialchars($record['doctor_name'] ?? 'N/A'); ?>
                                        </p>
                                        <p class="text-muted small mt-1">
                                            <i class="far fa-calendar-alt"></i> <?php echo date('M j, Y', strtotime($record['record_date'])); ?>
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <?php echo getStatusBadge($record['status']); ?>
                                    <a href="record-detail.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-primary ms-2">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No medical records yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions Row -->
        <div class="quick-actions">
            <a href="book-appointment.php" class="action-card">
                <div class="action-icon"><i class="fas fa-plus-circle"></i></div>
                <div class="action-title">Book Appointment</div>
                <div class="action-desc">Schedule a new visit</div>
            </a>
            
            <a href="records.php" class="action-card">
                <div class="action-icon"><i class="fas fa-notes-medical"></i></div>
                <div class="action-title">Medical Records</div>
                <div class="action-desc">View your health history</div>
            </a>
            
            <a href="prescriptions.php" class="action-card">
                <div class="action-icon"><i class="fas fa-prescription-bottle"></i></div>
                <div class="action-title">Prescriptions</div>
                <div class="action-desc">View your medications</div>
            </a>
            
            <a href="profile.php" class="action-card">
                <div class="action-icon"><i class="fas fa-user-circle"></i></div>
                <div class="action-title">My Profile</div>
                <div class="action-desc">Update personal info</div>
            </a>
        </div>
        
        <!-- Health Tip -->
        <div class="health-tip">
            <div class="d-flex align-items-start gap-3">
                <div class="bg-white bg-opacity-20 rounded-circle p-3">
                    <i class="fas fa-lightbulb fa-2x"></i>
                </div>
                <div class="flex-grow-1">
                    <h4 class="fw-bold mb-2"><i class="fas fa-heart me-2"></i> Health Tip of the Day</h4>
                    <p class="mb-0 opacity-90">Stay hydrated! Drink at least 8 glasses of water daily to maintain optimal health and energy levels. Regular exercise and balanced diet are key to a healthy lifestyle.</p>
                </div>
                <div class="text-end">
                    <p class="small opacity-75 mb-0"><i class="far fa-calendar-alt"></i> <?php echo date('l, F j, Y'); ?></p>
                </div>
            </div>
        </div>
    </main>
</body>
</html>