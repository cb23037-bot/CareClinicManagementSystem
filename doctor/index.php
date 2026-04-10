<?php
require_once('../includes/functions.php');
$doctor_id = 1;
$patient_records = get_all_patient_records($doctor_id);

$page_title = "My Appointment";
include('../includes/header.php');
?>

<main class="main-card">
    <?php if (isset($_GET['delete']) && $_GET['delete'] == 'success'): ?>
        <div class="alert-box success">
            <i class="fa-solid fa-circle-check"></i>
            <span>Record deleted successfully!</span>
            <button class="close-alert" onclick="this.parentElement.style.display='none';">&times;</button>
        </div>
    <?php endif; ?>

    <div class="patient-records-header">
        <h2>Patient Records</h2>
        <div class="table-controls">
            <div class="search-wrapper">
                <i class="fa fa-search"></i>
                <input type="text" id="patientSearch" placeholder="Search patients...">
            </div>

            <div class="filter-wrapper">
                <i class="fa fa-filter filter-icon"></i>
                <select class="status-select" id="statusFilter">
                    <option value="">All Status</option>
                    <option value="Done">Done</option>
                    <option value="Upcoming">Upcoming</option>
                    <option value="Booked">Booked</option>
                </select>
            </div>
        </div>
    </div>

    <table id="patientTable">
        <thead>
            <tr>
                <th>Patient</th>
                <th>Contact</th>
                <th>Condition</th>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($patient_records)): ?>
                <tr class="no-data">
                    <td colspan="7" style="text-align:center;">No records found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($patient_records as $record): ?>
                    <tr class="patient-row">
                        <td>
                            <div class="patient-info">
                                <span class="avatar"><?php echo substr($record['name'], 0, 2); ?></span>
                                <div>
                                    <strong class="patient-name"><?php echo $record['name']; ?></strong><br>
                                    <small class="text-muted"><?php echo $record['patient_id']; ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php echo $record['phone']; ?><br>
                            <?php echo $record['email']; ?>
                        </td>
                        <td class="condition-text"><?php echo $record['condition']; ?></td>
                        <td><?php echo $record['date']; ?></td>
                        <td><?php echo $record['time']; ?></td>
                        <td>
                            <span class="status-pill status-<?php echo strtolower($record['status']); ?> status-text">
                                <?php echo $record['status']; ?>
                            </span>
                        </td>
                        <td class="actions">
                            <a href="delete_record.php?id=<?php echo $record['patient_id']; ?>" class="action-btn delete" onclick="return confirm('Are you sure?')">
                                <i class="fa-regular fa-trash-can"></i>
                            </a>
                            <a href="view_record.php?id=<?php echo $record['patient_id']; ?>" class="action-btn view">
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
        const searchInput = document.getElementById('patientSearch');
        const statusFilter = document.getElementById('statusFilter');
        const tableRows = document.querySelectorAll('.patient-row');

        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedStatus = statusFilter.value.toLowerCase();

            tableRows.forEach(row => {
                const name = row.querySelector('.patient-name').textContent.toLowerCase();
                const condition = row.querySelector('.condition-text').textContent.toLowerCase();
                const status = row.querySelector('.status-text').textContent.trim().toLowerCase();

                const matchesSearch = name.includes(searchTerm) || condition.includes(searchTerm);
                const matchesStatus = selectedStatus === "" || status === selectedStatus;

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