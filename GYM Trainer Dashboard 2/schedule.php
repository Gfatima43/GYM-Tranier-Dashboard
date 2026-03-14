<?php
session_start();

require 'middleware/middle.php';
requireAuth();

$database = require 'bootstrap.php';
$pdo = $database->pdo;

$logged_in = isset($_SESSION['user_id']);
$user_name  = $_SESSION['user_name'] ?? '';
$user_role  = $_SESSION['role']      ?? '';
$userId    = $_SESSION['user_id']    ?? '';

// $currentPage = 'schedule';

$isAdmin   = isAdmin();
$isTrainer = isTrainer();
$trainerId = null;

$message     = '';
$messageType = '';

// ════════════════════════════════════════════════════════════════
//  POST — assign / delete schedule
// ════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'assign_schedule') {

        $clientId = (int)($_POST['client_id'] ?? 0);
        $planId   = (int)($_POST['plan_id'] ?? 0);
        $selectedDays = $_POST['days'] ?? [];

        if ($clientId < 1 || $planId < 1 || empty($selectedDays)) {
            $message = 'Please select a client, plan, and at least one day.';
            $messageType = 'danger';
        } else {

            // Full name → short column mapping
            $dayMap = [
                'Monday'    => 'MON',
                'Tuesday'   => 'TUE',
                'Wednesday' => 'WED',
                'Thursday'  => 'THU',
                'Friday'    => 'FRI',
                'Saturday'  => 'SAT',
                'Sunday'    => 'SUN'
            ];

            // default values
            $days = [
                'MON' => 0,
                'TUE' => 0,
                'WED' => 0,
                'THU' => 0,
                'FRI' => 0,
                'SAT' => 0,
                'SUN' => 0
            ];

            for ($i = 0; $i < count($selectedDays); $i++) {
                $day = $selectedDays[$i];
                $days[$dayMap[$day]] = 1;
            }

            // Remove existing schedule for this client 
            $pdo->prepare("DELETE FROM client_schedules WHERE client_id = ?")->execute([$clientId]);
            $stmt = $pdo->prepare("INSERT INTO client_schedules (client_id, plan_id, MON, TUE, WED, THU, FRI, SAT, SUN) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)   ");
            $stmt->execute([
                $clientId,
                $planId,
                $days['MON'],
                $days['TUE'],
                $days['WED'],
                $days['THU'],
                $days['FRI'],
                $days['SAT'],
                $days['SUN']
            ]);

            $message = 'Schedule assigned successfully!';
            $messageType = 'success';
        }
    }

    // ── DELETE SCHEDULE ───────────────────────────────────────
    elseif ($action === 'delete_schedule') {
        $clientId = (int)($_POST['client_id'] ?? 0);
        if ($clientId > 0) {
            $pdo->prepare("DELETE FROM client_schedules WHERE client_id = ?")->execute([$clientId]);
            $message = 'Schedule removed.';
            $messageType = 'success';
        }
    }

    $_SESSION['flash'] = ['msg' => $message, 'type' => $messageType];
    header('Location: schedule.php');
    exit();
}

if (isset($_SESSION['flash'])) {
    $message     = $_SESSION['flash']['msg'];
    $messageType = $_SESSION['flash']['type'];
    unset($_SESSION['flash']);
}

// ════════════════════════════════════════════════════════════════
//  READ data
// ════════════════════════════════════════════════════════════════

// Clients — trainers see only their own, admin sees all
if ($isTrainer) {
    $trainerRow = $pdo->prepare("SELECT id FROM trainers WHERE user_id=?");
    $trainerRow->execute([$userId]);
    $trainerId = $trainerRow->fetchColumn();
}

// Plans
if ($isTrainer && $trainerId) {
    $stmt = $pdo->prepare("SELECT wp.*, COUNT(w.id) AS exercise_count 
    FROM workout_plans wp 
    LEFT JOIN workouts w ON w.plan_id = wp.id WHERE wp.trainer_id = ? 
    GROUP BY wp.id ORDER BY wp.id DESC");
    $stmt->execute([$trainerId]);
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $plans = $pdo->query("SELECT wp.*, COUNT(w.id) AS exercise_count 
    FROM workout_plans wp 
    LEFT JOIN workouts w ON w.plan_id=wp.id 
    GROUP BY wp.id 
    ORDER BY wp.id DESC")->fetchAll(PDO::FETCH_ASSOC);
}

// Exercises by plan
$allExercises = $pdo->query("SELECT * FROM workouts ORDER BY plan_id, id")->fetchAll(PDO::FETCH_ASSOC);
$exercisesByPlan = [];
foreach ($allExercises as $ex) {
    $exercisesByPlan[$ex['plan_id']][] = $ex;
}

// Clients
if ($isTrainer && $trainerId) {
    $clients = $pdo->prepare("SELECT * FROM clients WHERE trainer_id=? ORDER BY firstName");
    $clients->execute([$trainerId]);
    $clients = $clients->fetchAll(PDO::FETCH_ASSOC);
} else {
    $clients = $pdo->query("SELECT * FROM clients ORDER BY firstName")->fetchAll(PDO::FETCH_ASSOC);
}

// All schedules with full detail
$scheduleRows = $pdo->query("
    SELECT cs.id, cs.MON, cs.TUE, cs.WED, cs.THU, cs.FRI, cs.SAT, cs.SUN, cs.client_id, cs.plan_id,
           c.firstName, c.lastName, c.trainer_id,
           wp.plan_name
    FROM client_schedules cs
    JOIN clients c ON cs.client_id = c.id
    JOIN workout_plans wp ON cs.plan_id = wp.id
    ORDER BY c.firstName
")->fetchAll(PDO::FETCH_ASSOC);

if ($isTrainer && $trainerId) {
    $scheduleRows = array_filter($scheduleRows, fn($s) => $s['trainer_id'] == $trainerId);
    $scheduleRows = array_values($scheduleRows);
}

// ── Build day → [entries] map for calendar ─────────────────────
// entry: client_id, client_name, plan_id, plan_name
$dayMap = []; // key = 'Monday' … 'Sunday'
$scheduleByClient = [];
$dayShortToLong = [
    'MON' => 'Monday',
    'TUE' => 'Tuesday',
    'WED' => 'Wednesday',
    'THU' => 'Thursday',
    'FRI' => 'Friday',
    'SAT' => 'Saturday',
    'SUN' => 'Sunday'
];

foreach ($scheduleRows as $s) {
    $cid  = $s['client_id'];
    $name = $s['firstName'] . ' ' . $s['lastName'];

    // Initialize client data if not exists
    if (!isset($scheduleByClient[$cid])) {
        $scheduleByClient[$cid] = [
            'name'      => $name,
            'plan_name' => $s['plan_name'],
            'plan_id'   => $s['plan_id'],
            'days'      => [],
            'ids'       => []
        ];
    }

    // Iterate through each day and add to map if scheduled
    foreach ($dayShortToLong as $dayShort => $dayLong) {
        if ($s[$dayShort] == 1) {
            if (!isset($dayMap[$dayLong])) {
                $dayMap[$dayLong] = [];
            }
            $dayMap[$dayLong][] = [
                'client_id'   => $cid,
                'client_name' => $name,
                'plan_id'     => $s['plan_id'],
                'plan_name'   => $s['plan_name'],
            ];

            if (!in_array($dayLong, $scheduleByClient[$cid]['days'])) {
                $scheduleByClient[$cid]['days'][] = $dayLong;
            }
        }
    }

    if (!in_array($s['id'], $scheduleByClient[$cid]['ids'])) {
        $scheduleByClient[$cid]['ids'][] = $s['id'];
    }
}

// ── Calendar: current month nav ────────────────────────────────
$monthParam = $_GET['month'] ?? date('Y-m');
[$yr, $mo]  = explode('-', $monthParam);
$yr = (int)$yr;
$mo = (int)$mo;
if ($mo < 1) {
    $mo = 12;
    $yr--;
}
if ($mo > 12) {
    $mo = 1;
    $yr++;
}

$firstRow   = (int)date('N', mktime(0, 0, 0, $mo, 1, $yr)); // 1=Mon…7=Sun
$daysInMonth = (int)date('t', mktime(0, 0, 0, $mo, 1, $yr));
$monthLabel = date('F Y', mktime(0, 0, 0, $mo, 1, $yr));

$prevMonth = sprintf('%04d-%02d', $mo === 1 ? $yr - 1 : $yr, $mo === 1 ? 12 : $mo - 1);
$nextMonth = sprintf('%04d-%02d', $mo === 12 ? $yr + 1 : $yr, $mo === 12 ? 1 : $mo + 1);

// weekday name for a given date number (1-indexed, month context)
function weekdayName(int $yr, int $mo, int $day): string
{
    $map = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
    return $map[(int)date('N', mktime(0, 0, 0, $mo, $day, $yr))];
}

// colour palette for client avatars (cycles)
$clientColors = [
    ['linear-gradient(135deg,#1D546D,#5F9598)', '#1D546D'],
    ['linear-gradient(135deg,#22c55e,#16a34a)', '#16a34a'],
    ['linear-gradient(135deg,#f59e0b,#d97706)', '#d97706'],
    ['linear-gradient(135deg,#8b5cf6,#7c3aed)', '#7c3aed'],
    ['linear-gradient(135deg,#06b6d4,#0891b2)', '#0891b2'],
    ['linear-gradient(135deg,#ec4899,#db2777)', '#db2777'],
    ['linear-gradient(135deg,#ef4444,#dc2626)', '#ef4444'],
    ['linear-gradient(135deg,#14b8a6,#0d9488)', '#0d9488'],
];

// Assign stable color index to each client
$clientColorMap = [];
$colorIdx = 0;
foreach ($scheduleByClient as $cid => $_) {
    $clientColorMap[$cid] = $colorIdx % count($clientColors);
    $colorIdx++;
}

// Today
$todayStr = date('Y-m-d');

// JS payloads
$exercisesJson = json_encode($exercisesByPlan, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
$dayMapJson    = json_encode($dayMap,           JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
$days   = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

$clientsJs = [];
foreach ($clients as $c) {
    $clientsJs[] = [
        'id' => $c['id'],
        'name' => htmlspecialchars($c['firstName'] . ' ' . $c['lastName'], ENT_QUOTES)
    ];
}
$clientsJson = json_encode($clientsJs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);

$plansJs = [];
foreach ($plans as $p) {
    $plansJs[] = [
        'id' => $p['id'],
        'name' => htmlspecialchars($p['plan_name'], ENT_QUOTES)
    ];
}
$plansJson = json_encode($plansJs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule - GYM Trainer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* ── Calendar overrides ─────────────────────────────────────────── */
        .cal-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            border-left: 1px solid rgba(29, 84, 109, .07);
            border-top: 1px solid rgba(29, 84, 109, .07);
        }

        .cal-day-name {
            padding: 1rem 0;
            text-align: center;
            font-size: 1.1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: rgba(6, 30, 41, .38);
            border-right: 1px solid rgba(29, 84, 109, .07);
            border-bottom: 1px solid rgba(29, 84, 109, .07);
            background: rgba(29, 84, 109, .02);
        }

        .cal-cell {
            min-height: 10rem;
            padding: .8rem;
            border-right: 1px solid rgba(29, 84, 109, .07);
            border-bottom: 1px solid rgba(29, 84, 109, .07);
            vertical-align: top;
            position: relative;
            transition: background .15s;
        }

        .cal-cell:hover {
            background: rgba(29, 84, 109, .025);
            cursor: pointer;
        }

        .cal-cell.other-month {
            background: rgba(29, 84, 109, .018);
        }

        .cal-cell.today .cal-date {
            background: var(--primary);
            color: #fff;
            border-radius: 50%;
            width: 2.6rem;
            height: 2.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cal-date {
            font-size: 1.25rem;
            font-weight: 700;
            color: rgba(6, 30, 41, .55);
            margin-bottom: .5rem;
            width: 2.6rem;
            height: 2.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cal-cell.other-month .cal-date {
            color: rgba(6, 30, 41, .25);
        }

        /* ── Calendar event chips ─── */
        .cal-event {
            display: flex;
            align-items: center;
            gap: .4rem;
            padding: .28rem .6rem;
            border-radius: .5rem;
            font-size: 1.05rem;
            font-weight: 600;
            margin-bottom: .3rem;
            cursor: pointer;
            transition: opacity .15s;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .cal-event:hover {
            opacity: .82;
        }

        .cal-event .ev-dot {
            width: .5rem;
            height: .5rem;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .cal-event .ev-name {
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .cal-more {
            font-size: 1rem;
            color: rgba(6, 30, 41, .4);
            font-weight: 600;
            padding: .15rem .4rem;
        }

        /* ── Day detail panel (slide-in from right) ─── */
        .day-panel-overlay {
            position: fixed;
            inset: 0;
            background: rgba(6, 30, 41, .35);
            z-index: 1100;
            opacity: 0;
            pointer-events: none;
            transition: opacity .25s;
        }

        .day-panel-overlay.open {
            opacity: 1;
            pointer-events: all;
        }

        .day-panel {
            position: fixed;
            top: 0;
            right: -42rem;
            width: 40rem;
            height: 100vh;
            background: #fff;
            box-shadow: -8px 0 40px rgba(6, 30, 41, .12);
            z-index: 1101;
            transition: right .3s cubic-bezier(.4, 0, .2, 1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .day-panel-overlay.open .day-panel {
            right: 0;
        }

        .day-panel-head {
            padding: 2.4rem 2.4rem 1.6rem;
            border-bottom: 1px solid rgba(29, 84, 109, .08);
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-shrink: 0;
        }

        .day-panel-body {
            padding: 1.6rem 2.4rem;
            overflow-y: auto;
            flex: 1;
        }

        .day-panel-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
        }

        .day-panel-sub {
            font-size: 1.2rem;
            color: rgba(6, 30, 41, .4);
            margin-top: .2rem;
        }

        .panel-close-btn {
            background: rgba(6, 30, 41, .07);
            border: none;
            width: 3.4rem;
            height: 3.4rem;
            border-radius: .8rem;
            cursor: pointer;
            font-size: 1.6rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Client card inside panel */
        .panel-client-card {
            border: 1.5px solid rgba(29, 84, 109, .1);
            border-radius: 1.4rem;
            padding: 1.6rem;
            margin-bottom: 1.4rem;
            transition: box-shadow .2s;
        }

        .panel-client-card:hover {
            box-shadow: 0 4px 18px rgba(29, 84, 109, .1);
        }

        .panel-client-head {
            display: flex;
            align-items: center;
            gap: 1.2rem;
            margin-bottom: 1.2rem;
        }

        .client-av-sm {
            width: 3.8rem;
            height: 3.8rem;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }

        .panel-plan-badge {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .3rem .9rem;
            border-radius: .6rem;
            background: rgba(29, 84, 109, .08);
            color: var(--primary);
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .panel-ex-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .8rem 1.2rem;
            border-radius: .8rem;
            background: rgba(29, 84, 109, .03);
            margin-bottom: .5rem;
            font-size: 1.2rem;
        }

        .panel-ex-num {
            width: 2.2rem;
            height: 2.2rem;
            border-radius: .5rem;
            background: var(--primary);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            flex-shrink: 0;
            margin-right: .8rem;
        }

        /* ── Modal / day pills ─── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(6, 30, 41, .45);
            z-index: 1200;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity .2s;
        }

        .modal-overlay.open {
            opacity: 1;
            pointer-events: all;
        }

        .modal-box {
            background: #fff;
            border-radius: 2rem;
            padding: 3rem;
            width: 100%;
            box-shadow: 0 24px 60px rgba(6, 30, 41, .18);
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-title {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--dark);
        }

        .day-pills {
            display: flex;
            gap: .6rem;
            flex-wrap: wrap;
        }

        .day-pill {
            padding: .5rem 1.1rem;
            border-radius: 10rem;
            border: 1.5px solid rgba(29, 84, 109, .18);
            background: transparent;
            color: rgba(6, 30, 41, .55);
            font-family: 'DM Sans', sans-serif;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .15s;
            user-select: none;
        }

        .day-pill.selected {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }

        .sched-table {
            width: 100%;
            border-collapse: collapse;
        }

        .sched-table th {
            padding: 1rem 1.4rem;
            font-size: 1.1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: rgba(6, 30, 41, .4);
            border-bottom: 1px solid rgba(29, 84, 109, .08);
            text-align: left;
        }

        .sched-table td {
            padding: 1.2rem 1.4rem;
            border-bottom: 1px solid rgba(29, 84, 109, .05);
            font-size: 1.25rem;
            vertical-align: middle;
        }

        .sched-table tr:last-child td {
            border-bottom: none;
        }

        .day-badge {
            display: inline-block;
            padding: .2rem .7rem;
            border-radius: .5rem;
            font-size: 1rem;
            font-weight: 600;
            background: rgba(29, 84, 109, .08);
            color: var(--primary);
            margin: .15rem;
        }

        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            color: rgba(6, 30, 41, .35);
        }

        .empty-state i {
            font-size: 4rem;
            display: block;
            margin-bottom: 1.2rem;
        }

        .empty-state p {
            font-size: 1.4rem;
        }

        .del-icon {
            width: 6rem;
            height: 6rem;
            border-radius: 1.6rem;
            background: rgba(239, 68, 68, .1);
            color: #ef4444;
            font-size: 2.4rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.6rem;
        }
    </style>
</head>

<body>
    <?php if ($message): ?>
        <div class="toast-wrap" id="toastWrap">
            <div class="toast-msg toast-<?= htmlspecialchars($messageType) ?>">
                <i class="fas fa-<?= $messageType === 'success' ? 'circle-check' : 'circle-exclamation' ?> me-2"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php include 'assets/sidebar.php'; ?>

    <header class="topbar">
        <div class="topbar-left">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div>
                <div class="topbar-title">Schedule</div>
                <div class="topbar-subtitle"><?= $monthLabel ?></div>
            </div>
        </div>
        <div class="topbar-actions">
            <?php if ($logged_in): ?>

                <span style="font-size:1.1rem;font-weight:700;padding:.4rem 1rem;border-radius:10rem;
                  background:<?= $user_role === 'admin' ? 'rgba(139,92,246,.12)' : 'rgba(29,84,109,.1)' ?>;
                  color:<?= $user_role === 'admin' ? '#7c3aed' : 'var(--primary)' ?>">
                    <?= $user_role === 'admin' ? '⚙ Admin' : '🏋 Trainer' ?>
                </span>
                <button class="topbar-btn"><i class="fas fa-bell"></i><span class="topbar-notif">4</span></button>
                <div class="topbar-user">
                    <div class="user-avatar-sm"><?php echo substr($_SESSION['user_name'], 0, 2); ?></div><span class="user-name-sm"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span><i class="fas fa-chevron-down"
                        style="font-size:1rem;color:rgba(6,30,41,.4);margin-left:.4rem"></i>
                </div>
                <div class="topbar-btn">
                    <button class="btn-logout" onclick="handleLogout()">
                        <i class="fas fa-arrow-right-from-bracket fa-flip-horizontal"></i>
                    </button>
                </div>
            <?php else: ?>
                <button class="btn-login topbar-btn" onclick="window.location.href='login.php'" style="border-radius:10px; padding:0 40px; margin-top: 0;">
                    <i class="fas fa-sign-in-alt"></i>
                    Login
                </button>
            <?php endif; ?>
        </div>
    </header>

    <main class="main-content">
        <!-- Page header: nav + actions -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2rem;flex-wrap:wrap;gap:1rem">
            <div style="display:flex;align-items:center;gap:1rem">
                <a href="?month=<?= $prevMonth ?>" class="cal-nav-btn" style="text-decoration:none"><i class="fas fa-chevron-left"></i></a>
                <span style="font-family:'Barlow Condensed',sans-serif;font-size:2rem;font-weight:700;color:var(--dark);min-width:16rem;text-align:center"><?= $monthLabel ?></span>
                <a href="?month=<?= $nextMonth ?>" class="cal-nav-btn" style="text-decoration:none"><i class="fas fa-chevron-right"></i></a>
                <a href="?month=<?= date('Y-m') ?>" style="padding:.5rem 1.2rem;border-radius:.8rem;border:1.5px solid rgba(29,84,109,.2);background:transparent;color:var(--primary);font-size:1.2rem;font-weight:600;cursor:pointer;text-decoration:none">Today</a>
            </div>
            <div style="display:flex;gap:.8rem">
                <button class="btn-add" onclick="openScheduleModal()">
                    <i class="fas fa-calendar-plus"></i>Assign Schedule
                </button>
                <a href="workout-plan.php" class="btn-add" style="background:rgba(29,84,109,.1);color:var(--primary);text-decoration:none">
                    <i class="fas fa-dumbbell"></i>Workout Plans
                </a>
            </div>
        </div>

        <!-- Calendar -->
        <div class="section-card">
            <!-- <div class="section-header">
                <div class="cal-nav">
                    <button class="cal-nav-btn"><i class="fas fa-chevron-left"></i></button>
                    <span class="cal-month">February 2026</span>
                    <button class="cal-nav-btn"><i class="fas fa-chevron-right"></i></button>
                </div>
                <div style="display:flex;gap:.6rem">
                    <button style="padding:.5rem 1.2rem;border-radius:.8rem;border:1.5px solid var(--primary);background:var(--primary);color:#fff;font-size:1.2rem;font-weight:600;cursor:pointer;">Month</button>
                    <button style="padding:.5rem 1.2rem;border-radius:.8rem;border:1.5px solid rgba(29,84,109,.2);background:transparent;color:var(--primary);font-size:1.2rem;font-weight:600;cursor:pointer;">Week</button>
                    <button style="padding:.5rem 1.2rem;border-radius:.8rem;border:1.5px solid rgba(29,84,109,.2);background:transparent;color:var(--primary);font-size:1.2rem;font-weight:600;cursor:pointer;">Day</button>
                </div>
            </div> -->
            <div class="cal-grid">
                <?php
                $dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                foreach ($dayNames as $dn):
                ?>
                    <div class="cal-day-name"><?= $dn ?></div>
                <?php endforeach;

                // Lead blanks (Mon=1 → 0 blanks, Tue=2 → 1 blank, etc.)
                for ($b = 1; $b < $firstRow; $b++):
                    $prevDaysInMonth = (int)date('t', mktime(0, 0, 0, $mo - 1 < 1 ? 12 : $mo - 1, 1, $mo - 1 < 1 ? $yr - 1 : $yr));
                    $prevDay = $prevDaysInMonth - ($firstRow - $b - 1);
                ?>
                    <div class="cal-cell other-month">
                        <div class="cal-date"><?= $prevDay ?></div>
                    </div>
                <?php endfor;

                // Actual days
                for ($d = 1; $d <= $daysInMonth; $d++):
                    $dateStr  = sprintf('%04d-%02d-%02d', $yr, $mo, $d);
                    $isToday  = $dateStr === $todayStr;
                    $wdName   = weekdayName($yr, $mo, $d); // e.g. 'Monday'
                    $events   = $dayMap[$wdName] ?? [];
                    $maxShow  = 3;
                    $extra    = max(0, count($events) - $maxShow);
                ?>
                    <div class="cal-cell<?= $isToday ? ' today' : '' ?>" onclick="openDayPanel('<?= $wdName ?>','<?= $d ?>')">
                        <div class="cal-date"><?= $d ?></div>
                        <?php foreach (array_slice($events, 0, $maxShow) as $ev):
                            $ci   = $clientColorMap[$ev['client_id']] ?? 0;
                            $bg   = $clientColors[$ci][0];
                            $col  = $clientColors[$ci][1];
                            $init = strtoupper(substr($ev['client_name'], 0, 1));
                        ?>
                            <div class="cal-event" style="background:rgba(0,0,0,0);border:1.5px solid <?= $col ?>20;color:<?= $col ?>"
                                onclick="event.stopPropagation();openDayPanel('<?= $wdName ?>','<?= $d ?>')">
                                <span class="ev-dot" style="background:<?= $col ?>"></span>
                                <span class="ev-name"><?= htmlspecialchars($ev['client_name']) ?></span>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($extra > 0): ?>
                            <div class="cal-more">+<?= $extra ?> more</div>
                        <?php endif; ?>
                    </div>
                <?php endfor;

                // Trailing blanks to fill last row
                $totalCells = $firstRow - 1 + $daysInMonth;
                $trailing   = (7 - ($totalCells % 7)) % 7;
                for ($t = 1; $t <= $trailing; $t++): ?>
                    <div class="cal-cell other-month">
                        <div class="cal-date"><?= $t ?></div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Schedules list below calendar -->
        <div class="section-card">
            <div style="padding:2rem 2.4rem 1.4rem;border-bottom:1px solid rgba(29,84,109,.07);display:flex;align-items:center;justify-content:space-between">
                <span style="font-size:1.6rem;font-weight:700;color:var(--dark)">
                    <i class="fas fa-list-check" style="color:var(--primary);margin-right:.7rem"></i>All Client Schedules
                </span>
                <span style="font-size:1.2rem;color:rgba(6,30,41,.4)"><?= count($scheduleByClient) ?> client<?= count($scheduleByClient) !== 1 ? 's' : '' ?></span>
            </div>
            <?php if (empty($scheduleByClient)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-days"></i>
                    <p>No assigned schedules yet.<br>Click <strong>Assign Schedule</strong> to get started.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto">
                    <table class="sched-table">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Workout Plan</th>
                                <th>Training Days</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($scheduleByClient as $cid => $sched):
                                $ci   = $clientColorMap[$cid] ?? 0;
                                $bg   = $clientColors[$ci][0];
                                $init = strtoupper(substr($sched['name'], 0, 1));
                                $pts  = explode(' ', $sched['name']);
                                if (isset($pts[1])) $init .= strtoupper(substr($pts[1], 0, 1));
                            ?>
                                <tr>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:1.2rem">
                                            <div class="client-av" style="background:<?= $bg ?>"><?= $init ?></div>
                                            <span style="font-weight:600"><?= htmlspecialchars($sched['name']) ?></span>
                                        </div>
                                    </td>
                                    <td><span style="font-weight:600;color:var(--primary)"><?= htmlspecialchars($sched['plan_name']) ?></span></td>
                                    <td>
                                        <?php foreach ($sched['days'] as $day): ?><span class="day-badge"><?= $day ?></span><?php endforeach; ?>
                                    </td>
                                    <td>
                                        <button class="action-btn edit" title="Edit" onclick="openScheduleModal(<?= $sched['plan_id'] ?>, <?= $cid ?>, '<?= htmlspecialchars($sched['name'], ENT_QUOTES) ?>', <?= htmlspecialchars(json_encode($sched['days']), ENT_QUOTES) ?>)"><i class="fas fa-pen"></i></button>
                                        <button class="action-btn del" title="Remove" onclick="openDeleteScheduleModal(<?= $cid ?>,'<?= htmlspecialchars($sched['name'], ENT_QUOTES) ?>')"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- ════ DAY DETAIL PANEL ════ -->
        <div class="day-panel-overlay" id="dayPanelOverlay" onclick="closeDayPanel()">
            <div class="day-panel" onclick="event.stopPropagation()">
                <div class="day-panel-head">
                    <div>
                        <div class="day-panel-title" id="panelDayTitle">Monday</div>
                        <div class="day-panel-sub" id="panelDaySub">No sessions</div>
                    </div>
                    <button class="panel-close-btn" onclick="closeDayPanel()"><i class="fas fa-times"></i></button>
                </div>
                <div class="day-panel-body" id="panelBody">
                    <!-- filled by JS -->
                </div>
            </div>
        </div>

        <!-- ════ ASSIGN SCHEDULE MODAL ════ -->
        <div class="modal-overlay" id="scheduleModal">
            <div class="modal-box" style="max-width:54rem">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem">
                    <div class="modal-title" id="schedModalTitle">Client Schedule</div>
                    <button onclick="closeModal('scheduleModal')" style="background:rgba(6,30,41,.08);border:none;width:3.4rem;height:3.4rem;border-radius:.8rem;cursor:pointer;font-size:1.6rem;color:var(--dark)"><i class="fas fa-times"></i></button>
                </div>
                <form method="POST" action="schedule.php" id="schedForm">
                    <input type="hidden" name="action" value="assign_schedule">
                    <input type="hidden" name="client_id" id="schedClientId">

                    <!-- Client select -->
                    <div style="margin-bottom:1.6rem" id="clientSelectWrap">
                        <label class="gym-label">Client *</label>
                        <select name="client_id" id="schedClientSelect" class="gym-select" onchange="syncClientId(this.value)" required>
                            <option value="">— Select client —</option>
                            <?php foreach ($clients as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['firstName'] . ' ' . $c['lastName']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Plan select -->
                    <div style="margin-bottom:1.6rem">
                        <label class="gym-label">Workout Plan *</label>
                        <select name="plan_id" id="schedPlanSelect" class="gym-select" required>
                            <option value="">— Select plan —</option>
                            <?php foreach ($plans as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['plan_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                     <!-- Day selector -->
                    <div style="margin-bottom:2rem">
                        <label class="gym-label">Training Days * <span style="font-weight:400;text-transform:none;color:rgba(6,30,41,.4)">(select at least one)</span></label>
                        <div class="day-pills" id="dayPills">
                            <?php foreach ($days as $d): ?>
                                <button type="button" class="day-pill" data-day="<?= $d ?>" onclick="toggleDay(this)"><?= substr($d, 0, 3) ?></button>
                            <?php endforeach; ?>
                        </div>
                        <div id="dayInputs"></div>
                    </div>
                    <div style="display:flex;gap:1rem">
                        <button type="button" onclick="closeModal('scheduleModal')" style="flex:1;height:4.6rem;border-radius:1rem;border:1.5px solid rgba(29,84,109,.2);background:transparent;font-family:'DM Sans',sans-serif;font-size:1.3rem;font-weight:600;color:var(--dark);cursor:pointer">Cancel</button>
                        <button type="submit" onclick="return injectDayInputs()" style="flex:2;height:4.6rem;border-radius:1rem;border:none;background:linear-gradient(135deg,var(--primary),var(--secondary));color:#fff;font-family:'DM Sans',sans-serif;font-size:1.3rem;font-weight:600;cursor:pointer"><i class="fas fa-calendar-check" style="margin-right:.6rem"></i>Save Schedule</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ══════════════════════════════════════════
            DELETE SCHEDULE MODAL
        ══════════════════════════════════════════ -->
        <div class="modal-overlay" id="deleteScheduleModal">
            <div class="modal-box" style="max-width:44rem">
                <div class="del-icon"><i class="fas fa-calendar-xmark"></i></div>
                <div class="modal-title" style="text-align:center;margin-bottom:.8rem">Remove Schedule</div>
                <p style="text-align:center;font-size:1.3rem;color:rgba(6,30,41,.6);margin-bottom:2.4rem">Remove all scheduled days for <strong id="deleteSchedName"></strong>?</p>
                <form method="POST" action="schedule.php">
                    <input type="hidden" name="action" value="delete_schedule">
                    <input type="hidden" name="client_id" id="deleteSchedClientId">
                    <div style="display:flex;gap:1rem">
                        <button type="button" onclick="closeModal('deleteScheduleModal')" style="flex:1;height:4.6rem;border-radius:1rem;border:1.5px solid rgba(29,84,109,.2);background:transparent;font-family:'DM Sans',sans-serif;font-size:1.3rem;font-weight:600;color:var(--dark);cursor:pointer">Cancel</button>
                        <button type="submit" style="flex:1;height:4.6rem;border-radius:1rem;border:none;background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;font-family:'DM Sans',sans-serif;font-size:1.3rem;font-weight:600;cursor:pointer">Yes, Remove</button>
                    </div>
                </form>
            </div>
        </div>
        </div>


    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ── Sidebar ──
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        if (sidebarToggle && sidebar && sidebarOverlay) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('open');
                sidebarOverlay.classList.toggle('open');

            });
            sidebarOverlay.addEventListener('click', () => {
                sidebar.classList.remove('open');
                sidebarOverlay.classList.remove('open');
            });
        }

        // ── Data from PHP ─────────────────────────────────────────────────
        const dayMap = <?= $dayMapJson ?>;
        const exercisesData = <?= $exercisesJson ?>;

        // colour palette mirroring PHP
        const clientPalette = [{
                bg: 'linear-gradient(135deg,#1D546D,#5F9598)',
                col: '#1D546D'
            },
            {
                bg: 'linear-gradient(135deg,#22c55e,#16a34a)',
                col: '#16a34a'
            },
            {
                bg: 'linear-gradient(135deg,#f59e0b,#d97706)',
                col: '#d97706'
            },
            {
                bg: 'linear-gradient(135deg,#8b5cf6,#7c3aed)',
                col: '#7c3aed'
            },
            {
                bg: 'linear-gradient(135deg,#06b6d4,#0891b2)',
                col: '#0891b2'
            },
            {
                bg: 'linear-gradient(135deg,#ec4899,#db2777)',
                col: '#db2777'
            },
            {
                bg: 'linear-gradient(135deg,#ef4444,#dc2626)',
                col: '#ef4444'
            },
            {
                bg: 'linear-gradient(135deg,#14b8a6,#0d9488)',
                col: '#0d9488'
            },
        ];
        // Stable color per client_id
        const clientColorCache = {};
        let colorSeq = 0;

        function clientColor(id) {
            if (!(id in clientColorCache)) clientColorCache[id] = colorSeq++ % clientPalette.length;
            return clientPalette[clientColorCache[id]];
        }
        // Pre-seed from PHP
        <?php foreach ($clientColorMap as $cid => $idx): ?>
            clientColorCache[<?= $cid ?>] = <?= $idx ?>;
        <?php endforeach; ?>

        function handleLogout() {
            if (confirm('Are you sure you want to logout?')) window.location.href = 'logout.php';
        }

        // ── Modals ────────────────────────────────────────────────────────
        function openModal(id) {
            document.getElementById(id).classList.add('open');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('open');
        }
        document.querySelectorAll('.modal-overlay').forEach(m => m.addEventListener('click', e => {
            if (e.target === m) m.classList.remove('open');
        }));

        // ── Day Panel ─────────────────────────────────────────────────────
        function openDayPanel(dayName, dayNum) {
            const events = dayMap[dayName] || [];

            document.getElementById('panelDayTitle').textContent = dayName;
            document.getElementById('panelDaySub').textContent =
                events.length ? `${events.length} client${events.length!==1?'s':''} scheduled` : 'No sessions today';

            const body = document.getElementById('panelBody');
            body.innerHTML = '';

            if (!events.length) {
                body.innerHTML = `
                <div style="text-align:center;padding:4rem 2rem">
                    <div style="font-size:4rem;margin-bottom:1.2rem">📅</div>
                    <p style="font-size:1.4rem;color:rgba(6,30,41,.35);margin-bottom:2rem">No sessions on ${dayName}</p>
                    <button onclick="openScheduleModal()" style="padding:.9rem 2.4rem;border-radius:1rem;border:none;background:linear-gradient(135deg,var(--primary),var(--secondary));color:#fff;font-family:'DM Sans',sans-serif;font-size:1.3rem;font-weight:600;cursor:pointer">
                        <i class="fas fa-calendar-plus" style="margin-right:.6rem"></i>Assign Schedule
                    </button>
                </div>`;
                document.getElementById('dayPanelOverlay').classList.add('open');
                return;
            }

            events.forEach(ev => {
                const pal = clientColor(ev.client_id);
                const init = ev.client_name.split(' ').map(p => p[0] || '').join('').slice(0, 2).toUpperCase();
                const exs = exercisesData[ev.plan_id] || [];

                const card = document.createElement('div');
                card.className = 'panel-client-card';

                let exHtml = '';
                exs.forEach((e, i) => {
                    exHtml += `<div class="panel-ex-row">
                    <div style="display:flex;align-items:center;flex:1">
                        <div class="panel-ex-num">${i+1}</div>
                        <span style="font-weight:600;color:var(--dark)">${esc(e.workout_name)}</span>
                    </div>
                    <span style="font-size:1.1rem;font-weight:600;color:var(--secondary);white-space:nowrap">${e.sets}×${e.set_counter}</span>
                </div>`;
                });

                card.innerHTML = `
                <div class="panel-client-head">
                    <div class="client-av-sm" style="background:${pal.bg}">${init}</div>
                    <div>
                        <div style="font-size:1.4rem;font-weight:700;color:var(--dark)">${esc(ev.client_name)}</div>
                    </div>
                </div>
                <div class="panel-plan-badge">
                    <i class="fas fa-dumbbell" style="font-size:.9rem"></i>${esc(ev.plan_name)}
                </div>
                ${exs.length ? `<div style="margin-top:.6rem">${exHtml}</div>` : `<p style="font-size:1.2rem;color:rgba(6,30,41,.4)">No exercises in this plan.</p>`}
                <div style="margin-top:1.2rem;display:flex;gap:.6rem">
                    <button onclick="closeDayPanel();openScheduleModal(${ev.plan_id},${ev.client_id})"
                        style="flex:1;padding:.7rem 1rem;border-radius:.8rem;border:1.5px solid rgba(29,84,109,.15);background:transparent;font-family:'DM Sans',sans-serif;font-size:1.15rem;font-weight:600;color:var(--primary);cursor:pointer">
                        <i class="fas fa-pen" style="margin-right:.4rem"></i>Edit
                    </button>
                </div>`;
                body.appendChild(card);
            });

            document.getElementById('dayPanelOverlay').classList.add('open');
        }

        function closeDayPanel() {
            document.getElementById('dayPanelOverlay').classList.remove('open');
        }

        function esc(s) {
            return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        // ── Assign modal ──────────────────────────────────────────────────
        function openScheduleModal(planId, clientId, currentDays) {
            document.getElementById('schedModalTitle').textContent = planId ? 'Edit Schedule' : 'Assign Schedule';

            // Pre-select plan
            document.getElementById('schedPlanSelect').value = planId || '';
            document.getElementById('schedClientSelect').value = clientId || '';

            // Pre-select days
            document.querySelectorAll('.day-pill').forEach(p => {
                p.classList.toggle('selected', Array.isArray(currentDays) && currentDays.includes(p.dataset.day));
            });
            document.getElementById('dayInputs').innerHTML = '';
            openModal('scheduleModal');
        }

        function syncClientId(val) {
            document.getElementById('schedClientId').value = val;
        }

        function toggleDay(pill) {
            pill.classList.toggle('selected');
        }

        function injectDayInputs() {
            const sel = [...document.querySelectorAll('.day-pill.selected')].map(p => p.dataset.day);
            if (!sel.length) {
                alert('Please select at least one training day.');
                return false;
            }
            if (!document.getElementById('schedClientSelect').value) {
                alert('Please select a client.');
                return false;
            }
            if (!document.getElementById('schedPlanSelect').value) {
                alert('Please select a plan.');
                return false;
            }
            const c = document.getElementById('dayInputs');
            container.innerHTML = '';
            sel.forEach(d => {
                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'days[]';
                inp.value = d;
                container.appendChild(inp);
            });
            return true;
        }

        // ── Delete modal ──────────────────────────────────────────────────
        function openDeleteScheduleModal(clientId, name) {
            document.getElementById('deleteSchedClientId').value = clientId;
            document.getElementById('deleteSchedName').textContent = name;
            openModal('deleteScheduleModal');
        }

        // Toast
        const toast = document.getElementById('toastWrap');
        if (toast) setTimeout(() => toast.remove(), 4200);
    </script>
</body>

</html>