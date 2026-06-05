<?php
// calendar.php – embeds inside layout.php (no standalone HTML boilerplate needed)
global $config;
$eventsFile = $config['PATH_TO_DATA'] . 'calendar-events.json';
$eventsJson = file_exists($eventsFile) ? file_get_contents($eventsFile) : '[]';

// Detect current language from session (set by layout.php)
$lang = $_SESSION['LANGUAGE'] ?? 'th';
$calendarLocale = ($lang === 'th') ? 'th-TH' : 'en-US';
?>

<link rel="stylesheet" href="./assets/schedule-x-4.6.0/css/schedule-x.css">

<style>
    .calendar-wrapper {
        max-width: 900px;
        margin: 0 auto;
        padding: 0 12px;
    }

    .calendar-header {
        padding: 14px 0 10px;
        display: flex;
        align-items: center;
        gap: 10px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        margin-bottom: 12px;
    }

    .calendar-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: #a5b4fc;
    }

    #calendar-app {
        height: 680px;
    }

    .calendar-container {
        max-width: 640px;
        margin: 20px auto;
        padding: 0 12px;
        height: calc(100vh - 40px);
        box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.3);
        border-radius: 20px;
        overflow: hidden;
        background: #1e2937;
    }

    .header {
        background: rgba(15, 23, 42, 0.95);
        padding: 16px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid #334155;
    }

    .logo {
        font-size: 1.5rem;
        font-weight: 800;
        background: linear-gradient(90deg, #a5b4fc, #c4d0ff);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* === CAPSULE BUTTONS === */
    .sx__button,
    .sx__today-button,
    .sx__view-selection-selected-item,
    .sx__date-input {
        border-radius: 9999px !important;
        /* Makes buttons fully capsule/pill */
        padding: 4px 12px !important;
        font-weight: 600;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        height: 32px !important;
    }
    
    .sx__calendar-header {
        align-items: center !important;
    }

    /* Make view switcher buttons more capsule */
    .sx__view-switcher button {
        border-radius: 9999px !important;
    }

    /* Today button & other controls */
    .sx__today-button,
    .sx__date-picker-button {
        border-radius: 9999px !important;
    }

    /* Optional: nicer hover effect */
    .sx__button:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.2);
    }
</style>

<div class="calendar-wrapper">
    <div class="calendar-header">
        <span class="calendar-title"><?php echo ($lang === 'th') ? 'ปฏิทิน' : 'Calendar'; ?></span>
    </div>
    <div id="calendar-app"></div>
</div>

<!-- Schedule-X dependencies (local) -->
<!-- Dependencies (exact order from official docs) -->
<script src="./assets/schedule-x-4.6.0/js/preact.min.js"></script>
<script src="./assets/schedule-x-4.6.0/js/hooks.umd.js"></script>
<script src="./assets/schedule-x-4.6.0/js/signals-core.min.js"></script>
<script src="./assets/schedule-x-4.6.0/js/signals.min.js"></script>
<script src="./assets/schedule-x-4.6.0/js/jsxRuntime.umd.js"></script>
<script src="./assets/schedule-x-4.6.0/js/compat.umd.js"></script>
<script src="./assets/schedule-x-4.6.0/js/core.umd.js"></script>

<script type="module">
    const {
        createCalendar,
        createViewMonthGrid,
        createViewWeek,
        createViewDay,
        createViewMonthAgenda
    } = window.SXCalendar;

    const events = <?= $eventsJson ?>;
    const currentLocale = '<?= $calendarLocale ?>';

    // Thai translations for Schedule-X UI labels
    const thTH = {
        Week: 'สัปดาห์',
        Month: 'เดือน',
        Day: 'วัน',
        Today: 'วันนี้',
        'Month Agenda': 'รายการเดือน',
        'Start time': 'เวลาเริ่มต้น',
        'End time': 'เวลาสิ้นสุด',
        'All day': 'ทั้งวัน',
        'No events': 'ไม่มีกิจกรรม',
        'Delete event': 'ลบกิจกรรม',
        'Save event': 'บันทึกกิจกรรม',
        'Event title': 'ชื่อกิจกรรม',
        'Add event': 'เพิ่มกิจกรรม',
        'Edit event': 'แก้ไขกิจกรรม',
        'Cancel': 'ยกเลิก',
        'Close': 'ปิด',
        'Previous': 'ก่อนหน้า',
        'Next': 'ถัดไป',
        'previous period': 'ช่วงก่อนหน้า',
        'next period': 'ช่วงถัดไป',
    };

    // Build config
    const calendarConfig = {
        defaultView: 'month-agenda',
        selectedDate: '<?= date('Y-m-d') ?>',
        locale: currentLocale,
        views: [
            createViewMonthGrid(),
            createViewWeek(),
            createViewDay(),
            createViewMonthAgenda()
        ],
        events: events,
        theme: 'dark'
    };

    // Add Thai translations when locale is Thai
    if (currentLocale === 'th-TH') {
        calendarConfig.translations = {
            thTH
        };
    }

    const calendar = createCalendar(calendarConfig);
    calendar.render(document.getElementById('calendar-app'));
</script>