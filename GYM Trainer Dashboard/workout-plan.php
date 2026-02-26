<?php
$currentPage = 'workout-plan';
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
</head>

<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php include 'assets/sidebar.php'; ?>

    <header class="topbar">
        <div class="topbar-left">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div>
                <div class="topbar-title">Workout Plans</div>
                <div class="topbar-subtitle">6 active plans</div>
            </div>
        </div>
        <div class="topbar-actions">
            <button class="topbar-btn"><i class="fas fa-bell"></i><span class="topbar-notif">4</span></button>
            <div class="topbar-user">
                <div class="user-avatar-sm">IM</div><span class="user-name-sm">Irfan Malik</span><i class="fas fa-chevron-down" style="font-size:1rem;color:rgba(6,30,41,.4);margin-left:.4rem"></i>
            </div>
            <div class="topbar-btn">
                <button class="btn-logout" onclick="handleLogout()">
                    <i class="fas fa-arrow-right-from-bracket fa-flip-horizontal"></i>
                </button>
            </div>
        </div>
    </header>
    <main class="main-content">
        <div class="plans-grid">
            <!-- Plan 1 -->
            <div class="plan-card">
                <div class="plan-header">
                    <div class="plan-icon" style="background:rgba(29,84,109,.1);color:var(--primary)"><i class="fas fa-fire"></i></div>
                    <div class="plan-name">Weight Loss</div>
                    <span class="plan-tag" style="background:rgba(29,84,109,.1);color:var(--primary)">8 Weeks</span>
                    <div class="exercise-list">
                        <div class="exercise-item" style="--d:#1D546D"><span style="width:.5rem;height:.5rem;border-radius:50%;background:#1D546D;flex-shrink:0"></span>Cardio Circuit · 45min</div>
                        <div class="exercise-item"><span style="width:.5rem;height:.5rem;border-radius:50%;background:#1D546D;flex-shrink:0"></span>HIIT Training · 30min</div>
                        <div class="exercise-item"><span style="width:.5rem;height:.5rem;border-radius:50%;background:#1D546D;flex-shrink:0"></span>Core Workouts · 20min</div>
                    </div>
                </div>
                <div class="plan-divider"></div>
                <div class="plan-body">
                    <div class="plan-stat"><span class="plan-stat-label">Assigned Clients</span><span class="plan-stat-val">8</span></div>
                    <div class="plan-stat"><span class="plan-stat-label">Sessions/Week</span><span class="plan-stat-val">5</span></div>
                    <div class="plan-stat"><span class="plan-stat-label">Avg Completion</span><span class="plan-stat-val">78%</span></div>
                </div>
                <div class="plan-footer">
                    <button class="btn-sm btn-sm-primary"><i class="fas fa-eye"></i>View</button>
                    <button class="btn-sm btn-sm-outline"><i class="fas fa-pen"></i>Edit</button>
                    <button class="btn-sm btn-sm-outline ms-auto" style="border-color:rgba(239,68,68,.2);color:#ef4444"><i class="fas fa-trash"></i></button>
                </div>
            </div>
            <!-- Plan 2 -->
            <div class="plan-card">
                <div class="plan-header">
                    <div class="plan-icon" style="background:rgba(34,197,94,.1);color:#16a34a"><i class="fas fa-dumbbell"></i></div>
                    <div class="plan-name">Muscle Gain</div>
                    <span class="plan-tag" style="background:rgba(34,197,94,.1);color:#16a34a">12 Weeks</span>
                    <div class="exercise-list">
                        <div class="exercise-item"><span style="width:.5rem;height:.5rem;border-radius:50%;background:#16a34a;flex-shrink:0"></span>Compound Lifts · 60min</div>
                        <div class="exercise-item"><span style="width:.5rem;height:.5rem;border-radius:50%;background:#16a34a;flex-shrink:0"></span>Isolation Work · 30min</div>
                        <div class="exercise-item"><span style="width:.5rem;height:.5rem;border-radius:50%;background:#16a34a;flex-shrink:0"></span>Progressive Overload</div>
                    </div>
                </div>
                <div class="plan-divider"></div>
                <div class="plan-body">
                    <div class="plan-stat"><span class="plan-stat-label">Assigned Clients</span><span class="plan-stat-val">5</span></div>
                    <div class="plan-stat"><span class="plan-stat-label">Sessions/Week</span><span class="plan-stat-val">4</span></div>
                    <div class="plan-stat"><span class="plan-stat-label">Avg Completion</span><span class="plan-stat-val">61%</span></div>
                </div>
                <div class="plan-footer">
                    <button class="btn-sm btn-sm-primary"><i class="fas fa-eye"></i>View</button>
                    <button class="btn-sm btn-sm-outline"><i class="fas fa-pen"></i>Edit</button>
                    <button class="btn-sm btn-sm-outline ms-auto" style="border-color:rgba(239,68,68,.2);color:#ef4444"><i class="fas fa-trash"></i></button>
                </div>
            </div>
            <!-- Plan 3 -->
            <div class="plan-card">
                <div class="plan-header">
                    <div class="plan-icon" style="background:rgba(245,158,11,.1);color:#d97706"><i class="fas fa-person-running"></i></div>
                    <div class="plan-name">Cardio Endurance</div>
                    <span class="plan-tag" style="background:rgba(245,158,11,.1);color:#d97706">6 Weeks</span>
                    <div class="exercise-list">
                        <div class="exercise-item"><span style="width:.5rem;height:.5rem;border-radius:50%;background:#d97706;flex-shrink:0"></span>Long-distance Run</div>
                        <div class="exercise-item"><span style="width:.5rem;height:.5rem;border-radius:50%;background:#d97706;flex-shrink:0"></span>Cycling · 45min</div>
                        <div class="exercise-item"><span style="width:.5rem;height:.5rem;border-radius:50%;background:#d97706;flex-shrink:0"></span>Rowing Machine</div>
                    </div>
                </div>
                <div class="plan-divider"></div>
                <div class="plan-body">
                    <div class="plan-stat"><span class="plan-stat-label">Assigned Clients</span><span class="plan-stat-val">4</span></div>
                    <div class="plan-stat"><span class="plan-stat-label">Sessions/Week</span><span class="plan-stat-val">6</span></div>
                    <div class="plan-stat"><span class="plan-stat-label">Avg Completion</span><span class="plan-stat-val">91%</span></div>
                </div>
                <div class="plan-footer">
                    <button class="btn-sm btn-sm-primary"><i class="fas fa-eye"></i>View</button>
                    <button class="btn-sm btn-sm-outline"><i class="fas fa-pen"></i>Edit</button>
                    <button class="btn-sm btn-sm-outline ms-auto" style="border-color:rgba(239,68,68,.2);color:#ef4444"><i class="fas fa-trash"></i></button>
                </div>
            </div>
            <!-- Plan 4 -->
            <div class="plan-card">
                <div class="plan-header">
                    <div class="plan-icon" style="background:rgba(139,92,246,.1);color:#7c3aed"><i class="fas fa-bolt"></i></div>
                    <div class="plan-name">Strength & Power</div>
                    <span class="plan-tag" style="background:rgba(139,92,246,.1);color:#7c3aed">10 Weeks</span>
                    <div class="exercise-list">
                        <div class="exercise-item"><span style="width:.5rem;height:.5rem;border-radius:50%;background:#7c3aed;flex-shrink:0"></span>Olympic Lifts · 60min</div>
                        <div class="exercise-item"><span style="width:.5rem;height:.5rem;border-radius:50%;background:#7c3aed;flex-shrink:0"></span>Plyometrics · 30min</div>
                        <div class="exercise-item"><span style="width:.5rem;height:.5rem;border-radius:50%;background:#7c3aed;flex-shrink:0"></span>Powerlifting</div>
                    </div>
                </div>
                <div class="plan-divider"></div>
                <div class="plan-body">
                    <div class="plan-stat"><span class="plan-stat-label">Assigned Clients</span><span class="plan-stat-val">3</span></div>
                    <div class="plan-stat"><span class="plan-stat-label">Sessions/Week</span><span class="plan-stat-val">4</span></div>
                    <div class="plan-stat"><span class="plan-stat-label">Avg Completion</span><span class="plan-stat-val">44%</span></div>
                </div>
                <div class="plan-footer">
                    <button class="btn-sm btn-sm-primary"><i class="fas fa-eye"></i>View</button>
                    <button class="btn-sm btn-sm-outline"><i class="fas fa-pen"></i>Edit</button>
                    <button class="btn-sm btn-sm-outline ms-auto" style="border-color:rgba(239,68,68,.2);color:#ef4444"><i class="fas fa-trash"></i></button>
                </div>
            </div>
            <!-- Plan 5 -->
            <div class="plan-card">
                <div class="plan-header">
                    <div class="plan-icon" style="background:rgba(95,149,152,.1);color:var(--secondary)"><i class="fas fa-spa"></i></div>
                    <div class="plan-name">Flexibility</div>
                    <span class="plan-tag" style="background:rgba(95,149,152,.1);color:var(--secondary)">4 Weeks</span>
                    <div class="exercise-list">
                        <div class="exercise-item"><span style="width:.5rem;height:.5rem;border-radius:50%;background:var(--secondary);flex-shrink:0"></span>Yoga Flow · 40min</div>
                        <div class="exercise-item"><span style="width:.5rem;height:.5rem;border-radius:50%;background:var(--secondary);flex-shrink:0"></span>Mobility Work</div>
                        <div class="exercise-item"><span style="width:.5rem;height:.5rem;border-radius:50%;background:var(--secondary);flex-shrink:0"></span>Stretch Routines</div>
                    </div>
                </div>
                <div class="plan-divider"></div>
                <div class="plan-body">
                    <div class="plan-stat"><span class="plan-stat-label">Assigned Clients</span><span class="plan-stat-val">2</span></div>
                    <div class="plan-stat"><span class="plan-stat-label">Sessions/Week</span><span class="plan-stat-val">3</span></div>
                    <div class="plan-stat"><span class="plan-stat-label">Avg Completion</span><span class="plan-stat-val">20%</span></div>
                </div>
                <div class="plan-footer">
                    <button class="btn-sm btn-sm-primary"><i class="fas fa-eye"></i>View</button>
                    <button class="btn-sm btn-sm-outline"><i class="fas fa-pen"></i>Edit</button>
                    <button class="btn-sm btn-sm-outline ms-auto" style="border-color:rgba(239,68,68,.2);color:#ef4444"><i class="fas fa-trash"></i></button>
                </div>
            </div>
            <!-- Add New Plan -->
            <div class="add-plan-card" onclick="alert('Create new plan!')">
                <div class="add-icon"><i class="fas fa-plus"></i></div>
                <div class="add-label">Create New Plan</div>
                <div style="font-size:1.2rem;color:rgba(6,30,41,.3)">Design a custom workout plan</div>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <!-- <script>
const sidebar=document.getElementById('sidebar'),toggle=document.getElementById('sidebarToggle'),overlay=document.getElementById('sidebarOverlay');
toggle.onclick=()=>{sidebar.classList.toggle('open');overlay.classList.toggle('open');};
overlay.onclick=()=>{sidebar.classList.remove('open');overlay.classList.remove('open');};
</script> -->
</body>

</html>