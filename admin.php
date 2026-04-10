<?php
require 'functions.php';
require_role('admin');

$patients = array_filter(load_users(), function ($user) {
    return $user['role'] === 'patient';
});

$selectedPatient = null;
if (isset($_GET['view_id'])) {
    $selectedId = (int)$_GET['view_id'];
    $patient = find_user_by_id($selectedId);
    if ($patient && $patient['role'] === 'patient') {
        $selectedPatient = $patient;
    }
}

if (isset($_GET['delete_id'])) {
    $deleteId = (int)$_GET['delete_id'];
    $patient = find_user_by_id($deleteId);

    if ($patient && $patient['role'] === 'patient') {
        delete_user_by_id($deleteId);
        set_flash('Patient record deleted successfully.');
        header('Location: admin.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Patient Management</title>
    <link rel="stylesheet" href="assets/prototype.css">
</head>
<body class="admin-page-body">
    <header class="admin-header">
        <div class="container topbar-pill admin-topbar">
            <a class="hero-brand" href="profile.php">
                <img class="hero-brand-logo" src="assets/careclinic-logo.png" alt="CareClinic">
            </a>
            <div class="nav-links">
                <a href="profile.php">My Profile</a>
            </div>
            <a class="btn-logout" href="logout.php">Log out</a>
        </div>
    </header>
    <main class="admin-page">
        <section class="admin-card">
            <div class="admin-heading">
                <h2>Patient Records</h2>
                <p>View, manage, and delete patient registrations.</p>
            </div>

            <?php if ($message = flash_message()): ?>
                <div class="alert"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($patients)): ?>
                            <tr><td colspan="6">No registered patients found.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($patients as $patient): ?>
                            <tr>
                                <td><?php echo $patient['id']; ?></td>
                                <td><?php echo htmlspecialchars($patient['name']); ?></td>
                                <td><?php echo htmlspecialchars($patient['email']); ?></td>
                                <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                                <td><?php echo htmlspecialchars($patient['address']); ?></td>
                                <td>
                                    <a class="action-link" href="admin.php?view_id=<?php echo $patient['id']; ?>">View</a>
                                    <a class="action-link danger" href="admin.php?delete_id=<?php echo $patient['id']; ?>" onclick="return confirm('Delete this patient record?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($selectedPatient): ?>
                <section class="admin-card" style="margin-top: 24px; padding: 24px;">
                    <h3>Patient Details</h3>
                    <div class="patient-details">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($selectedPatient['name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($selectedPatient['email']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($selectedPatient['phone']); ?></p>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($selectedPatient['address']); ?></p>
                        <p><strong>Gender:</strong> <?php echo htmlspecialchars($selectedPatient['gender'] ?: 'Not set'); ?></p>
                        <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($selectedPatient['dob'] ?: 'Not set'); ?></p>
                    </div>
                </section>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
