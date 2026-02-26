<?php
session_start();

$logged_in = isset($_SESSION['user_id']);
$currentPage = 'attendance'; // sidebar tab is active
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - GYM Trainer</title>
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
    
    <header class="topbar">
        <div class="topbar-left">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div>
                <div class="topbar-title">Attendance</div>
                <div class="topbar-subtitle">Thursday, Feb 19, 2026</div>
            </div>
        </div>
        <div class="topbar-actions">
            <button class="topbar-btn"><i class="fas fa-bell"></i><span class="topbar-notif">4</span></button>
            <input type="date" value="2026-02-19" style="height:4rem;border:1.5px solid rgba(29,84,109,.15);border-radius:1rem;padding:0 1.2rem;font-size:1.3rem;font-family:'DM Sans',sans-serif;color:var(--dark);outline:none;background:#fff">
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
        <div class="att-stats">
            <div class="att-stat">
                <div class="att-stat-val" style="color:#22c55e">18</div>
                <div class="att-stat-label">Present Today</div>
            </div>
            <div class="att-stat">
                <div class="att-stat-val" style="color:#ef4444">3</div>
                <div class="att-stat-label">Absent</div>
            </div>
            <div class="att-stat">
                <div class="att-stat-val" style="color:#f59e0b">2</div>
                <div class="att-stat-label">Late</div>
            </div>
            <div class="att-stat">
                <div class="att-stat-val" style="color:var(--primary)">89%</div>
                <div class="att-stat-label">Attendance Rate</div>
            </div>
        </div>
        <div class="section-card">
            <div class="section-header">
                <span class="section-title">Today's Attendance</span>
                <div style="display:flex;gap:.8rem;align-items:center">
                    <span style="font-size:1.2rem;color:rgba(6,30,41,.45)">Thu, Feb 19</span>
                    <button style="padding:.6rem 1.4rem;border-radius:.8rem;border:none;background:linear-gradient(135deg,var(--primary),var(--secondary));color:#fff;font-size:1.2rem;font-weight:600;cursor:pointer;" onclick="exportAttendance()"><i class="fas fa-download" style="margin-right:.5rem"></i>Export</button>
                </div>
            </div>
            <div style="overflow-x:auto">
                <table class="att-table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Session Time</th>
                            <th>Plan</th>
                            <th>This Week</th>
                            <th>Rate</th>
                            <th>Status</th>
                            <th>Mark</th>
                        </tr>
                    </thead>
                    <tbody id="attBody"></tbody>
                </table>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- <script src="js/script.js"></script> -->
    <script>
        // ── Logout ──
        function handleLogout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'login.php';
            }
        }
        const sidebar = document.getElementById('sidebar'),
            toggle = document.getElementById('sidebarToggle'),
            overlay = document.getElementById('sidebarOverlay');
        toggle.onclick = () => {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('open');
        };
        overlay.onclick = () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
        };

        const records = [{
                init: 'SA',
                color: 'linear-gradient(135deg,#1D546D,#5F9598)',
                name: 'Sara Ahmed',
                time: '08:00 AM',
                plan: 'Weight Loss',
                week: ['p', 'p', 'p', 'p', 'p'],
                rate: 100,
                status: 'Present'
            },
            {
                init: 'MK',
                color: 'linear-gradient(135deg,#22c55e,#16a34a)',
                name: 'Mike Khan',
                time: '10:00 AM',
                plan: 'Muscle Gain',
                week: ['p', 'a', 'p', 'p', 'l'],
                rate: 80,
                status: 'Present'
            },
            {
                init: 'LR',
                color: 'linear-gradient(135deg,#f59e0b,#d97706)',
                name: 'Layla Rahman',
                time: '12:00 PM',
                plan: 'Cardio Endurance',
                week: ['p', 'p', 'p', 'p', 'p'],
                rate: 95,
                status: 'Present'
            },
            {
                init: 'AR',
                color: 'linear-gradient(135deg,#8b5cf6,#7c3aed)',
                name: 'Ali Raza',
                time: '02:00 PM',
                plan: 'Strength & Power',
                week: ['p', 'a', 'a', 'p', 'x'],
                rate: 60,
                status: 'Absent'
            },
            {
                init: 'ZN',
                color: 'linear-gradient(135deg,#ef4444,#dc2626)',
                name: 'Zara Noor',
                time: '03:00 PM',
                plan: 'Flexibility',
                week: ['a', 'a', 'a', 'a', 'x'],
                rate: 10,
                status: 'Absent'
            },
            {
                init: 'OF',
                color: 'linear-gradient(135deg,#06b6d4,#0891b2)',
                name: 'Omar Farooq',
                time: '04:00 PM',
                plan: 'Weight Loss',
                week: ['p', 'p', 'l', 'p', 'x'],
                rate: 75,
                status: 'Late'
            },
            {
                init: 'HS',
                color: 'linear-gradient(135deg,#ec4899,#db2777)',
                name: 'Hina Shah',
                time: '05:00 PM',
                plan: 'Muscle Gain',
                week: ['p', 'p', 'p', 'p', 'x'],
                rate: 90,
                status: 'Present'
            },
            {
                init: 'BM',
                color: 'linear-gradient(135deg,#14b8a6,#0d9488)',
                name: 'Bilal Mahmood',
                time: '05:00 PM',
                plan: 'Cardio Endurance',
                week: ['p', 'p', 'a', 'p', 'x'],
                rate: 78,
                status: 'Absent'
            },
        ];

        const dotLabel = {
            'p': '<i class="fas fa-check"></i>',
            'a': '<i class="fas fa-times"></i>',
            'l': 'L',
            'x': ''
        };

        function renderAtt() {
            const tb = document.getElementById('attBody');
            tb.innerHTML = '';
            records.forEach((r, i) => {
                const sc = r.status === 'Present' ? 'status-active' : r.status === 'Late' ? 'status-pending' : 'status-inactive';
                const dots = r.week.map(d => `<div class="att-dot ${d}">${dotLabel[d]}</div>`).join('');
                tb.innerHTML += `<tr>
            <td><div style="display:flex;align-items:center;gap:1.2rem"><div class="client-av" style="background:${r.color}">${r.init}</div><span style="font-weight:600">${r.name}</span></div></td>
            <td>${r.time}</td>
            <td style="color:rgba(6,30,41,.55)">${r.plan}</td>
            <td><div class="att-dots">${dots}</div></td>
            <td><span style="font-weight:700;color:${r.rate>=80?'#16a34a':r.rate>=60?'#d97706':'#ef4444'}">${r.rate}%</span></td>
            <td><span class="badge-status ${sc}">${r.status}</span></td>
            <td>
                <button class="mark-btn mark-present" onclick="mark(${i},'Present')"><i class="fas fa-check"></i> P</button>
                <button class="mark-btn mark-absent" onclick="mark(${i},'Absent')" style="margin-left:.4rem"><i class="fas fa-times"></i> A</button>
            </td>
        </tr>`;
            });
        }
        renderAtt();

        function mark(i, status) {
            records[i].status = status;
            renderAtt();
        }

        function exportAttendance() {
            alert('Attendance exported as CSV!');
        }
    </script>
</body>

</html>