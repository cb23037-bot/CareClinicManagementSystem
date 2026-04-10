<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
    <i class="fas fa-tachometer-alt"></i> Dashboard
</a>
<a class="nav-link <?php echo $current_page == 'patients.php' ? 'active' : ''; ?>" href="patients.php">
    <i class="fas fa-users"></i> Patient Directory
</a>
<a class="nav-link <?php echo $current_page == 'appointments.php' ? 'active' : ''; ?>" href="appointments.php">
    <i class="fas fa-calendar-check"></i> Appointment
</a>
<a class="nav-link <?php echo $current_page == 'doctors.php' ? 'active' : ''; ?>" href="doctors.php">
    <i class="fas fa-user-md"></i> Doctor Rosters
</a>
<a class="nav-link <?php echo $current_page == 'records.php' ? 'active' : ''; ?>" href="records.php">
    <i class="fas fa-folder-open"></i> Medical Records
</a>
<a class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>" href="profile.php">
    <i class="fas fa-user-circle"></i> Profile
</a>