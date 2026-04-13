<?php
require_once '../config/database.php';
redirectIfNotLoggedIn();
if(!hasRole('doctor')) {
    header('Location: ../login.php');
    exit();
}

$patient_id = $_GET['id'] ?? 0;

// Get patient info
$stmt = $pdo->prepare("
    SELECT u.*, p.* 
    FROM users u 
    JOIN patients p ON u.id = p.user_id 
    WHERE p.patient_id = ?
");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch();

if(!$patient) {
    header('Location: patients.php');
    exit();
}

// Get medical records
$stmt = $pdo->prepare("
    SELECT mr.*, d.full_name as doctor_name
    FROM medical_records mr
    LEFT JOIN doctors doc ON mr.doctor_id = doc.id
    LEFT JOIN users d ON doc.user_id = d.id
    WHERE mr.patient_id = ?
    ORDER BY mr.record_date DESC
");
$stmt->execute([$patient['id']]);
$records = $stmt->fetchAll();

// Get appointments
$stmt = $pdo->prepare("
    SELECT a.*, d.full_name as doctor_name
    FROM appointments a
    JOIN doctors doc ON a.doctor_id = doc.id
    JOIN users d ON doc.user_id = d.id
    WHERE a.patient_id = ?
    ORDER BY a.app_date DESC
");
$stmt->execute([$patient['id']]);
$appointments = $stmt->fetchAll();

function getStatusClass($status) {
    switch($status) {
        case 'stable': return 'success';
        case 'recovering': return 'info';
        case 'under_observation': return 'warning';
        case 'critical': return 'danger';
        case 'completed': return 'success';
        case 'scheduled': return 'primary';
        case 'cancelled': return 'danger';
        case 'confirmed': return 'success';
        default: return 'secondary';
    }
}

function getStatusText($status) {
    switch($status) {
        case 'stable': return 'Stable';
        case 'recovering': return 'Recovering';
        case 'under_observation': return 'Under Observation';
        case 'critical': return 'Critical';
        case 'completed': return 'Completed';
        case 'scheduled': return 'Scheduled';
        case 'cancelled': return 'Cancelled';
        case 'confirmed': return 'Confirmed';
        default: return ucfirst($status);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareClinic - Patient Details</title>
    
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

        .patient-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            padding: 25px;
        }

        .patient-avatar-large {
            width: 80px;
            height: 80px;
            background: #5864c7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            font-weight: 600;
        }

        .patient-info h2 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            padding: 20px 25px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
        }

        .info-item label {
            font-size: 12px;
            color: #6b7280;
            display: block;
            margin-bottom: 4px;
        }

        .info-item .value {
            font-size: 14px;
            font-weight: 500;
        }

        .btn-primary-custom {
            background: #5864c7;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary-custom:hover {
            background: #3f5efb;
            color: white;
        }

        .dataTables_wrapper {
            padding: 20px;
        }

        .table > :not(caption) > * > * {
            padding: 12px 16px;
            vertical-align: middle;
        }

        .badge-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
        }

        .btn-icon {
            padding: 6px 12px;
            margin: 0 3px;
            border-radius: 8px;
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        @media (max-width: 900px) {
            .menu { gap: 16px; }
            .hero-title { font-size: 48px; }
        }

        @media (max-width: 680px) {
            .nav { flex-direction: column; align-items: stretch; }
            .menu { justify-content: flex-start; }
            .hero-title { font-size: 38px; margin-top: 34px; }
            .info-grid { grid-template-columns: 1fr; }
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
                <a href="dashboard.php">Dashboard</a>
                <a href="patients.php" class="active">My Patients</a>
                <a href="appointments.php">My Appointment</a>
                <a href="schedule.php">Schedule Availability</a>
                <a href="records.php">Medical Records</a>
                <a href="profile.php">Profile</a>
            </div>

            <button class="logout-btn" onclick="window.location.href='../logout.php'">Logout</button>
        </nav>

        <h1 class="hero-title">Patient Details</h1>
    </section>

    <main class="page">
        <!-- Patient Info Card -->
        <div class="card">
            <div class="patient-header">
                <div style="display: flex; gap: 20px; align-items: center;">
                    <div class="patient-avatar-large">
                        <?php echo strtoupper(substr($patient['full_name'], 0, 2)); ?>
                    </div>
                    <div class="patient-info">
                        <h2><?php echo htmlspecialchars($patient['full_name']); ?></h2>
                        <p style="color: #6b7280; margin: 0;">
                            <i class="fas fa-id-card"></i> Patient ID: <?php echo htmlspecialchars($patient['patient_id']); ?>
                        </p>
                    </div>
                </div>
                <a href="add-record.php?patient_id=<?php echo $patient['id']; ?>" class="btn-primary-custom">
                    <i class="fas fa-plus"></i> Add Medical Record
                </a>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <div class="value"><?php echo htmlspecialchars($patient['email']); ?></div>
                </div>
                <div class="info-item">
                    <label><i class="fas fa-phone"></i> Phone</label>
                    <div class="value"><?php echo htmlspecialchars($patient['phone'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-item">
                    <label><i class="fas fa-venus-mars"></i> Gender</label>
                    <div class="value"><?php echo htmlspecialchars($patient['gender'] ?? 'Not specified'); ?></div>
                </div>
                <div class="info-item">
                    <label><i class="fas fa-tint"></i> Blood Group</label>
                    <div class="value"><?php echo htmlspecialchars($patient['blood_group'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-item">
                    <label><i class="fas fa-birthday-cake"></i> Date of Birth</label>
                    <div class="value"><?php echo htmlspecialchars($patient['dob'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-item">
                    <label><i class="fas fa-map-marker-alt"></i> Address</label>
                    <div class="value"><?php echo htmlspecialchars($patient['address'] ?? 'N/A'); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Medical Records Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-notes-medical"></i> Medical Records</h3>
            </div>
            <div class="table-responsive">
                <table id="recordsTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Condition</th>
                            <th>Doctor</th>
                            <th>Diagnosis</th>
                            <th>Prescription</th>
                            <th>Vitals</th>
                            <th>Status</th>
                            <th>Next Appointment</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($records) > 0): ?>
                            <?php foreach($records as $record): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($record['record_date'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($record['condition_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($record['doctor_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars(substr($record['diagnosis'], 0, 50)) . (strlen($record['diagnosis']) > 50 ? '...' : ''); ?></td>
                                <td><?php echo htmlspecialchars(substr($record['prescription'], 0, 50)) . (strlen($record['prescription']) > 50 ? '...' : ''); ?></td>
                                <td style="font-size: 12px;">
                                    <?php if($record['blood_pressure'] || $record['heart_rate'] || $record['temperature']): ?>
                                        <div class="vitals-display">
                                            <?php if($record['blood_pressure']): ?>
                                                <span class="badge bg-light text-dark me-1"><i class="fas fa-tachometer-alt text-primary"></i> BP: <?php echo $record['blood_pressure']; ?></span>
                                            <?php endif; ?>
                                            <?php if($record['heart_rate']): ?>
                                                <span class="badge bg-light text-dark me-1"><i class="fas fa-heart text-danger"></i> HR: <?php echo $record['heart_rate']; ?></span>
                                            <?php endif; ?>
                                            <?php if($record['temperature']): ?>
                                                <span class="badge bg-light text-dark me-1"><i class="fas fa-thermometer-half text-warning"></i> Temp: <?php echo $record['temperature']; ?>°C</span>
                                            <?php endif; ?>
                                            <?php if($record['respiratory_rate']): ?>
                                                <span class="badge bg-light text-dark me-1"><i class="fas fa-lungs text-info"></i> RR: <?php echo $record['respiratory_rate']; ?></span>
                                            <?php endif; ?>
                                            <?php if($record['oxygen_saturation']): ?>
                                                <span class="badge bg-light text-dark"><i class="fas fa-percent text-success"></i> SpO2: <?php echo $record['oxygen_saturation']; ?>%</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>                                
                                <td>
                                    <span class="badge bg-<?php echo getStatusClass($record['status']); ?> badge-status">
                                        <i class="fas <?php echo $record['status'] == 'stable' ? 'fa-check-circle' : ($record['status'] == 'critical' ? 'fa-exclamation-triangle' : 'fa-chart-line'); ?>"></i>
                                        <?php echo getStatusText($record['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $record['next_appointment_date'] ? date('M d, Y', strtotime($record['next_appointment_date'])) : '<span class="text-muted"><i class="fas fa-minus"></i> No follow-up</span>'; ?></td>
                                <td>
                                    <a href="edit-record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-primary btn-icon" title="Edit Record">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-folder-open"></i> No medical records found
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Appointment History Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calendar-alt"></i> Appointment History</h3>
            </div>
            <div class="table-responsive">
                <table id="appointmentsTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Doctor</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($appointments) > 0): ?>
                            <?php foreach($appointments as $apt): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($apt['app_date'])); ?></td>
                                <td><i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($apt['app_time'])); ?></td>
                                <td><?php echo htmlspecialchars($apt['doctor_name']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo getStatusClass($apt['status']); ?> badge-status">
                                        <i class="fas <?php echo $apt['status'] == 'completed' ? 'fa-check-circle' : ($apt['status'] == 'cancelled' ? 'fa-times-circle' : 'fa-calendar-check'); ?>"></i>
                                        <?php echo ucfirst($apt['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    <i class="fas fa-calendar-times"></i> No appointment history found
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
<script>
    $(document).ready(function() {
        // Initialize DataTable for Medical Records only if there are actual records
        var recordsTable = $('#recordsTable');
        var recordsTbody = recordsTable.find('tbody');
        var hasRecordsData = false;
        
        // Check if there are real data rows (not the colspan "no records" row)
        recordsTbody.find('tr').each(function() {
            if ($(this).find('td[colspan]').length === 0) {
                hasRecordsData = true;
            }
        });
        
        if (hasRecordsData) {
            $('#recordsTable').DataTable({
                pageLength: 10,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
                order: [[0, 'desc']],
                language: {
                    search: "<i class='fas fa-search'></i> Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ records",
                    paginate: {
                        previous: "<i class='fas fa-chevron-left'></i>",
                        next: "<i class='fas fa-chevron-right'></i>"
                    }
                }
            });
        }
        
        // Initialize DataTable for Appointments only if there are actual appointments
        var appointmentsTable = $('#appointmentsTable');
        var appointmentsTbody = appointmentsTable.find('tbody');
        var hasAppointmentsData = false;
        
        appointmentsTbody.find('tr').each(function() {
            if ($(this).find('td[colspan]').length === 0) {
                hasAppointmentsData = true;
            }
        });
        
        if (hasAppointmentsData) {
            $('#appointmentsTable').DataTable({
                pageLength: 10,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
                order: [[0, 'desc']],
                language: {
                    search: "<i class='fas fa-search'></i> Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ appointments",
                    paginate: {
                        previous: "<i class='fas fa-chevron-left'></i>",
                        next: "<i class='fas fa-chevron-right'></i>"
                    }
                }
            });
        }
    });
</script>
</body>
</html>