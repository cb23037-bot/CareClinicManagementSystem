<?php
require_once '../config/database.php';
redirectIfNotLoggedIn();
if(!hasRole('patient')) {
    header('Location: ../login.php');
    exit();
}

$record_id = $_GET['id'] ?? 0;

// Get patient info
$stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$patient = $stmt->fetch();

// Get record details - ONLY use existing columns
$stmt = $pdo->prepare("
    SELECT mr.*, 
           d.full_name as doctor_name,
           p.full_name as patient_name, 
           pat.patient_id, 
           pat.blood_group
    FROM medical_records mr
    JOIN patients pat ON mr.patient_id = pat.id
    JOIN users p ON pat.user_id = p.id
    LEFT JOIN doctors doc ON mr.doctor_id = doc.id
    LEFT JOIN users d ON doc.user_id = d.id
    WHERE mr.id = ? AND mr.patient_id = ?
");
$stmt->execute([$record_id, $patient['id']]);
$record = $stmt->fetch();

if(!$record) {
    header('Location: records.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="CareClinicLogo.jpeg">
    <title>Medical Record Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f0f0f0;
            padding: 40px 20px;
        }

        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            .print-container {
                box-shadow: none;
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none;
            }
            @page {
                size: A4;
                margin: 2cm;
            }
        }

        .print-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        /* Header */
        .report-header {
            background: #2a5298;
            color: white;
            padding: 30px;
            text-align: center;
        }

        .hospital-name {
            font-size: 24px;
            font-weight: bold;
        }

        .report-title {
            font-size: 18px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid rgba(255,255,255,0.3);
        }

        .record-id {
            font-size: 12px;
            margin-top: 10px;
            opacity: 0.8;
        }

        /* Sections */
        .section {
            padding: 20px 30px;
            border-bottom: 1px solid #e0e0e0;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #2a5298;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid #2a5298;
            display: inline-block;
        }

        .info-row {
            display: flex;
            padding: 8px 0;
            font-size: 14px;
        }

        .info-label {
            width: 130px;
            font-weight: 600;
            color: #555;
        }

        .info-value {
            flex: 1;
            color: #333;
        }

        .medical-box {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 14px;
            line-height: 1.6;
            white-space: pre-wrap;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-stable { background: #d4edda; color: #155724; }
        .status-recovering { background: #d1ecf1; color: #0c5460; }
        .status-under_observation { background: #fff3cd; color: #856404; }
        .status-critical { background: #f8d7da; color: #721c24; }

        .footer {
            background: #f8f9fa;
            padding: 15px 30px;
            text-align: center;
            font-size: 11px;
            color: #888;
        }

        .print-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #2a5298;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 30px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            z-index: 1000;
        }

        .print-btn:hover {
            background: #1e3c72;
        }

        .signature {
            margin-top: 30px;
            padding-top: 20px;
            display: flex;
            justify-content: space-between;
        }

        .signature-line {
            text-align: center;
            width: 200px;
        }

        .signature-line .line {
            border-top: 1px solid #333;
            margin-top: 40px;
            padding-top: 8px;
            font-size: 12px;
        }

        @media (max-width: 600px) {
            .section {
                padding: 15px;
            }
            .info-row {
                flex-direction: column;
            }
            .info-label {
                width: 100%;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <button onclick="window.print()" class="print-btn no-print">🖨️ Print / Save PDF</button>

    <div class="print-container">
        <!-- Header -->
        <div class="report-header">
            <div class="hospital-name">CARECLINIC MEDICAL CENTER</div>
            <div class="report-title">MEDICAL RECORD REPORT</div>
            <div class="record-id">Record ID: <?php echo htmlspecialchars($record['record_id']); ?></div>
        </div>

        <!-- Patient Information -->
        <div class="section">
            <div class="section-title">PATIENT INFORMATION</div>
            <div class="info-row">
                <div class="info-label">Patient Name:</div>
                <div class="info-value"><?php echo htmlspecialchars($record['patient_name']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Patient ID:</div>
                <div class="info-value"><?php echo htmlspecialchars($record['patient_id']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Blood Group:</div>
                <div class="info-value"><?php echo htmlspecialchars($record['blood_group'] ?? 'N/A'); ?></div>
            </div>
        </div>

        <!-- Visit Information -->
        <div class="section">
            <div class="section-title">VISIT INFORMATION</div>
            <div class="info-row">
                <div class="info-label">Record Date:</div>
                <div class="info-value"><?php echo date('F j, Y', strtotime($record['record_date'])); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Doctor:</div>
                <div class="info-value"><?php echo htmlspecialchars($record['doctor_name'] ?? 'N/A'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Condition:</div>
                <div class="info-value"><?php echo htmlspecialchars($record['condition_name']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">
                    <span class="status-badge status-<?php echo $record['status']; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $record['status'])); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Diagnosis -->
        <div class="section">
            <div class="section-title">DIAGNOSIS</div>
            <div class="medical-box">
                <?php echo nl2br(htmlspecialchars($record['diagnosis'] ?: 'No diagnosis recorded.')); ?>
            </div>
        </div>

        <!-- Prescription -->
        <div class="section">
            <div class="section-title">PRESCRIPTION</div>
            <div class="medical-box">
                <?php echo nl2br(htmlspecialchars($record['prescription'] ?: 'No prescription recorded.')); ?>
            </div>
        </div>

        <!-- Additional Notes -->
        <?php if($record['notes']): ?>
        <div class="section">
            <div class="section-title">ADDITIONAL NOTES</div>
            <div class="medical-box">
                <?php echo nl2br(htmlspecialchars($record['notes'])); ?>
            </div>
        </div>
        <?php endif; ?>


        <!-- Signature -->
        <div class="section">
            <div class="signature">
                <div class="signature-line">
                    <div class="line">Physician Signature</div>
                    <div style="margin-top: 10px;"><?php echo htmlspecialchars($record['doctor_name'] ?? 'Attending Physician'); ?></div>
                </div>
                <div class="signature-line">
                    <div class="line">Patient Signature</div>
                    <div style="margin-top: 10px;">________________________</div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This is an official medical record from CareClinic Medical Center</p>
            <p>Generated on: <?php echo date('F j, Y \a\t g:i A'); ?></p>
        </div>
    </div>
</body>
</html>