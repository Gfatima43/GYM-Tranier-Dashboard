<?php
session_start();

$database = require 'bootstrap.php';
$pdo = $database->pdo;

$logged_in = isset($_SESSION['user_id']);
$user_name  = $_SESSION['user_name'] ?? '';
$user_role  = $_SESSION['role']      ?? '';
// $currentPage = 'schedule';
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
</head>

<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <?php include 'assets/sidebar.php'; ?>
    <header class="topbar">
        <div class="topbar-left">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div>
                <div class="topbar-title">Schedule</div>
                <div class="topbar-subtitle">February 2026</div>
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
        <div class="row g-4">
            <div class="d-flex">
                <button class="btn-add-session" onclick="alert('Schedule new session!')"><i class="fas fa-plus"></i>New Session</button>
            </div>
            <div class="col-lg-9">
                <div class="section-card">
                    <div class="section-header">
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
                    </div>
                    <div class="cal-grid">
                        <div class="cal-day-name">Sun</div>
                        <div class="cal-day-name">Mon</div>
                        <div class="cal-day-name">Tue</div>
                        <div class="cal-day-name">Wed</div>
                        <div class="cal-day-name">Thu</div>
                        <div class="cal-day-name">Fri</div>
                        <div class="cal-day-name">Sat</div>
                        <!-- Row 1 -->
                        <div class="cal-cell other-month">
                            <div class="cal-date">26</div>
                        </div>
                        <div class="cal-cell other-month">
                            <div class="cal-date">27</div>
                        </div>
                        <div class="cal-cell other-month">
                            <div class="cal-date">28</div>
                        </div>
                        <div class="cal-cell other-month">
                            <div class="cal-date">29</div>
                        </div>
                        <div class="cal-cell other-month">
                            <div class="cal-date">30</div>
                        </div>
                        <div class="cal-cell other-month">
                            <div class="cal-date">31</div>
                        </div>
                        <div class="cal-cell">
                            <div class="cal-date">1</div>
                        </div>
                        <!-- Row 2 -->
                        <div class="cal-cell">
                            <div class="cal-date">2</div>
                        </div>
                        <div class="cal-cell">
                            <div class="cal-date">3</div>
                            <div class="cal-event ev-blue">Sara A.</div>
                        </div>
                        <div class="cal-cell">
                            <div class="cal-date">4</div>
                            <div class="cal-event ev-green">Mike K.</div>
                        </div>
                        <div class="cal-cell">
                            <div class="cal-date">5</div>
                            <div class="cal-event ev-blue">Sara A.</div>
                            <div class="cal-event ev-amber">Layla R.</div>
                        </div>
                        <div class="cal-cell">
                            <div class="cal-date">6</div>
                            <div class="cal-event ev-green">Mike K.</div>
                        </div>
                        <div class="cal-cell">
                            <div class="cal-date">7</div>
                            <div class="cal-event ev-purple">Ali R.</div>
                        </div>
                        <div class="cal-cell">
                            <div class="cal-date">8</div>
                            <div class="cal-event ev-teal">Group</div>
                        </div>
                        <!-- Row 3 -->
                        <div class="cal-cell">
                            <div class="cal-date">9</div>
                        </div>
                        <div class="cal-cell">
                            <div class="cal-date">10</div>
                            <div class="cal-event ev-blue">Sara A.</div>
                            <div class="cal-event ev-teal">Group</div>
                        </div>
                        <div class="cal-cell">
                            <div class="cal-date">11</div>
                            <div class="cal-event ev-green">Mike K.</div>
                        </div>
                        <div class="cal-cell">
                            <div class="cal-date">12</div>
                            <div class="cal-event ev-blue">Sara A.</div>
                        </div>
                        <div class="cal-cell">
                            <div class="cal-date">13</div>
                            <div class="cal-event ev-amber">Layla R.</div>
                        </div>
                        <div class="cal-cell">
                            <div class="cal-date">14</div>
                            <div class="cal-event ev-purple">Ali R.</div>
                        </div>
                        <div class="cal-cell">
                            <div class="cal-date">15</div>
                            <div class="cal-event ev-teal">Group</div>
                        </div>
                        <!-- Row 4 -->
                        <div class="cal-cell">
                            <div class="cal-date">16</div>
                        </div>
                        <div class="cal-cell">
                            <div class="cal-date">17</div>
                            <div class="cal-event ev-blue">Sara A.</div>
                            <div class="cal-event ev-teal">Group</div>
                        </div>
                        <div class="cal-cell">
                            <div class="cal-date">18</div>
                            <div class="cal-event ev-green">Mike K.</div>
                            <div class="cal-event ev-amber">Layla R.</div>
                        </div>
                        <div class="cal-cell">
                            <div class="cal-date">19</div>
                            <div class="cal-event ev-blue">Sara A.</div>
                            <div class="cal-event ev-amber">Mike K.</div>
                        </div>
                        <div class="cal-cell today">
                            <div class="cal-date">19</div>
                            <div class="cal-event ev-blue">Sara A.</div>
                            <div class="cal-event ev-amber">Mike K.</div>
                            <div class="cal-event ev-teal">Layla R.</div>
                            <div class="cal-event ev-purple">Ali R.</div>
                        </div>
                        <div class="cal-cell">
                            <div class="cal-date">20</div>
                            <div class="cal-event ev-green">Mike K.</div>
                        </div>
                        <div class="cal-cell">
                            <div class="cal-date">21</div>
                            <div class="cal-event ev-teal">Group Yoga</div>
                        </div>
                        <!-- Row 5 -->
                        <div class="cal-cell">
                            <div class="cal-date">22</div>
                        </div>
                        <div class="cal-cell">
                            <div class="cal-date">23</div>
                            <div class="cal-event ev-blue">Sara A.</div>
                        </div>
                        <div class="cal-cell">
                            <div class="cal-date">24</div>
                            <div class="cal-event ev-green">Mike K.</div>
                        </div>
                        <div class="cal-cell">
                            <div class="cal-date">25</div>
                            <div class="cal-event ev-amber">Layla R.</div>
                        </div>
                        <div class="cal-cell">
                            <div class="cal-date">26</div>
                            <div class="cal-event ev-purple">Ali R.</div>
                        </div>
                        <div class="cal-cell">
                            <div class="cal-date">27</div>
                        </div>
                        <div class="cal-cell">
                            <div class="cal-date">28</div>
                            <div class="cal-event ev-teal">Group</div>
                        </div>
                        <!-- Row 6 -->
                        <div class="cal-cell other-month">
                            <div class="cal-date">1</div>
                        </div>
                        <div class="cal-cell other-month">
                            <div class="cal-date">2</div>
                        </div>
                        <div class="cal-cell other-month">
                            <div class="cal-date">3</div>
                        </div>
                        <div class="cal-cell other-month">
                            <div class="cal-date">4</div>
                        </div>
                        <div class="cal-cell other-month">
                            <div class="cal-date">5</div>
                        </div>
                        <div class="cal-cell other-month">
                            <div class="cal-date">6</div>
                        </div>
                        <div class="cal-cell other-month">
                            <div class="cal-date">7</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="section-card" style="animation-delay:.1s;margin-bottom:2rem">
                    <div class="section-header"><span class="section-title">Today</span><span style="font-size:1.2rem;color:var(--secondary)">Feb 19</span></div>
                    <div class="upcoming-list">
                        <div class="upcoming-item">
                            <div class="up-time">
                                <div class="up-time-val">8:00</div>
                                <div class="up-time-label">AM</div>
                            </div>
                            <div class="up-dot" style="background:#22c55e"></div>
                            <div class="up-info">
                                <div class="up-name">Sara Ahmed</div>
                                <div class="up-plan">Weight Loss · Done</div>
                            </div>
                        </div>
                        <div class="upcoming-item">
                            <div class="up-time">
                                <div class="up-time-val">10:00</div>
                                <div class="up-time-label">AM</div>
                            </div>
                            <div class="up-dot" style="background:#f59e0b"></div>
                            <div class="up-info">
                                <div class="up-name">Mike Khan</div>
                                <div class="up-plan">Muscle Gain · Next</div>
                            </div>
                        </div>
                        <div class="upcoming-item">
                            <div class="up-time">
                                <div class="up-time-val">12:00</div>
                                <div class="up-time-label">PM</div>
                            </div>
                            <div class="up-dot" style="background:var(--secondary)"></div>
                            <div class="up-info">
                                <div class="up-name">Layla Rahman</div>
                                <div class="up-plan">Cardio</div>
                            </div>
                        </div>
                        <div class="upcoming-item">
                            <div class="up-time">
                                <div class="up-time-val">2:00</div>
                                <div class="up-time-label">PM</div>
                            </div>
                            <div class="up-dot" style="background:#8b5cf6"></div>
                            <div class="up-info">
                                <div class="up-name">Ali Raza</div>
                                <div class="up-plan">Strength</div>
                            </div>
                        </div>
                        <div class="upcoming-item">
                            <div class="up-time">
                                <div class="up-time-val">5:00</div>
                                <div class="up-time-label">PM</div>
                            </div>
                            <div class="up-dot" style="background:#ef4444"></div>
                            <div class="up-info">
                                <div class="up-name">Group HIIT</div>
                                <div class="up-plan">8 members</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="section-card" style="animation-delay:.2s">
                    <div class="section-header"><span class="section-title">Legend</span></div>
                    <div style="padding:1.6rem 2rem;display:flex;flex-direction:column;gap:1rem">
                        <div style="display:flex;align-items:center;gap:.8rem"><span style="width:1.2rem;height:1.2rem;border-radius:.3rem;background:rgba(29,84,109,.15);display:inline-block"></span><span style="font-size:1.25rem">Individual Sessions</span></div>
                        <div style="display:flex;align-items:center;gap:.8rem"><span style="width:1.2rem;height:1.2rem;border-radius:.3rem;background:rgba(95,149,152,.2);display:inline-block"></span><span style="font-size:1.25rem">Group Classes</span></div>
                        <div style="display:flex;align-items:center;gap:.8rem"><span style="width:1.2rem;height:1.2rem;border-radius:.3rem;background:rgba(245,158,11,.15);display:inline-block"></span><span style="font-size:1.25rem">Cardio Sessions</span></div>
                        <div style="display:flex;align-items:center;gap:.8rem"><span style="width:1.2rem;height:1.2rem;border-radius:.3rem;background:rgba(139,92,246,.15);display:inline-block"></span><span style="font-size:1.25rem">Strength Training</span></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js">
        // const sidebar=document.getElementById('sidebar'),toggle=document.getElementById('sidebarToggle'),overlay=document.getElementById('sidebarOverlay');
        // toggle.onclick=()=>{sidebar.classList.toggle('open');overlay.classList.toggle('open');};
        // overlay.onclick=()=>{sidebar.classList.remove('open');overlay.classList.remove('open');};
    </script>
</body>

</html>