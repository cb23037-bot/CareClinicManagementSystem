<?php
session_start();

if (!isset($_SESSION['admin_id']) || !isset($_SESSION['logged_in_role']) || $_SESSION['logged_in_role'] !== 'admin') {
    header("Location: system_login.html?error=Please+log+in+as+admin+first.");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Doctor Rosters - CAMS</title>
  <style>
    :root {
      --primary: #3f5efb;
      --primary-dark: #2438b8;
      --text: #1f2937;
      --muted: #6b7280;
      --line: #e5e7eb;
      --bg: #f3f4f6;
      --white: #ffffff;
      --green-bg: #eef8db;
      --green-text: #78b11c;
      --gray-badge: #ececec;
      --danger: #e11d48;
      --success: #56c271;
      --shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
      --radius-lg: 22px;
      --radius-md: 14px;
      --radius-sm: 10px;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: Arial, Helvetica, sans-serif;
      background: #ffffff;
      color: var(--text);
    }

    .hero {
      background:
        linear-gradient(rgba(255,255,255,0.58), rgba(255,255,255,0.58)),
        url('background.jpg') center/cover no-repeat;
      min-height: 290px;
      border-bottom-left-radius: 46px;
      border-bottom-right-radius: 46px;
      padding: 14px 26px 36px;
      position: relative;
    }

    .nav {
      max-width: 1180px;
      margin: 0 auto;
      background: rgba(255,255,255,0.62);
      backdrop-filter: blur(5px);
      border-radius: 26px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 14px 22px;
      gap: 18px;
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 10px;
      min-width: 100px;
    }

    .logo {
      width: 44px;
      height: 44px;
      flex-shrink: 0;
      object-fit: contain;
    }

    .brand small {
      display: block;
      color: #0d6aa8;
      font-weight: 700;
      margin-top: 4px;
    }

    .menu {
      display: flex;
      align-items: center;
      gap: 34px;
      flex-wrap: wrap;
      justify-content: center;
      flex: 1;
    }

    .menu a {
      text-decoration: none;
      color: #5864c7;
      font-size: 14px;
    }

    .menu a.active {
      text-decoration: underline;
      text-underline-offset: 5px;
    }

    .logout-btn {
      border: none;
      background: #5864c7;
      color: #fff;
      padding: 8px 14px;
      border-radius: 12px;
      font-weight: 700;
      cursor: pointer;
    }

    .hero-title {
      text-align: center;
      color: rgba(255,255,255,0.9);
      font-size: 66px;
      font-style: italic;
      font-weight: 300;
      margin: 54px 0 0;
    }

    .page {
      max-width: 1180px;
      margin: -44px auto 60px;
      padding: 0 18px;
      position: relative;
      z-index: 2;
    }

    .card {
      background: var(--white);
      border: 1px solid #bdbdbd;
      box-shadow: var(--shadow);
    }

    .schedule-card {
      padding: 0;
    }

    .card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 18px;
      padding: 16px 20px;
      border-bottom: 1px solid var(--line);
      flex-wrap: wrap;
    }

    .card-title {
      font-size: 18px;
      font-weight: 500;
    }

    .header-actions {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .toggle-group {
      display: flex;
      border: 1px solid var(--line);
      border-radius: 8px;
      overflow: hidden;
      background: #fff;
    }

    .toggle-btn {
      border: none;
      background: #fff;
      padding: 7px 15px;
      font-size: 12px;
      cursor: pointer;
    }

    .toggle-btn.active {
      background: #060822;
      color: #fff;
    }

    .add-btn {
      border: none;
      background: #2f67f6;
      color: #fff;
      padding: 10px 18px;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .add-btn span {
      font-size: 18px;
      line-height: 1;
    }

    .toolbar {
      display: flex;
      justify-content: flex-end;
      gap: 12px;
      padding: 10px 20px 0;
      flex-wrap: wrap;
    }

    .search-box,
    .filter-box {
      height: 36px;
      border: 1px solid var(--line);
      border-radius: 8px;
      background: #fafafa;
      color: var(--muted);
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 0 12px;
      font-size: 13px;
    }

    .search-box {
      width: 250px;
    }

    .filter-box {
      min-width: 180px;
      justify-content: space-between;
    }

    .toolbar-input,
    .toolbar-select {
      border: none;
      background: transparent;
      outline: none;
      font-size: 13px;
      color: var(--muted);
      width: 100%;
      font-family: inherit;
    }

    .toolbar-select {
      cursor: pointer;
    }

    .table-wrap {
      padding: 14px 12px 8px;
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 900px;
    }

    thead th {
      text-align: left;
      font-size: 12px;
      color: #222;
      padding: 16px 10px;
      border-bottom: 1px solid var(--line);
    }

    tbody td {
      padding: 10px;
      border-bottom: 1px solid #efefef;
      font-size: 13px;
      color: #333;
      vertical-align: middle;
    }

    .doctor-cell {
      display: flex;
      align-items: center;
      gap: 12px;
      background: #eaf4ff;
      border-left: 3px solid #4f46e5;
      border-radius: 10px;
      padding: 10px;
      max-width: 220px;
    }

    .avatar {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      display: grid;
      place-items: center;
      color: #fff;
      font-size: 14px;
      flex-shrink: 0;
    }

    .a-blue { background: #3b82f6; }
    .a-green { background: #22c55e; }
    .a-orange { background: #f97316; }
    .a-purple { background: #a855f7; }
    .a-red { background: #ef4444; }

    .doctor-info strong {
      display: block;
      font-size: 12px;
      margin-bottom: 3px;
    }

    .doctor-info span {
      font-size: 10px;
      color: #718096;
    }

    .date-muted {
      color: #b0b0b0;
    }

    .slot-id {
      font-weight: 700;
    }

    .status {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 8px 14px;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 600;
    }

    .status.occupied {
      background: var(--green-bg);
      color: var(--green-text);
    }

    .status.available {
      background: var(--gray-badge);
      color: #6b7280;
    }

    .actions {
      display: flex;
      gap: 12px;
      align-items: center;
    }

    .action-btn {
      border: none;
      background: transparent;
      cursor: pointer;
      font-size: 15px;
      padding: 0;
    }

    .delete { color: #f19999; }
    .edit { color: #8ac89a; }

    .pagination {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 4px;
      padding: 10px 0 6px;
      color: #b8b8b8;
      font-size: 12px;
    }

    .page-pill {
      width: 20px;
      height: 20px;
      border-radius: 4px;
      display: grid;
      place-items: center;
      border: 1px solid #ededed;
      background: #f8f8f8;
    }

    .page-pill.active {
      background: #6068e8;
      color: #fff;
    }

    .empty-row td {
      text-align: center;
      color: #9ca3af;
      padding: 28px 10px;
      font-size: 14px;
    }

    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.12);
      display: none;
      align-items: center;
      justify-content: center;
      padding: 20px;
      z-index: 50;
    }

    .modal-overlay.show {
      display: flex;
    }

    .modal {
      width: 100%;
      max-width: 430px;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 20px 55px rgba(0,0,0,0.18);
      border: 1px solid #dedede;
      padding: 18px 18px 16px;
    }

    .detail-modal {
      max-width: 460px;
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
    }

    .modal-header h3 {
      margin: 0;
      font-size: 16px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .close-btn {
      border: none;
      background: transparent;
      font-size: 20px;
      cursor: pointer;
      color: #666;
    }

    .form-group {
      margin-bottom: 14px;
    }

    .form-group label {
      display: block;
      margin-bottom: 7px;
      font-size: 14px;
      color: #111;
      font-weight: 600;
    }

    .input,
    .textarea,
    select {
      width: 100%;
      border: 1px solid #ececec;
      background: #f6f6f8;
      border-radius: 7px;
      padding: 11px 12px;
      font-size: 13px;
      color: #555;
      outline: none;
    }

    .textarea {
      min-height: 92px;
      resize: none;
      font-family: inherit;
    }

    .input-icon {
      position: relative;
    }

    .input-icon .icon {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: #666;
      font-size: 16px;
      pointer-events: none;
    }

    .modal-footer {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-top: 12px;
      padding-top: 10px;
      border-top: 1px solid #f0f0f0;
    }

    .btn {
      border-radius: 8px;
      padding: 11px 14px;
      font-size: 14px;
      cursor: pointer;
      border: 1px solid #dcdcdc;
    }

    .btn-cancel {
      background: #fff;
      color: #111;
    }

    .btn-primary {
      background: #060822;
      border-color: #060822;
      color: #fff;
    }

    .btn-danger {
      background: #e11d48;
      border-color: #e11d48;
      color: #fff;
    }

    .btn-outline {
      background: #fff;
      color: #111;
    }

    .view-section {
      display: none;
    }

    .view-section.active {
      display: block;
    }

    .calendar-wrap {
      padding: 12px 14px 16px;
    }

    .calendar-toolbar {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 18px;
      margin-bottom: 12px;
      flex-wrap: wrap;
    }

    .week-nav-btn {
      border: 1px solid #e5e7eb;
      background: #fff;
      border-radius: 6px;
      width: 28px;
      height: 28px;
      cursor: pointer;
      font-size: 14px;
    }

    .week-range {
      font-size: 13px;
      color: #6b7280;
      min-width: 200px;
      text-align: center;
    }

    .calendar-box {
      border: 1px solid #e5e7eb;
      border-radius: 14px;
      overflow: hidden;
      background: #fff;
    }

    .calendar-grid {
      display: grid;
      grid-template-columns: 120px repeat(7, 1fr);
      min-width: 900px;
    }

    .calendar-head {
      background: #f9fafb;
      font-size: 12px;
      color: #374151;
      padding: 14px 12px;
      border-bottom: 1px solid #e5e7eb;
      border-right: 1px solid #e5e7eb;
      min-height: 48px;
    }

    .time-col {
      background: #f9fafb;
      font-size: 13px;
      color: #6b7280;
      padding: 16px 12px;
      border-right: 1px solid #e5e7eb;
      border-bottom: 1px solid #e5e7eb;
      min-height: 72px;
      display: flex;
      align-items: flex-start;
    }

    .calendar-cell {
      position: relative;
      padding: 8px;
      border-right: 1px solid #e5e7eb;
      border-bottom: 1px solid #e5e7eb;
      min-height: 72px;
      background: #fff;
    }

    .slot-pill {
      display: inline-block;
      padding: 5px 10px;
      border-radius: 999px;
      font-size: 11px;
      color: #fff;
      white-space: nowrap;
      max-width: 100%;
      overflow: hidden;
      text-overflow: ellipsis;
      cursor: pointer;
      margin-bottom: 4px;
    }

    .slot-pill.available {
      background: #b8b8b8;
    }

    .slot-pill.occupied {
      background: #22c55e;
    }

    .legend-box {
      margin-top: 14px;
      width: 130px;
      border: 1px solid #ededed;
      background: #fff;
      box-shadow: 0 3px 10px rgba(0,0,0,0.06);
      padding: 10px;
      border-radius: 4px;
      font-size: 9px;
      color: #6b7280;
    }

    .legend-title {
      font-size: 8px;
      margin-bottom: 6px;
      letter-spacing: 0.5px;
    }

    .legend-item {
      display: flex;
      align-items: center;
      gap: 6px;
      margin-bottom: 4px;
      font-size: 9px;
    }

    .legend-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      flex-shrink: 0;
    }

    .legend-dot.green {
      background: #22c55e;
    }

    .legend-dot.gray {
      background: #b8b8b8;
    }

    .detail-row {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      margin-bottom: 12px;
    }

    .detail-icon {
      width: 18px;
      color: #8b95a7;
      font-size: 16px;
      line-height: 1.4;
      margin-top: 2px;
      flex-shrink: 0;
    }

    .detail-content label {
      display: block;
      color: #4b5563;
      font-size: 14px;
      margin-bottom: 4px;
    }

    .detail-content .value {
      font-size: 15px;
      color: #111827;
    }

    .detail-notes {
      background: #f6f6f8;
      min-height: 76px;
      border-radius: 6px;
      padding: 12px;
      color: #111827;
    }

    .detail-actions {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-top: 14px;
      padding-top: 16px;
      border-top: 1px solid #f0f0f0;
    }

    @media (max-width: 900px) {
      .menu {
        gap: 16px;
      }

      .hero-title {
        font-size: 48px;
      }
    }

    @media (max-width: 680px) {
      .nav {
        flex-direction: column;
        align-items: stretch;
      }

      .menu {
        justify-content: flex-start;
      }

      .hero-title {
        font-size: 38px;
        margin-top: 34px;
      }

      .card-header,
      .toolbar {
        justify-content: flex-start;
      }

      .search-box,
      .filter-box {
        width: 100%;
      }

      .modal-footer,
      .detail-actions {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <section class="hero">
    <nav class="nav">
      <div class="brand">
        <img src="CareClinicLogo.jpeg" alt="CareClinic" class="logo" />
        <small>CareClinic</small>
      </div>

      <div class="menu">
        <a href="#">Dashboard</a>
        <a href="#">Patient Directory</a>
        <a href="#">Appointment</a>
        <a href="#" class="active">Doctor Rosters</a>
        <a href="#">Medical Records</a>
        <a href="#">Profile</a>
      </div>

      <button class="logout-btn" onclick="window.location.href='logout.php'">Logout</button>
    </nav>

    <h1 class="hero-title">Doctor Rosters</h1>
  </section>

  <main class="page">
    <section class="card schedule-card">
      <div class="card-header">
        <div class="card-title">Doctors Schedule</div>

        <div class="header-actions">
          <div class="toggle-group">
            <button class="toggle-btn active" id="listViewBtn">List</button>
            <button class="toggle-btn" id="calendarViewBtn">Calendar</button>
          </div>

          <button class="add-btn" id="openModalBtn">
            <span>＋</span> Add New Slot
          </button>
        </div>
      </div>

      <div id="listSection" class="view-section active">
        <div class="toolbar">
          <div class="search-box">
            <span>🔍</span>
            <input
              type="text"
              id="searchInput"
              class="toolbar-input"
              placeholder="Search..."
            />
          </div>

          <div class="filter-box">
            <span>↕</span>
            <select id="sortSelect" class="toolbar-select">
              <option value="latest">Latest Added</option>
              <option value="doctor-az">Doctor A-Z</option>
              <option value="doctor-za">Doctor Z-A</option>
              <option value="date-asc">Date Earliest</option>
              <option value="date-desc">Date Latest</option>
              <option value="time-asc">Time Earliest</option>
              <option value="time-desc">Time Latest</option>
            </select>
          </div>
        </div>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Doctor</th>
                <th>Time Section</th>
                <th>Date</th>
                <th>Slot ID</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="scheduleBody">
              <tr class="empty-row">
                <td colspan="6">No slots added yet</td>
              </tr>
            </tbody>
          </table>

          <div class="pagination">
            <span>‹</span>
            <span class="page-pill active">1</span>
            <span class="page-pill">2</span>
            <span class="page-pill">3</span>
            <span class="page-pill">4</span>
            <span class="page-pill">5</span>
            <span>›</span>
          </div>
        </div>
      </div>

      <div id="calendarSection" class="view-section">
        <div class="calendar-wrap">
          <div class="calendar-toolbar">
            <button class="week-nav-btn" id="prevWeekBtn">‹</button>
            <div class="week-range" id="weekRangeText">-</div>
            <button class="week-nav-btn" id="nextWeekBtn">›</button>
          </div>

          <div class="table-wrap">
            <div class="calendar-box">
              <div class="calendar-grid" id="calendarGrid"></div>
            </div>
          </div>

          <div class="legend-box">
            <div class="legend-title">STATUS LEGEND</div>
            <div class="legend-item">
              <span class="legend-dot green"></span>
              <span>Occupied Slot</span>
            </div>
            <div class="legend-item">
              <span class="legend-dot gray"></span>
              <span>Available Slot</span>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <div class="modal-overlay" id="slotModal">
    <div class="modal">
      <div class="modal-header">
        <h3 id="formModalTitle">＋ Add New Slot</h3>
        <button class="close-btn" id="closeModalBtn">×</button>
      </div>

      <div class="form-group">
        <label>Doctor *</label>
        <select id="doctorSelect">
          <option selected disabled value="">Select a doctor</option>
          <option value="Dr. Ahmed">Dr. Ahmed</option>
          <option value="Dr. Sarah">Dr. Sarah</option>
          <option value="Dr. Fatima">Dr. Fatima</option>
          <option value="Dr. Aiman">Dr. Aiman</option>
          <option value="Dr. Hasan">Dr. Hasan</option>
          <option value="Dr. Ammar">Dr. Ammar</option>
        </select>
      </div>

      <div class="form-group">
        <label>Date *</label>
        <div class="input-icon">
          <input class="input" id="dateInput" type="date" />
          <span class="icon">🗓</span>
        </div>
      </div>

      <div class="form-group">
        <label>Start Time *</label>
        <select id="timeSelect">
          <option selected disabled value="">Choose a time</option>
          <option value="09:00 AM">09:00 AM</option>
          <option value="10:00 AM">10:00 AM</option>
          <option value="11:00 AM">11:00 AM</option>
          <option value="12:00 PM">12:00 PM</option>
          <option value="02:00 PM">02:00 PM</option>
          <option value="03:00 PM">03:00 PM</option>
          <option value="04:00 PM">04:00 PM</option>
          <option value="05:00 PM">05:00 PM</option>
        </select>
      </div>

      <div class="form-group">
        <label>Notes</label>
        <textarea class="textarea" id="notesInput" placeholder="Additional notes or subject details..."></textarea>
      </div>

      <div class="modal-footer">
        <button class="btn btn-cancel" id="cancelModalBtn">Cancel</button>
        <button class="btn btn-primary" id="saveSlotBtn">Add Slot</button>
      </div>
    </div>
  </div>

  <div class="modal-overlay" id="detailsModal">
    <div class="modal detail-modal">
      <div class="modal-header">
        <h3>🗓 Appointment Details</h3>
        <button class="close-btn" id="closeDetailsBtn">×</button>
      </div>

      <div class="detail-row">
        <div class="detail-icon">👤</div>
        <div class="detail-content">
          <label>Doctor</label>
          <div class="value" id="detailDoctor">-</div>
        </div>
      </div>

      <div class="detail-row">
        <div class="detail-icon">🗓</div>
        <div class="detail-content">
          <label>Date</label>
          <div class="value" id="detailDate">-</div>
        </div>
      </div>

      <div class="detail-row">
        <div class="detail-icon">🕘</div>
        <div class="detail-content">
          <label>Time</label>
          <div class="value" id="detailTime">-</div>
        </div>
      </div>

      <div class="detail-row">
        <div class="detail-icon">📝</div>
        <div class="detail-content" style="width: 100%;">
          <label>Notes</label>
          <div class="detail-notes" id="detailNotes">No notes added.</div>
        </div>
      </div>

      <div class="detail-actions">
        <button class="btn btn-outline" id="detailEditBtn">✎ Edit</button>
        <button class="btn btn-danger" id="detailDeleteBtn">🗑 Delete</button>
      </div>
    </div>
  </div>

  <script>
    const openModalBtn = document.getElementById('openModalBtn');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const cancelModalBtn = document.getElementById('cancelModalBtn');
    const saveSlotBtn = document.getElementById('saveSlotBtn');
    const slotModal = document.getElementById('slotModal');
    const formModalTitle = document.getElementById('formModalTitle');

    const detailsModal = document.getElementById('detailsModal');
    const closeDetailsBtn = document.getElementById('closeDetailsBtn');
    const detailDoctor = document.getElementById('detailDoctor');
    const detailDate = document.getElementById('detailDate');
    const detailTime = document.getElementById('detailTime');
    const detailNotes = document.getElementById('detailNotes');
    const detailEditBtn = document.getElementById('detailEditBtn');
    const detailDeleteBtn = document.getElementById('detailDeleteBtn');

    const doctorSelect = document.getElementById('doctorSelect');
    const dateInput = document.getElementById('dateInput');
    const timeSelect = document.getElementById('timeSelect');
    const notesInput = document.getElementById('notesInput');

    const searchInput = document.getElementById('searchInput');
    const sortSelect = document.getElementById('sortSelect');

    const scheduleBody = document.getElementById('scheduleBody');
    const listViewBtn = document.getElementById('listViewBtn');
    const calendarViewBtn = document.getElementById('calendarViewBtn');
    const listSection = document.getElementById('listSection');
    const calendarSection = document.getElementById('calendarSection');

    if (location.protocol !== 'http:' && location.protocol !== 'https:') {
      alert('Sila buka aplikasi ini melalui web server (contohnya http://localhost) supaya PHP endpoint boleh berfungsi.');
    }

    const calendarGrid = document.getElementById('calendarGrid');
    const weekRangeText = document.getElementById('weekRangeText');
    const prevWeekBtn = document.getElementById('prevWeekBtn');
    const nextWeekBtn = document.getElementById('nextWeekBtn');

    const doctorsData = {
      "Dr. Ahmed": {
        role: "General Practitioner (GP)",
        avatarClass: "a-blue",
        avatarSymbol: "👤"
      },
      "Dr. Sarah": {
        role: "Family Medicine Doctor",
        avatarClass: "a-green",
        avatarSymbol: "👤"
      },
      "Dr. Fatima": {
        role: "Gynecologist",
        avatarClass: "a-orange",
        avatarSymbol: "👤"
      },
      "Dr. Aiman": {
        role: "Pediatrician",
        avatarClass: "a-blue",
        avatarSymbol: "👤"
      },
      "Dr. Hasan": {
        role: "General Practitioner (GP)",
        avatarClass: "a-purple",
        avatarSymbol: "👤"
      },
      "Dr. Ammar": {
        role: "General Practitioner (GP)",
        avatarClass: "a-red",
        avatarSymbol: "👤"
      }
    };

    let editingSlotId = null;
    let selectedDetailSlotId = null;
    let slots = [];

    const calendarTimes = [
      "09:00 AM",
      "10:00 AM",
      "11:00 AM",
      "12:00 PM",
      "02:00 PM",
      "03:00 PM",
      "04:00 PM",
      "05:00 PM"
    ];

    let currentWeekStart = getStartOfWeek(new Date());

    function getStartOfWeek(date) {
      const d = new Date(date);
      const day = d.getDay();
      const diff = d.getDate() - day;
      d.setDate(diff);
      d.setHours(0, 0, 0, 0);
      return d;
    }

    function addDays(date, days) {
      const newDate = new Date(date);
      newDate.setDate(newDate.getDate() + days);
      return newDate;
    }

    function parseLocalDate(dateString) {
      if (!dateString) return null;

      const parts = dateString.split('-').map(Number);
      if (parts.length !== 3 || parts.some(Number.isNaN)) return null;

      return new Date(parts[0], parts[1] - 1, parts[2]);
    }

    function getDateKey(date) {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    }

    function formatDate(dateString) {
      if (!dateString) return null;
      const date = parseLocalDate(dateString);
      if (isNaN(date.getTime())) return null;

      return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
      });
    }

    function formatWeekRange(startDate) {
      const endDate = addDays(startDate, 6);

      const startText = startDate.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric'
      });

      const endText = endDate.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
      });

      return `${startText} - ${endText}`;
    }

    function timeToMinutes(timeString) {
      const [time, meridiem] = timeString.split(' ');
      let [hours, minutes] = time.split(':').map(Number);

      if (meridiem === 'PM' && hours !== 12) {
        hours += 12;
      }

      if (meridiem === 'AM' && hours === 12) {
        hours = 0;
      }

      return (hours * 60) + minutes;
    }

    function getProcessedSlots() {
      const searchTerm = searchInput.value.trim().toLowerCase();
      const sortValue = sortSelect.value;

      let processed = [...slots];

      if (searchTerm) {
        processed = processed.filter(slot => {
          const searchableText = `
            ${slot.doctor}
            ${slot.role}
            ${slot.displayDate}
            ${slot.timeValue}
            ${slot.slotId}
            ${slot.notes || ''}
            ${slot.status}
          `.toLowerCase();

          return searchableText.includes(searchTerm);
        });
      }

      processed.sort((a, b) => {
        switch (sortValue) {
          case 'doctor-az':
            return a.doctor.localeCompare(b.doctor);

          case 'doctor-za':
            return b.doctor.localeCompare(a.doctor);

          case 'date-asc':
            return parseLocalDate(a.rawDate) - parseLocalDate(b.rawDate);

          case 'date-desc':
            return parseLocalDate(b.rawDate) - parseLocalDate(a.rawDate);

          case 'time-asc':
            return timeToMinutes(a.timeValue) - timeToMinutes(b.timeValue);

          case 'time-desc':
            return timeToMinutes(b.timeValue) - timeToMinutes(a.timeValue);

          case 'latest':
          default:
            return b.slotId - a.slotId;
        }
      });

      return processed;
    }

    function getStatusBadge(status) {
      if (status === 'occupied') {
        return `<span class="status occupied">◌ Occupied</span>`;
      }
      return `<span class="status available">⌛ Available</span>`;
    }

    function getCalendarPillClass(status) {
      return status === 'occupied' ? 'occupied' : 'available';
    }

    function openFormModal(mode = 'add', slot = null) {
      slotModal.classList.add('show');

      if (mode === 'add') {
        editingSlotId = null;
        formModalTitle.textContent = '＋ Add New Slot';
        saveSlotBtn.textContent = 'Add Slot';
        resetForm();
        return;
      }

      if (slot) {
        editingSlotId = slot.slotId;
        formModalTitle.textContent = '🗓 Appointment Details';
        saveSlotBtn.textContent = 'Save Changes';

        doctorSelect.value = slot.doctor;
        dateInput.value = slot.rawDate;
        timeSelect.value = slot.timeValue;
        notesInput.value = slot.notes || '';
      }
    }

    function closeFormModal() {
      slotModal.classList.remove('show');
    }

    function openDetailsModal(slotId) {
      const slot = slots.find(item => item.slotId === slotId);
      if (!slot) return;

      selectedDetailSlotId = slotId;
      detailDoctor.textContent = slot.doctor;
      detailDate.textContent = slot.displayDate;
      detailTime.textContent = slot.timeValue;
      detailNotes.textContent = slot.notes ? slot.notes : 'No notes added.';
      detailsModal.classList.add('show');
    }

    function closeDetailsModal() {
      detailsModal.classList.remove('show');
    }

    function resetForm() {
      doctorSelect.value = "";
      dateInput.value = "";
      timeSelect.value = "";
      notesInput.value = "";
    }

    function switchToListView() {
      listSection.classList.add('active');
      calendarSection.classList.remove('active');
      listViewBtn.classList.add('active');
      calendarViewBtn.classList.remove('active');
      renderTable();
    }

    function switchToCalendarView() {
      listSection.classList.remove('active');
      calendarSection.classList.add('active');
      listViewBtn.classList.remove('active');
      calendarViewBtn.classList.add('active');
      renderCalendar();
    }

    function renderTable() {
      const processedSlots = getProcessedSlots();

      if (processedSlots.length === 0) {
        scheduleBody.innerHTML = `
          <tr class="empty-row">
            <td colspan="6">No matching slots found</td>
          </tr>
        `;
        return;
      }

      scheduleBody.innerHTML = processedSlots.map((slot) => {
        return `
          <tr>
            <td>
              <div class="doctor-cell">
                <div class="avatar ${slot.avatarClass}">${slot.avatarSymbol}</div>
                <div class="doctor-info">
                  <strong>${slot.doctor}</strong>
                  <span>${slot.role}</span>
                </div>
              </div>
            </td>
            <td>${slot.time}</td>
            <td class="date-muted">${slot.displayDate}</td>
            <td class="slot-id">${slot.slotId}</td>
            <td>${getStatusBadge(slot.status)}</td>
            <td>
              <div class="actions">
                <button class="action-btn delete" data-action="delete" data-id="${slot.slotId}">🗑</button>
                <button class="action-btn edit" data-action="edit" data-id="${slot.slotId}">✎</button>
              </div>
            </td>
          </tr>
        `;
      }).join('');
    }

    function renderCalendar() {
      const processedSlots = getProcessedSlots();
      weekRangeText.textContent = formatWeekRange(currentWeekStart);

      const days = [];
      for (let i = 0; i < 7; i++) {
        days.push(addDays(currentWeekStart, i));
      }

      let html = `
        <div class="calendar-head">Time</div>
        ${days.map(day => `<div class="calendar-head">${day.toLocaleDateString('en-US', { weekday: 'long' })}</div>`).join('')}
      `;

      calendarTimes.forEach(time => {
        html += `<div class="time-col">${time}</div>`;

        days.forEach(day => {
          const dayKey = getDateKey(day);
          const matchedSlots = processedSlots.filter(slot => slot.rawDate === dayKey && slot.timeValue === time);

          html += `<div class="calendar-cell">`;

          matchedSlots.forEach(slot => {
            html += `
              <div class="slot-pill ${getCalendarPillClass(slot.status)}" data-slot-pill="${slot.slotId}" title="${slot.doctor}">
                ${slot.doctor}
              </div>
            `;
          });

          html += `</div>`;
        });
      });

      calendarGrid.innerHTML = html;
    }

    async function saveSlot() {
  const selectedDoctor = doctorSelect.value;
  const enteredDate = dateInput.value;
  const selectedTime = timeSelect.value;
  const notesValue = notesInput.value.trim();

  if (!selectedDoctor) {
    alert('Please select a doctor.');
    return;
  }

  if (!enteredDate) {
    alert('Please select a date.');
    return;
  }

  const formattedDate = formatDate(enteredDate);

  if (!formattedDate) {
    alert('Please select a valid date.');
    return;
  }

  if (!selectedTime) {
    alert('Please choose a start time.');
    return;
  }

  const doctorInfo = doctorsData[selectedDoctor];

  const slotData = {
    slotId: editingSlotId,
    doctor: selectedDoctor,
    role: doctorInfo.role,
    avatarClass: doctorInfo.avatarClass,
    avatarSymbol: doctorInfo.avatarSymbol,
    rawDate: enteredDate,
    timeValue: selectedTime,
    notes: notesValue
  };

  const url = editingSlotId === null ? './add_slot.php' : './update_slot.php';

  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(slotData)
    });

    if (!response.ok) {
      const text = await response.text();
      throw new Error(`HTTP ${response.status} ${response.statusText}: ${text}`);
    }

    const result = await response.json();

    if (result.success) {
      currentWeekStart = getStartOfWeek(parseLocalDate(enteredDate));
      closeFormModal();
      resetForm();
      editingSlotId = null;
      await loadSlotsFromDatabase();
      alert(result.message);
    } else {
      alert(result.message);
    }
  } catch (error) {
    console.error('Error saving slot:', error);
    alert('Failed to save slot.');
  }
}

   async function deleteSlot(slotId) {
  try {
    const response = await fetch('./delete_slot.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ slotId })
    });

    if (!response.ok) {
      const text = await response.text();
      throw new Error(`HTTP ${response.status} ${response.statusText}: ${text}`);
    }

    const result = await response.json();

    if (result.success) {
      closeDetailsModal();
      await loadSlotsFromDatabase();
      alert(result.message);
    } else {
      alert(result.message);
    }
  } catch (error) {
    console.error('Error deleting slot:', error);
    alert('Failed to delete slot.');
  }
}

    openModalBtn.addEventListener('click', () => openFormModal('add'));
    closeModalBtn.addEventListener('click', closeFormModal);
    cancelModalBtn.addEventListener('click', closeFormModal);
    saveSlotBtn.addEventListener('click', saveSlot);

    listViewBtn.addEventListener('click', switchToListView);
    calendarViewBtn.addEventListener('click', switchToCalendarView);

    prevWeekBtn.addEventListener('click', () => {
      currentWeekStart = addDays(currentWeekStart, -7);
      renderCalendar();
    });

    nextWeekBtn.addEventListener('click', () => {
      currentWeekStart = addDays(currentWeekStart, 7);
      renderCalendar();
    });

    closeDetailsBtn.addEventListener('click', closeDetailsModal);

    detailEditBtn.addEventListener('click', () => {
      const slot = slots.find(item => item.slotId === selectedDetailSlotId);
      if (!slot) return;

      closeDetailsModal();
      openFormModal('edit', slot);
    });

    detailDeleteBtn.addEventListener('click', () => {
      if (selectedDetailSlotId === null) return;

      const confirmDelete = confirm('Are you sure you want to delete this slot?');
      if (confirmDelete) {
        deleteSlot(selectedDetailSlotId);
      }
    });

    scheduleBody.addEventListener('click', (e) => {
      const actionButton = e.target.closest('[data-action]');
      if (!actionButton) return;

      const slotId = Number(actionButton.dataset.id);
      const action = actionButton.dataset.action;
      const slot = slots.find(item => item.slotId === slotId);
      if (!slot) return;

      if (action === 'edit') {
        openFormModal('edit', slot);
      }

      if (action === 'delete') {
        openDetailsModal(slotId);
      }
    });

    calendarGrid.addEventListener('click', (e) => {
      const pill = e.target.closest('[data-slot-pill]');
      if (!pill) return;

      const slotId = Number(pill.dataset.slotPill);
      openDetailsModal(slotId);
    });

    searchInput.addEventListener('input', () => {
      renderTable();
    });

    sortSelect.addEventListener('change', () => {
      renderTable();
    });

    slotModal.addEventListener('click', (e) => {
      if (e.target === slotModal) {
        closeFormModal();
      }
    });

    detailsModal.addEventListener('click', (e) => {
      if (e.target === detailsModal) {
        closeDetailsModal();
      }
    });

  loadSlotsFromDatabase();

    async function loadSlotsFromDatabase() {
  try {
    const response = await fetch('./get_slots.php');
    if (!response.ok) {
      const text = await response.text();
      throw new Error(`HTTP ${response.status} ${response.statusText}: ${text}`);
    }
    const data = await response.json();

    slots = data;
    renderTable();
    renderCalendar();
  } catch (error) {
    console.error('Error loading slots:', error);
    alert('Failed to load data from database.');
  }
}

  </script>
</body>
</html>
