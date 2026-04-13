<?php
require_once '../config/database.php';
redirectIfNotLoggedIn();
if(!hasRole('doctor')) {
    header('Location: ../login.php');
    exit();
}

// Get doctor info
$stmt = $pdo->prepare("
    SELECT u.*, d.* 
    FROM users u 
    JOIN doctors d ON u.id = d.user_id 
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$doctor = $stmt->fetch();

// Get statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM appointments WHERE doctor_id = ?");
$stmt->execute([$doctor['id']]);
$total_appointments = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM appointments WHERE doctor_id = ? AND app_date = CURDATE()");
$stmt->execute([$doctor['id']]);
$today_appointments = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM medical_records WHERE doctor_id = ?");
$stmt->execute([$doctor['id']]);
$total_records = $stmt->fetch()['total'];

// Get today's appointments
$stmt = $pdo->prepare("
    SELECT a.*, p.full_name as patient_name, pat.patient_id, pat.blood_group
    FROM appointments a
    JOIN patients pat ON a.patient_id = pat.id
    JOIN users p ON pat.user_id = p.id
    WHERE a.doctor_id = ? AND a.app_date = CURDATE()
    ORDER BY a.app_time ASC
");
$stmt->execute([$doctor['id']]);
$today_appointments_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareClinic - Doctor Dashboard</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
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

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .action-card {
            background: white;
            border-radius: 14px;
            padding: 25px;
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
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 6px;
        }

        .action-desc {
            font-size: 13px;
            color: #6b7280;
        }

        .btn-icon {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .table > :not(caption) > * > * {
            padding: 12px 16px;
            vertical-align: middle;
        }

        .patient-avatar {
            width: 32px;
            height: 32px;
            background: #5864c7;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: 600;
            margin-right: 10px;
        }

        .patient-cell {
            display: flex;
            align-items: center;
        }

        .badge-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
        }

        @media (max-width: 900px) {
            .menu { gap: 16px; }
            .hero-title { font-size: 48px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .quick-actions { grid-template-columns: 1fr; }
        }

        @media (max-width: 680px) {
            .nav { flex-direction: column; align-items: stretch; }
            .menu { justify-content: flex-start; }
            .hero-title { font-size: 38px; margin-top: 34px; }
            .stats-grid { grid-template-columns: 1fr; }
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
                <a href="dashboard.php" class="active">Dashboard</a>
                <a href="patients.php">My Patients</a>
                <a href="appointments.php">My Appointment</a>
                <a href="schedule.php">Schedule Availability</a>
                <a href="records.php">Medical Records</a>
                <a href="profile.php">Profile</a>
            </div>

            <button class="logout-btn" onclick="window.location.href='../logout.php'">Logout</button>
        </nav>

        <h1 class="hero-title">Doctor Dashboard</h1>
    </section>

    <main class="page">
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Appointments</h3>
                    <div class="stat-number"><?php echo $total_appointments; ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Today's Appointments</h3>
                    <div class="stat-number"><?php echo $today_appointments; ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Medical Records</h3>
                    <div class="stat-number"><?php echo $total_records; ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-notes-medical"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Specialization</h3>
                    <div class="stat-number" style="font-size: 18px;"><?php echo htmlspecialchars($doctor['specialization']); ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-stethoscope"></i></div>
            </div>
        </div>

        <!-- Today's Appointments Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calendar-day"></i> Today's Appointments</h3>
                <a href="appointments.php" class="btn btn-sm btn-outline-primary">View All <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Patient Name</th>
                            <th>Patient ID</th>
                            <th>Blood Group</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($today_appointments_list) > 0): ?>
                            <?php foreach($today_appointments_list as $apt): ?>
                            <tr>
                                <td><strong><?php echo date('g:i A', strtotime($apt['app_time'])); ?></strong></td>
                                <td>
                                    <div class="patient-cell">
                                        <div class="patient-avatar"><?php echo strtoupper(substr($apt['patient_name'], 0, 1)); ?></div>
                                        <?php echo htmlspecialchars($apt['patient_name']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($apt['patient_id']); ?></td>
                                <td><?php echo htmlspecialchars($apt['blood_group'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $apt['status'] == 'confirmed' ? 'success' : 'warning'; ?> badge-status">
                                        <i class="fas <?php echo $apt['status'] == 'confirmed' ? 'fa-check-circle' : 'fa-clock'; ?>"></i>
                                        <?php echo ucfirst($apt['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="view-patient.php?id=<?php echo $apt['patient_id']; ?>" class="btn btn-sm btn-outline-info btn-icon" title="View Records">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fas fa-smile-wink"></i> No appointments scheduled for today. Enjoy your day!
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="patients.php" class="action-card">
                <div class="action-icon"><i class="fas fa-users"></i></div>
                <div class="action-title">My Patients</div>
                <div class="action-desc">View and manage patient list</div>
            </a>
            <a href="records.php" class="action-card">
                <div class="action-icon"><i class="fas fa-notes-medical"></i></div>
                <div class="action-title">Medical Records</div>
                <div class="action-desc">Add and manage patient records</div>
            </a>
        </div>
    </main>
</body>
</html>