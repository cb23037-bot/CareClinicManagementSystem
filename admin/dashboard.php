<?php
require_once '../config/database.php';
redirectIfNotLoggedIn();
if(!hasRole('admin')) {
    header('Location: ../login.php');
    exit();
}

// Get statistics
$stats = [];
$stmt = $pdo->query("SELECT COUNT(*) as total FROM patients");
$stats['patients'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM doctors");
$stats['doctors'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM appointments");
$stats['appointments'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM medical_records");
$stats['records'] = $stmt->fetch()['total'];

// Get recent appointments
$recentAppointments = $pdo->query("
    SELECT a.*, p.full_name as patient_name, d.full_name as doctor_name 
    FROM appointments a
    JOIN patients pat ON a.patient_id = pat.id
    JOIN users p ON pat.user_id = p.id
    JOIN doctors doc ON a.doctor_id = doc.id
    JOIN users d ON doc.user_id = d.id
    ORDER BY a.created_at DESC LIMIT 5
")->fetchAll();

// Get monthly appointment data for chart
$monthlyData = [];
for($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthName = date('M', strtotime("-$i months"));
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM appointments WHERE DATE_FORMAT(app_date, '%Y-%m') = ?");
    $stmt->execute([$month]);
    $monthlyData[$monthName] = $stmt->fetch()['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareClinic - Admin Dashboard</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .badge-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
        }

        .table > :not(caption) > * > * {
            padding: 12px 16px;
            vertical-align: middle;
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
                <a href="dashboard.php" class="active">Dashboard</a>
                <a href="patients.php">Patients</a>
                <a href="doctors.php">Doctors</a>
                <a href="appointments.php">Appointments</a>
                <a href="records.php">Records</a>
            </div>

            <button class="logout-btn" onclick="window.location.href='../logout.php'">Logout</button>
        </nav>

        <h1 class="hero-title">Admin Dashboard</h1>
    </section>

    <main class="page">
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h3><i class="fas fa-users"></i> Total Patients</h3>
                    <div class="stat-number"><?php echo $stats['patients']; ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-user-plus"></i></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-info">
                    <h3><i class="fas fa-user-md"></i> Total Doctors</h3>
                    <div class="stat-number"><?php echo $stats['doctors']; ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-stethoscope"></i></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-info">
                    <h3><i class="fas fa-calendar-check"></i> Appointments</h3>
                    <div class="stat-number"><?php echo $stats['appointments']; ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-info">
                    <h3><i class="fas fa-notes-medical"></i> Medical Records</h3>
                    <div class="stat-number"><?php echo $stats['records']; ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-folder-open"></i></div>
            </div>
        </div>
        
        <!-- Charts and Recent Appointments -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-chart-line"></i> Appointment Statistics</h3>
                    </div>
                    <div class="p-4">
                        <canvas id="appointmentChart" height="250"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-clock"></i> Recent Appointments</h3>
                        <a href="appointments.php" class="btn btn-sm btn-outline-primary">View All <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recentAppointments as $apt): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($apt['patient_name']); ?></td>
                                    <td><?php echo htmlspecialchars($apt['doctor_name']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($apt['app_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $apt['status'] == 'completed' ? 'success' : ($apt['status'] == 'cancelled' ? 'danger' : 'warning'); ?> badge-status">
                                            <i class="fas <?php echo $apt['status'] == 'completed' ? 'fa-check-circle' : ($apt['status'] == 'cancelled' ? 'fa-times-circle' : 'fa-clock'); ?>"></i>
                                            <?php echo ucfirst($apt['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="patients.php" class="action-card">
                <div class="action-icon"><i class="fas fa-users"></i></div>
                <div class="action-title">Manage Patients</div>
                <div class="action-desc">View and manage patient records</div>
            </a>
            <a href="doctors.php" class="action-card">
                <div class="action-icon"><i class="fas fa-user-md"></i></div>
                <div class="action-title">Manage Doctors</div>
                <div class="action-desc">Add or remove doctors</div>
            </a>
            <a href="appointments.php" class="action-card">
                <div class="action-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="action-title">Manage Appointments</div>
                <div class="action-desc">View and update appointments</div>
            </a>
            <a href="records.php" class="action-card">
                <div class="action-icon"><i class="fas fa-notes-medical"></i></div>
                <div class="action-title">Medical Records</div>
                <div class="action-desc">View all medical records</div>
            </a>
        </div>
    </main>

    <script>
        const ctx = document.getElementById('appointmentChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_keys($monthlyData)); ?>,
                datasets: [{
                    label: 'Appointments',
                    data: <?php echo json_encode(array_values($monthlyData)); ?>,
                    borderColor: '#5864c7',
                    backgroundColor: 'rgba(88, 100, 199, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    </script>
</body>
</html>