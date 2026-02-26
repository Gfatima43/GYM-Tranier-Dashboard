<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];

$logged_in = isset($userId);
$currentPage = 'dashboard'; // sidebar tab is active

if ($role === 'admin') {
    $stmt = $pdo->query("SELECT COUNT(*) FROM clients");
} else {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE trainer_id = ?");
    $stmt->execute([$userId]);
}

$totalClients = $stmt->fetchColumn();

if ($role === 'admin') {
    $clients = $pdo->query("
        SELECT clients.*, users.name AS trainer_name
        FROM clients
        LEFT JOIN users ON clients.trainer_id = users.id
    ");
} else {
    $clients = $pdo->prepare("
        SELECT * FROM clients WHERE trainer_id = ?
    ");
    $clients->execute([$userId]);
}
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
            <div class="stat-item">
                <div class="stat-value">8</div>
                <div class="stat-label">Total Clients</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">6</div>
                <div class="stat-label">Today's Sessions</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">89%</div>
                <div class="stat-label">Attendance Rate</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">12</div>
                <div class="stat-label">Active Plans</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">4.9</div>
                <div class="stat-label">Avg Rating</div>
            </div>
        </div>

        <!-- Overview Cards -->
        <div class="kpi-grid content-section">
            <div class="kpi-card c1">
                <div class="kpi-icon-wrap"><i class="fas fa-users"></i></div>
                <i class="kpi-card-bg-icon fas fa-users"></i>
                <div class="kpi-label">Total Clients</div>
                <div class="kpi-value">8</div>
                <div class="kpi-trend up">
                    <i class="fas fa-arrow-trend-up"></i> +3 this month
                </div>
            </div>
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
                    <div class="client-row">
                        <div class="client-av" style="background:linear-gradient(135deg,#1D546D,#5F9598)">SA</div>
                        <div class="client-info">
                            <div class="client-name">Sara Ahmed</div>
                            <div class="client-plan">Weight Loss · 8 weeks</div>
                        </div>
                        <div class="progress-wrap">
                            <div class="progress-label">78%</div>
                            <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:78%"></div></div>
                        </div>
                        <span class="client-status status-active">Active</span>
                    </div>
                    <div class="client-row">
                        <div class="client-av" style="background:linear-gradient(135deg,#22c55e,#16a34a)">MK</div>
                        <div class="client-info">
                            <div class="client-name">Mike Khan</div>
                            <div class="client-plan">Muscle Gain · 12 weeks</div>
                        </div>
                        <div class="progress-wrap">
                            <div class="progress-label">55%</div>
                            <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:55%"></div></div>
                        </div>
                        <span class="client-status status-active">Active</span>
                    </div>
                    <div class="client-row">
                        <div class="client-av" style="background:linear-gradient(135deg,#f59e0b,#d97706)">LR</div>
                        <div class="client-info">
                            <div class="client-name">Layla Rahman</div>
                            <div class="client-plan">Cardio Endurance · 6 weeks</div>
                        </div>
                        <div class="progress-wrap">
                            <div class="progress-label">91%</div>
                            <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:91%"></div></div>
                        </div>
                        <span class="client-status status-active">Active</span>
                    </div>
                    <div class="client-row">
                        <div class="client-av" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed)">AR</div>
                        <div class="client-info">
                            <div class="client-name">Ali Raza</div>
                            <div class="client-plan">Strength & Power · 10 weeks</div>
                        </div>
                        <div class="progress-wrap">
                            <div class="progress-label">34%</div>
                            <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:34%"></div></div>
                        </div>
                        <span class="client-status status-pending">Pending</span>
                    </div>
                    <div class="client-row">
                        <div class="client-av" style="background:linear-gradient(135deg,#ef4444,#dc2626)">ZN</div>
                        <div class="client-info">
                            <div class="client-name">Zara Noor</div>
                            <div class="client-plan">Flexibility · 4 weeks</div>
                        </div>
                        <div class="progress-wrap">
                            <div class="progress-label">0%</div>
                            <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:0%"></div></div>
                        </div>
                        <span class="client-status status-inactive">Inactive</span>
                    </div>
                </div>
            </div>

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
