<?php
session_start();

require 'middleware/middle.php';
requireAuth();

$database = require 'bootstrap.php';
$pdo = $database->pdo;

$userId = $_SESSION['user_id'];
$logged_in = isset($userId);
$role = $_SESSION['role'] ?? '';

$isAdmin = isAdmin();
$isTrainer = isTrainer();
$isClient = isClient();

$currentPage = 'dashboard'; // sidebar tab is active

// ── Role-based statistics ──
if ($isAdmin) {
    $totalClients   = (int)$pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
    $totalSessions   = (int)$pdo->query("SELECT COUNT(*) FROM client_schedules")->fetchColumn();
    $activeClients  = (int)$pdo->query("SELECT COUNT(*) FROM clients WHERE status='Active'")->fetchColumn();
    $totalTrainers  = (int)$pdo->query("SELECT COUNT(*) FROM trainers WHERE status='Active'")->fetchColumn();
    $totalAssignedClients  = (int)$pdo->query("SELECT COUNT(*) FROM trainer_clients WHERE status='active'")->fetchColumn();

    // Assigned clients
    $totalAssignedClients = (int)$pdo->query("SELECT COUNT(*) FROM trainer_clients WHERE status = 'active'")->fetchColumn();

    // Recent clients
    $recentClients  = $pdo->query("
        SELECT c.*, u.name AS trainer_name
        FROM clients c
        LEFT JOIN users u ON c.trainer_id = u.id
        ORDER BY c.id DESC LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Trainer list for quick stats
    $trainerStats = $pdo->query("
        SELECT t.id, t.name, t.status,
               COUNT(c.id) AS client_count
        FROM trainers t
        LEFT JOIN clients c ON c.trainer_id = t.id
        GROUP BY t.id, t.name, t.status
        ORDER BY client_count DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Initialize trainer variables
    $trainerId = '';
    $assignedClients = [];
    $totalClients = 0;
    $activeClients = 0;
    $totalAssignedClients = 0;
    $totalSessions = 0;
    $recentClients = [];
    $trainerStats = [];

    if ($isTrainer) {
        $stmt = $pdo->prepare("SELECT id FROM trainers WHERE user_id = ?");
        $stmt->execute([$userId]);
        $trainerId = $stmt->fetchColumn();

        if ($trainerId) {
            // Get assigned clients through trainer_clients table
            $stmt = $pdo->prepare("
            SELECT c.*
            FROM trainer_clients tc
            JOIN clients c ON tc.client_id = c.id
            WHERE tc.trainer_id = ?
            AND tc.status = 'active'");
            $stmt->execute([$trainerId]);
            $assignedClients = $stmt->fetchAll();

            // Trainer sees only their own data
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE trainer_id = ?");
            $stmt->execute([$trainerId]);
            $totalClients = (int)$stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE trainer_id = ? AND status='Active'");
            $stmt->execute([$trainerId]);
            $activeClients = (int)$stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM trainer_clients WHERE trainer_id = ? AND status='active'");
            $stmt->execute([$trainerId]);
            $totalAssignedClients = (int)$stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM client_schedules cs JOIN clients c ON cs.client_id = c.id WHERE c.trainer_id = ?");
            $stmt->execute([$trainerId]);
            $totalSessions = (int)$stmt->fetchColumn();

            // Recent clients assigned to this trainer
            $stmt = $pdo->prepare("SELECT * FROM clients WHERE trainer_id = ? ORDER BY id DESC LIMIT 5");
            $stmt->execute([$trainerId]);
            $recentClients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    $totalTrainers = 0; // not relevant for trainer view
}

// Weekly Schedule: Build dayMap
$dayShortToLong = [
    'MON' => 'Monday',
    'TUE' => 'Tuesday',
    'WED' => 'Wednesday',
    'THU' => 'Thursday',
    'FRI' => 'Friday',
    'SAT' => 'Saturday',
    'SUN' => 'Sunday'
];

// Fetch schedule rows trainers see only their own clients
if ($isTrainer && !empty($trainerId)) {
    $schStmt = $pdo->prepare("SELECT cs.MON,cs.TUE,cs.WED,cs.THU,cs.FRI,cs.SAT,cs.SUN,
            cs.client_id, cs.plan_id,
            c.firstName, c.lastName, c.trainer_id,
            wp.plan_name
        FROM client_schedules cs
        JOIN clients c  ON cs.client_id = c.id
        JOIN workout_plans wp ON cs.plan_id = wp.id
        WHERE c.trainer_id = ?
        ORDER BY c.firstName
    ");
    $schStmt->execute([$trainerId]);
} else {
    $schStmt = $pdo->query("SELECT cs.MON,cs.TUE,cs.WED,cs.THU,cs.FRI,cs.SAT,cs.SUN,
            cs.client_id, cs.plan_id,
            c.firstName, c.lastName, c.trainer_id,
            wp.plan_name
        FROM client_schedules cs
        JOIN clients c  ON cs.client_id = c.id
        JOIN workout_plans wp ON cs.plan_id = wp.id
        ORDER BY c.firstName
    ");
}
$scheduleRows = $schStmt->fetchAll(PDO::FETCH_ASSOC);
 
$dayMap = []; // 'Monday' => [ [client_id, client_name, plan_name], … ]
$schedByClient  = [];
foreach ($scheduleRows as $s) {
    $cid  = $s['client_id'];
    $name = $s['firstName'] . ' ' . $s['lastName'];
    if (!isset($schedByClient[$cid])) {
        $schedByClient[$cid] = ['name' => $name, 'plan_name' => $s['plan_name']];
    }
    foreach ($dayShortToLong as $short => $long) {
        if (!empty($s[$short])) {
            $dayMap[$long][] = [
                'client_id'   => $cid,
                'client_name' => $name,
                'plan_name'   => $s['plan_name'],
            ];
        }
    }
}

// Stable colour per client (cycles through palette)
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
$clientColorMap = [];
$colorIdx = 0;
foreach ($schedByClient as $cid => $_) {
    $clientColorMap[$cid] = $colorIdx++ % count($clientColors);
}
 
// Today's day name (e.g. 'Thursday')
$todayDayName = date('l');

// Avatar colour palette
$colours = [
    'linear-gradient(135deg,#1D546D,#5F9598)',
    'linear-gradient(135deg,#22c55e,#16a34a)',
    'linear-gradient(135deg,#f59e0b,#d97706)',
    'linear-gradient(135deg,#8b5cf6,#7c3aed)',
    'linear-gradient(135deg,#ef4444,#dc2626)',
    'linear-gradient(135deg,#06b6d4,#0891b2)',
    'linear-gradient(135deg,#ec4899,#db2777)',
    'linear-gradient(135deg,#14b8a6,#0d9488)',
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - GYM Trainer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <!-- ═════════════════ SIDEBAR OVERLAY (mobile) ═════════════════ -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- ═══════════════════ SIDEBAR ═══════════════════ -->
    <?php include 'assets/sidebar.php'; ?>

    <!-- ═══════════════════ TOPBAR ═══════════════════ -->
    <?php include 'assets/topbar.php'; ?>

    <!-- ═══════════════════ MAIN ═══════════════════ -->
    <main class="main-content">

        <!-- Stats Bar -->
        <div class="stats-bar content-section">
            <?php if ($isAdmin) : ?>
                <div class="stat-item">
                    <div class="stat-value"><?= $totalClients ?></div>
                    <div class="stat-label">Total Clients</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= $totalTrainers ?></div>
                    <div class="stat-label">Total Trainers</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= $totalSessions ?></div>
                    <div class="stat-label">Today's Sessions</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= $totalAssignedClients ?></div>
                    <div class="stat-label">Today Assigned</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">89%</div>
                    <div class="stat-label">Attendance Rate</div>
                </div>
            <?php else: ?>
                <div class="stat-item">
                    <div class="stat-value"><?= $totalClients ?></div>
                    <div class="stat-label">My Clients</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= $activeClients ?></div>
                    <div class="stat-label">Active Plans</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= $totalSessions ?></div>
                    <div class="stat-label">Today's Sessions</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">7</div>
                    <div class="stat-label">Pending Tasks</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">4.9</div>
                    <div class="stat-label">Avg Rating</div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Overview Cards -->
        <div class="kpi-grid content-section">
            <!-- <div class="kpi-card c1">
                <div class="kpi-icon-wrap"><i class="fas fa-users"></i></div>
                <i class="kpi-card-bg-icon fas fa-users"></i>
                <div class="kpi-label"><?= $isAdmin ? 'Total Clients' : 'My Clients' ?></div>
                <div class="kpi-value"><?= $totalClients ?></div>
                <div class="kpi-trend up">
                    <i class="fas fa-arrow-trend-up"></i> +3 this month
                </div>
            </div> -->
            <?php if ($isAdmin): ?>
                <!-- <div class="kpi-card c2">
                    <div class="kpi-icon-wrap"><i class="fas fa-user-tie"></i></div>
                    <i class="kpi-card-bg-icon fas fa-user-tie"></i>
                    <div class="kpi-label">Total Trainers</div>
                    <div class="kpi-value"><?= $totalTrainers ?></div>
                    <div class="kpi-trend neutral"><i class="fas fa-circle"></i> System-wide</div>
                </div>
                <div class="kpi-card c3">
                    <div class="kpi-icon-wrap"><i class="fas fa-link"></i></div>
                    <i class="kpi-card-bg-icon fas fa-link"></i>
                    <div class="kpi-label">
                        <?= $isAdmin ? 'Total Clients Assigned' : 'Assigned Clients' ?>
                    </div>
                    <div class="kpi-value">
                        <?= $totalAssignedClients ?? 0 ?>
                    </div>
                    <div class="kpi-trend up">
                        <i class="fas fa-link"></i>
                        <?= $isAdmin ? 'Across all trainers' : 'Assigned to you' ?>
                    </div>
                </div> -->
            <?php else: ?>
                <!-- <div class="kpi-card c2">
                    <div class="kpi-icon-wrap"><i class="fas fa-dumbbell"></i></div>
                    <i class="kpi-card-bg-icon fas fa-dumbbell"></i>
                    <div class="kpi-label">Today's Sessions</div>
                    <div class="kpi-value">6</div>
                    <div class="kpi-trend neutral">
                        <i class="fas fa-clock"></i> Next at 10:00 AM
                    </div>
                </div>
                <div class="kpi-card c3">
                    <div class="kpi-icon-wrap"><i class="fas fa-clipboard-list"></i></div>
                    <i class="kpi-card-bg-icon fas fa-clipboard-list"></i>
                    <div class="kpi-label">Pending Tasks</div>
                    <div class="kpi-value">7</div>
                    <div class="kpi-trend down">
                        <i class="fas fa-triangle-exclamation"></i> 2 overdue
                    </div>
                </div> -->
            <?php endif; ?>
        </div>

        <!-- Client Progress + Quick Actions -->
        <div class="dashboard-grid content-section">
            <!-- Client Progress -->
            <div class="section-card">
                <div class="section-header">
                    <span class="section-title">Client Progress</span>
                    <a href="clients.php" class="section-action">View All →</a>
                </div>
                <div class="section-body">
                    <?php if (empty($recentClients)): ?>
                        <div style="padding:4rem;text-align:center;color:rgba(6,30,41,.35);font-size:1.35rem">
                            <i class="fas fa-users" style="font-size:3rem;display:block;margin-bottom:1rem"></i>
                            <?= $isAdmin ? 'No clients in the system yet.' : 'No clients assigned to you yet.' ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentClients as $i => $client):
                            $fullName = ($client['firstName'] ?? '') . ' ' . ($client['lastName'] ?? '');
                            $fullName = trim($fullName) ?: ($client['name'] ?? 'Unknown');
                            $initials = strtoupper(substr($fullName, 0, 1));
                            $parts    = explode(' ', $fullName);
                            if (isset($parts[1])) $initials .= strtoupper(substr($parts[1], 0, 1));
                            $colour   = $colours[$i % count($colours)];
                            $progress = (int)($client['progress'] ?? 0);
                            $status   = $client['status'] ?? 'Active';
                            $badgeCls = $status === 'Active' ? 'status-active' : ($status === 'Inactive' ? 'status-inactive' : 'status-pending');
                        ?>
                            <div class="client-row">
                                <div class="client-av" style="background:<?= $colour ?>"><?= $initials ?></div>
                                <div class="client-info">
                                    <div class="client-name"><?= htmlspecialchars($fullName) ?></div>
                                    <div class="client-plan">
                                        <?= htmlspecialchars($client['plan'] ?? 'No plan') ?>
                                        <?php if ($isAdmin && !empty($client['trainer_name'])): ?>
                                            · <span style="color:var(--secondary)">Trainer: <?= htmlspecialchars($client['trainer_name']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="progress-wrap">
                                    <div class="progress-label"><?= $progress ?>%</div>
                                    <div class="progress-bar-wrap">
                                        <div class="progress-bar-fill" style="width:<?= $progress ?>%"></div>
                                    </div>
                                </div>
                                <span class="client-status <?= $badgeCls ?>"><?= htmlspecialchars($status) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($isAdmin): ?>
                <div class="section-card">
                    <div class="section-header">
                        <span class="section-title">Trainer Overview</span>
                        <a href="trainers.php" class="section-action">Manage →</a>
                    </div>
                    <div class="section-body">
                        <?php if (empty($trainerStats)): ?>
                            <div style="padding:4rem;text-align:center;color:rgba(6,30,41,.35);font-size:1.35rem">
                                No trainers added yet.
                            </div>
                        <?php else: ?>
                            <?php foreach ($trainerStats as $i => $trainer):
                                $ti  = strtoupper(substr($trainer['name'], 0, 1));
                                $tp  = explode(' ', $trainer['name']);
                                if (isset($tp[1])) $ti .= strtoupper(substr($tp[1], 0, 1));
                                $tc  = $colours[$i % count($colours)];
                            ?>
                                <div class="trainer-card d-flex justify-content-start m-4 gap-3 p-3" style="margin:.4rem 1.6rem;border-radius:1rem">
                                    <div class="client-av" style="background:<?= $tc ?>;width:4.2rem;height:4.2rem"><?= $ti ?></div>
                                    <div>
                                        <div style="font-weight:600;font-size:1.35rem"><?= htmlspecialchars($trainer['name']) ?></div>
                                        <div style="font-size:1.15rem;color:rgba(1, 11, 15, 0.45)">Trainer</div>
                                    </div>
                                    <div style="font-size:1.20rem;color:var(--secondary)">clients</div>
                                    <div class="trainer-count" style="font-size:small;"><?= $trainer['client_count'] ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <div style="padding:1.6rem 2rem;border-top:1px solid rgba(29,84,109,.06);margin-top:.8rem">
                            <a href="trainers.php" class="btn-add" style="display:inline-flex; text-decoration:none">
                                <i class="fas fa-plus"></i> Add Trainer
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Today's Sessions -->
                <div class="section-card">
                    <div class="section-header">
                        <span class="section-title">Today's Sessions</span>
                        <a href="schedule.php" class="section-action">Full Schedule →</a>
                    </div>
                    <div class="section-body">
                        <?php if (empty($todaySessions)): ?>
                            <div style="padding:4rem;text-align:center;color:rgba(6,30,41,.35)">
                                <i class="fas fa-calendar-day" style="font-size:3rem;display:block;margin-bottom:1rem"></i>
                                <div style="font-size:1.35rem;font-weight:600;color:var(--dark)">No sessions today</div>
                                <div style="font-size:1.2rem;margin-top:.4rem">Enjoy the rest day!</div>
                            </div>
                        <?php else:
                            $accentColors = ['var(--primary)','#f59e0b','var(--secondary)','#8b5cf6','#ef4444','#06b6d4','#ec4899','#14b8a6'];
                            foreach ($todaySessions as $i => $session):
                                $fullName = trim(($session['firstName'] ?? '') . ' ' . ($session['lastName'] ?? ''));
                                $accent   = $accentColors[$i % count($accentColors)];
                                $isLast   = ($i === count($todaySessions) - 1);
                        ?>
                            <div class="client-row" style="flex-direction:column;align-items:flex-start;gap:0.6rem;<?= $isLast ? 'border:none' : '' ?>">
                                <div style="display:flex;align-items:center;gap:1.2rem;width:100%">
                                    <div style="width:0.4rem;height:4rem;background:<?= $accent ?>;border-radius:4px;flex-shrink:0"></div>
                                    <div>
                                        <div style="font-size:1.35rem;font-weight:600;color:var(--dark)"><?= htmlspecialchars($fullName) ?></div>
                                        <div style="font-size:1.15rem;color:rgba(6,30,41,0.45)"><?= htmlspecialchars($session['plan_name']) ?></div>
                                    </div>
                                    <?php
                                        $status   = $session['status'] ?? 'Active';
                                        $badgeCls = $status === 'Active' ? 'status-active' : ($status === 'Inactive' ? 'status-inactive' : 'status-pending');
                                    ?>
                                    <span class="client-status <?= $badgeCls ?> ms-auto"><?= htmlspecialchars($status) ?></span>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                    <!-- <div class="section-body">
                        <div class="client-row" style="flex-direction:column;align-items:flex-start;gap:0.6rem;">
                            <div style="display:flex;align-items:center;gap:1.2rem;width:100%">
                                <div style="width:0.4rem;height:4rem;background:var(--primary);border-radius:4px;flex-shrink:0"></div>
                                <div>
                                    <div style="font-size:1.35rem;font-weight:600;color:var(--dark)">Sara Ahmed</div>
                                    <div style="font-size:1.15rem;color:rgba(6,30,41,0.45)">08:00 – 09:00 AM · Weight Loss</div>
                                </div>
                                <span class="client-status status-active ms-auto">Done</span>
                            </div>
                        </div>
                        <div class="client-row" style="flex-direction:column;align-items:flex-start;gap:0.6rem;">
                            <div style="display:flex;align-items:center;gap:1.2rem;width:100%">
                                <div style="width:0.4rem;height:4rem;background:#f59e0b;border-radius:4px;flex-shrink:0"></div>
                                <div>
                                    <div style="font-size:1.35rem;font-weight:600;color:var(--dark)">Mike Khan</div>
                                    <div style="font-size:1.15rem;color:rgba(6,30,41,0.45)">10:00 – 11:00 AM · Muscle Gain</div>
                                </div>
                                <span class="client-status status-pending ms-auto">Next</span>
                            </div>
                        </div>
                        <div class="client-row" style="flex-direction:column;align-items:flex-start;gap:0.6rem;">
                            <div style="display:flex;align-items:center;gap:1.2rem;width:100%">
                                <div style="width:0.4rem;height:4rem;background:var(--secondary);border-radius:4px;flex-shrink:0"></div>
                                <div>
                                    <div style="font-size:1.35rem;font-weight:600;color:var(--dark)">Layla Rahman</div>
                                    <div style="font-size:1.15rem;color:rgba(6,30,41,0.45)">12:00 – 01:00 PM · Cardio</div>
                                </div>
                                <span class="client-status status-inactive ms-auto">Upcoming</span>
                            </div>
                        </div>
                        <div class="client-row" style="flex-direction:column;align-items:flex-start;gap:0.6rem;">
                            <div style="display:flex;align-items:center;gap:1.2rem;width:100%">
                                <div style="width:0.4rem;height:4rem;background:#8b5cf6;border-radius:4px;flex-shrink:0"></div>
                                <div>
                                    <div style="font-size:1.35rem;font-weight:600;color:var(--dark)">Ali Raza</div>
                                    <div style="font-size:1.15rem;color:rgba(6,30,41,0.45)">02:00 – 03:00 PM · Strength</div>
                                </div>
                                <span class="client-status status-inactive ms-auto">Upcoming</span>
                            </div>
                        </div>
                        <div class="client-row" style="flex-direction:column;align-items:flex-start;gap:0.6rem;border:none">
                            <div style="display:flex;align-items:center;gap:1.2rem;width:100%">
                                <div style="width:0.4rem;height:4rem;background:#ef4444;border-radius:4px;flex-shrink:0"></div>
                                <div>
                                    <div style="font-size:1.35rem;font-weight:600;color:var(--dark)">Group Class</div>
                                    <div style="font-size:1.15rem;color:rgba(6,30,41,0.45)">05:00 – 06:00 PM · HIIT · 8 members</div>
                                </div>
                                <span class="client-status status-inactive ms-auto">Upcoming</span>
                            </div>
                        </div>
                    </div> -->
                </div>
            <?php endif; ?>
        </div>

        <!-- Weekly Schedule -->
        <div class="section-card content-section" style="animation-delay:0.3s">
            <div class="section-header">
                <span class="section-title">Weekly Schedule</span>
                <a href="schedule.php" class="section-action">Manage →</a>
            </div>
            <div class="schedule-scroll">
                <div class="week-grid">
                    <?php
                    // Mon=1 … Sun=7 — iterate all 7 days of the current week
                    $weekDays = [
                        1 => ['short' => 'Mon', 'long' => 'Monday'],
                        2 => ['short' => 'Tue', 'long' => 'Tuesday'],
                        3 => ['short' => 'Wed', 'long' => 'Wednesday'],
                        4 => ['short' => 'Thu', 'long' => 'Thursday'],
                        5 => ['short' => 'Fri', 'long' => 'Friday'],
                        6 => ['short' => 'Sat', 'long' => 'Saturday'],
                        7 => ['short' => 'Sun', 'long' => 'Sunday'],
                    ];
                    // Dates for each weekday in the current week (Mon-based)
                    $todayDow = (int)date('N');           // 1=Mon…7=Sun
                    $weekStartTs = strtotime('today') - (($todayDow - 1) * 86400);
 
                    foreach ($weekDays as $dow => $day):
                        $dayTs     = $weekStartTs + (($dow - 1) * 86400);
                        $dayNum    = (int)date('j', $dayTs);
                        $isToday   = ($dow === $todayDow);
                        $clients   = $dayMap[$day['long']] ?? [];
                        // De-duplicate by client_id (a client appears once per day even if multiple rows)
                        $seen = [];
                        $uniqueClients = [];
                        foreach ($clients as $c) {
                            if (!isset($seen[$c['client_id']])) {
                                $seen[$c['client_id']] = true;
                                $uniqueClients[] = $c;
                            }
                        }
                    ?>
                    <div>
                        <div class="day-col-header">
                            <div class="day-name"><?= $day['short'] ?></div>
                            <div class="day-num<?= $isToday ? ' today' : '' ?>"><?= $dayNum ?></div>
                        </div>
 
                        <?php if (empty($uniqueClients)): ?>
                            <div class="slot-empty">
                                <i class="fas fa-moon"></i><br>Rest Day
                            </div>
                        <?php else: ?>
                            <?php foreach ($uniqueClients as $entry):
                                $ci    = $clientColorMap[$entry['client_id']] ?? 0;
                                // Map palette colour to existing slot classes; fall back to inline style
                                $slotClasses = ['slot-blue','slot-teal','slot-green','slot-amber','slot-blue','slot-purple','slot-red','slot-teal'];
                                $slotCls     = $slotClasses[$ci] ?? 'slot-blue';
                                // Build short display name: "Sara A."
                                $nameParts   = explode(' ', $entry['client_name']);
                                $displayName = $nameParts[0] . (isset($nameParts[1]) ? ' ' . $nameParts[1][0] . '.' : '');
                            ?>
                                <div class="session-slot <?= $slotCls ?>">
                                    <div class="s-name"><?= htmlspecialchars($displayName) ?></div>
                                    <div class="s-time" style="font-size:1rem;opacity:5"><?= htmlspecialchars($entry['plan_name']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <script>
        // ── Sidebar Toggle (mobile) ──
        const sidebar = document.getElementById("sidebar");
        const sidebarToggle = document.getElementById("sidebarToggle");
        const sidebarOverlay = document.getElementById("sidebarOverlay");

        if (sidebarToggle && sidebar && sidebarOverlay) {
            sidebarToggle.addEventListener("click", () => {
                sidebar.classList.toggle("open");
                sidebarOverlay.classList.toggle("open");
            });
            sidebarOverlay.addEventListener("click", () => {
                sidebar.classList.remove("open");
                sidebarOverlay.classList.remove("open");
            });
        }

        // ── Logout ──
        function handleLogout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'login.php';
            }
        }
    </script>
</body>

</html>