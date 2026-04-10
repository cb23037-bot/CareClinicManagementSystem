<?php
require 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $user = find_user_by_email($email);

    if ($user && $password === $user['password']) {
        $_SESSION['user_email'] = $user['email'];
        redirect_after_login($user);
    }

    set_flash('Invalid email or password.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareClinic Login</title>
    <link rel="stylesheet" href="assets/prototype.css">
</head>
<body class="auth-page login-page">
    <div class="page-background auth-background"></div>
    <main class="auth-layout">
        <section class="auth-card auth-card-login">
            <div class="auth-card-inner">
                <img class="brand-logo brand-logo-login" src="assets/careclinic-logo.png" alt="CareClinic">

                <?php if ($message = flash_message()): ?>
                    <div class="alert"><?php echo $message; ?></div>
                <?php endif; ?>

                <form method="post" class="auth-form auth-form-login">
                    <label class="input-with-icon">
                        <span class="sr-only">Email</span>
                        <span class="field-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" role="presentation"><path d="M12 12c2.76 0 5-2.46 5-5.5S14.76 1 12 1 7 3.46 7 6.5 9.24 12 12 12Zm0 2c-4.42 0-8 2.46-8 5.5V22h16v-2.5c0-3.04-3.58-5.5-8-5.5Z"/></svg>
                        </span>
                        <input type="email" name="email" required autocomplete="username">
                    </label>
                    <label class="input-with-icon">
                        <span class="sr-only">Password</span>
                        <span class="field-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" role="presentation"><path d="M17 9h-1V7a4 4 0 1 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-6 0V7a2 2 0 1 1 4 0v2h-4Zm1 8a2 2 0 1 1 .001-4.001A2 2 0 0 1 12 17Z"/></svg>
                        </span>
                        <input type="password" name="password" required autocomplete="current-password">
                    </label>

                    <button type="submit" class="btn-primary auth-submit">Log in</button>
                </form>

                <a class="secondary-link" href="register.php">I don't have an account</a>
            </div>
        </section>
    </main>
</body>
</html>
