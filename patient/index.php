<?php
require_once('../includes/functions.php');
$page_title = "CareClinic - My Appointments";
include('../includes/header.php');

// Simulated logged-in patient
$patient_id = 'PT001';
$my_appointments = get_patient_appointments($patient_id);
?>

<main class="main-card">
    <?php if (isset($_GET['cancel'])): ?>
        <?php if ($_GET['cancel'] == 'success'): ?>
            <div class="alert-box success">
                <i class="fa-solid fa-circle-check"></i>
                Appointment cancelled successfully!
                <button class="close-alert" onclick="this.parentElement.style.display='none';">&times;</button>
            </div>
        <?php elseif ($_GET['cancel'] == 'error'): ?>
            <div class="alert-box error">
                <i class="fa-solid fa-circle-xmark"></i>
                Error: Could not cancel appointment.
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="patient-records-header">
        <h2>Appointment</h2>

        <div class="table-controls">
            <div class="search-wrapper">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="appointmentSearch" placeholder="Search doctor...">
            </div>

            <div class="filter-wrapper">
                <i class="fa-solid fa-filter filter-icon"></i>
                <select id="statusFilter" class="status-select">
                    <option value="">All Status</option>
                    <option value="Done">Done</option>
                    <option value="Booked">Booked</option>
                </select>
            </div>

            <a href="book_appointment.php" class="btn-primary-plus" style="background:#1e3a8a; color:white; padding:10px 20px; border-radius:10px; text-decoration:none; font-weight:600;">+ Book Appointment</a>
        </div>
    </div>

    <table id="appointmentsTable">
        <thead>
            <tr>
                <th>Doctor</th>
                <th>Condition / Service</th>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
                <th style="text-align:center;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($my_appointments)): ?>
                <tr class="no-data">
                    <td colspan="6" align="center">No appointments found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($my_appointments as $appointment):
                    $dr_name = $appointment['doctor_name'];
                    // Generate dynamic initials for the avatar
                    $name_parts = explode(' ', str_replace('Dr. ', '', $dr_name));
                    $initials = strtoupper(substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : ''));
                ?>
                    <tr class="appointment-row">
                        <td>
                            <div class="patient-info">
                                <div class="avatar"><?php echo $initials; ?></div>
                                <div>
                                    <div class="doctor-name-text" style="font-weight:600;"><?php echo htmlspecialchars($dr_name); ?></div>
                                    <div class="patient-id">DR<?php echo sprintf("%03d", array_search($dr_name, ['Dr. Ahmed', 'Dr. Hasan', 'Dr. Ammar', 'Dr. Alisa']) + 1); ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="condition-text"><?php echo htmlspecialchars($appointment['condition']); ?></td>
                        <td><?php echo $appointment['date']; ?></td>
                        <td><?php echo date("h:i A", strtotime($appointment['time'])); ?></td>
                        <td>
                            <span class="status-pill status-<?php echo strtolower($appointment['status']); ?>">
                                <?php echo $appointment['status']; ?>
                            </span>
                        </td>
                        <td class="actions" style="justify-content: center;">
                            <?php if ($appointment['status'] !== 'Done'): ?>
                                <a href="delete_appointment.php?id=<?php echo $appointment['appointment_id']; ?>"
                                    class="action-btn delete" onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                    <i class="fa-regular fa-trash-can"></i>
                                </a>
                            <?php endif; ?>
                            <a href="view_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="action-btn view">
                                <i class="fa-regular fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('appointmentSearch');
        const statusFilter = document.getElementById('statusFilter');
        const rows = document.querySelectorAll('.appointment-row');

        function filterTable() {
            const searchText = searchInput.value.toLowerCase();
            const filterStatus = statusFilter.value.toLowerCase();

            rows.forEach(row => {
                const doctorName = row.querySelector('.doctor-name-text').textContent.toLowerCase();
                const condition = row.querySelector('.condition-text').textContent.toLowerCase();
                const status = row.querySelector('.status-pill').textContent.toLowerCase().trim();

                const matchesSearch = doctorName.includes(searchText) || condition.includes(searchText);
                const matchesStatus = filterStatus === "" || status === filterStatus;

                if (matchesSearch && matchesStatus) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }

        searchInput.addEventListener('input', filterTable);
        statusFilter.addEventListener('change', filterTable);
    });
</script>