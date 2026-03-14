<?php
session_start();

require 'middleware/middle.php';
requireAuth();

if (!isClient()) {
    header('Location: dashboard.php');
    exit();
}

$database = require 'bootstrap.php';
$pdo      = $database->pdo;

$userEmail = $_SESSION['user_email'] ?? '';
$userName  = $_SESSION['user_name']  ?? '';

// ── Fetch client record ──────────────────────────────────────────
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
    $client = [
        'id' => 0, 'firstName' => $userName, 'lastName' => '',
        'plan' => 'N/A', 'progress' => 0, 'sessions' => 0, 'status' => 'Active',
        'trainer_name' => null, 'specialization' => null,
        'experience_years' => 0, 'trainer_status' => null,
    ];
}

$clientId = $client['id'];

// ── Fetch ALL schedules for this client ──────────────────────────
$schedules   = [];
$allWorkouts = [];
$daysPerWeek = 0;

if ($clientId) {
    $schedStmt = $pdo->prepare("
        SELECT cs.*, wp.plan_name
        FROM client_schedules cs
        JOIN workout_plans wp ON cs.plan_id = wp.id
        WHERE cs.client_id = ?
        ORDER BY cs.id
    ");
    $schedStmt->execute([$clientId]);
    $schedules = $schedStmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($schedules)) {
        // Collect plan IDs and fetch all workouts
        $planIds = array_unique(array_column($schedules, 'plan_id'));
        $in      = implode(',', array_map('intval', $planIds));
        $allWorkouts = $pdo->query("SELECT * FROM workouts WHERE plan_id IN ({$in}) ORDER BY plan_id, id")->fetchAll(PDO::FETCH_ASSOC);

        // Count unique training days across all schedules
        $dayCols = ['MON','TUE','WED','THU','FRI','SAT','SUN'];
        $trainingDays = [];
        foreach ($schedules as $s) {
            foreach ($dayCols as $col) {
                if (!empty($s[$col])) $trainingDays[$col] = true;
            }
        }
        $daysPerWeek = count($trainingDays);
    }
}

// Group workouts by plan
$workoutsByPlan = [];
foreach ($allWorkouts as $w) {
    $workoutsByPlan[$w['plan_id']][] = $w;
}

// Today info
$phpDayToCol = [1=>'MON',2=>'TUE',3=>'WED',4=>'THU',5=>'FRI',6=>'SAT',7=>'SUN'];
$todayCol    = $phpDayToCol[(int)date('N')];
$todayTraining = false;
foreach ($schedules as $s) {
    if (!empty($s[$todayCol])) { $todayTraining = true; break; }
}

// ── Monthly calendar ────────────────────────────────────────────
$monthParam = $_GET['month'] ?? date('Y-m');
[$yr, $mo]  = explode('-', $monthParam);
$yr = (int)$yr; $mo = (int)$mo;
if ($mo < 1) { $mo = 12; $yr--; }
if ($mo > 12) { $mo = 1;  $yr++; }
$firstDow    = (int)date('N', mktime(0,0,0,$mo,1,$yr)); // 1=Mon…7=Sun
$daysInMonth = (int)date('t', mktime(0,0,0,$mo,1,$yr));
$monthLabel  = date('F Y', mktime(0,0,0,$mo,1,$yr));
$prevMonth   = sprintf('%04d-%02d', $mo===1?$yr-1:$yr, $mo===1?12:$mo-1);
$nextMonth   = sprintf('%04d-%02d', $mo===12?$yr+1:$yr, $mo===12?1:$mo+1);

// Day-of-week → column key
$dowToCol = [1=>'MON',2=>'TUE',3=>'WED',4=>'THU',5=>'FRI',6=>'SAT',7=>'SUN'];

// Build: for each calendar day, which plan names are active?
function getPlansForDate(int $yr, int $mo, int $day, array $schedules, array $dowToCol): array {
    $dow = (int)date('N', mktime(0,0,0,$mo,$day,$yr));
    $col = $dowToCol[$dow];
    $result = [];
    foreach ($schedules as $s) {
        if (!empty($s[$col])) $result[] = $s['plan_name'];
    }
    return $result;
}

$todayStr  = date('Y-m-d');
$dayLabels = ['MON'=>'Mon','TUE'=>'Tue','WED'=>'Wed','THU'=>'Thu','FRI'=>'Fri','SAT'=>'Sat','SUN'=>'Sun'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Plans – GYM Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* ── Trainer banner ──────────────────────────── */
        .trainer-banner {
            background: var(--dark);
            border-radius: var(--card-radius);
            padding: 2.4rem 3rem;
            position: relative;
            overflow: hidden;
            margin-bottom: 2rem;
            animation: fadeUp .5s ease both;
        }
        .trainer-banner::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 70% 80% at 0% 50%, rgba(29,84,109,.85) 0%, transparent 60%),
                radial-gradient(ellipse 50% 60% at 100% 20%, rgba(95,149,152,.3) 0%, transparent 50%);
            pointer-events: none;
        }
        .trainer-banner > * { position: relative; z-index: 1; }
        .tb-avatar {
            width: 6rem; height: 6rem;
            border-radius: 1.6rem;
            background: linear-gradient(135deg, var(--secondary), var(--accent));
            display: flex; align-items: center; justify-content: center;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 2.2rem; font-weight: 800; color: #fff;
            border: 2px solid rgba(255,255,255,.15);
            flex-shrink: 0;
        }
        .tb-name {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 2.6rem; font-weight: 800;
            color: #fff; text-transform: uppercase; line-height: 1;
        }
        .tb-spec { font-size: 1.2rem; color: rgba(95,149,152,.85); margin-top: .3rem; }
        .tb-stat { text-align: center; }
        .tb-stat-val {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 2.8rem; font-weight: 800; color: #fff; line-height: 1;
        }
        .tb-stat-lbl { font-size: 1.05rem; color: rgba(95,149,152,.7); text-transform: uppercase; letter-spacing: .06em; }

        /* ── Stats row ─────────────────────────────── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.6rem;
            margin-bottom: 2rem;
        }
        .stat-mini {
            background: #fff;
            border-radius: var(--card-radius);
            padding: 1.8rem 2rem;
            border: 1px solid rgba(29,84,109,.07);
            box-shadow: 0 2px 12px rgba(6,30,41,.06);
            animation: fadeUp .5s ease .1s both;
        }
        .stat-mini-val {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 3.4rem; font-weight: 800;
            color: var(--primary); line-height: 1;
        }
        .stat-mini-lbl { font-size: 1.1rem; color: rgba(6,30,41,.45); font-weight: 500; margin-top: .3rem; }

        /* ── Plan block ────────────────────────────── */
        .plan-block {
            background: #fff;
            border-radius: var(--card-radius);
            border: 1px solid rgba(29,84,109,.08);
            box-shadow: 0 2px 12px rgba(6,30,41,.06);
            overflow: hidden;
            margin-bottom: 2rem;
            animation: fadeUp .5s ease .2s both;
        }
        .plan-block-head {
            padding: 1.8rem 2.4rem;
            border-bottom: 1px solid rgba(29,84,109,.07);
            display: flex; align-items: center; gap: 1.4rem;
        }
        .plan-block-icon {
            width: 4.8rem; height: 4.8rem;
            border-radius: 1.2rem;
            background: rgba(29,84,109,.1);
            color: var(--primary);
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; flex-shrink: 0;
        }
        .plan-block-name {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 2rem; font-weight: 800;
            color: var(--dark); text-transform: uppercase;
        }
        .plan-block-meta { font-size: 1.15rem; color: rgba(6,30,41,.45); margin-top: .2rem; }

        /* Exercise row inside plan */
        .ex-row {
            display: flex; align-items: center; gap: 1.4rem;
            padding: 1.3rem 2.4rem;
            border-bottom: 1px solid rgba(29,84,109,.04);
        }
        .ex-row:last-child { border-bottom: none; }
        .ex-row:hover { background: rgba(29,84,109,.025); }
        .ex-num {
            width: 3rem; height: 3rem; border-radius: .8rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff; display: flex; align-items: center; justify-content: center;
            font-family: 'Barlow Condensed', sans-serif; font-size: 1.4rem; font-weight: 700; flex-shrink: 0;
        }
        .ex-name { font-size: 1.3rem; font-weight: 600; color: var(--dark); flex: 1; }
        .ex-badge {
            padding: .25rem .8rem; border-radius: .5rem;
            background: rgba(29,84,109,.08); color: var(--primary);
            font-size: 1.05rem; font-weight: 700;
        }
        .ex-badge.reps { background: rgba(95,149,152,.1); color: var(--secondary); }

        /* Week chips */
        .week-chips { display: flex; gap: .6rem; flex-wrap: wrap; }
        .week-chip {
            padding: .4rem 1.1rem; border-radius: 10rem;
            font-size: 1.15rem; font-weight: 600;
            border: 1.5px solid rgba(29,84,109,.15); color: rgba(6,30,41,.4);
        }
        .week-chip.active   { background: var(--primary); border-color: var(--primary); color: #fff; }
        .week-chip.today-chip { background: #22c55e; border-color: #22c55e; color: #fff; }

        /* Calendar */
        .cal-grid {
            display: grid;
            grid-template-columns: repeat(7,1fr);
            border-left: 1px solid rgba(29,84,109,.07);
            border-top: 1px solid rgba(29,84,109,.07);
        }
        .cal-day-name {
            text-align: center; padding: 1rem 0;
            font-size: 1.05rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .07em; color: rgba(6,30,41,.38);
            border-right: 1px solid rgba(29,84,109,.07);
            border-bottom: 1px solid rgba(29,84,109,.07);
            background: rgba(29,84,109,.02);
        }
        .cal-cell {
            min-height: 9rem; padding: .7rem;
            border-right: 1px solid rgba(29,84,109,.07);
            border-bottom: 1px solid rgba(29,84,109,.07);
            position: relative; transition: background .15s;
        }
        .cal-cell:hover { background: rgba(29,84,109,.02); }
        .cal-cell.other-month { background: rgba(29,84,109,.018); }
        .cal-date {
            font-size: 1.2rem; font-weight: 700; color: rgba(6,30,41,.5);
            width: 2.6rem; height: 2.6rem; display: flex; align-items: center;
            justify-content: center; margin-bottom: .4rem;
        }
        .cal-cell.today .cal-date {
            background: var(--primary); color: #fff; border-radius: 50%;
        }
        .cal-cell.other-month .cal-date { color: rgba(6,30,41,.2); }
        .cal-event {
            padding: .25rem .55rem; border-radius: .4rem;
            font-size: 1rem; font-weight: 600; margin-bottom: .25rem;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            background: rgba(29,84,109,.1); color: var(--primary);
        }

        /* Today training status */
        .today-status-card {
            border-radius: var(--card-radius); padding: 1.8rem 2.4rem;
            border: 1.5px solid; margin-bottom: 2rem;
            animation: fadeUp .5s ease .15s both;
        }
        .today-status-card.training { background: rgba(29,84,109,.06); border-color: rgba(29,84,109,.2); }
        .today-status-card.rest     { background: rgba(95,149,152,.05); border-color: rgba(95,149,152,.18); }

        .client-role-badge {
            font-size: 1.1rem; font-weight: 700; padding: .4rem 1rem;
            border-radius: 10rem; background: rgba(34,197,94,.1); color: #16a34a;
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
                <div class="topbar-title">My Plans</div>
                <div class="topbar-subtitle"><?= $monthLabel ?></div>
            </div>
        </div>
        <div class="topbar-actions">
            <button class="topbar-btn"><i class="fas fa-bell"></i></button>
            <div class="topbar-user">
                <div class="user-avatar-sm"><?= strtoupper(substr($userName,0,2)) ?></div>
                <span class="user-name-sm"><?= htmlspecialchars($userName) ?></span>
                <i class="fas fa-chevron-down" style="font-size:1rem;color:rgba(6,30,41,0.4);margin-left:.4rem"></i>
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

        <!-- TRAINER BANNER -->
        <?php if (!empty($client['trainer_name'])): ?>
            <div class="trainer-banner">
                <div class="d-flex align-items-center gap-4 flex-wrap">
                    <div class="tb-avatar">
                        <?= strtoupper(implode('', array_map(fn($p) => substr($p,0,1), array_slice(explode(' ', $client['trainer_name']), 0, 2)))) ?>
                    </div>
                    <div>
                        <div style="font-size:1.1rem;color:rgba(95,149,152,.7);text-transform:uppercase;letter-spacing:.1em;font-weight:600;margin-bottom:.3rem">Your Trainer</div>
                        <div class="tb-name"><?= htmlspecialchars($client['trainer_name']) ?></div>
                        <?php if (!empty($client['specialization'])): ?>
                            <div class="tb-spec"><?= htmlspecialchars($client['specialization']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div style="margin-left:auto;display:flex;gap:3rem;flex-wrap:wrap">
                        <?php if ($client['experience_years'] > 0): ?>
                            <div class="tb-stat">
                                <div class="tb-stat-val"><?= $client['experience_years'] ?></div>
                                <div class="tb-stat-lbl">Yrs Exp</div>
                            </div>
                        <?php endif; ?>
                        <div class="tb-stat">
                            <div class="tb-stat-val"><?= $daysPerWeek ?></div>
                            <div class="tb-stat-lbl">Days/Week</div>
                        </div>
                        <div class="tb-stat">
                            <div class="tb-stat-val"><?= count($allWorkouts) ?></div>
                            <div class="tb-stat-lbl">Exercises</div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- 3-STAT ROW -->
        <div class="stats-row">
            <div class="stat-mini">
                <div class="stat-mini-val"><?= $client['progress'] ?>%</div>
                <div class="stat-mini-lbl">Overall Progress</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-val"><?= count($schedules) ?></div>
                <div class="stat-mini-lbl">Plan<?= count($schedules)!==1?'s':'' ?></div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-val"><?= $daysPerWeek ?></div>
                <div class="stat-mini-lbl">Training Days/Week</div>
            </div>
        </div>

        <!-- TODAY TRAINING STATUS -->
        <div class="today-status-card <?= $todayTraining ? 'training' : 'rest' ?>">
            <div class="d-flex align-items-center gap-3 justify-content-between flex-wrap">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:4.2rem;height:4.2rem;border-radius:50%;background:<?= $todayTraining ? 'rgba(29,84,109,.12)' : 'rgba(95,149,152,.1)' ?>;display:flex;align-items:center;justify-content:center;font-size:1.9rem;color:<?= $todayTraining ? 'var(--primary)' : 'var(--secondary)' ?>">
                        <i class="fas fa-<?= $todayTraining ? 'fire-flame-curved' : 'moon' ?>"></i>
                    </div>
                    <div>
                        <div style="font-family:'Barlow Condensed',sans-serif;font-size:1.6rem;font-weight:700;color:<?= $todayTraining ? 'var(--primary)' : 'var(--secondary)' ?>;text-transform:uppercase;letter-spacing:.05em">
                            Today is a <?= $todayTraining ? 'Training' : 'Rest' ?> Day
                        </div>
                        <div style="font-size:1.2rem;color:rgba(6,30,41,.45)"><?= date('l, d F Y') ?></div>
                    </div>
                </div>
                <a href="client-dashboard.php" style="display:inline-flex;align-items:center;gap:.6rem;padding:.8rem 1.8rem;background:<?= $todayTraining ? 'linear-gradient(135deg,var(--primary),var(--secondary))' : 'rgba(95,149,152,.12)' ?>;color:<?= $todayTraining ? '#fff' : 'var(--secondary)' ?>;border-radius:1rem;text-decoration:none;font-size:1.2rem;font-weight:600;font-family:'DM Sans',sans-serif;">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <!-- WEEKLY SCHEDULE CHIPS -->
        <?php if (!empty($schedules)): ?>
            <div class="section-card mb-4" style="padding:2rem 2.4rem;animation:fadeUp .5s ease .1s both">
                <div class="section-title mb-3">Weekly Schedule</div>
                <div class="week-chips">
                    <?php foreach ($dayLabels as $col => $label):
                        // Check across all schedules if this day is training
                        $isTrainingDay = false;
                        foreach ($schedules as $s) { if (!empty($s[$col])) { $isTrainingDay = true; break; } }
                        $isToday  = $col === $todayCol;
                        $chipCls  = $isToday && $isTrainingDay ? 'week-chip today-chip'
                                  : ($isTrainingDay            ? 'week-chip active' : 'week-chip');
                    ?>
                        <span class="<?= $chipCls ?>">
                            <?= $label ?><?= $isTrainingDay ? ' <i class="fas fa-dumbbell" style="font-size:.8rem;margin-left:.2rem"></i>' : '' ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- PER-PLAN BLOCKS -->
        <?php if (empty($schedules)): ?>
            <div class="section-card mb-4" style="padding:4rem;text-align:center;color:rgba(6,30,41,.35)">
                <i class="fas fa-dumbbell" style="font-size:4rem;display:block;margin-bottom:1.2rem"></i>
                <div style="font-size:1.5rem;font-weight:600;color:var(--dark)">No workout plans assigned yet</div>
                <div style="font-size:1.3rem;margin-top:.5rem">Your trainer will assign a plan soon.</div>
            </div>
        <?php else:
            $planIcons = ['🤸','💪','🏃','🧘','⚡','🔥','🏋️','🎯'];
            foreach ($schedules as $si => $sched):
                $planWorkouts = $workoutsByPlan[$sched['plan_id']] ?? [];
                // Which days is this plan scheduled?
                $planDays = [];
                foreach ($dayLabels as $col => $lbl) {
                    if (!empty($sched[$col])) $planDays[] = $lbl;
                }
        ?>
            <div class="plan-block">
                <div class="plan-block-head">
                    <div class="plan-block-icon"><?= $planIcons[$si % count($planIcons)] ?></div>
                    <div>
                        <div class="plan-block-name"><?= htmlspecialchars($sched['plan_name']) ?></div>
                        <div class="plan-block-meta">
                            <?= count($planWorkouts) ?> exercise<?= count($planWorkouts)!==1?'s':'' ?>
                            &nbsp;·&nbsp;
                            Training on: <?= implode(', ', $planDays) ?: 'No days set' ?>
                        </div>
                    </div>
                    <!-- Day chips right side -->
                    <div class="ms-auto d-flex gap-2 flex-wrap">
                        <?php foreach ($planDays as $dLabel): ?>
                            <span style="padding:.3rem .9rem;border-radius:.6rem;background:rgba(29,84,109,.08);color:var(--primary);font-size:1.1rem;font-weight:600"><?= $dLabel ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php if (empty($planWorkouts)): ?>
                    <div style="padding:2rem 2.4rem;color:rgba(6,30,41,.35);font-size:1.2rem">No exercises added to this plan yet.</div>
                <?php else: ?>
                    <?php foreach ($planWorkouts as $ei => $ex): ?>
                        <div class="ex-row">
                            <div class="ex-num"><?= $ei + 1 ?></div>
                            <div class="ex-name"><?= htmlspecialchars($ex['workout_name']) ?></div>
                            <span class="ex-badge"><?= $ex['sets'] ?> sets</span>
                            <span class="ex-badge reps"><?= $ex['set_counter'] ?> reps</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; endif; ?>

        <!-- MONTHLY CALENDAR -->
        <div class="section-card mb-4" style="animation:fadeUp .5s ease .3s both">
            <div class="section-header">
                <div style="display:flex;align-items:center;gap:1rem">
                    <a href="?month=<?= $prevMonth ?>" style="width:3.4rem;height:3.4rem;border-radius:.8rem;border:1.5px solid rgba(29,84,109,.15);display:flex;align-items:center;justify-content:center;color:var(--primary);text-decoration:none;font-size:1.4rem;">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <span style="font-family:'Barlow Condensed',sans-serif;font-size:2rem;font-weight:700;color:var(--dark);min-width:16rem;text-align:center"><?= $monthLabel ?></span>
                    <a href="?month=<?= $nextMonth ?>" style="width:3.4rem;height:3.4rem;border-radius:.8rem;border:1.5px solid rgba(29,84,109,.15);display:flex;align-items:center;justify-content:center;color:var(--primary);text-decoration:none;font-size:1.4rem;">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <a href="?month=<?= date('Y-m') ?>" style="padding:.5rem 1.2rem;border-radius:.8rem;border:1.5px solid rgba(29,84,109,.2);color:var(--primary);font-size:1.2rem;font-weight:600;text-decoration:none">Today</a>
                </div>
                <div style="display:flex;align-items:center;gap:1rem">
                    <span style="font-size:1.2rem;color:rgba(6,30,41,.45)">
                        <span style="width:.9rem;height:.9rem;background:var(--primary);border-radius:50%;display:inline-block;margin-right:.4rem"></span>Training Day
                    </span>
                    <span style="font-size:1.2rem;color:rgba(6,30,41,.45)">
                        <span style="width:.9rem;height:.9rem;background:#22c55e;border-radius:50%;display:inline-block;margin-right:.4rem"></span>Today
                    </span>
                </div>
            </div>
            <div class="cal-grid">
                <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $dn): ?>
                    <div class="cal-day-name"><?= $dn ?></div>
                <?php endforeach;

                // Leading blanks
                $prevDim = (int)date('t', mktime(0,0,0,$mo-1<1?12:$mo-1,1,$mo-1<1?$yr-1:$yr));
                for ($b = 1; $b < $firstDow; $b++):
                    $pDay = $prevDim - ($firstDow - $b - 1);
                ?>
                    <div class="cal-cell other-month"><div class="cal-date"><?= $pDay ?></div></div>
                <?php endfor;

                // Actual days
                for ($d = 1; $d <= $daysInMonth; $d++):
                    $dateStr   = sprintf('%04d-%02d-%02d', $yr, $mo, $d);
                    $isToday   = $dateStr === $todayStr;
                    $planNames = getPlansForDate($yr, $mo, $d, $schedules, $dowToCol);
                ?>
                    <div class="cal-cell<?= $isToday ? ' today' : '' ?>">
                        <div class="cal-date"><?= $d ?></div>
                        <?php foreach (array_slice($planNames, 0, 2) as $pn): ?>
                            <div class="cal-event" style="<?= $isToday ? 'background:rgba(34,197,94,.15);color:#16a34a' : '' ?>">
                                <?= htmlspecialchars($pn) ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if (count($planNames) > 2): ?>
                            <div style="font-size:1rem;color:rgba(6,30,41,.35);padding:.15rem .4rem">+<?= count($planNames)-2 ?> more</div>
                        <?php endif; ?>
                    </div>
                <?php endfor;

                // Trailing blanks
                $total    = $firstDow - 1 + $daysInMonth;
                $trailing = (7 - ($total % 7)) % 7;
                for ($t = 1; $t <= $trailing; $t++): ?>
                    <div class="cal-cell other-month"><div class="cal-date"><?= $t ?></div></div>
                <?php endfor; ?>
            </div>
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const sidebar = document.getElementById('sidebar');
        const toggle  = document.getElementById('sidebarToggle');
        const overlay = document.getElementById('sidebarOverlay');
        toggle.onclick  = () => { sidebar.classList.toggle('open');  overlay.classList.toggle('open'); };
        overlay.onclick = () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); };

        function handleLogout() {
            if (confirm('Are you sure you want to logout?')) window.location.href = 'logout.php';
        }
    </script>
</body>
</html>