<?php
session_start();

$logged_in = isset($_SESSION['user_id']);
$currentPage = 'overview'; // sidebar tab is active
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overview - GYM Trainer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <!-- Sidebar Overlay (mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <!-- ═══════════════════ SIDEBAR ═══════════════════ -->
    <?php include 'assets/sidebar.php'; ?>

    <header class="topbar">
        <div class="topbar-left">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div>
                <div class="topbar-title">Overview</div>
                <div class="topbar-subtitle">Performance Analytics</div>
            </div>
        </div>
        <div class="topbar-actions">
            <button class="topbar-btn"><i class="fas fa-bell"></i><span class="topbar-notif">4</span></button>
            <div class="topbar-user">
                <div class="user-avatar-sm">IM</div><span class="user-name-sm">Irfan Malik</span><i class="fas fa-chevron-down"
                    style="font-size:1rem;color:rgba(6,30,41,.4);margin-left:.4rem"></i>
            </div>
            <div class="topbar-btn">
                <button class="btn-logout" onclick="handleLogout()">
                    <i class="fas fa-arrow-right-from-bracket fa-flip-horizontal"></i>
                </button>
            </div>
        </div>
    </header>
    <main class="main-content">
        <!-- KPI Cards -->
        <div class="kpi-grid">
            <div class="kpi-card c1">
                <div class="kpi-top-bar" style="background:linear-gradient(90deg,#1D546D,#5F9598)"></div>
                <div class="kpi-icon" style="background:rgba(29,84,109,.1);color:var(--primary)"><i class="fas fa-users"></i></div>
                <div class="kpi-label">Total Clients</div>
                <div class="kpi-value">8</div>
                <div class="kpi-trend up"><i class="fas fa-arrow-trend-up"></i> +3 this month</div>
            </div>
            <div class="kpi-card c2">
                <div class="kpi-top-bar" style="background:linear-gradient(90deg,#22c55e,#16a34a)"></div>
                <div class="kpi-icon" style="background:rgba(34,197,94,.1);color:#16a34a"><i class="fas fa-calendar-check"></i></div>
                <div class="kpi-label">Sessions This Month</div>
                <div class="kpi-value">87</div>
                <div class="kpi-trend up"><i class="fas fa-arrow-trend-up"></i> +14% vs last</div>
            </div>
            <div class="kpi-card c3">
                <div class="kpi-top-bar" style="background:linear-gradient(90deg,#f59e0b,#d97706)"></div>
                <div class="kpi-icon" style="background:rgba(245,158,11,.1);color:#d97706"><i class="fas fa-clipboard-list"></i></div>
                <div class="kpi-label">Pending Tasks</div>
                <div class="kpi-value">7</div>
                <div class="kpi-trend down"><i class="fas fa-triangle-exclamation"></i> 2 overdue</div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Bar Chart: Weekly Sessions -->
            <div class="col-lg-8">
                <div class="section-card h-100">
                    <div class="section-header">
                        <span class="section-title">Weekly Sessions</span>
                        <select style="border:1.5px solid rgba(29,84,109,.15);border-radius:.8rem;padding:.4rem 1rem;font-size:1.2rem;color:var(--dark);outline:none;background:#fff">
                            <option>This Week</option>
                            <option>Last Week</option>
                            <option>This Month</option>
                        </select>
                    </div>
                    <div style="padding:2.4rem">
                        <div class="bar-chart-wrap" id="barChart"></div>
                        <div style="display:flex;justify-content:center;gap:2rem;margin-top:1rem">
                            <span style="display:flex;align-items:center;gap:.5rem;font-size:1.2rem;color:rgba(6,30,41,.5)"><span style="width:1rem;height:1rem;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--secondary));display:inline-block"></span>Sessions</span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Donut: Client Types -->
            <div class="col-lg-4">
                <div class="section-card h-100">
                    <div class="section-header"><span class="section-title">Client Types</span></div>
                    <div style="padding:2.4rem">
                        <div class="donut-row" style="justify-content:center;flex-direction:column;align-items:center;gap:2rem">
                            <svg class="donut-svg" width="160" height="160" viewBox="0 0 160 160">
                                <circle cx="80" cy="80" r="60" fill="none" stroke="#eef2f5" stroke-width="22" />
                                <circle cx="80" cy="80" r="60" fill="none" stroke="#1D546D" stroke-width="22" stroke-dasharray="188 189" stroke-dashoffset="0" stroke-linecap="round" transform="rotate(-90 80 80)" />
                                <circle cx="80" cy="80" r="60" fill="none" stroke="#5F9598" stroke-width="22" stroke-dasharray="90 377" stroke-dashoffset="-188" stroke-linecap="round" transform="rotate(-90 80 80)" />
                                <circle cx="80" cy="80" r="60" fill="none" stroke="#7ec8cb" stroke-width="22" stroke-dasharray="63 377" stroke-dashoffset="-278" stroke-linecap="round" transform="rotate(-90 80 80)" />
                                <text x="80" y="76" text-anchor="middle" font-family="'Barlow Condensed'" font-size="28" font-weight="800" fill="#061E29">24</text>
                                <text x="80" y="92" text-anchor="middle" font-size="10" fill="rgba(6,30,41,.4)">Clients</text>
                            </svg>
                            <div class="donut-legend" style="width:100%">
                                <div class="legend-item"><span class="legend-dot" style="background:#1D546D"></span><span class="legend-label">Weight Loss</span><span class="legend-val">50%</span></div>
                                <div class="legend-item"><span class="legend-dot" style="background:#5F9598"></span><span class="legend-label">Muscle Gain</span><span class="legend-val">24%</span></div>
                                <div class="legend-item"><span class="legend-dot" style="background:#7ec8cb"></span><span class="legend-label">Endurance</span><span class="legend-val">17%</span></div>
                                <div class="legend-item"><span class="legend-dot" style="background:#eef2f5;border:1px solid #ccc"></span><span class="legend-label">Other</span><span class="legend-val">9%</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="section-card" style="animation-delay:.3s">
            <div class="section-header"><span class="section-title">Client Performance Metrics</span></div>
            <div class="metric-row"><span class="metric-name">Average Goal Completion</span>
                <div class="metric-bar-wrap">
                    <div class="metric-bar-fill" style="width:72%"></div>
                </div><span class="metric-pct">72%</span>
            </div>
            <div class="metric-row"><span class="metric-name">Session Retention Rate</span>
                <div class="metric-bar-wrap">
                    <div class="metric-bar-fill" style="width:89%"></div>
                </div><span class="metric-pct">89%</span>
            </div>
            <div class="metric-row"><span class="metric-name">Client Satisfaction Score</span>
                <div class="metric-bar-wrap">
                    <div class="metric-bar-fill" style="width:96%"></div>
                </div><span class="metric-pct">96%</span>
            </div>
            <div class="metric-row"><span class="metric-name">Workout Plan Adherence</span>
                <div class="metric-bar-wrap">
                    <div class="metric-bar-fill" style="width:65%"></div>
                </div><span class="metric-pct">65%</span>
            </div>
            <div class="metric-row"><span class="metric-name">New Client Conversion</span>
                <div class="metric-bar-wrap">
                    <div class="metric-bar-fill" style="width:44%"></div>
                </div><span class="metric-pct">44%</span>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <script>
        // Bar chart
        const days = [{
            d: 'Mon',
            v: 8
        }, {
            d: 'Tue',
            v: 12
        }, {
            d: 'Wed',
            v: 6
        }, {
            d: 'Thu',
            v: 15
        }, {
            d: 'Fri',
            v: 10
        }, {
            d: 'Sat',
            v: 4
        }, {
            d: 'Sun',
            v: 2
        }];
        const max = Math.max(...days.map(d => d.v));
        const bc = document.getElementById('barChart');
        days.forEach(d => {
            const h = Math.round((d.v / max) * 100);
            bc.innerHTML += `<div class="bar-item"><span class="bar-val">${d.v}</span><div class="bar-fill" style="height:0%;transition:height .8s ease" data-h="${h}%"></div><span class="bar-label">${d.d}</span></div>`;
        });
        setTimeout(() => {
            bc.querySelectorAll('.bar-fill').forEach(b => b.style.height = b.dataset.h);
        }, 200);
    </script>
</body>

</html>