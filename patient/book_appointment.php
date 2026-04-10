<?php
require_once('../includes/functions.php');
$page_title = "CareClinic - Book Appointment";
include('../includes/header.php');

// Simulated logged-in patient code
$patient_id_code = 'PT001';
$patient_name = 'Sarah';

// Initialize state
$current_step = $_POST['step'] ?? 1;
$selected_service = $_POST['service_id'] ?? '';
$selected_schedule = $_POST['selected_schedule'] ?? '';

$services = [
    'gp' => ['name' => 'General Practitioner (GP)', 'desc' => 'Treatment for common illnesses'],
    'ped' => ['name' => 'Pediatrician', 'desc' => 'Specialized care for children'],
    'fam' => ['name' => 'Family Medicine Doctor', 'desc' => 'Chronic disease management'],
    'gyn' => ['name' => 'Gynecologist', 'desc' => 'Women’s health services']
];


// Handle Navigation & Database
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Previous Button
    if (isset($_POST['previous'])) {
        if ($current_step == 1) {
            header("Location: index.php");
            exit();
        } else {
            $current_step--;
        }
    }

    // Next Button
    if (isset($_POST['next'])) {
        if ($current_step == 1 && empty($selected_service)) {
            $error = "Please select a service before proceeding.";
        } elseif ($current_step == 2 && empty($selected_schedule)) {
            $error = "Please select a time slot.";
        } else {
            $current_step++;
        }
    }

    // Confirm Button (Database Part)
    if (isset($_POST['confirm'])) {
        // 1. Map Service to Doctor ID (matches your DB)
        $doctor_mapping = ['gp' => 1, 'ped' => 2, 'fam' => 3, 'gyn' => 4];
        $doctor_id = $doctor_mapping[$selected_service] ?? 1;

        // 2. Format Date and Time
        $timestamp = strtotime($selected_schedule);
        $app_date = date("Y-m-d", $timestamp);
        $app_time = date("H:i:s", $timestamp);
        $service_name = $services[$selected_service]['name'];

        // 3. Call the function from your functions.php
        // Note: Using your existing create_appointment function
        $result = create_appointment($patient_id_code, $doctor_id, $service_name, $app_date, $app_time);

        if ($result) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['success_message'] = "Your appointment has been booked successfully!";
            header("Location: index.php");
            exit();
        } else {
            $error = "Database error: Could not save the appointment.";
        }
    }
}
?>

<main class="main-card book-card">
    <div class="book-header">
        <h2>Book Appointment</h2>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert-box error" style="background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form action="" method="post" id="bookForm">
        <input type="hidden" name="step" value="<?php echo $current_step; ?>">

        <?php if ($current_step == 1): ?>
            <div class="step-content">
                <h3 class="step-title">Select a service</h3>
                <div class="services-grid">
                    <?php foreach ($services as $id => $service): ?>
                        <div class="service-option <?php echo ($selected_service == $id) ? 'selected' : ''; ?>"
                            onclick="selectService('<?php echo $id; ?>', this)">
                            <div class="service-info">
                                <h4><?php echo $service['name']; ?></h4>
                                <p><?php echo $service['desc']; ?></p>
                            </div>
                            <i class="fa-solid fa-chevron-right"></i>
                        </div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="service_id" id="selected_service" value="<?php echo htmlspecialchars($selected_service); ?>">
            </div>

        <?php elseif ($current_step == 2): ?>
            <input type="hidden" name="service_id" value="<?php echo htmlspecialchars($selected_service); ?>">
            <div class="step-content">
                <h3 class="step-title">Select Date & Time</h3>
                <div class="schedule-grid">
                    <div class="day-card">
                        <div class="day-label">Tuesday, 24 March 2026</div>
                        <div class="time-slots">
                            <?php $slots = ['09:00 AM', '11:00 AM', '02:00 PM'];
                            foreach ($slots as $s): $val = "2026-03-24 $s"; ?>
                                <label class="time-slot">
                                    <input type="radio" name="selected_schedule" value="<?php echo $val; ?>" <?php echo ($selected_schedule == $val) ? 'checked' : ''; ?>>
                                    <span><?php echo $s; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($current_step == 3): ?>
            <input type="hidden" name="service_id" value="<?php echo htmlspecialchars($selected_service); ?>">
            <input type="hidden" name="selected_schedule" value="<?php echo htmlspecialchars($selected_schedule); ?>">
            <div class="step-content">
                <h3 class="step-title">Confirm Appointment</h3>
                <div class="details-grid">
                    <div class="detail-group"><label>Patient</label>
                        <div class="read-only-box"><?php echo $patient_name; ?></div>
                    </div>
                    <div class="detail-group"><label>Service</label>
                        <div class="read-only-box"><?php echo $services[$selected_service]['name']; ?></div>
                    </div>
                    <div class="detail-group full-width"><label>Date & Time</label>
                        <div class="read-only-box"><?php echo $selected_schedule; ?></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" name="previous" class="btn-prev">Previous</button>
            <?php if ($current_step < 3): ?>
                <button type="submit" name="next" class="btn-next">Next</button>
            <?php else: ?>
                <button type="submit" name="confirm" class="btn-next">Confirm</button>
            <?php endif; ?>
        </div>
    </form>
</main>

<script>
    function selectService(id, element) {
        document.getElementById('selected_service').value = id;
        document.querySelectorAll('.service-option').forEach(opt => opt.classList.remove('selected'));
        element.classList.add('selected');
    }
</script>