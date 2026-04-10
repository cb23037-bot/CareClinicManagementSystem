<?php
require_once 'functions.php';
require_login();

$user = current_user();
$currentRole = $user['role'] ?? 'patient';

if (isset($requiredHomeRole) && $requiredHomeRole !== $currentRole) {
    header('Location: ' . home_path_for_role($currentRole));
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user['name'] = sanitize($_POST['name'] ?? $user['name']);
    $user['phone'] = sanitize($_POST['phone'] ?? $user['phone']);
    $user['address'] = sanitize($_POST['address'] ?? $user['address']);
    $user['gender'] = sanitize($_POST['gender'] ?? $user['gender']);
    $user['dob'] = sanitize($_POST['dob'] ?? $user['dob']);

    if ($user['name'] === '') {
        $errors[] = 'Name cannot be empty.';
    }

    if (empty($errors)) {
        update_user($user);
        $_SESSION['user_email'] = $user['email'];
        set_flash('Profile updated successfully.');
        header('Location: ' . basename($_SERVER['PHP_SELF']));
        exit;
    }
}

$flashMessage = flash_message();
$currentPage = basename($_SERVER['PHP_SELF']);
$roleLabel = ucfirst($currentRole);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($roleLabel); ?> Home - CareClinic</title>
    <link rel="stylesheet" href="assets/prototype.css">
</head>
<body class="profile-page-body home-page-body">
    <header class="hero-banner">
        <div class="hero-overlay"></div>
        <div class="container hero-container">
            <div class="topbar-pill">
                <a class="hero-brand" href="<?php echo htmlspecialchars($currentPage); ?>">
                    <img class="hero-brand-logo" src="assets/careclinic-logo.png" alt="CareClinic">
                </a>
                <nav class="nav-links">
                    <a class="nav-placeholder" href="#" onclick="return false;">Dashboard</a>
                    <a class="nav-placeholder" href="#" onclick="return false;">My Patients</a>
                    <a class="nav-placeholder" href="#" onclick="return false;">My Appointments</a>
                    <a class="nav-placeholder" href="#" onclick="return false;">Schedule Availability</a>
                    <a class="nav-placeholder" href="#" onclick="return false;">Medical Records</a>
                    <a class="active" href="<?php echo htmlspecialchars($currentPage); ?>">Profile</a>
                </nav>
                <a class="btn-logout" href="logout.php">Logout</a>
            </div>
            <h1 class="hero-home-title">Welcome, <?php echo htmlspecialchars($user['name']); ?></h1>
            <p class="hero-home-subtitle"><?php echo htmlspecialchars($roleLabel); ?> Home</p>
        </div>
    </header>

    <main class="profile-page">
        <div class="container profile-grid">
            <section class="dashboard-card profile-panel">
                <div class="profile-panel-header">
                    <h3>My Profile</h3>
                </div>

                <?php if ($errors): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="profile-top-summary">
                    <div class="avatar-box">
                        <div class="avatar-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" role="presentation"><path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5Zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z"/></svg>
                        </div>
                        <button type="button" class="avatar-edit">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.8 9.94l-3.75-3.75L3 17.25Zm17.7-10.04a1.003 1.003 0 0 0 0-1.42l-2.5-2.5a1.003 1.003 0 0 0-1.42 0l-1.96 1.96 3.75 3.75 2.13-1.79Z"/></svg>
                            Edit
                        </button>
                    </div>
                </div>

                <form method="post" class="profile-form profile-form-grid">
                    <label>
                        <span>Full Name</span>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </label>

                    <label>
                        <span>ID Number</span>
                        <input type="text" value="<?php echo htmlspecialchars((string) $user['id']); ?>" disabled>
                    </label>

                    <label>
                        <span>Gender</span>
                        <select name="gender">
                            <option value="" <?php echo $user['gender'] === '' ? 'selected' : ''; ?>>-- Select Gender --</option>
                            <option value="Male" <?php echo $user['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $user['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo $user['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </label>

                    <label>
                        <span>Email</span>
                        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                    </label>

                    <label>
                        <span>Phone Number</span>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                    </label>

                    <label class="profile-address-field">
                        <span>Address</span>
                        <textarea name="address"><?php echo htmlspecialchars($user['address']); ?></textarea>
                    </label>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Save Changes</button>
                    </div>
                </form>

            </section>
        </div>
    </main>

    <?php if ($flashMessage): ?>
        <div class="flash-modal-overlay" id="flashModal">
            <div class="flash-modal-card" role="dialog" aria-modal="true" aria-label="Status Message">
                <img class="flash-modal-logo" src="assets/careclinic-logo.png" alt="CareClinic">
                <p class="flash-modal-message"><?php echo htmlspecialchars($flashMessage); ?></p>
                <button type="button" class="flash-modal-ok" id="flashModalOk">Okay</button>
            </div>
        </div>
        <script>
            (function () {
                var modal = document.getElementById('flashModal');
                var okButton = document.getElementById('flashModalOk');
                if (!modal || !okButton) {
                    return;
                }
                okButton.addEventListener('click', function () {
                    modal.remove();
                });
            })();
        </script>
    <?php endif; ?>
</body>
</html>
