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

// Get all patients with appointments to this doctor
$patients = $pdo->prepare("
    SELECT DISTINCT u.*, p.* 
    FROM users u 
    JOIN patients p ON u.id = p.user_id
    JOIN appointments a ON p.id = a.patient_id
    WHERE a.doctor_id = ?
    ORDER BY u.full_name
");
$patients->execute([$doctor['id']]);
$patients_list = $patients->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareClinic - My Patients</title>
    
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
            background: white;
        }

        .card-title {
            font-size: 20px;
            font-weight: 600;
            margin: 0;
        }

        .patient-avatar {
            width: 36px;
            height: 36px;
            background: #5864c7;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            font-weight: 600;
            margin-right: 10px;
        }

        .patient-cell {
            display: flex;
            align-items: center;
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

        .dataTables_wrapper {
            padding: 20px;
        }

        .table > :not(caption) > * > * {
            padding: 12px 16px;
            vertical-align: middle;
        }

        @media (max-width: 900px) {
            .menu { gap: 16px; }
            .hero-title { font-size: 48px; }
        }

        @media (max-width: 680px) {
            .nav { flex-direction: column; align-items: stretch; }
            .menu { justify-content: flex-start; }
            .hero-title { font-size: 38px; margin-top: 34px; }
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

        <h1 class="hero-title">My Patients</h1>
    </section>

    <main class="page">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Patient List</h2>
            </div>
            <div class="table-responsive">
                <table id="patientsTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Patient ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Blood Group</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($patients_list as $patient): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($patient['patient_id']); ?></td>
                            <td>
                                <div class="patient-cell">
                                    <div class="patient-avatar"><?php echo strtoupper(substr($patient['full_name'], 0, 1)); ?></div>
                                    <?php echo htmlspecialchars($patient['full_name']); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($patient['email']); ?></td>
                            <td><?php echo htmlspecialchars($patient['phone'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($patient['blood_group'] ?? 'N/A'); ?></td>
                            <td>
                                <a href="view-patient.php?id=<?php echo $patient['patient_id']; ?>" class="btn btn-sm btn-outline-info btn-icon" title="View Records">
                                    <i class="fas fa-eye"></i> 
                                </a>
                                <a href="add-record.php?patient_id=<?php echo $patient['id']; ?>" class="btn btn-sm btn-outline-success btn-icon" title="Add Record">
                                    <i class="fas fa-plus"></i> 
                                </a>
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
            $('#patientsTable').DataTable({
                pageLength: 10,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
                language: {
                    search: "<i class='fas fa-search'></i> Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ patients",
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