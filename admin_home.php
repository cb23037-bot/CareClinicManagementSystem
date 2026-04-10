<?php
require 'functions.php';
require_role('admin');

$admin = current_user();
$searchTerm = trim((string) ($_GET['q'] ?? ''));
$roleFilter = strtolower(trim((string) ($_GET['role'] ?? 'all')));
$allowedRoleFilters = ['all', 'admin', 'doctor', 'patient'];
if (!in_array($roleFilter, $allowedRoleFilters, true)) {
    $roleFilter = 'all';
}

$baseQuery = [];
if ($searchTerm !== '') {
    $baseQuery['q'] = $searchTerm;
}
if ($roleFilter !== 'all') {
    $baseQuery['role'] = $roleFilter;
}

$buildAdminUrl = function (array $extra = []) use ($baseQuery) {
    $params = array_merge($baseQuery, $extra);
    return 'admin_home.php' . ($params ? '?' . http_build_query($params) : '');
};

$redirectToCurrentFilter = function () use ($buildAdminUrl) {
    header('Location: ' . $buildAdminUrl());
    exit;
};

$editErrors = [];
$editingProfile = null;
$viewProfile = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));
    $targetId = (int) ($_POST['user_id'] ?? 0);
    $targetUser = $targetId > 0 ? find_user_by_id($targetId) : null;

    if (!$targetUser) {
        set_flash('Selected profile was not found.');
        $redirectToCurrentFilter();
    }

    if ($action === 'delete_profile') {
        if ((int) $targetUser['id'] === (int) $admin['id']) {
            set_flash('You cannot delete the account currently signed in.');
            $redirectToCurrentFilter();
        }

        delete_user_by_id($targetUser['id']);
        set_flash('Profile deleted successfully.');
        $redirectToCurrentFilter();
    }

    if ($action === 'save_profile') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));
        $gender = trim((string) ($_POST['gender'] ?? ''));
        $dob = trim((string) ($_POST['dob'] ?? ''));
        $role = strtolower(trim((string) ($_POST['role'] ?? 'patient')));

        if ($name === '') {
            $editErrors[] = 'Name is required.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $editErrors[] = 'A valid email is required.';
        }
        if (!in_array($role, ['admin', 'doctor', 'patient'], true)) {
            $editErrors[] = 'Invalid role selected.';
        }
        if ($gender !== '' && !in_array($gender, ['Male', 'Female', 'Other'], true)) {
            $editErrors[] = 'Invalid gender selected.';
        }

        $existing = $email !== '' ? find_user_by_email($email) : null;
        if ($existing && (int) $existing['id'] !== (int) $targetUser['id']) {
            $editErrors[] = 'Another account already uses this email.';
        }

        $targetUser['name'] = $name;
        $targetUser['email'] = $email;
        $targetUser['phone'] = $phone;
        $targetUser['address'] = $address;
        $targetUser['gender'] = $gender;
        $targetUser['dob'] = $dob;
        $targetUser['role'] = $role;

        if (empty($editErrors)) {
            update_user($targetUser);

            if ((int) $targetUser['id'] === (int) $admin['id']) {
                $_SESSION['user_email'] = $targetUser['email'];
            }

            set_flash('Profile updated successfully.');
            $redirectToCurrentFilter();
        }

        $editingProfile = $targetUser;
    }
}

if ($editingProfile === null && isset($_GET['edit'])) {
    $candidate = find_user_by_id((int) $_GET['edit']);
    if ($candidate) {
        $editingProfile = $candidate;
    }
}

if (isset($_GET['view'])) {
    $candidate = find_user_by_id((int) $_GET['view']);
    if ($candidate) {
        $viewProfile = $candidate;
    }
}

$allProfiles = load_users();
$profiles = array_values(array_filter($allProfiles, function ($profile) use ($searchTerm, $roleFilter) {
    if ($roleFilter !== 'all' && strtolower((string) ($profile['role'] ?? '')) !== $roleFilter) {
        return false;
    }

    if ($searchTerm === '') {
        return true;
    }

    $needle = strtolower($searchTerm);
    $haystack = strtolower(implode(' ', [
        (string) ($profile['name'] ?? ''),
        (string) ($profile['email'] ?? ''),
        (string) ($profile['phone'] ?? ''),
        (string) ($profile['address'] ?? ''),
        (string) ($profile['role'] ?? ''),
        (string) ($profile['id'] ?? ''),
    ]));

    return strpos($haystack, $needle) !== false;
}));

$flashMessage = flash_message();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Home - CareClinic</title>
    <link rel="stylesheet" href="assets/prototype.css">
</head>
<body class="profile-page-body home-page-body">
    <header class="hero-banner">
        <div class="hero-overlay"></div>
        <div class="container hero-container">
            <div class="topbar-pill">
                <a class="hero-brand" href="admin_home.php">
                    <img class="hero-brand-logo" src="assets/careclinic-logo.png" alt="CareClinic">
                </a>
                <nav class="nav-links">
                    <a class="nav-placeholder" href="#" onclick="return false;">Dashboard</a>
                    <a class="nav-placeholder" href="#" onclick="return false;">My Patients</a>
                    <a class="nav-placeholder" href="#" onclick="return false;">My Appointments</a>
                    <a class="nav-placeholder" href="#" onclick="return false;">Schedule Availability</a>
                    <a class="nav-placeholder" href="#" onclick="return false;">Medical Records</a>
                    <a class="active" href="admin_home.php">Profile</a>
                </nav>
                <a class="btn-logout" href="logout.php">Logout</a>
            </div>
            <h1>Profile</h1>
        </div>
    </header>

    <main class="profile-page">
        <div class="container profile-grid">
            <section class="dashboard-card profile-panel">
                <div class="profile-panel-header">
                    <h3>List of Profile</h3>
                </div>

                <div class="records-shell">
                    <div class="records-head">
                        <h4>User Profiles</h4>
                        <form method="get" class="records-filters">
                            <label class="records-search">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15.5 14h-.79l-.28-.27A6.5 6.5 0 1 0 14 15.5l.27.28v.79L20 21.49 21.49 20l-5.99-6Zm-6 0A4.5 4.5 0 1 1 10 5a4.5 4.5 0 0 1-.5 9Z"/></svg>
                                <input type="text" name="q" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="Search profiles...">
                            </label>
                            <label class="records-select-wrap">
                                <select name="role">
                                    <option value="all" <?php echo $roleFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    <option value="doctor" <?php echo $roleFilter === 'doctor' ? 'selected' : ''; ?>>Doctor</option>
                                    <option value="patient" <?php echo $roleFilter === 'patient' ? 'selected' : ''; ?>>Patient</option>
                                </select>
                            </label>
                            <button type="submit" class="records-filter-btn">Filter</button>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="records-table">
                            <thead>
                                <tr>
                                    <th>Profile</th>
                                    <th>Contact</th>
                                    <th>Gender</th>
                                    <th>Role</th>
                                    <th>Date of Birth</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($profiles)): ?>
                                    <tr>
                                        <td colspan="6" class="empty-row">No profiles found for your filter.</td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($profiles as $profile): ?>
                                    <?php
                                        $avatarText = strtoupper(substr((string) $profile['name'], 0, 1));
                                        $roleClass = 'role-badge-' . strtolower((string) $profile['role']);
                                        $profileUrl = htmlspecialchars($buildAdminUrl(['view' => $profile['id']]));
                                        $editUrl = htmlspecialchars($buildAdminUrl(['edit' => $profile['id']]));
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="record-profile">
                                                <span class="record-avatar"><?php echo $avatarText !== '' ? $avatarText : 'U'; ?></span>
                                                <div>
                                                    <p class="record-name"><?php echo htmlspecialchars($profile['name']); ?></p>
                                                    <p class="record-meta">ID <?php echo htmlspecialchars((string) $profile['id']); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <p class="record-contact"><?php echo htmlspecialchars($profile['phone'] !== '' ? $profile['phone'] : '-'); ?></p>
                                            <p class="record-meta"><?php echo htmlspecialchars($profile['email']); ?></p>
                                        </td>
                                        <td><?php echo htmlspecialchars($profile['gender'] !== '' ? $profile['gender'] : '-'); ?></td>
                                        <td><span class="role-badge <?php echo htmlspecialchars($roleClass); ?>"><?php echo htmlspecialchars(ucfirst($profile['role'])); ?></span></td>
                                        <td><?php echo htmlspecialchars($profile['dob'] !== '' ? $profile['dob'] : '-'); ?></td>
                                        <td>
                                            <div class="record-actions">
                                                <a class="icon-action icon-view" href="<?php echo $profileUrl; ?>" title="View">
                                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5c-5 0-9.27 3.11-11 7 1.73 3.89 6 7 11 7s9.27-3.11 11-7c-1.73-3.89-6-7-11-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Zm0-6.4A2.4 2.4 0 1 0 12 14.4a2.4 2.4 0 0 0 0-4.8Z"/></svg>
                                                </a>
                                                <a class="icon-action icon-edit" href="<?php echo $editUrl; ?>" title="Edit">
                                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.8 9.94l-3.75-3.75L3 17.25Zm17.7-10.04a1.003 1.003 0 0 0 0-1.42l-2.5-2.5a1.003 1.003 0 0 0-1.42 0l-1.96 1.96 3.75 3.75 2.13-1.79Z"/></svg>
                                                </a>
                                                <button
                                                    type="button"
                                                    class="icon-action icon-delete js-delete-profile"
                                                    data-user-id="<?php echo htmlspecialchars((string) $profile['id']); ?>"
                                                    data-user-name="<?php echo htmlspecialchars($profile['name']); ?>"
                                                    title="Delete"
                                                    <?php echo (int) $profile['id'] === (int) $admin['id'] ? 'disabled' : ''; ?>
                                                >
                                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 7h12l-1 13a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L6 7Zm3-4h6l1 2h4v2H4V5h4l1-2Z"/></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <?php if ($viewProfile): ?>
        <div class="record-modal-overlay">
            <div class="record-modal-card">
                <h3>Profile Details</h3>
                <div class="record-detail-grid">
                    <p><strong>Full Name</strong><span><?php echo htmlspecialchars($viewProfile['name']); ?></span></p>
                    <p><strong>ID Number</strong><span><?php echo htmlspecialchars((string) $viewProfile['id']); ?></span></p>
                    <p><strong>Role</strong><span><?php echo htmlspecialchars(ucfirst($viewProfile['role'])); ?></span></p>
                    <p><strong>Email</strong><span><?php echo htmlspecialchars($viewProfile['email']); ?></span></p>
                    <p><strong>Phone</strong><span><?php echo htmlspecialchars($viewProfile['phone'] !== '' ? $viewProfile['phone'] : '-'); ?></span></p>
                    <p><strong>Gender</strong><span><?php echo htmlspecialchars($viewProfile['gender'] !== '' ? $viewProfile['gender'] : '-'); ?></span></p>
                    <p><strong>Date of Birth</strong><span><?php echo htmlspecialchars($viewProfile['dob'] !== '' ? $viewProfile['dob'] : '-'); ?></span></p>
                    <p><strong>Address</strong><span><?php echo htmlspecialchars($viewProfile['address'] !== '' ? $viewProfile['address'] : '-'); ?></span></p>
                </div>
                <div class="record-modal-actions">
                    <a class="btn-secondary" href="<?php echo htmlspecialchars($buildAdminUrl()); ?>">Close</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($editingProfile): ?>
        <div class="record-modal-overlay">
            <div class="record-modal-card">
                <h3>Edit Profile</h3>

                <?php if ($editErrors): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($editErrors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" class="record-edit-form">
                    <input type="hidden" name="action" value="save_profile">
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars((string) $editingProfile['id']); ?>">

                    <label>
                        <span>Full Name</span>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($editingProfile['name']); ?>" required>
                    </label>
                    <label>
                        <span>Email</span>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($editingProfile['email']); ?>" required>
                    </label>
                    <label>
                        <span>Phone Number</span>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($editingProfile['phone']); ?>">
                    </label>
                    <label>
                        <span>Gender</span>
                        <select name="gender">
                            <option value="" <?php echo $editingProfile['gender'] === '' ? 'selected' : ''; ?>>-- Select Gender --</option>
                            <option value="Male" <?php echo $editingProfile['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $editingProfile['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo $editingProfile['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </label>
                    <label>
                        <span>Role</span>
                        <select name="role">
                            <option value="admin" <?php echo $editingProfile['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="doctor" <?php echo $editingProfile['role'] === 'doctor' ? 'selected' : ''; ?>>Doctor</option>
                            <option value="patient" <?php echo $editingProfile['role'] === 'patient' ? 'selected' : ''; ?>>Patient</option>
                        </select>
                    </label>
                    <label>
                        <span>Date of Birth</span>
                        <input type="date" name="dob" value="<?php echo htmlspecialchars($editingProfile['dob']); ?>">
                    </label>
                    <label class="field-full">
                        <span>Address</span>
                        <textarea name="address"><?php echo htmlspecialchars($editingProfile['address']); ?></textarea>
                    </label>

                    <div class="record-modal-actions">
                        <button type="submit" class="btn-primary">Save</button>
                        <a class="btn-secondary" href="<?php echo htmlspecialchars($buildAdminUrl()); ?>">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="confirm-modal-overlay" id="deleteProfileModal" hidden>
        <div class="confirm-modal-card">
            <img class="confirm-modal-logo" src="assets/careclinic-logo.png" alt="CareClinic">
            <p class="confirm-modal-message" id="deleteModalMessage">Are you sure you want to delete this record? This action cannot be undone.</p>
            <div class="confirm-modal-actions">
                <button type="button" class="confirm-btn-yes" id="deleteModalYes">Yes</button>
                <button type="button" class="confirm-btn-cancel" id="deleteModalCancel">Cancel</button>
            </div>
        </div>
    </div>

    <form method="post" id="deleteProfileForm" class="hidden-form">
        <input type="hidden" name="action" value="delete_profile">
        <input type="hidden" name="user_id" id="deleteProfileId" value="">
    </form>

    <?php if ($flashMessage): ?>
        <div class="flash-modal-overlay" id="flashModal">
            <div class="flash-modal-card" role="dialog" aria-modal="true" aria-label="Status Message">
                <img class="flash-modal-logo" src="assets/careclinic-logo.png" alt="CareClinic">
                <p class="flash-modal-message"><?php echo htmlspecialchars($flashMessage); ?></p>
                <button type="button" class="flash-modal-ok" id="flashModalOk">Okay</button>
            </div>
        </div>
    <?php endif; ?>

    <script>
        (function () {
            var flashModal = document.getElementById('flashModal');
            var flashOk = document.getElementById('flashModalOk');
            if (flashModal && flashOk) {
                flashOk.addEventListener('click', function () {
                    flashModal.remove();
                });
            }

            var deleteModal = document.getElementById('deleteProfileModal');
            var deleteMessage = document.getElementById('deleteModalMessage');
            var deleteIdField = document.getElementById('deleteProfileId');
            var deleteForm = document.getElementById('deleteProfileForm');
            var deleteYes = document.getElementById('deleteModalYes');
            var deleteCancel = document.getElementById('deleteModalCancel');
            var deleteButtons = document.querySelectorAll('.js-delete-profile');

            function closeDeleteModal() {
                if (deleteModal) {
                    deleteModal.hidden = true;
                }
                if (deleteIdField) {
                    deleteIdField.value = '';
                }
            }

            deleteButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    if (!deleteModal || !deleteIdField) {
                        return;
                    }
                    var userId = button.getAttribute('data-user-id') || '';
                    var userName = button.getAttribute('data-user-name') || 'this user';
                    deleteIdField.value = userId;
                    if (deleteMessage) {
                        deleteMessage.textContent = 'Are you sure you want to delete "' + userName + '"? This action cannot be undone.';
                    }
                    deleteModal.hidden = false;
                });
            });

            if (deleteCancel) {
                deleteCancel.addEventListener('click', closeDeleteModal);
            }

            if (deleteYes) {
                deleteYes.addEventListener('click', function () {
                    if (deleteForm && deleteIdField && deleteIdField.value !== '') {
                        deleteForm.submit();
                    }
                });
            }
        })();
    </script>
</body>
</html>
