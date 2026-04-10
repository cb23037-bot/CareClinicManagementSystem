<?php
// Get current directory and current file name
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

    <header class="hero-nav">
        <div class="nav-container">
            <div class="logo">
                <img src="../images/logo.jpeg" alt="CareClinic Logo">
            </div>

            <nav class="nav-links">
                <?php if ($current_dir == 'patient'): ?>
                    <a href="home.php" class="<?php echo ($current_page == 'home.php') ? 'active' : ''; ?>">Home</a>

                    <a href="book_appointment.php" class="<?php echo ($current_page == 'book_appointment.php') ? 'active' : ''; ?>">Book Appointment</a>

                    <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">My Appointments</a>

                    <a href="medical_records.php" class="<?php echo ($current_page == 'medical_records.php') ? 'active' : ''; ?>">My Medical Records</a>

                    <a href="profile.php" class="<?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">Profile</a>

                <?php else: ?>
                    <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">Dashboard</a>

                    <a href="my_patients.php" class="<?php echo ($current_page == 'my_patients.php') ? 'active' : ''; ?>">My Patients</a>

                    <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">My Appointments</a>

                    <a href="availability.php" class="<?php echo ($current_page == 'availability.php') ? 'active' : ''; ?>">Schedule Availability</a>

                    <a href="medical_records.php" class="<?php echo ($current_page == 'medical_records.php') ? 'active' : ''; ?>">Medical Records</a>

                    <a href="profile.php" class="<?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">Profile</a>
                <?php endif; ?>
            </nav>

            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
        <div class="hero-text">
            <?php
            // 1. Check if a specific header title was set in the individual page file
            if (isset($page_title_header)) {
                echo $page_title_header;
            } else {
                // 2. Otherwise, determine title based on the filename and user folder
                switch ($current_page) {
                    case 'home.php':
                    case 'dashboard.php':
                        echo ($current_dir == 'patient') ? "Welcome Home" : "Doctor Dashboard";
                        break;

                    case 'book_appointment.php':
                        echo "Book Appointment";
                        break;

                    case 'index.php':
                        echo ($current_dir == 'patient') ? "My Appointments" : "Patient Appointments";
                        break;

                    case 'medical_records.php':
                        echo ($current_dir == 'patient') ? "My Medical Records" : "Patient Medical Records";
                        break;

                    case 'my_patients.php':
                        echo "My Patients";
                        break;

                    case 'availability.php':
                        echo "Schedule Availability";
                        break;

                    case 'profile.php':
                        echo "My Profile";
                        break;

                    default:
                        echo "CareClinic";
                        break;
                }
            }
            ?>
        </div>
    </header>