<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$logged_in = isset($_SESSION['user_id']);
$currentPage = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['role'] ?? '';
$user_name = $_SESSION['user_name'] ?? '';
?>


<!-- ═══════════════════ SIDEBAR ═══════════════════ -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><img src="images/fitness-logo-design-sports-logo_331749-164.avif" width="50px" class="img-circle"></div>
        <div class="brand-text">
            <span class="brand-name">Gym</span>
            <span class="brand-sub">Trainer Hub</span>
        </div>
    </div>

    <?php if ($logged_in): ?>
        <div class="sidebar-profile">
            <div class="sidebar-avatar"><?= substr($_SESSION['user_name'] && $_SESSION['role']  == 'admin' ? 'trainer' : '', 0, 2); ?></div>
            <div class="sidebar-profile-info">
                <div class="name"><?= htmlspecialchars($user_name) ?></div>
                <div class="role"><?= $user_role === 'admin' ? 'Admin Dashboard' : 'Trainer Dashboard' ?></div>
            </div>
            <div class="online-dot"></div>
        </div>
    <?php endif; ?>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Main</div>

        <a class="nav-item-link <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>" href="dashboard.php" data-page="dashboard">
            <span class="nav-icon"><i class="fas fa-grip"></i></span>
            Dashboard
        </a>

        <?php if ($logged_in): ?>
            <a class="nav-item-link <?= ($currentPage == 'overview.php') ? 'active' : '' ?>" href="overview.php" data-page="overview">
                <span class="nav-icon"><i class="fas fa-chart-line"></i></span>
                Overview
            </a>

            <?php if ($user_role === 'admin'): ?>
                <a class="nav-item-link <?= ($currentPage == 'trainers.php') ? 'active' : '' ?>" href="trainers.php" data-page="trainers">
                    <span class="nav-icon"><i class="fas fa-user-tie"></i></span>
                    Trainer
                </a>
            <?php endif ?>

            <div class="nav-section-label"><?= $user_role === 'admin' ? 'Clients' : 'My Clients' ?></div>

            <a class="nav-item-link <?= ($currentPage == 'clients.php') ? 'active' : '' ?>" href="clients.php" data-page="clients">
                <span class="nav-icon"><i class="fas fa-users"></i></span>                
                <?= $user_role === 'admin' ? 'All Clients' : 'My Clients' ?>
                <span class="nav-badge">8</span>
            </a>

        <?php endif ?>

        <a class="nav-item-link <?= ($currentPage == 'workout-plan.php') ? 'active' : '' ?>" href="workout-plan.php" data-page="workouts">
            <span class="nav-icon"><i class="fas fa-dumbbell"></i></span>
            Workout Plans
            <span class="nav-badge">6</span>
        </a>

        <div class="nav-section-label">Management</div>

        <a class="nav-item-link <?= ($currentPage == 'schedule.php') ? 'active' : '' ?>" href="schedule.php" data-page="schedule">
            <span class="nav-icon"><i class="fas fa-calendar-days"></i></span>
            Schedule
        </a>

        <?php if ($logged_in): ?>
            <a class="nav-item-link <?= ($currentPage == 'attendance.php') ? 'active' : '' ?>" href="attendance.php" data-page="attendance">
                <span class="nav-icon"><i class="fas fa-clipboard-check"></i></span>
                Attendance
                <span class="nav-badge danger">3</span>
            </a>

            <a class="nav-item-link <?= ($currentPage == 'message.php') ? 'active' : '' ?>" href="message.php" data-page="messages">
                <span class="nav-icon"><i class="fas fa-comment-dots"></i></span>
                Messages
                <span class="nav-badge danger">5</span>
            </a>

            <div class="nav-section-label">Account</div>

            <a class="nav-item-link <?= ($currentPage == 'profile.php') ? 'active' : '' ?>" href="profile.php" data-page="profile">
                <span class="nav-icon"><i class="fas fa-user-circle"></i></span>
                Profile
            </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <?php if ($logged_in): ?>
            <button class="btn-logout" onclick="handleLogout()">
                <i class="fas fa-arrow-right-from-bracket"></i>
                Logout
            </button>
        <?php else: ?>
            <button class="btn-login" onclick="window.location.href='login.php'">
                <i class="fas fa-sign-in-alt"></i>
                Login
            </button>
        <?php endif; ?>
    </div>
</aside>