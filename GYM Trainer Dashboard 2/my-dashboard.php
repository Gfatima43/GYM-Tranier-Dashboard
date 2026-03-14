<?php
session_start();

require 'middleware/middle.php';
requireAuth();

// Clients must stay on their own dashboard
if (!isClient()) {
    header('Location: dashboard.php');
    exit();
}

$database = require 'bootstrap.php';
$pdo      = $database->pdo;

$userEmail = $_SESSION['user_email'] ?? '';
$userName  = $_SESSION['user_name']  ?? '';

// ── Fetch client record ──────────────────────────────────────────
$progress = (int)$pdo->query("SELECT COUNT(*) FROM clients WHERE 'progress'")->fetchColumn();
$stmt = $pdo->prepare("
    SELECT c.*, t.name AS trainer_name, t.specialization, t.experience_years, t.status AS trainer_status
    FROM clients c
    LEFT JOIN trainers t ON c.trainer_id = t.id
    WHERE c.email = ?
    LIMIT 1
");
$stmt->execute([$userEmail]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    // Logged in as client role but no clients row found — show a friendly message
    $client = [
        'id'               => 0,
        'firstName'        => $userName,
        'lastName'         => '',
        'plan'             => 'N/A',
        'progress'         => 0,
        'sessions'         => 0,
        'status'           => 'Active',
        'trainer_name'     => null,
        'specialization'   => null,
        'experience_years' => 0,
        'trainer_status'   => null,
    ];
}

$clientId = $client['id'];

// ── Fetch client's schedule + plan ──────────────────────────────
$schedule = null;
$workouts = [];
$todayTraining = false;
$daysPerWeek   = 0;

if ($clientId) {
    $schedStmt = $pdo->prepare("
        SELECT cs.*, wp.plan_name
        FROM client_schedules cs
        JOIN workout_plans wp ON cs.plan_id = wp.id
        WHERE cs.client_id = ?
        LIMIT 1
    ");
    $schedStmt->execute([$clientId]);
    $schedule = $schedStmt->fetch(PDO::FETCH_ASSOC);

    if ($schedule) {
        // Which days is the client scheduled?
        $dayColumns  = ['MON','TUE','WED','THU','FRI','SAT','SUN'];
        $daysPerWeek = 0;
        foreach ($dayColumns as $col) {
            if (!empty($schedule[$col])) $daysPerWeek++;
        }

        // Is today a training day?
        $phpDayToCol = [1=>'MON',2=>'TUE',3=>'WED',4=>'THU',5=>'FRI',6=>'SAT',7=>'SUN'];
        $todayCol    = $phpDayToCol[(int)date('N')];
        $todayTraining = !empty($schedule[$todayCol]);

        // Fetch workouts for this plan
        $wStmt = $pdo->prepare("SELECT * FROM workouts WHERE plan_id = ? ORDER BY id");
        $wStmt->execute([$schedule['plan_id']]);
        $workouts = $wStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// ── Greeting ────────────────────────────────────────────────────
$hour = (int)date('H');
$greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');

// Day labels for weekly chips
$dayLabels = [
    'MON' => 'Mon', 'TUE' => 'Tue', 'WED' => 'Wed',
    'THU' => 'Thu', 'FRI' => 'Fri', 'SAT' => 'Sat', 'SUN' => 'Sun',
];
$phpDayToCol2 = [1=>'MON',2=>'TUE',3=>'WED',4=>'THU',5=>'FRI',6=>'SAT',7=>'SUN'];
$todayColKey  = $phpDayToCol2[(int)date('N')];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard – GYM Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* ── Client-specific overrides ───────────────────────────── */
        .client-hero {
            background: var(--dark);
            border-radius: var(--card-radius);
            padding: 3rem 3.2rem;
            position: relative;
            overflow: hidden;
            margin-bottom: 2rem;
            animation: fadeUp .5s ease both;
        }
        .client-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 10% 50%, rgba(29,84,109,.9) 0%, transparent 65%),
                radial-gradient(ellipse 60% 80% at 90% 20%, rgba(95,149,152,.35) 0%, transparent 55%);
            pointer-events: none;
        }
        .client-hero > * { position: relative; z-index: 1; }
        .hero-greeting {
            font-size: 1.3rem;
            color: rgba(255,255,255,.5);
            text-transform: uppercase;
            letter-spacing: .1em;
            font-weight: 600;
            margin-bottom: .4rem;
        }
        .hero-name {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 4rem;
            font-weight: 800;
            color: #fff;
            line-height: 1;
            text-transform: uppercase;
        }
        .hero-plan-badge {
            display: inline-flex;
            align-items: center;
            gap: .6rem;
            margin-top: 1rem;
            padding: .45rem 1.2rem;
            background: rgba(126,200,203,.15);
            border: 1px solid rgba(126,200,203,.3);
            border-radius: 10rem;
            color: var(--accent);
            font-size: 1.2rem;
            font-weight: 600;
        }

        /* Stat bar inside hero */
        .hero-stat-bar {
            display: flex;
            gap: 0;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 1.2rem;
            overflow: hidden;
            margin-top: 2.4rem;
        }
        .hero-stat-item {
            flex: 1;
            padding: 1.4rem 2rem;
            text-align: center;
            border-right: 1px solid rgba(255,255,255,.08);
        }
        .hero-stat-item:last-child { border-right: none; }
        .hero-stat-val {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 2.8rem;
            font-weight: 800;
            color: #fff;
            line-height: 1;
        }
        .hero-stat-lbl {
            font-size: 1.05rem;
            color: rgba(95,149,152,.8);
            text-transform: uppercase;
            letter-spacing: .07em;
            margin-top: .3rem;
        }

        /* Today card */
        .today-card {
            border-radius: var(--card-radius);
            padding: 2rem 2.4rem;
            margin-bottom: 2rem;
            border: 1.5px solid;
            animation: fadeUp .5s ease .1s both;
        }
        .today-card.training {
            background: rgba(29,84,109,.06);
            border-color: rgba(29,84,109,.2);
        }
        .today-card.rest {
            background: rgba(95,149,152,.05);
            border-color: rgba(95,149,152,.18);
        }
        .today-label {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1.6rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
        }
        .today-day {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 3.4rem;
            font-weight: 800;
            line-height: 1;
            color: var(--dark);
        }

        /* Workout exercise card */
        .exercise-card {
            background: #fff;
            border: 1px solid rgba(29,84,109,.08);
            border-radius: var(--card-radius);
            padding: 0;
            box-shadow: 0 2px 12px rgba(6,30,41,.06);
            animation: fadeUp .5s ease .15s both;
        }
        .exercise-row {
            display: flex;
            align-items: center;
            gap: 1.4rem;
            padding: 1.5rem 2.2rem;
            border-bottom: 1px solid rgba(29,84,109,.05);
            transition: background .15s;
        }
        .exercise-row:last-child { border-bottom: none; }
        .exercise-row:hover { background: rgba(29,84,109,.025); }
        .ex-num {
            width: 3.4rem;
            height: 3.4rem;
            border-radius: 1rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1.6rem;
            font-weight: 700;
            flex-shrink: 0;
        }
        .ex-name {
            font-size: 1.35rem;
            font-weight: 600;
            color: var(--dark);
            flex: 1;
        }
        .ex-badge {
            padding: .3rem .9rem;
            border-radius: .6rem;
            background: rgba(29,84,109,.08);
            color: var(--primary);
            font-size: 1.1rem;
            font-weight: 700;
        }

        /* Trainer card */
        .trainer-card {
            background: var(--dark);
            border-radius: var(--card-radius);
            padding: 2.4rem;
            color: #fff;
            position: relative;
            overflow: hidden;
            animation: fadeUp .5s ease .2s both;
        }
        .trainer-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse 100% 70% at 0% 50%, rgba(29,84,109,.8) 0%, transparent 60%);
            pointer-events: none;
        }
        .trainer-card > * { position: relative; z-index: 1; }
        .trainer-avatar {
            width: 6rem;
            height: 6rem;
            border-radius: 1.6rem;
            background: linear-gradient(135deg, var(--secondary), var(--accent));
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 2.2rem;
            font-weight: 800;
            color: #fff;
            border: 2px solid rgba(255,255,255,.15);
            flex-shrink: 0;
        }
        .trainer-name {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #fff;
            line-height: 1;
        }
        .trainer-spec {
            font-size: 1.2rem;
            color: rgba(95,149,152,.9);
            margin-top: .3rem;
        }

        /* Progress bar card */
        .progress-card {
            background: #fff;
            border-radius: var(--card-radius);
            border: 1px solid rgba(29,84,109,.08);
            box-shadow: 0 2px 12px rgba(6,30,41,.06);
            padding: 2.2rem;
            animation: fadeUp .5s ease .25s both;
        }
        .progress-track {
            height: 1.4rem;
            background: rgba(29,84,109,.08);
            border-radius: 10rem;
            overflow: hidden;
            margin-top: 1.2rem;
        }
        .progress-fill {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            transition: width 1.2s ease;
        }

        /* Weekly chips */
        .week-chips {
            display: flex;
            gap: .7rem;
            flex-wrap: wrap;
            margin-top: .8rem;
        }
        .week-chip {
            padding: .5rem 1.3rem;
            border-radius: 10rem;
            font-size: 1.2rem;
            font-weight: 600;
            border: 1.5px solid rgba(29,84,109,.15);
            color: rgba(6,30,41,.45);
            background: transparent;
        }
        .week-chip.active {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }
        .week-chip.today-chip {
            background: #22c55e;
            border-color: #22c55e;
            color: #fff;
        }

        /* Rest day icon */
        .rest-icon {
            width: 5rem;
            height: 5rem;
            border-radius: 50%;
            background: rgba(95,149,152,.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            color: var(--secondary);
        }

        /* Topbar client badge */
        .client-role-badge {
            font-size: 1.1rem;
            font-weight: 700;
            padding: .4rem 1rem;
            border-radius: 10rem;
            background: rgba(34,197,94,.1);
            color: #16a34a;
        }

        /* Sidebar for client — hide irrelevant links */
        .sidebar-nav .admin-only,
        .sidebar-nav .trainer-only {
            display: none !important;
        }
    </style>
</head>
<body>
    <!-- ═════════════════ SIDEBAR OVERLAY (mobile) ═════════════════ -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- ═══════════════════ SIDEBAR ═══════════════════ -->
    <?php include 'assets/sidebar.php'; ?>

    <!-- ═══════════════════ TOPBAR ═══════════════════ -->
    <header class="topbar">
        <div class="topbar-left">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div>
                <div class="topbar-title">My Dashboard</div>
                <div class="topbar-subtitle" id="current-date">Welcome back!</div>
            </div>
        </div>
        <div class="topbar-actions">
            <button class="topbar-btn" data-tip="Notifications">
                <i class="fas fa-bell"></i>
            </button>
            <div class="topbar-user">
                <div class="user-avatar-sm"><?= strtoupper(substr($userName, 0, 2)) ?></div>
                <span class="user-name-sm"><?= htmlspecialchars($userName) ?></span>
                <i class="fas fa-chevron-down" style="font-size:1rem;color:rgba(6,30,41,0.4);margin-left:0.4rem;"></i>
            </div>
            <div class="topbar-btn">
                <button class="btn-logout" onclick="handleLogout()">
                    <i class="fas fa-arrow-right-from-bracket fa-flip-horizontal"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- ═══ MAIN ═══ -->
    <main class="main-content">

        <!-- HERO -->
        <div class="client-hero">
            <div class="hero-greeting"><?= $greeting ?></div>
            <div class="hero-name"><?= htmlspecialchars($client['firstName'] . ' ' . ($client['lastName'] ?? '')) ?></div>
            <?php if ($schedule): ?>
                <div class="hero-plan-badge">
                    <i class="fas fa-dumbbell" style="font-size:.9rem"></i>
                    <?= htmlspecialchars($schedule['plan_name']) ?>
                </div>
            <?php endif; ?>

            <!-- 4-stat bar -->
            <div class="hero-stat-bar">
                <div class="hero-stat-item">
                    <div class="hero-stat-val"><?= $client['progress'] ?>%</div>
                    <div class="hero-stat-lbl">Progress</div>
                </div>
                <div class="hero-stat-item">
                    <div class="hero-stat-val"><?= $client['sessions'] ?></div>
                    <div class="hero-stat-lbl">Sessions</div>
                </div>
                <div class="hero-stat-item">
                    <div class="hero-stat-val"><?= $daysPerWeek ?></div>
                    <div class="hero-stat-lbl">Days / Week</div>
                </div>
                <div class="hero-stat-item">
                    <div class="hero-stat-val"><?= count($workouts) ?></div>
                    <div class="hero-stat-lbl">Exercises</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- LEFT COLUMN -->
            <div class="col-lg-8">

                <!-- TODAY BANNER -->
                <?php if ($schedule): ?>
                    <div class="today-card <?= $todayTraining ? 'training' : 'rest' ?>">
                        <div class="d-flex align-items-center gap-3">
                            <?php if ($todayTraining): ?>
                                <div style="width:5rem;height:5rem;border-radius:50%;background:rgba(29,84,109,.12);display:flex;align-items:center;justify-content:center;font-size:2.2rem;color:var(--primary);">
                                    <i class="fas fa-fire-flame-curved"></i>
                                </div>
                            <?php else: ?>
                                <div class="rest-icon"><i class="fas fa-moon"></i></div>
                            <?php endif; ?>
                            <div>
                                <div class="today-label" style="color:<?= $todayTraining ? 'var(--primary)' : 'var(--secondary)' ?>">
                                    <?= $todayTraining ? 'Training Day' : 'Rest Day' ?>
                                </div>
                                <div class="today-day"><?= date('l') ?></div>
                                <?php if (!$todayTraining): ?>
                                    <div style="font-size:1.2rem;color:rgba(6,30,41,.45);margin-top:.3rem">
                                        Rest up — you've earned it 💪
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- TODAY'S WORKOUT -->
                <?php if ($schedule && $todayTraining && count($workouts)): ?>
                    <div class="exercise-card mb-4">
                        <div class="section-header">
                            <span class="section-title">Today's Workout</span>
                            <span style="font-size:1.2rem;color:rgba(6,30,41,.4)"><?= htmlspecialchars($schedule['plan_name']) ?></span>
                        </div>
                        <?php foreach ($workouts as $i => $ex): ?>
                            <div class="exercise-row">
                                <div class="ex-num"><?= $i + 1 ?></div>
                                <div class="ex-name"><?= htmlspecialchars($ex['workout_name']) ?></div>
                                <span class="ex-badge"><?= $ex['sets'] ?> sets</span>
                                <span class="ex-badge" style="background:rgba(95,149,152,.1);color:var(--secondary)">
                                    <?= $ex['set_counter'] ?> reps
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ($schedule && !$todayTraining): ?>
                    <!-- Rest day — show next training days -->
                    <div class="section-card mb-4" style="padding:2.4rem;text-align:center;color:rgba(6,30,41,.4)">
                        <i class="fas fa-calendar-check" style="font-size:3rem;display:block;margin-bottom:1rem;color:var(--secondary)"></i>
                        <div style="font-size:1.4rem;font-weight:600;color:var(--dark)">No workout today</div>
                        <div style="font-size:1.2rem;margin-top:.4rem">Check your weekly schedule below for your next training day.</div>
                    </div>

                <?php elseif (!$schedule): ?>
                    <div class="section-card mb-4" style="padding:3rem;text-align:center;color:rgba(6,30,41,.4)">
                        <i class="fas fa-dumbbell" style="font-size:3rem;display:block;margin-bottom:1rem"></i>
                        <div style="font-size:1.4rem;font-weight:600;color:var(--dark)">No workout plan assigned yet</div>
                        <div style="font-size:1.2rem;margin-top:.4rem">Your trainer will set up your schedule soon.</div>
                    </div>
                <?php endif; ?>

                <!-- WEEKLY SCHEDULE CHIPS -->
                <?php if ($schedule): ?>
                    <div class="section-card" style="padding:2.2rem;animation:fadeUp .5s ease .3s both">
                        <div class="section-title mb-3">Weekly Schedule</div>
                        <div class="week-chips">
                            <?php foreach ($dayLabels as $col => $label):
                                $isTrainingDay = !empty($schedule[$col]);
                                $isToday       = $col === $todayColKey;
                                $chipClass     = $isToday && $isTrainingDay ? 'week-chip today-chip'
                                               : ($isTrainingDay            ? 'week-chip active'
                                                                            : 'week-chip');
                            ?>
                                <span class="<?= $chipClass ?>">
                                    <?= $label ?>
                                    <?php if ($isTrainingDay): ?>
                                        <i class="fas fa-dumbbell" style="font-size:.85rem;margin-left:.3rem"></i>
                                    <?php endif; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <div style="margin-top:1.6rem;display:flex;gap:1rem;flex-wrap:wrap">
                            <a href="my-plans.php" style="display:inline-flex;align-items:center;gap:.7rem;padding:1rem 2rem;background:linear-gradient(135deg,var(--primary),var(--secondary));color:#fff;border-radius:1rem;text-decoration:none;font-size:1.3rem;font-weight:600;font-family:'DM Sans',sans-serif;">
                                <i class="fas fa-calendar-days"></i> View Full Monthly Plan
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- RIGHT COLUMN -->
            <div class="col-lg-4 d-flex flex-column gap-4">

                <!-- TRAINER CARD -->
                <?php if (!empty($client['trainer_name'])): ?>
                    <div class="trainer-card">
                        <div style="font-size:1.1rem;color:rgba(95,149,152,.7);text-transform:uppercase;letter-spacing:.1em;font-weight:600;margin-bottom:1.4rem">Your Trainer</div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="trainer-avatar">
                                <?= strtoupper(implode('', array_map(fn($p) => substr($p,0,1), explode(' ', $client['trainer_name'])))) ?>
                            </div>
                            <div>
                                <div class="trainer-name"><?= htmlspecialchars($client['trainer_name']) ?></div>
                                <?php if (!empty($client['specialization'])): ?>
                                    <div class="trainer-spec"><?= htmlspecialchars($client['specialization']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="display:flex;gap:1.2rem;margin-top:1.8rem">
                            <?php if ($client['experience_years'] > 0): ?>
                                <div style="text-align:center">
                                    <div style="font-family:'Barlow Condensed',sans-serif;font-size:2.4rem;font-weight:800;color:#fff;line-height:1"><?= $client['experience_years'] ?></div>
                                    <div style="font-size:1.05rem;color:rgba(95,149,152,.7);text-transform:uppercase;letter-spacing:.06em">Yrs Exp</div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($client['trainer_status'])): ?>
                                <div style="text-align:center">
                                    <div style="margin-top:.3rem">
                                        <span style="display:inline-flex;align-items:center;gap:.4rem;padding:.3rem .9rem;border-radius:10rem;background:<?= $client['trainer_status']==='Active' ? 'rgba(34,197,94,.15)' : 'rgba(239,68,68,.15)' ?>;color:<?= $client['trainer_status']==='Active' ? '#22c55e' : '#ef4444' ?>;font-size:1.1rem;font-weight:600">
                                            <i class="fas fa-circle" style="font-size:.6rem"></i>
                                            <?= htmlspecialchars($client['trainer_status']) ?>
                                        </span>
                                    </div>
                                    <div style="font-size:1.05rem;color:rgba(95,149,152,.7);text-transform:uppercase;letter-spacing:.06em;margin-top:.3rem">Status</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="section-card" style="padding:2.4rem;text-align:center">
                        <div style="font-size:3rem;margin-bottom:1rem">🏋️</div>
                        <div style="font-size:1.3rem;font-weight:600;color:var(--dark)">No trainer assigned yet</div>
                        <div style="font-size:1.2rem;color:rgba(6,30,41,.45);margin-top:.4rem">A trainer will be assigned to you soon.</div>
                    </div>
                <?php endif; ?>

                <!-- PROGRESS CARD -->
                <div class="progress-card">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.6rem">
                        <div class="section-title">My Progress</div>
                        <span style="font-family:'Barlow Condensed',sans-serif;font-size:3rem;font-weight:800;color:var(--primary)"><?= $client['progress'] ?>%</span>
                    </div>
                    <div style="font-size:1.2rem;color:rgba(6,30,41,.45)"><?= htmlspecialchars($client['plan']) ?> Plan</div>
                    <div class="progress-track">
                        <div class="progress-fill" id="progFill" style="width:0%"></div>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-top:.8rem">
                        <span style="font-size:1.1rem;color:rgba(6,30,41,.4)">0%</span>
                        <span style="font-size:1.1rem;color:rgba(6,30,41,.4)">Goal: 100%</span>
                    </div>
                </div>

                <!-- STATUS CARD -->
                <div class="section-card" style="padding:2rem;animation:fadeUp .5s ease .35s both">
                    <div class="section-title mb-3">Account</div>
                    <div style="display:flex;flex-direction:column;gap:1rem">
                        <div style="display:flex;justify-content:space-between;align-items:center">
                            <span style="font-size:1.2rem;color:rgba(6,30,41,.5)">Status</span>
                            <span class="badge-status <?= $client['status']==='Active' ? 'status-active' : ($client['status']==='Inactive' ? 'status-inactive' : 'status-pending') ?>">
                                <?= htmlspecialchars($client['status']) ?>
                            </span>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center">
                            <span style="font-size:1.2rem;color:rgba(6,30,41,.5)">Plan Type</span>
                            <span style="font-size:1.25rem;font-weight:600;color:var(--dark)"><?= htmlspecialchars($client['plan']) ?></span>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center">
                            <span style="font-size:1.2rem;color:rgba(6,30,41,.5)">Sessions Done</span>
                            <span style="font-size:1.25rem;font-weight:700;color:var(--primary)"><?= $client['sessions'] ?></span>
                        </div>
                    </div>
                </div>

            </div><!-- /right column -->
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar
        const sidebar = document.getElementById('sidebar');
        const toggle  = document.getElementById('sidebarToggle');
        const overlay = document.getElementById('sidebarOverlay');
        toggle.onclick = () => { sidebar.classList.toggle('open'); overlay.classList.toggle('open'); };
        overlay.onclick = () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); };

        // Logout
        function handleLogout() {
            if (confirm('Are you sure you want to logout?')) window.location.href = 'logout.php';
        }

        // Date
        document.addEventListener('DOMContentLoaded', () => {
            const el = document.getElementById('current-date');
            if (el) el.textContent = new Date().toLocaleDateString('en-GB', {weekday:'long',day:'2-digit',month:'short',year:'numeric'});
        });

        // Animate progress bar
        window.addEventListener('load', () => {
            setTimeout(() => {
                const fill = document.getElementById('progFill');
                if (fill) fill.style.width = '<?= $client['progress'] ?>%';
            }, 400);
        });
    </script>
</body>
</html>