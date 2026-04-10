<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
    <i class="fas fa-home"></i> Home
</a>
<a class="nav-link <?php echo $current_page == 'book-appointment.php' ? 'active' : ''; ?>" href="book-appointment.php">
    <i class="fas fa-calendar-plus"></i> Book Appointment
</a>
<a class="nav-link <?php echo $current_page == 'appointments.php' ? 'active' : ''; ?>" href="appointments.php">
    <i class="fas fa-calendar-check"></i> My Appointments
</a>
<a class="nav-link <?php echo $current_page == 'records.php' ? 'active' : ''; ?>" href="records.php">
    <i class="fas fa-folder-open"></i> My Medical Records
</a>
<a class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>" href="profile.php">
    <i class="fas fa-user-circle"></i> Profile
</a>