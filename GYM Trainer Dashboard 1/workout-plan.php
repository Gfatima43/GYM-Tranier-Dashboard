<?php
session_start();

require 'middleware/middle.php';
requireAuth();

$database = require 'bootstrap.php';
$pdo = $database->pdo;

$logged_in = isset($_SESSION['user_id']);
$user_name  = $_SESSION['user_name'] ?? '';
$user_role  = $_SESSION['role'] ?? '';
$userId    = $_SESSION['user_id'] ?? '';

$isAdmin   = isAdmin();
$isTrainer = isTrainer();
$trainerId = null;

// $currentPage = 'workout-plan';

$message     = '';
$messageType = '';


// ════════════════════════════════════════════════════════════════
//  POST handlers
// ════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── ADD PLAN ──
    if ($action === 'add_plan') {
        $planName  = trim($_POST['plan_name'] ?? '');
        $exercises = $_POST['exercises'] ?? [];   // array of {name, sets, set_counter}

        if (empty($planName)) {
            $message = 'Plan name is required.';
            $messageType = 'danger';
        } else {
            $stmt = $pdo->prepare("INSERT INTO workout_plans (plan_name, trainer_id) VALUES (?, ?)");
            $stmt->execute([$planName, $userId]);
            $planId = $pdo->lastInsertId();

            $exStmt = $pdo->prepare("INSERT INTO workouts (plan_id, workout_name, sets, set_counter) VALUES (?, ?, ?, ?)");
            foreach ($exercises as $ex) {
                $exName = trim($ex['name'] ?? '');
                $exSets = max(1, (int)($ex['sets'] ?? 3));
                $exCount = max(1, (int)($ex['set_counter'] ?? 5));
                if ($exName !== '') {
                    $exStmt->execute([$planId, $exName, $exSets, $exCount]);
                }
            }
            $message = "Plan \"{$planName}\" created successfully!";
            $messageType = 'success';
        }
    }

    // ── EDIT PLAN ──
    elseif ($action === 'edit_plan') {
        $planId   = (int)($_POST['plan_id'] ?? 0);
        $planName = trim($_POST['plan_name'] ?? '');
        $exercises = $_POST['exercises'] ?? [];

        if (empty($planName) || $planId < 1) {
            $message = 'Plan name is required.';
            $messageType = 'danger';
        } else {
            // if ($isTrainer) {
            //     $check = $pdo->prepare("SELECT trainer_id FROM workout_plans WHERE id = ?");
            //     $check->execute([$planId]);
            //     $ownerId = $check->fetchColumn();

            //     if ($ownerId != $trainerId) {
            //         die("Unauthorized action.");
            //     }
            // }

            $pdo->prepare("UPDATE workout_plans SET plan_name = ? WHERE id = ?")->execute([$planName, $planId]);

            // Delete existing exercises and re-insert
            $pdo->prepare("DELETE FROM workouts WHERE plan_id = ?")->execute([$planId]);
            $exStmt = $pdo->prepare("INSERT INTO workouts (plan_id, workout_name, sets, set_counter) VALUES (?, ?, ?, ?)");
            foreach ($exercises as $ex) {
                $exName = trim($ex['name'] ?? '');
                $exSets = max(1, (int)($ex['sets'] ?? 3));
                $exCount = max(1, (int)($ex['set_counter'] ?? 5));
                if ($exName !== '') {
                    $exStmt->execute([$planId, $exName, $exSets, $exCount]);
                }
            }
            $message = "Plan \"{$planName}\" updated successfully!";
            $messageType = 'success';
        }
    }

    // ── DELETE PLAN ──
    elseif ($action === 'delete_plan') {
        $planId = (int)($_POST['plan_id'] ?? 0);

        if ($planId > 0) {

            // Security: trainer can delete only own plans
            // if ($isTrainer) {
            //     $check = $pdo->prepare("SELECT trainer_id FROM workout_plans WHERE id = ?");
            //     $check->execute([$planId]);
            //     $ownerId = $check->fetchColumn();

            //     if ($ownerId != $trainerId) {
            //         die("Unauthorized action.");
            //     }
            // }

            $pdo->prepare("DELETE FROM workouts WHERE plan_id = ?")->execute([$planId]);
            $pdo->prepare("DELETE FROM client_schedules WHERE plan_id = ?")->execute([$planId]);
            $pdo->prepare("DELETE FROM workout_plans WHERE id = ?")->execute([$planId]);

            $message = 'Workout plan deleted.';
            $messageType = 'success';
        }
    }

    // ── ASSIGN / EDIT CLIENT SCHEDULE ───
    elseif ($action === 'assign_schedule') {

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
            $pdo->prepare("DELETE FROM client_schedules WHERE client_id = ?")       ->execute([$clientId]);
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
        $scheduleId = (int)($_POST['schedule_id'] ?? 0);
        if ($scheduleId > 0) {
            $pdo->prepare("DELETE FROM client_schedules WHERE id = ?")->execute([$scheduleId]);
            $message = 'Schedule removed.';
            $messageType = 'success';
        }
    }

    $_SESSION['flash'] = ['msg' => $message, 'type' => $messageType];
    header('Location: workout-plan.php');
    exit();
}

// Flash
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
    $trainerRow = $pdo->prepare("SELECT id FROM trainers WHERE user_id = ?");
    $trainerRow->execute([$userId]);
    $trainerId = $trainerRow->fetchColumn();
}

// All plans with exercise count
if ($isTrainer && $trainerId) {
    $stmt = $pdo->prepare("
        SELECT wp.*, COUNT(w.id) AS exercise_count
        FROM workout_plans wp
        LEFT JOIN workouts w ON w.plan_id = wp.id
        WHERE wp.trainer_id = ?
        GROUP BY wp.id
        ORDER BY wp.id DESC
    ");
    $stmt->execute([$trainerId]);
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $plans = $pdo->query("
        SELECT wp.*, COUNT(w.id) AS exercise_count
        FROM workout_plans wp
        LEFT JOIN workouts w ON w.plan_id = wp.id
        GROUP BY wp.id
        ORDER BY wp.id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
}

// All exercises grouped by plan
$allExercises = $pdo->query("SELECT * FROM workouts ORDER BY plan_id, id")->fetchAll(PDO::FETCH_ASSOC);
$exercisesByPlan = [];
foreach ($allExercises as $ex) {
    $exercisesByPlan[$ex['plan_id']][] = $ex;
}

// Clients
if ($isTrainer && $trainerId) {
    $clients = $pdo->prepare("SELECT * FROM clients WHERE trainer_id = ? ORDER BY firstName");
    $clients->execute([$trainerId]);
    $clients = $clients->fetchAll(PDO::FETCH_ASSOC);
} else {
    $clients = $pdo->query("SELECT * FROM clients ORDER BY firstName")->fetchAll(PDO::FETCH_ASSOC);
}


// Client schedules with plan name + client name
$schedules = $pdo->query("
    SELECT cs.id, cs.MON, cs.TUE, cs.WED, cs.THU, cs.FRI, cs.SAT, cs.SUN, cs.client_id, cs.plan_id,
           CONCAT(c.firstName,' ',c.lastName) AS client_name,
           wp.plan_name
    FROM client_schedules cs
    JOIN clients c ON cs.client_id = c.id
    JOIN workout_plans wp ON cs.plan_id = wp.id
    GROUP BY cs.client_id
")->fetchAll(PDO::FETCH_ASSOC);

// Group schedules by client for display
$scheduleByClient = [];
foreach ($schedules as $s) {
    $cid = $s['client_id'];
    $scheduleByClient[$cid]['name']      = $s['client_name'];
    $scheduleByClient[$cid]['plan_name'] = $s['plan_name'];
    $scheduleByClient[$cid]['plan_id']   = $s['plan_id'];
    $scheduleByClient[$cid]['ids'][]     = $s['id'];
    
    // Build days array from individual day columns
    $days = [];
    $dayMap = ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'];
    foreach ($dayMap as $day) {
        if (!empty($s[$day])) {
            $days[] = $day;
        }
    }
    $scheduleByClient[$cid]['days'] = $days;
}

// Build JSON payloads for JS
$plansJson     = json_encode(array_values($plans), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
$exercisesJson = json_encode($exercisesByPlan,      JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);

$clientsJs = [];
foreach ($clients as $c) {
    $clientsJs[] = [
        'id'   => $c['id'],
        'name' => htmlspecialchars($c['firstName'] . ' ' . $c['lastName'], ENT_QUOTES),
    ];
}
$clientsJson = json_encode($clientsJs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);

// // Filter schedules for trainer
// if ($isTrainer && $trainerId) {
//     $schedules = array_filter($schedules, fn($s) => $s['trainer_id'] == $trainerId);
// }


// Icon / colour map
$planIcons = ['🤸‍♀', '💪', '🏃', '🧘', '⚡', '🔥', '🏋️', '🎯'];
$planColours = [
    'linear-gradient(135deg,#1D546D,#5F9598)',
    'linear-gradient(135deg,#22c55e,#16a34a)',
    'linear-gradient(135deg,#f59e0b,#d97706)',
    'linear-gradient(135deg,#8b5cf6,#7c3aed)',
    'linear-gradient(135deg,#06b6d4,#0891b2)',
    'linear-gradient(135deg,#ec4899,#db2777)',
    'linear-gradient(135deg,#ef4444,#dc2626)',
    'linear-gradient(135deg,#14b8a6,#0d9488)',
];
$planBg = [
    'rgba(29,84,109,.1)',
    'rgba(34,197,94,.1)',
    'rgba(245,158,11,.1)',
    'rgba(139,92,246,.1)',
    'rgba(6,182,212,.1)',
    'rgba(236,72,153,.1)',
    'rgba(239,68,68,.1)',
    'rgba(20,184,166,.1)',
];
$planIconColour = [
    'var(--primary)',
    '#16a34a',
    '#d97706',
    '#7c3aed',
    '#0891b2',
    '#db2777',
    '#ef4444',
    '#0d9488',
];

// Days of week
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workout Plans - GYM Trainer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* ── Exercise builder inside modal ── */
        .ex-row {
            display: grid;
            grid-template-columns: 1fr 8rem 8rem 3.4rem;
            gap: .8rem;
            align-items: center;
            margin-bottom: .8rem;
        }

        .ex-del-btn {
            width: 3.2rem;
            height: 3.2rem;
            border-radius: .7rem;
            border: none;
            background: rgba(239, 68, 68, .1);
            color: #ef4444;
            font-size: 1.4rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .2s;
            flex-shrink: 0;
        }

        .ex-del-btn:hover {
            background: #ef4444;
            color: #fff;
        }

        .btn-add-ex {
            display: inline-flex;
            align-items: center;
            gap: .6rem;
            padding: .7rem 1.4rem;
            border: 1.5px dashed rgba(29, 84, 109, .25);
            border-radius: .8rem;
            background: transparent;
            color: var(--primary);
            font-family: 'DM Sans', sans-serif;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
            margin-top: .4rem;
        }

        .btn-add-ex:hover {
            background: rgba(29, 84, 109, .06);
            border-style: solid;
        }

        /* ── Day pill selector ── */
        .day-pills {
            display: flex;
            gap: .6rem;
            flex-wrap: wrap;
        }

        .day-pill {
            padding: .5rem 1.2rem;
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

        /* ── Plan card accent colours ── */
        .plan-card-icon {
            width: 5.6rem;
            height: 5.6rem;
            border-radius: 1.4rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.4rem;
            margin-bottom: 1.4rem;
        }

        .plan-ex-chip {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            font-size: 1.15rem;
            color: rgba(6, 30, 41, .55);
            padding: .3rem 0;
        }

        /* .plan-ex-chip::before {
            content: '';
            width: .45rem;
            height: .45rem;
            border-radius: 50%;
            background: currentColor;
            flex-shrink: 0;
        } */

        /* empty state */
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

        .btn-sm-assign {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .5rem 1.1rem;
            border-radius: .7rem;
            border: none;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            font-family: 'DM Sans', sans-serif;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity .2s;
        }

        .btn-sm-assign:hover {
            opacity: .85;
        }

        .sched-table {
            width: 100%;
            border-collapse: collapse;
        }

        .sched-table th {
            padding: 1.1rem 1.6rem;
            font-size: 1.1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: rgba(6, 30, 41, .4);
            border-bottom: 1px solid rgba(29, 84, 109, .08);
            text-align: left;
        }

        .sched-table td {
            padding: 1.3rem 1.6rem;
            border-bottom: 1px solid rgba(29, 84, 109, .05);
            font-size: 1.25rem;
            vertical-align: middle;
        }

        .sched-table tr:last-child td {
            border-bottom: none;
        }

        .sched-table tr:hover td {
            background: rgba(29, 84, 109, .02);
        }

        .day-badge {
            display: inline-block;
            padding: .2rem .75rem;
            border-radius: .5rem;
            font-size: 1rem;
            font-weight: 600;
            background: rgba(29, 84, 109, .08);
            color: var(--primary);
            margin: .15rem;
        }

        .ex-col-header {
            font-size: 1.1rem;
            font-weight: 600;
            color: rgba(6, 30, 41, .4);
            padding-left: .2rem;
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
                <div class="topbar-title">Workout Plans</div>
                <div class="topbar-subtitle"><?= count($plans) ?> plans · <?= count($scheduleByClient) ?> clients scheduled</div>
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
        <!-- ── Page header ── -->
        <div style="display:flex;align-items:center;justify-content: flex-end;margin-bottom:2.4rem">
            <div style="display:flex;gap:1rem">
                <button class="btn-add" onclick="openScheduleModal()">
                    <i class="fas fa-calendar-plus"></i>Client Schedule
                </button>
                <button class="btn-add" style="background:linear-gradient(135deg,#22c55e,#16a34a)" onclick="openAddPlanModal()">
                    <i class="fas fa-plus"></i>New Plan
                </button>
            </div>
        </div>


        <!-- ── PLANS GRID ── -->
        <div class="section-card" style="margin-bottom:2rem">
            <div style="padding:2rem 2.4rem 1.4rem;border-bottom:1px solid rgba(29,84,109,.07)">
                <span style="font-size:1.6rem;font-weight:700;color:var(--dark)">
                    <i class="fas fa-dumbbell" style="color:var(--primary);margin-right:.7rem"></i>Workout Plans
                </span>
            </div>
            <?php if (empty($plans)): ?>
                <div class="empty-state">
                    <i class="fas fa-dumbbell"></i>
                    <p>No workout plans yet.<br>Click <strong>New Plan</strong> to create your first one.</p>
                </div>
            <?php else: ?>
                <div class="plans-grid" style="padding:2.4rem">
                    <?php foreach ($plans as $i => $plan):
                        $idx      = $i % count($planIcons);
                        $exList   = $exercisesByPlan[$plan['id']] ?? [];
                        $exCount  = count($exList);
                        $showMax  = 3;
                    ?>
                        <div class="plan-card">
                            <div class="plan-header">
                                <div class="plan-card-icon" style="background:<?= $planBg[$idx] ?>;color:<?= $planIconColour[$idx] ?>">
                                    <?= $planIcons[$idx] ?>
                                </div>
                                <div class="plan-name"><?= htmlspecialchars($plan['plan_name']) ?></div>
                                <span class="plan-tag" style="background:<?= $planBg[$idx] ?>;color:<?= $planIconColour[$idx] ?>">
                                    <?= $exCount ?> exercise<?= $exCount !== 1 ? 's' : '' ?>
                                </span>
                                <div style="margin-top:1rem">
                                    <div class="exercise-list">
                                        <?php foreach (array_slice($exList, 0, $showMax) as $ex): ?>
                                            <div class="plan-ex-chip"><span style="width:.5rem;height:.5rem;border-radius:50%;background:#1D546D;flex-shrink:0"></span>
                                                <?= htmlspecialchars($ex['workout_name']) ?> · <?= $ex['sets'] ?> sets x <?= $ex['set_counter'] ?> reps
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if ($exCount > $showMax): ?>
                                        <div style="font-size:1.1rem;color:rgba(6,30,41,.35);margin-top:.4rem">
                                            +<?= $exCount - $showMax ?> more…
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="plan-divider"></div>
                            <div class="plan-footer" style="flex-wrap:wrap;gap:.6rem">
                                <button class="btn-sm btn-sm-primary" onclick="openViewPlanModal(<?= $plan['id'] ?>)">
                                    <i class="fas fa-eye"></i>View
                                </button>
                                <button class="btn-sm btn-sm-outline" onclick="openEditPlanModal(<?= $plan['id'] ?>)">
                                    <i class="fas fa-pen"></i>Edit
                                </button>
                                <button class="btn-sm btn-sm-outline ms-auto"
                                    style="border-color:rgba(239,68,68,.2);color:#ef4444"
                                    onclick="openDeletePlanModal(<?= $plan['id'] ?>, '<?= htmlspecialchars($plan['plan_name'], ENT_QUOTES) ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Add new card -->
                    <div class="add-plan-card" onclick="openAddPlanModal()">
                        <div class="add-icon"><i class="fas fa-plus"></i></div>
                        <div class="add-label">Create New Plan</div>
                        <div style="font-size:1.2rem;color:rgba(6,30,41,.3)">Design a custom workout plan</div>
                    </div>
                </div>
            <?php endif; ?>


            <!-- CLIENT SCHEDULEs LIST -->
            <div class="section-card">
                <div style="padding:2rem 2.4rem 1.4rem;border-bottom:1px solid rgba(29,84,109,.07);display:flex;align-items:center;justify-content:space-between">
                    <span style="font-size:1.6rem;font-weight:700;color:var(--dark)">
                        <i class="fas fa-calendar-days" style="color:var(--primary);margin-right:.7rem"></i>Client Schedules
                    </span>
                    <a href="schedule.php" style="font-size:1.2rem;font-weight:600;color:var(--primary);text-decoration:none">
                        View Calendar <i class="fas fa-arrow-right" style="margin-left:.4rem"></i>
                    </a>
                </div>
                <?php if (empty($scheduleByClient)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-days"></i>
                        <p>No client schedules yet.<br>Click <strong>Client Schedule</strong> to get started.</p>
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
                                    $initials = strtoupper(substr($sched['name'], 0, 1));
                                    $parts    = explode(' ', $sched['name']);
                                    if (isset($parts[1])) $initials .= strtoupper(substr($parts[1], 0, 1));
                                ?>
                                    <tr>
                                        <td>
                                            <div style="display:flex;align-items:center;gap:1.2rem">
                                                <div class="client-av" style="background:linear-gradient(135deg,#1D546D,#5F9598)"><?= $initials ?></div>
                                                <span style="font-weight:600"><?= htmlspecialchars($sched['name']) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="font-weight:600;color:var(--primary)"><?= htmlspecialchars($sched['plan_name']) ?></span>
                                        </td>
                                        <td>
                                            <?php foreach ($sched['days'] as $day): ?>
                                                <span class="day-badge"><?= $day ?></span>
                                            <?php endforeach; ?>
                                        </td>
                                        <td>
                                            <button class="action-btn edit" title="Edit"
                                                onclick="openScheduleModal(<?= $sched['plan_id'] ?>, <?= $cid ?>, '<?= htmlspecialchars($sched['name'], ENT_QUOTES) ?>', <?= json_encode($sched['days']) ?>)">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <button class="action-btn del" title="Remove"
                                                onclick="openDeleteScheduleModal(<?= $cid ?>, '<?= htmlspecialchars($sched['name'], ENT_QUOTES) ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ══════════════════════════════════════════
                    ADD / EDIT PLAN MODAL
        ══════════════════════════════════════════ -->
        <div class="modal-overlay" id="planModal">
            <div class="modal-box" style="max-width:60rem">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem">
                    <div class="modal-title" id="planModalTitle">New Workout Plan</div>
                    <button onclick="closeModal('planModal')" style="background:rgba(6,30,41,.08);border:none;width:3.4rem;height:3.4rem;border-radius:.8rem;cursor:pointer;font-size:1.6rem;color:var(--dark)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" action="workout-plan.php" id="planForm">
                    <input type="hidden" name="action" id="planAction" value="add_plan">
                    <input type="hidden" name="plan_id" id="planIdField" value="">

                    <div style="margin-bottom:1.6rem">
                        <label class="gym-label">Plan Name</label>
                        <input type="text" name="plan_name" id="planNameInput" class="gym-input" placeholder="e.g. Weight Loss Blast" required>
                    </div>

                    <!-- Exercise builder -->
                    <div style="margin-bottom:1rem">
                        <!-- <label class="gym-label">Exercises</label> -->
                        <div style="display:grid;grid-template-columns:1fr 8rem 8rem 3.4rem;gap:.8rem;margin-bottom:.5rem">
                            <span class="ex-col-header">Exercise Name</span>
                            <span class="ex-col-header">Sets</span>
                            <span class="ex-col-header">Reps</span>
                            <span></span>
                        </div>
                        <div id="exerciseList"></div>
                        <button type="button" class="btn-add-ex" onclick="addExerciseRow()">
                            <i class="fas fa-plus"></i> Add Exercise
                        </button>
                    </div>

                    <div style="display:flex;gap:1rem;margin-top:2.4rem">
                        <button type="button" onclick="closeModal('planModal')"
                            style="flex:1;height:4.6rem;border-radius:1rem;border:1.5px solid rgba(29,84,109,.2);background:transparent;font-family:'DM Sans',sans-serif;font-size:1.3rem;font-weight:600;color:var(--dark);cursor:pointer">
                            Cancel
                        </button>
                        <button type="submit"
                            style="flex:2;height:4.6rem;border-radius:1rem;border:none;background:linear-gradient(135deg,var(--primary),var(--secondary));color:#fff;font-family:'DM Sans',sans-serif;font-size:1.3rem;font-weight:600;cursor:pointer">
                            <i class="fas fa-check" style="margin-right:.6rem"></i>Save Plan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ══════════════════════════════════════════
            VIEW PLAN MODAL (read-only)
        ══════════════════════════════════════════ -->
        <div class="modal-overlay" id="viewPlanModal">
            <div class="modal-box" style="max-width:52rem">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem">
                    <div class="modal-title" id="viewPlanName">Plan Details</div>
                    <button onclick="closeModal('viewPlanModal')" style="background:rgba(6,30,41,.08);border:none;width:3.4rem;height:3.4rem;border-radius:.8rem;cursor:pointer;font-size:1.6rem;color:var(--dark)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="viewExList" style="display:flex;flex-direction:column;gap:.6rem"></div>
                <div style="margin-top:2.4rem;text-align:right">
                    <button onclick="closeModal('viewPlanModal')"
                        style="padding:.9rem 2.4rem;border-radius:1rem;border:1.5px solid rgba(29,84,109,.2);background:transparent;font-family:'DM Sans',sans-serif;font-size:1.3rem;font-weight:600;color:var(--dark);cursor:pointer">
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════
            DELETE PLAN MODAL
        ══════════════════════════════════════════ -->
        <div class="modal-overlay" id="deletePlanModal">
            <div class="modal-box" style="max-width:44rem">
                <div class="del-icon"><i class="fas fa-trash"></i></div>
                <div class="modal-title" style="text-align:center;margin-bottom:.8rem">Delete Plan</div>
                <p style="text-align:center;font-size:1.3rem;color:rgba(6,30,41,.6);margin-bottom:2.4rem">
                    Are you sure you want to delete <strong id="deletePlanName"></strong>?
                    All associated exercises and schedules will be removed.
                </p>
                <form method="POST" action="workout-plan.php">
                    <input type="hidden" name="action" value="delete_plan">
                    <input type="hidden" name="plan_id" id="deletePlanId">
                    <div style="display:flex;gap:1rem">
                        <button type="button" onclick="closeModal('deletePlanModal')"
                            style="flex:1;height:4.6rem;border-radius:1rem;border:1.5px solid rgba(29,84,109,.2);background:transparent;font-family:'DM Sans',sans-serif;font-size:1.3rem;font-weight:600;color:var(--dark);cursor:pointer">
                            Cancel
                        </button>
                        <button type="submit"
                            style="flex:1;height:4.6rem;border-radius:1rem;border:none;background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;font-family:'DM Sans',sans-serif;font-size:1.3rem;font-weight:600;cursor:pointer">
                            Yes, Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ══════════════════════════════════════════
            ASSIGN SCHEDULE MODAL
        ══════════════════════════════════════════ -->
        <div class="modal-overlay" id="scheduleModal">
            <div class="modal-box" style="max-width:54rem">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem">
                    <div class="modal-title" id="schedModalTitle">Client Schedule</div>
                    <button onclick="closeModal('scheduleModal')" style="background:rgba(6,30,41,.08);border:none;width:3.4rem;height:3.4rem;border-radius:.8rem;cursor:pointer;font-size:1.6rem;color:var(--dark)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" action="workout-plan.php" id="schedForm">
                    <input type="hidden" name="action" value="assign_schedule">
                    <input type="hidden" name="client_id" id="schedClientId">

                    <!-- Client select -->
                    <div style="margin-bottom:1.6rem" id="clientSelectWrap">
                        <label class="gym-label">Client *</label>
                        <select name="client_id" id="schedClientSelect" class="gym-select" onchange="syncClientId(this.value)" required>
                            <option value="">— Select client —</option>
                            <?php foreach ($clients as $c): ?>
                                <option value="<?= $c['id'] ?>">
                                    <?= htmlspecialchars($c['firstName'] . ' ' . $c['lastName']) ?>
                                </option>
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
                                <button type="button" class="day-pill" data-day="<?= $d ?>" onclick="toggleDay(this)"><?= $d ?></button>
                            <?php endforeach; ?>
                        </div>
                        <!-- hidden inputs injected by JS -->
                        <div type="hidden" name="days[]" id="dayInputs"></div>
                    </div>

                    <div style="display:flex;gap:1rem">
                        <button type="button" onclick="closeModal('scheduleModal')"
                            style="flex:1;height:4.6rem;border-radius:1rem;border:1.5px solid rgba(29,84,109,.2);background:transparent;font-family:'DM Sans',sans-serif;font-size:1.3rem;font-weight:600;color:var(--dark);cursor:pointer">
                            Cancel
                        </button>
                        <button type="submit" onclick="return injectDayInputs()"
                            style="flex:2;height:4.6rem;border-radius:1rem;border:none;background:linear-gradient(135deg,var(--primary),var(--secondary));color:#fff;font-family:'DM Sans',sans-serif;font-size:1.3rem;font-weight:600;cursor:pointer">
                            <i class="fas fa-calendar-check" style="margin-right:.6rem"></i>Save Schedule
                        </button>
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
                <p style="text-align:center;font-size:1.3rem;color:rgba(6,30,41,.6);margin-bottom:2.4rem">
                    Remove all scheduled days for <strong id="deleteSchedName"></strong>?
                </p>
                <form method="POST" action="workout-plan.php" id="deleteSchedForm">
                    <input type="hidden" name="action" value="delete_schedule">
                    <input type="hidden" name="client_id" id="deleteSchedClientId">
                    <!-- <input type="hidden" name="plan_id" value="0"> -->
                    <div style="display:flex;gap:1rem">
                        <button type="button" onclick="closeModal('deleteScheduleModal')"
                            style="flex:1;height:4.6rem;border-radius:1rem;border:1.5px solid rgba(29,84,109,.2);background:transparent;font-family:'DM Sans',sans-serif;font-size:1.3rem;font-weight:600;color:var(--dark);cursor:pointer">
                            Cancel
                        </button>
                        <button type="button" onclick="confirmDeleteSchedule()"
                            style="flex:1;height:4.6rem;border-radius:1rem;border:none;background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;font-family:'DM Sans',sans-serif;font-size:1.3rem;font-weight:600;cursor:pointer">
                            Yes, Remove
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- <script src="js/script.js"></script> -->
    <script>
        // ── Data from PHP ───
        const plansData = <?= $plansJson ?>;
        const exercisesData = <?= $exercisesJson ?>;
        // const clientsData = <?= $clientsJson ?>;

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

        function handleLogout() {
            if (confirm('Are you sure you want to logout?')) window.location.href = 'logout.php';
        }

        // ── Modal helpers ──────────────────────────────────────────────
        function openModal(id) {
            document.getElementById(id).classList.add('open');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('open');
        }
        document.querySelectorAll('.modal-overlay').forEach(m => {
            m.addEventListener('click', e => {
                if (e.target === m) m.classList.remove('open');
            });
        });

        // ── Exercise builder helpers ───────────────────────────────────
        let exRowCount = 0;

        function addExerciseRow(name = '', sets = 3, reps = 3) {
            const container = document.getElementById('exerciseList');
            const idx = exRowCount++;
            const row = document.createElement('div');
            row.className = 'ex-row';
            row.innerHTML = `
                <input type="text" name="exercises[${idx}][name]" class="gym-input" placeholder="e.g. Barbell Squat" value="${escHtml(name)}" required style="margin:0">
                <input type="number" name="exercises[${idx}][sets]" class="gym-input"  placeholder="Sets" min="1" max="20" value="${sets}" style="margin:0">
                <input type="number" name="exercises[${idx}][set_counter]" class="gym-input" placeholder="Set Count" min="1" max="99" value="${reps}" style="margin:0">
                <button type="button" class="ex-del-btn" onclick="this.closest('.ex-row').remove()">
                <i class="fas fa-times"></i>
                </button>`;
            container.appendChild(row);
        }

        function escHtml(str) {
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        // ── ADD PLAN modal ─────────────────────────────────────────────
        function openAddPlanModal() {
            document.getElementById('planModalTitle').textContent = 'New Workout Plan';
            document.getElementById('planAction').value = 'add_plan';
            document.getElementById('planIdField').value = '';
            document.getElementById('planNameInput').value = '';
            document.getElementById('exerciseList').innerHTML = '';
            exRowCount = 0;
            addExerciseRow(); // start with one blank row
            openModal('planModal');
        }

        // ── EDIT PLAN modal ────────────────────────────────────────────
        function openEditPlanModal(id) {
            const plan = plansData.find(p => p.id == id);
            if (!plan) return;
            document.getElementById('planModalTitle').textContent = 'Edit Workout Plan';
            document.getElementById('planAction').value = 'edit_plan';
            document.getElementById('planIdField').value = id;
            document.getElementById('planNameInput').value = plan.plan_name;
            document.getElementById('exerciseList').innerHTML = '';
            exRowCount = 0;
            const exs = exercisesData[id] || [];
            exs.length ? exs.forEach(e => addExerciseRow(e.workout_name, e.sets, e.set_counter)) : addExerciseRow();
            openModal('planModal');
        }

        // ── VIEW PLAN modal ────────────────────────────────────────────
        function openViewPlanModal(id) {
            const plan = plansData.find(p => p.id == id);
            if (!plan) return;
            document.getElementById('viewPlanName').textContent = plan.plan_name;
            const exs = exercisesData[id] || [];
            const list = document.getElementById('viewExList');
            list.innerHTML = '';
            if (!exs.length) {
                list.innerHTML = '<p style="color:rgba(6,30,41,.4);font-size:1.3rem">No exercises added yet.</p>';
            } else {
                exs.forEach((e, i) => {
                    list.innerHTML += `
                <div style="display:flex;align-items:center;justify-content:space-between;padding:1.2rem 1.6rem;
                            background:rgba(29,84,109,.04);border-radius:1rem;margin-bottom:.6rem">
                    <div style="display:flex;align-items:center;gap:1rem">
                        <div style="width:3rem;height:3rem;border-radius:.8rem;background:var(--primary);color:#fff;
                                    display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.2rem">${i+1}</div>
                        <span style="font-size:1.35rem;font-weight:600;color:var(--dark)">${escHtml(e.workout_name)}</span>
                    </div>
                    <span style="font-size:1.2rem;font-weight:600;color:var(--secondary)">${e.sets} sets</span>
                    <span style="font-size:1.2rem;font-weight:600;color:var(--secondary)">${e.set_counter} reps</span>
                </div>`;
                });
            }
            openModal('viewPlanModal');
        }

        // ── DELETE PLAN modal ──────────────────────────────────────────
        function openDeletePlanModal(id, name) {
            document.getElementById('deletePlanId').value = id;
            document.getElementById('deletePlanName').textContent = name;
            openModal('deletePlanModal');
        }

        // ── ASSIGN SCHEDULE modal ──────────────────────────────────────
        function openScheduleModal(planId, clientId, currentDays) {
            document.getElementById('schedModalTitle').textContent = planId ? 'Assign to Client' : 'Assign Schedule';

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

        // ── EDIT SCHEDULE modal ──────────────────────────────────────
        function editScheduleModal(clientId, planId, clientName, days, scheduleIds) {
            // Set edit mode
            document.getElementById('schedForm').setAttribute('data-edit-mode', 'true');
            document.getElementById('schedForm').setAttribute('data-schedule-ids', JSON.stringify(scheduleIds));
            
            // Set title
            document.getElementById('schedModalTitle').textContent = 'Edit Client Schedule - ' + clientName;
            
            // Hide client selector in edit mode
            document.getElementById('clientSelectWrap').style.display = 'none';
            
            // Set hidden client ID and plan
            document.getElementById('schedClientId').value = clientId;
            document.getElementById('schedPlanSelect').value = planId;
            
            // Reset all day pills
            document.querySelectorAll('.day-pill').forEach(p => {
                p.classList.remove('selected');
            });
            
            // Select saved days
            if (Array.isArray(days) && days.length > 0) {
                days.forEach(day => {
                    const pill = document.querySelector(`.day-pill[data-day="${day}"]`);
                    if (pill) {
                        pill.classList.add('selected');
                    }
                });
            }
            
            // Open the modal
            openModal('scheduleModal');
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
            const container = document.getElementById('dayInputs');
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


        function openDeleteScheduleModal(clientId, name) {
            document.getElementById('deleteSchedClientId').value = clientId;
            document.getElementById('deleteSchedName').textContent = name;
            openModal('deleteScheduleModal');
        }

        // ── DELETE SCHEDULE modal ──────────────────────────────────────
        let deleteSchedClientId = null;

        function openDeleteScheduleModal(clientId, name) {
            deleteSchedClientId = clientId;
            document.getElementById('deleteSchedName').textContent = name;
            openModal('deleteScheduleModal');
        }

        function confirmDeleteSchedule() {
            document.getElementById('deleteSchedClientId').value = deleteSchedClientId;
            document.getElementById('deleteSchedForm').submit();
            
        }

        // ── Auto-dismiss toast ─────────────────────────────────────────
        const toast = document.getElementById('toastWrap');
        if (toast) setTimeout(() => toast.remove(), 4200);
    </script>
</body>

</html>