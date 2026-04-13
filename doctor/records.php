<?php
require_once '../config/database.php';
redirectIfNotLoggedIn();
if(!hasRole('doctor')) {
    header('Location: ../login.php');
    exit();
}

// Get doctor info
$stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$doctor = $stmt->fetch();

// Handle delete
if(isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM medical_records WHERE id = ? AND doctor_id = ?");
    $stmt->execute([$_GET['delete'], $doctor['id']]);
    header('Location: records.php?msg=deleted');
    exit();
}

// Get all medical records for this doctor's patients
$records = $pdo->prepare("
    SELECT mr.*, p.full_name as patient_name, pat.patient_id
    FROM medical_records mr
    JOIN patients pat ON mr.patient_id = pat.id
    JOIN users p ON pat.user_id = p.id
    WHERE mr.doctor_id = ?
    ORDER BY mr.record_date DESC
");
$records->execute([$doctor['id']]);
$records_list = $records->fetchAll();

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
    <title>CareClinic - Medical Records</title>
    
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
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            background: white;
        }

        .card-title {
            font-size: 20px;
            font-weight: 600;
            margin: 0;
        }

        .dataTables_wrapper {
            padding: 20px;
        }

        .table {
            margin-bottom: 0;
        }

        .table > :not(caption) > * > * {
            padding: 12px 16px;
        }

        .btn-icon {
            padding: 6px 10px;
            margin: 0 3px;
            border-radius: 8px;
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-icon i {
            font-size: 13px;
        }

        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        @media (max-width: 900px) {
            .menu { gap: 16px; }
            .hero-title { font-size: 48px; }
        }

        @media (max-width: 680px) {
            .nav { flex-direction: column; align-items: stretch; }
            .menu { justify-content: flex-start; }
            .hero-title { font-size: 38px; margin-top: 34px; }
            .dataTables_wrapper {
                padding: 10px;
                overflow-x: auto;
            }
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
                <a href="patients.php">My Patients</a>
                <a href="appointments.php">My Appointment</a>
                <a href="schedule.php">Schedule Availability</a>
                <a href="records.php" class="active">Medical Records</a>
                <a href="profile.php">Profile</a>
            </div>

            <button class="logout-btn" onclick="window.location.href='../logout.php'">Logout</button>
        </nav>

        <h1 class="hero-title">Medical Records</h1>
    </section>

    <main class="page">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">All Medical Records</h2>
                <a href="add-record.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Record
                </a>
            </div>
            
            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success m-3">
                    <i class="fas fa-check-circle"></i> Record <?php echo htmlspecialchars($_GET['msg']); ?> successfully!
                </div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table id="recordsTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Condition</th>
                            <th>Record Date</th>
                            <th>Status</th>
                            <th>Next Appointment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($records_list) > 0): ?>
                            <?php foreach($records_list as $record): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($record['patient_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($record['patient_id']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($record['condition_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($record['record_date'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo getStatusClass($record['status']); ?> badge-status">
                                        <?php echo getStatusText($record['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $record['next_appointment_date'] ? date('M d, Y', strtotime($record['next_appointment_date'])) : '<span class="text-muted"><i class="fas fa-minus"></i> No follow-up</span>'; ?></td>
                                <td>
                                    <a href="edit-record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-primary btn-icon" title="Edit">
                                        <i class="fas fa-edit"></i> 
                                    </a>
                                    <a href="?delete=<?php echo $record['id']; ?>" onclick="return confirm('Are you sure?')" class="btn btn-sm btn-outline-danger btn-icon" title="Delete">
                                        <i class="fas fa-trash-alt"></i> 
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
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
            $('#recordsTable').DataTable({
                pageLength: 10,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
                order: [[3, 'desc']],
                language: {
                    search: "<i class='fas fa-search'></i> Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    paginate: {
                        previous: "<i class='fas fa-chevron-left'></i>",
                        next: "<i class='fas fa-chevron-right'></i>"
                    }
                }
            });
        });
    </script>
</body>
</html>