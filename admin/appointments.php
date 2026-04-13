<?php
require_once '../config/database.php';
redirectIfNotLoggedIn();
if(!hasRole('admin')) {
    header('Location: ../login.php');
    exit();
}

// Handle status update
if(isset($_GET['update_status']) && isset($_GET['status'])) {
    $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
    $stmt->execute([$_GET['status'], $_GET['update_status']]);
    header('Location: appointments.php?msg=updated');
    exit();
}

// Get all appointments
$appointments = $pdo->query("
    SELECT a.*, 
           p.full_name as patient_name, pat.patient_id,
           d.full_name as doctor_name, doc.doctor_id, doc.specialization
    FROM appointments a
    JOIN patients pat ON a.patient_id = pat.id
    JOIN users p ON pat.user_id = p.id
    JOIN doctors doc ON a.doctor_id = doc.id
    JOIN users d ON doc.user_id = d.id
    ORDER BY a.app_date DESC, a.app_time DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareClinic - Manage Appointments</title>
    
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
        .alert-success { background: #d4edda; color: #155724; padding: 12px 20px; border-radius: 10px; margin: 20px; }
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
                <a href="patients.php">Patient Directory</a>
                <a href="appointments.php" class="active">Appointments</a>
                <a href="doctors.php">Doctor Rosters</a>
                <a href="records.php">Medical Records</a>
            </div>
            <button class="logout-btn" onclick="window.location.href='../logout.php'">Logout</button>
        </nav>
        <h1 class="hero-title">Manage Appointments</h1>
    </section>

    <main class="page">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-calendar-alt"></i> All Appointments</h2>
            </div>
            
            <?php if(isset($_GET['msg'])): ?>
                <div class="alert-success"><i class="fas fa-check-circle"></i> Appointment status updated successfully!</div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table id="appointmentsTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Appointment ID</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Date & Time</th>
                            <th>Condition</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($appointments as $apt): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($apt['appointment_id']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($apt['patient_name']); ?></strong><br>
                                <small class="text-muted">ID: <?php echo htmlspecialchars($apt['patient_id']); ?></small>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($apt['doctor_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($apt['specialization']); ?></small>
                            </td>
                            <td>
                                <?php echo date('M j, Y', strtotime($apt['app_date'])); ?><br>
                                <small class="text-muted"><i class="far fa-clock"></i> <?php echo date('g:i A', strtotime($apt['app_time'])); ?></small>
                            </td>
                            <td class="condition-preview" title="<?php echo htmlspecialchars($apt['app_condition'] ?? ''); ?>">
                                <?php if(!empty($apt['app_condition'])): ?>
                                    <i class="fas fa-stethoscope text-primary"></i>
                                    <?php echo htmlspecialchars(substr($apt['app_condition'], 0, 50)) . (strlen($apt['app_condition'] ?? '') > 50 ? '...' : ''); ?>
                                <?php else: ?>
                                    <span class="text-muted"><i class="fas fa-minus-circle"></i> Not specified</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $apt['status'] == 'completed' ? 'success' : ($apt['status'] == 'cancelled' ? 'danger' : ($apt['status'] == 'confirmed' ? 'primary' : 'warning')); ?> badge-status">
                                    <i class="fas <?php echo $apt['status'] == 'completed' ? 'fa-check-circle' : ($apt['status'] == 'cancelled' ? 'fa-times-circle' : 'fa-clock'); ?>"></i>
                                    <?php echo ucfirst($apt['status']); ?>
                                </span>
                            </td>
                            <td>
                                <select onchange="updateStatus(<?php echo $apt['id']; ?>, this.value)" class="form-select form-select-sm" style="width: auto; display: inline-block;">
                                    <option value="">Change Status</option>
                                    <option value="booked">Booked</option>
                                    <option value="done">Done</option>
                                </select>
                            </td>
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
            $('#appointmentsTable').DataTable({
                pageLength: 10,
                order: [[3, 'desc']],
                language: {
                    search: "<i class='fas fa-search'></i> Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ appointments",
                    paginate: { previous: "<i class='fas fa-chevron-left'></i>", next: "<i class='fas fa-chevron-right'></i>" }
                }
            });
        });
        
        function updateStatus(id, status) {
            if(status) {
                if(confirm('Change appointment status?')) {
                    window.location.href = `?update_status=${id}&status=${status}`;
                }
            }
        }
    </script>
</body>
</html>