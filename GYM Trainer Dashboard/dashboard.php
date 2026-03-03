<?php
session_start();
require 'middleware/middle.php';
requireAuth();

$database = require 'bootstrap.php';
$pdo = $database->pdo;

$userId = $_SESSION['user_id'];
// $role = $_SESSION['role'] ?? '';
// $isAdmin = false;
$isAdmin = isAdmin();
$isTrainer = isTrainer();

$logged_in = isset($userId);
$currentPage = 'dashboard'; // sidebar tab is active

// ── Role-based statistics ──
if ($isAdmin) {
    $totalClients   = (int)$pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
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
    if ($isTrainer) {
        $stmt = $pdo->prepare("SELECT id FROM trainers WHERE user_id = ?");
        $stmt->execute([$userId]);
        $trainerId = $stmt->fetchColumn();
    }

    if ($isTrainer) {
        $stmt = $pdo->prepare("
        SELECT c.*
        FROM trainer_clients tc
        JOIN clients c ON tc.client_id = c.id
        WHERE tc.trainer_id = ?
        AND tc.status = 'active'");
        $stmt->execute([$trainerId]);
        $assignedClients = $stmt->fetchAll();
    }
    
    // Trainer sees only their own data
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE trainer_id = ?");
    $stmt->execute([$trainerId]);
    $totalClients  = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE trainer_id = ? AND status='Active'");
    $stmt->execute([$trainerId]);
    $activeClients = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM trainer_clients WHERE trainer_id = ? AND status='active'");
    $stmt->execute([$trainerId]);
    $totalAssignedClients = (int)$stmt->fetchColumn();

    // $stmt = $pdo->prepare("SELECT COUNT(*) FROM trainer_clients WHERE trainer_id = ? AND status='active'");
    // $stmt->execute([$userId]);
    // $totalSessions = (int)$stmt->fetchColumn();

    $totalTrainers = 0; // not relevant for trainer view

    // Recent clients assigned to this trainer
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE trainer_id = ? ORDER BY id DESC LIMIT 5");
    $stmt->execute([$trainerId]);
    $recentClients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $trainerStats  = [];
}

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
                <div class="stat-value"><?= $totalAssignedClients ?></div>
                <div class="stat-label">Today's Sessions</div>
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
                <div class="stat-value">4.9</div>
                <div class="stat-label">Avg Rating</div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Overview Cards -->
        <div class="kpi-grid content-section">
            <div class="kpi-card c1">
                <div class="kpi-icon-wrap"><i class="fas fa-users"></i></div>
                <i class="kpi-card-bg-icon fas fa-users"></i>
                <div class="kpi-label"><?= $isAdmin ? 'Total Clients' : 'My Clients' ?></div>
                <div class="kpi-value"><?= $totalClients ?></div>
                <div class="kpi-trend up">
                    <i class="fas fa-arrow-trend-up"></i> +3 this month
                </div>
            </div>
            <?php if ($isAdmin): ?>
            <div class="kpi-card c2">
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
            </div>
            <?php else: ?>
            <div class="kpi-card c2">
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
            </div>
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
                    <div class="trainer-card" style="margin:.4rem 1.6rem;border-radius:1rem">
                        <div class="client-av" style="background:<?= $tc ?>;width:4.2rem;height:4.2rem"><?= $ti ?></div>
                        <div>
                            <div style="font-weight:600;font-size:1.35rem"><?= htmlspecialchars($trainer['name']) ?></div>
                            <div style="font-size:1.15rem;color:rgba(6,30,41,.45)">Trainer</div>
                        </div>
                        <div class="trainer-count"><?= $trainer['client_count'] ?></div>
                        <div style="font-size:1.1rem;color:rgba(6,30,41,.4)">clients</div>
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
                </div>
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
                    <!-- Mon -->
                    <div>
                        <div class="day-col-header">
                            <div class="day-name">Mon</div>
                            <div class="day-num">17</div>
                        </div>
                        <div class="session-slot slot-blue">
                            <div class="s-time">08:00</div>
                            <div class="s-name">Sara A.</div>
                        </div>
                        <div class="session-slot slot-teal">
                            <div class="s-time">14:00</div>
                            <div class="s-name">Group HIIT</div>
                        </div>
                    </div>
                    <!-- Tue -->
                    <div>
                        <div class="day-col-header">
                            <div class="day-name">Tue</div>
                            <div class="day-num">18</div>
                        </div>
                        <div class="session-slot slot-green">
                            <div class="s-time">09:00</div>
                            <div class="s-name">Mike K.</div>
                        </div>
                        <div class="session-slot slot-amber">
                            <div class="s-time">16:00</div>
                            <div class="s-name">Layla R.</div>
                        </div>
                    </div>
                    <!-- Wed -->
                    <div>
                        <div class="day-col-header">
                            <div class="day-name">Wed</div>
                            <div class="day-num">19</div>
                        </div>
                        <div class="session-slot slot-blue">
                            <div class="s-time">08:00</div>
                            <div class="s-name">Sara A.</div>
                        </div>
                        <div class="session-slot slot-amber">
                            <div class="s-time">10:00</div>
                            <div class="s-name">Mike K.</div>
                        </div>
                        <div class="session-slot slot-teal">
                            <div class="s-time">12:00</div>
                            <div class="s-name">Layla R.</div>
                        </div>
                        <div class="session-slot slot-purple">
                            <div class="s-time">14:00</div>
                            <div class="s-name">Ali R.</div>
                        </div>
                        <div class="session-slot slot-green">
                            <div class="s-time">17:00</div>
                            <div class="s-name">Group HIIT</div>
                        </div>
                    </div>
                    <!-- Thu (TODAY) -->
                    <div>
                        <div class="day-col-header">
                            <div class="day-name">Thu</div>
                            <div class="day-num today">19</div>
                        </div>
                        <div class="session-slot slot-blue">
                            <div class="s-time">08:00</div>
                            <div class="s-name">Sara A.</div>
                        </div>
                        <div class="session-slot slot-amber">
                            <div class="s-time">10:00</div>
                            <div class="s-name">Mike K.</div>
                        </div>
                        <div class="session-slot slot-teal">
                            <div class="s-time">12:00</div>
                            <div class="s-name">Layla R.</div>
                        </div>
                        <div class="session-slot slot-purple">
                            <div class="s-time">14:00</div>
                            <div class="s-name">Ali R.</div>
                        </div>
                        <div class="session-slot slot-green">
                            <div class="s-time">17:00</div>
                            <div class="s-name">Group HIIT</div>
                        </div>
                    </div>
                    <!-- Fri -->
                    <div>
                        <div class="day-col-header">
                            <div class="day-name">Fri</div>
                            <div class="day-num">20</div>
                        </div>
                        <div class="session-slot slot-green">
                            <div class="s-time">09:00</div>
                            <div class="s-name">Mike K.</div>
                        </div>
                        <div class="session-slot slot-purple">
                            <div class="s-time">15:00</div>
                            <div class="s-name">Ali R.</div>
                        </div>
                    </div>
                    <!-- Sat -->
                    <div>
                        <div class="day-col-header">
                            <div class="day-name">Sat</div>
                            <div class="day-num">21</div>
                        </div>
                        <div class="session-slot slot-teal">
                            <div class="s-time">10:00</div>
                            <div class="s-name">Group Yoga</div>
                        </div>
                    </div>
                    <!-- Sun -->
                    <div>
                        <div class="day-col-header">
                            <div class="day-name">Sun</div>
                            <div class="day-num">22</div>
                        </div>
                        <div class="slot-empty">
                            <i class="fas fa-moon"></i><br>Rest Day
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Grid: Activity + Tasks -->
        <div class="bottom-grid content-section">
            <!-- Recent Activity -->
            <div class="section-card" style="animation-delay:0.35s">
                <div class="section-header">
                    <span class="section-title">Recent Activity</span>
                    <a href="#" class="section-action">Clear All</a>
                </div>
                <div class="section-body">
                    <div class="activity-item">
                        <div class="activity-icon ai-green"><i class="fas fa-check-circle"></i></div>
                        <div class="activity-text">
                            <div class="activity-desc"><strong>Sara Ahmed</strong> completed today's session</div>
                            <div class="activity-time">2 minutes ago</div>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon ai-blue"><i class="fas fa-file-plus"></i></div>
                        <div class="activity-text">
                            <div class="activity-desc">New workout plan assigned to <strong>Ali Raza</strong></div>
                            <div class="activity-time">38 minutes ago</div>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon ai-amber"><i class="fas fa-message"></i></div>
                        <div class="activity-text">
                            <div class="activity-desc"><strong>Mike Khan</strong> sent you a message</div>
                            <div class="activity-time">1 hour ago</div>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon ai-red"><i class="fas fa-triangle-exclamation"></i></div>
                        <div class="activity-text">
                            <div class="activity-desc"><strong>Zara Noor</strong> missed her session</div>
                            <div class="activity-time">2 hours ago</div>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon ai-purple"><i class="fas fa-user-plus"></i></div>
                        <div class="activity-text">
                            <div class="activity-desc">New client <strong>Omar Farooq</strong> registered</div>
                            <div class="activity-time">Yesterday, 3:42 PM</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Tasks -->
            <div class="section-card" style="animation-delay:0.4s">
                <div class="section-header">
                    <span class="section-title">Pending Tasks</span>
                    <a href="#" class="section-action">+ Add Task</a>
                </div>
                <div class="section-body" id="taskList">
                    <div class="task-item" onclick="toggleTask(this)">
                        <div class="task-check done"><i class="fas fa-check"></i></div>
                        <div class="task-text done-text">Update Sara's weekly plan</div>
                        <span class="task-priority prio-high">High</span>
                    </div>
                    <div class="task-item" onclick="toggleTask(this)">
                        <div class="task-check"></div>
                        <div class="task-text">Review Mike's progress photos</div>
                        <span class="task-priority prio-medium">Medium</span>
                    </div>
                    <div class="task-item" onclick="toggleTask(this)">
                        <div class="task-check"></div>
                        <div class="task-text">Send meal plan to Layla</div>
                        <span class="task-priority prio-high">High</span>
                    </div>
                    <div class="task-item" onclick="toggleTask(this)">
                        <div class="task-check"></div>
                        <div class="task-text">Prepare HIIT session playlist</div>
                        <span class="task-priority prio-low">Low</span>
                    </div>
                    <div class="task-item" onclick="toggleTask(this)">
                        <div class="task-check"></div>
                        <div class="task-text">Schedule consultation with Zara</div>
                        <span class="task-priority prio-medium">Medium</span>
                    </div>
                    <div class="task-item" onclick="toggleTask(this)" style="border:none">
                        <div class="task-check"></div>
                        <div class="task-text">Monthly report submission</div>
                        <span class="task-priority prio-high">High</span>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <!-- <script>
        // ── Sidebar Toggle (mobile) ──
        const sidebar        = document.getElementById('sidebar');
        const sidebarToggle  = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        function openSidebar()  { sidebar.classList.add('open'); sidebarOverlay.classList.add('open'); }
        function closeSidebar() { sidebar.classList.remove('open'); sidebarOverlay.classList.remove('open'); }

        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
        });
        sidebarOverlay.addEventListener('click', closeSidebar);

        // ── Active Nav Link (SPA feel) ──
        document.querySelectorAll('.nav-item-link').forEach(link => {
            link.addEventListener('click', function () {
                document.querySelectorAll('.nav-item-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                // Update topbar title
                const labels = {
                    dashboard:  'Dashboard',
                    overview:   'Overview',
                    clients:    'My Clients',
                    workouts:   'Workout Plans',
                    schedule:   'Schedule',
                    attendance: 'Attendance',
                    messages:   'Messages',
                    profile:    'Profile',
                };
                const page = this.dataset.page;
                if (page && labels[page]) {
                    document.querySelector('.topbar-title').textContent = labels[page];
                }
                if (window.innerWidth < 992) closeSidebar();
            });
        });

        // ── Task Toggle ──
        function toggleTask(row) {
            const check = row.querySelector('.task-check');
            const text  = row.querySelector('.task-text');
            check.classList.toggle('done');
            text.classList.toggle('done-text');
            if (check.classList.contains('done')) {
                check.innerHTML = '<i class="fas fa-check"></i>';
            } else {
                check.innerHTML = '';
            }
        }

        // ── Logout ──
        function handleLogout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'login.php';
            }
        }

        // ── Animate progress bars on load ──
        window.addEventListener('load', () => {
            document.querySelectorAll('.progress-bar-fill').forEach(bar => {
                const target = bar.style.width;
                bar.style.width = '0%';
                requestAnimationFrame(() => {
                    setTimeout(() => { bar.style.width = target; }, 200);
                });
            });
        });

        // ── Counter animation ──
        function animateCount(el, target, duration = 1200) {
            let start = 0;
            const step = target / (duration / 16);
            const timer = setInterval(() => {
                start += step;
                if (start >= target) { el.textContent = el.dataset.suffix ? target + el.dataset.suffix : target; clearInterval(timer); return; }
                el.textContent = Math.floor(start) + (el.dataset.suffix || '');
            }, 16);
        }

        // ── Search filter ──
        document.querySelector('.topbar-search input').addEventListener('input', function () {
            const query = this.value.toLowerCase();
            document.querySelectorAll('.client-row').forEach(row => {
                const name = row.querySelector('.client-name');
                if (name) {
                    row.style.display = name.textContent.toLowerCase().includes(query) ? '' : 'none';
                }
            });
        });
    </script> -->
</body>
</html>
