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
        calendarConfig.translations = { thTH };
    }

    const calendar = createCalendar(calendarConfig);
    calendar.render(document.getElementById('calendar-app'));
</script>