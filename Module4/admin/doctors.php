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
            
            // Check if username exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if(!$stmt->fetch()) {
                // Insert user
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, full_name, phone, address) VALUES (?, ?, ?, 'doctor', ?, ?, ?)");
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

// Get all doctors
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
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow-lg fixed w-full z-50 top-0">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="flex items-center">
                        <img src="../logo.png" alt="Logo" class="h-8 w-8 mr-2">
                        <span class="font-bold text-xl text-indigo-600">CareClinic Admin</span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Welcome, <?php echo $_SESSION['full_name']; ?></span>
                    <a href="../logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 pt-20 pb-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Manage Doctors</h1>
            <button onclick="openAddModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition">
                + Add New Doctor
            </button>
        </div>
        
        <?php if(isset($_GET['msg'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                Doctor <?php echo $_GET['msg']; ?> successfully!
            </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Specialization</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Experience</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($doctors as $doctor): ?>
                        <tr>
                            <td class="px-6 py-4"><?php echo $doctor['doctor_id']; ?></td>
                            <td class="px-6 py-4 font-medium text-gray-900">Dr. <?php echo $doctor['full_name']; ?></td>
                            <td class="px-6 py-4"><?php echo $doctor['specialization']; ?></td>
                            <td class="px-6 py-4"><?php echo $doctor['experience_years']; ?> years</td>
                            <td class="px-6 py-4">RM <?php echo number_format($doctor['consultation_fee'], 2); ?></td>
                            <td class="px-6 py-4">
                                <button onclick="editDoctor(<?php echo htmlspecialchars(json_encode($doctor)); ?>)" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                <a href="?delete=<?php echo $doctor['user_id']; ?>" onclick="return confirm('Are you sure?')" class="text-red-600 hover:text-red-900">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="doctorModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-lg bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 id="modalTitle" class="text-xl font-bold text-gray-800">Add New Doctor</h3>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" id="action" name="action">
                <input type="hidden" id="user_id" name="user_id">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email *</label>
                        <input type="email" id="email" name="email" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Username *</label>
                        <input type="text" id="username" name="username" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div id="passwordDiv">
                        <label class="block text-sm font-medium text-gray-700">Password *</label>
                        <input type="password" id="password" name="password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Phone</label>
                        <input type="text" id="phone" name="phone" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Address</label>
                        <input type="text" id="address" name="address" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Specialization *</label>
                        <input type="text" id="specialization" name="specialization" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Qualification *</label>
                        <input type="text" id="qualification" name="qualification" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Experience (Years) *</label>
                        <input type="number" id="experience_years" name="experience_years" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Consultation Fee (RM) *</label>
                        <input type="number" step="0.01" id="consultation_fee" name="consultation_fee" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modalTitle').innerText = 'Add New Doctor';
            document.getElementById('action').value = 'add';
            document.getElementById('user_id').value = '';
            document.getElementById('passwordDiv').style.display = 'block';
            document.getElementById('password').required = true;
            document.getElementById('full_name').value = '';
            document.getElementById('email').value = '';
            document.getElementById('username').value = '';
            document.getElementById('phone').value = '';
            document.getElementById('address').value = '';
            document.getElementById('specialization').value = '';
            document.getElementById('qualification').value = '';
            document.getElementById('experience_years').value = '';
            document.getElementById('consultation_fee').value = '';
            document.getElementById('doctorModal').classList.remove('hidden');
        }
        
        function editDoctor(doctor) {
            document.getElementById('modalTitle').innerText = 'Edit Doctor';
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
            document.getElementById('doctorModal').classList.remove('hidden');
        }
        
        function closeModal() {
            document.getElementById('doctorModal').classList.add('hidden');
        }
    </script>
</body>
</html>