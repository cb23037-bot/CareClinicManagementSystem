<?php
require_once '../config/database.php';
redirectIfNotLoggedIn();
if(!hasRole('admin')) {
    header('Location: ../login.php');
    exit();
}

$records = $pdo->query("
    SELECT mr.*, 
           p.full_name as patient_name, pat.patient_id,
           d.full_name as doctor_name
    FROM medical_records mr
    JOIN patients pat ON mr.patient_id = pat.id
    JOIN users p ON pat.user_id = p.id
    LEFT JOIN doctors doc ON mr.doctor_id = doc.id
    LEFT JOIN users d ON doc.user_id = d.id
    ORDER BY mr.record_date DESC
")->fetchAll();

function getStatusClass($status) {
    switch($status) {
        case 'stable': return 'success';
        case 'recovering': return 'info';
        case 'under_observation': return 'warning';
        case 'critical': return 'danger';
        default: return 'secondary';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareClinic - Medical Records</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="icon" type="image/x-icon" href="CareClinicLogo.jpeg">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; background: #ffffff; }
        .hero {
            background: linear-gradient(rgba(255,255,255,0.58), rgba(255,255,255,0.58)), url('background.jpg') center/cover no-repeat;
            min-height: 220px;
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
        .hero-title { text-align: center; color: rgba(0,0,0,0.75); font-size: 48px; font-style: italic; font-weight: 300; margin: 30px 0 0; }
        .page { max-width: 1280px; margin: -28px auto 60px; padding: 0 18px; position: relative; z-index: 2; }
        .card { background: white; border: 1px solid #bdbdbd; box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08); border-radius: 14px; overflow: hidden; }
        .card-header { padding: 20px 24px; border-bottom: 1px solid #e5e7eb; background: white; }
        .card-title { font-size: 20px; font-weight: 600; margin: 0; }
        .card-title i { margin-right: 8px; color: #5864c7; }
        .dataTables_wrapper { padding: 20px; }
        .table > :not(caption) > * > * { padding: 12px 16px; vertical-align: middle; }
        .badge-status { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 500; }
        .vital-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            border-left: 4px solid #5864c7;
            transition: transform 0.2s;
        }
        
        .vital-card:hover {
            transform: translateY(-2px);
        }
        
        .vital-value {
            font-size: 22px;
            font-weight: bold;
            color: #5864c7;
            margin: 8px 0;
        }
        
        .vital-label {
            font-size: 11px;
            color: #6b7280;
            margin-top: 5px;
        }
        @media (max-width: 900px) { .menu { gap: 16px; } .hero-title { font-size: 36px; } }
        @media (max-width: 680px) { .nav { flex-direction: column; align-items: stretch; } .menu { justify-content: flex-start; } .hero-title { font-size: 28px; margin-top: 34px; } }
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
                <a href="patients.php">Patients</a>
                <a href="doctors.php">Doctors</a>
                <a href="appointments.php">Appointments</a>
                <a href="records.php" class="active">Records</a>
            </div>
            <button class="logout-btn" onclick="window.location.href='../logout.php'">Logout</button>
        </nav>
        <h1 class="hero-title">Medical Records</h1>
    </section>

    <main class="page">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-notes-medical"></i> All Medical Records</h2>
            </div>
            <div class="table-responsive">
                <table id="recordsTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Condition</th>
                            <th>Doctor</th>
                            <th>Record Date</th>
                            <th>Status</th>
                            <th>Next Appointment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($records as $record): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($record['patient_name']); ?></strong><br>
                                <small class="text-muted">ID: <?php echo htmlspecialchars($record['patient_id']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($record['condition_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['doctor_name'] ?? 'N/A'); ?></td>
                            <td><?php echo date('M j, Y', strtotime($record['record_date'])); ?></td>
                            <td>
                                <span class="badge bg-<?php echo getStatusClass($record['status']); ?> badge-status">
                                    <i class="fas <?php echo $record['status'] == 'stable' ? 'fa-check-circle' : ($record['status'] == 'critical' ? 'fa-exclamation-triangle' : 'fa-chart-line'); ?>"></i>
                                    <?php echo ucfirst(str_replace('_', ' ', $record['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo $record['next_appointment_date'] ? date('M j, Y', strtotime($record['next_appointment_date'])) : '<span class="text-muted"><i class="fas fa-minus"></i> No follow-up</span>'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#recordsTable').DataTable({
                pageLength: 10,
                order: [[4, 'desc']],
                language: {
                    search: "<i class='fas fa-search'></i> Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ records",
                    paginate: { previous: "<i class='fas fa-chevron-left'></i>", next: "<i class='fas fa-chevron-right'></i>" }
                }
            });
        });
    </script>
</body>
</html>