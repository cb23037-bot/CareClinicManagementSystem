<?php
require_once '../config/database.php';
redirectIfNotLoggedIn();
if(!hasRole('admin')) {
    header('Location: ../login.php');
    exit();
}

// Handle delete
if(isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'doctor'");
    $stmt->execute([$_GET['delete']]);
    header('Location: doctors.php?msg=deleted');
    exit();
}

// Handle add/edit
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['action'])) {
        if($_POST['action'] === 'add') {
            $full_name = sanitizeInput($_POST['full_name']);
            $email = sanitizeInput($_POST['email']);
            $username = sanitizeInput($_POST['username']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $phone = sanitizeInput($_POST['phone']);
            $address = sanitizeInput($_POST['address']);
            $specialization = sanitizeInput($_POST['specialization']);
            $qualification = sanitizeInput($_POST['qualification']);
            $experience_years = (int)$_POST['experience_years'];
            $consultation_fee = (float)$_POST['consultation_fee'];
            
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if(!$stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, full_name, phone, address) VALUES (?, ?, ?, 'doctor', ?, ?, ?)");
                if($stmt->execute([$username, $email, $password, $full_name, $phone, $address])) {
                    $user_id = $pdo->lastInsertId();
                    $doctor_id = generateUniqueId('DOC');
                    $stmt = $pdo->prepare("INSERT INTO doctors (user_id, doctor_id, specialization, qualification, experience_years, consultation_fee) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$user_id, $doctor_id, $specialization, $qualification, $experience_years, $consultation_fee]);
                    header('Location: doctors.php?msg=added');
                    exit();
                }
            }
        } elseif($_POST['action'] === 'edit') {
            $user_id = $_POST['user_id'];
            $full_name = sanitizeInput($_POST['full_name']);
            $email = sanitizeInput($_POST['email']);
            $phone = sanitizeInput($_POST['phone']);
            $address = sanitizeInput($_POST['address']);
            $specialization = sanitizeInput($_POST['specialization']);
            $qualification = sanitizeInput($_POST['qualification']);
            $experience_years = (int)$_POST['experience_years'];
            $consultation_fee = (float)$_POST['consultation_fee'];
            
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $phone, $address, $user_id]);
            
            $stmt = $pdo->prepare("UPDATE doctors SET specialization = ?, qualification = ?, experience_years = ?, consultation_fee = ? WHERE user_id = ?");
            $stmt->execute([$specialization, $qualification, $experience_years, $consultation_fee, $user_id]);
            header('Location: doctors.php?msg=updated');
            exit();
        }
    }
}

$doctors = $pdo->query("
    SELECT u.*, d.* 
    FROM users u 
    JOIN doctors d ON u.id = d.user_id 
    WHERE u.role = 'doctor'
    ORDER BY u.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareClinic - Manage Doctors</title>
    
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
        .card-header { padding: 20px 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; background: white; }
        .card-title { font-size: 20px; font-weight: 600; margin: 0; }
        .card-title i { margin-right: 8px; color: #5864c7; }
        .btn-primary-custom { background: #5864c7; color: white; padding: 8px 20px; border-radius: 12px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; border: none; }
        .btn-primary-custom:hover { background: #3f5efb; }
        .dataTables_wrapper { padding: 20px; }
        .table > :not(caption) > * > * { padding: 12px 16px; vertical-align: middle; }
        .alert-success { background: #d4edda; color: #155724; padding: 12px 20px; border-radius: 10px; margin: 20px; }
        .modal-content { border-radius: 14px; }
        @media (max-width: 900px) { .menu { gap: 16px; } .hero-title { font-size: 36px; } }
        @media (max-width: 680px) { .nav { flex-direction: column; align-items: stretch; } .menu { justify-content: flex-start; } .hero-title { font-size: 28px; margin-top: 34px; } }
    </style>
</head>
<body>
    <section class="hero">
        <nav class="nav">
            <div class="brand">
                <img src="CareClinicLogo.jpeg" alt="CareClinic" class="logo" >
                <small>CareClinic</small>
            </div>
            <div class="menu">
                <a href="dashboard.php">Dashboard</a>
                <a href="patients.php">Patients</a>
                <a href="doctors.php" class="active">Doctors</a>
                <a href="appointments.php">Appointments</a>
                <a href="records.php">Records</a>
            </div>
            <button class="logout-btn" onclick="window.location.href='../logout.php'">Logout</button>
        </nav>
        <h1 class="hero-title">Manage Doctors</h1>
    </section>

    <main class="page">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-user-md"></i> Doctor List</h2>
                <button onclick="openAddModal()" class="btn-primary-custom"><i class="fas fa-plus"></i> Add New Doctor</button>
            </div>
            
            <?php if(isset($_GET['msg'])): ?>
                <div class="alert-success"><i class="fas fa-check-circle"></i> Doctor <?php echo $_GET['msg']; ?> successfully!</div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table id="doctorsTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Doctor ID</th>
                            <th>Name</th>
                            <th>Specialization</th>
                            <th>Experience</th>
                            <th>Fee</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($doctors as $doctor): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($doctor['doctor_id']); ?></td>
                            <td><strong><?php echo htmlspecialchars($doctor['full_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($doctor['specialization']); ?></td>
                            <td><?php echo $doctor['experience_years']; ?> years</td>
                            <td>RM <?php echo number_format($doctor['consultation_fee'], 2); ?></td>
                            <td>
                                <button onclick='editDoctor(<?php echo json_encode($doctor); ?>)' class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-edit"></i> Edit</button>
                                <a href="?delete=<?php echo $doctor['user_id']; ?>" onclick="return confirm('Are you sure?')" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash-alt"></i> Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal -->
    <div class="modal fade" id="doctorModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle"><i class="fas fa-user-md"></i> Add New Doctor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="action" name="action">
                        <input type="hidden" id="user_id" name="user_id">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Full Name *</label><input type="text" id="full_name" name="full_name" required class="form-control"></div>
                            <div class="col-md-6"><label class="form-label">Email *</label><input type="email" id="email" name="email" required class="form-control"></div>
                            <div class="col-md-6"><label class="form-label">Username *</label><input type="text" id="username" name="username" required class="form-control"></div>
                            <div class="col-md-6" id="passwordDiv"><label class="form-label">Password *</label><input type="password" id="password" name="password" class="form-control"></div>
                            <div class="col-md-6"><label class="form-label">Phone</label><input type="text" id="phone" name="phone" class="form-control"></div>
                            <div class="col-md-6"><label class="form-label">Address</label><input type="text" id="address" name="address" class="form-control"></div>
                            <div class="col-md-6"><label class="form-label">Specialization *</label><input type="text" id="specialization" name="specialization" required class="form-control"></div>
                            <div class="col-md-6"><label class="form-label">Qualification *</label><input type="text" id="qualification" name="qualification" required class="form-control"></div>
                            <div class="col-md-6"><label class="form-label">Experience (Years) *</label><input type="number" id="experience_years" name="experience_years" required class="form-control"></div>
                            <div class="col-md-6"><label class="form-label">Consultation Fee (RM) *</label><input type="number" step="0.01" id="consultation_fee" name="consultation_fee" required class="form-control"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Doctor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#doctorsTable').DataTable({
                pageLength: 10,
                language: {
                    search: "<i class='fas fa-search'></i> Search:",
                    lengthMenu: "Show _MENU_ entries",
                    paginate: { previous: "<i class='fas fa-chevron-left'></i>", next: "<i class='fas fa-chevron-right'></i>" }
                }
            });
        });
        
        let doctorModal = new bootstrap.Modal(document.getElementById('doctorModal'));
        
        function openAddModal() {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-md"></i> Add New Doctor';
            document.getElementById('action').value = 'add';
            document.getElementById('user_id').value = '';
            document.getElementById('passwordDiv').style.display = 'block';
            document.getElementById('password').required = true;
            document.querySelector('form').reset();
            doctorModal.show();
        }
        
        function editDoctor(doctor) {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Doctor';
            document.getElementById('action').value = 'edit';
            document.getElementById('user_id').value = doctor.user_id;
            document.getElementById('passwordDiv').style.display = 'none';
            document.getElementById('password').required = false;
            document.getElementById('full_name').value = doctor.full_name;
            document.getElementById('email').value = doctor.email;
            document.getElementById('username').value = doctor.username;
            document.getElementById('phone').value = doctor.phone;
            document.getElementById('address').value = doctor.address;
            document.getElementById('specialization').value = doctor.specialization;
            document.getElementById('qualification').value = doctor.qualification;
            document.getElementById('experience_years').value = doctor.experience_years;
            document.getElementById('consultation_fee').value = doctor.consultation_fee;
            doctorModal.show();
        }
    </script>
</body>
</html>