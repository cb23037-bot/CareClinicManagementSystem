<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
    <i class="fas fa-tachometer-alt"></i> Dashboard
</a>
<a class="nav-link <?php echo $current_page == 'patients.php' ? 'active' : ''; ?>" href="patients.php">
    <i class="fas fa-users"></i> My Patients
</a>
<a class="nav-link <?php echo $current_page == 'my-appointments.php' ? 'active' : ''; ?>" href="my-appointments.php">
    <i class="fas fa-calendar-check"></i> My Appointments
</a>
<a class="nav-link <?php echo $current_page == 'schedule.php' ? 'active' : ''; ?>" href="schedule.php">
    <i class="fas fa-clock"></i> Schedule Availability
</a>
<a class="nav-link <?php echo $current_page == 'records.php' ? 'active' : ''; ?>" href="records.php">
    <i class="fas fa-folder-open"></i> Medical Records
</a>
<a class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>" href="profile.php">
    <i class="fas fa-user-circle"></i> Profile
</a>