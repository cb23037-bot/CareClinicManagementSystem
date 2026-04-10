<?php
require 'functions.php';

$errors = [];
$googleSignInEnabled = google_sign_in_enabled();
$googleClientId = google_client_id();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $gender = sanitize($_POST['gender'] ?? '');
    $dob = sanitize($_POST['dob'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? $password;

    if ($name === '') {
        $errors[] = 'Name is required.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }
    if ($password === '' || strlen($password) < 6) {
        $errors[] = 'Password must be 6 characters or more.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if (find_user_by_email($email)) {
        $errors[] = 'A user with this email already exists.';
    }

    if (empty($errors)) {
        create_user([
            'role' => 'patient',
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'gender' => $gender,
            'dob' => $dob,
            'password' => $password,
        ]);
        set_flash('Registration successful. You can now log in.');
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration</title>
    <link rel="stylesheet" href="assets/prototype.css">
    <?php if ($googleSignInEnabled): ?>
        <script src="https://accounts.google.com/gsi/client" async defer></script>
    <?php endif; ?>
</head>
<body class="auth-page register-page">
    <div class="page-background auth-background"></div>
    <main class="auth-layout">
        <section class="auth-card auth-card-register">
            <div class="auth-card-inner auth-card-inner-register">
                <img class="brand-logo brand-logo-register" src="assets/careclinic-logo.png" alt="CareClinic">

                <?php if ($errors): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div
                    class="google-button-shell<?php echo $googleSignInEnabled ? '' : ' is-disabled'; ?>"
                    <?php if (!$googleSignInEnabled): ?>
                        title="Add your Google Client ID in google_oauth_config.php to enable Google Sign-In."
                    <?php endif; ?>
                >
                    <div class="google-button">
                        <img src="assets/google-g.svg" alt="" aria-hidden="true">
                        Continue with Google
                    </div>
                    <?php if ($googleSignInEnabled): ?>
                        <div id="google-signin-overlay" class="google-signin-overlay" aria-hidden="true"></div>
                    <?php endif; ?>
                </div>

                <div class="auth-divider">
                    <span>or signup with</span>
                </div>

                <form method="post" class="auth-form auth-form-register">
                    <label>
                        <span>Full Name</span>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required autocomplete="name">
                    </label>
                    <label>
                        <span>Email</span>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required autocomplete="email">
                    </label>
                    <label>
                        <span>Password</span>
                        <input type="password" name="password" required autocomplete="new-password">
                    </label>
                    <button type="submit" class="btn-primary auth-submit">Register</button>
                </form>

                <a class="secondary-link" href="index.php">Already have an account?</a>
            </div>
        </section>
    </main>
    <?php if ($googleSignInEnabled): ?>
        <script>
            window.addEventListener('load', function () {
                if (!window.google || !google.accounts || !google.accounts.id) {
                    return;
                }

                google.accounts.id.initialize({
                    client_id: <?php echo json_encode($googleClientId); ?>,
                    callback: function (response) {
                        if (!response || !response.credential) {
                            return;
                        }

                        var form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'google_login.php';

                        var credential = document.createElement('input');
                        credential.type = 'hidden';
                        credential.name = 'credential';
                        credential.value = response.credential;
                        form.appendChild(credential);

                        document.body.appendChild(form);
                        form.submit();
                    },
                    auto_select: false,
                    cancel_on_tap_outside: true,
                    ux_mode: 'popup'
                });

                google.accounts.id.renderButton(
                    document.getElementById('google-signin-overlay'),
                    {
                        type: 'standard',
                        theme: 'outline',
                        size: 'large',
                        text: 'continue_with',
                        shape: 'pill',
                        width: 260
                    }
                );
            });
        </script>
    <?php endif; ?>
</body>
</html>
