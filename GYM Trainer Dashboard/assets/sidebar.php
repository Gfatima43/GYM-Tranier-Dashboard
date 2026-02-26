<?php
session_start();
require './middleware/middle.php';
$logged_in = isset($_SESSION['user_id']);
$currentPage = basename($_SERVER['PHP_SELF']);
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
                <div class="name"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
                <div class="role"><?= ($_SESSION['role'] === 'admin') ? 'Admin Dashboard' : 'Trainer Dashboard'; ?></div>
            </div>
            <div class="online-dot"></div>
        </div>
    <?php endif; ?>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Main</div>

        <a class="nav-item-link active <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>" href="dashboard.php" data-page="dashboard">
            <span class="nav-icon"><i class="fas fa-grid-2"></i></span>
            Dashboard
        </a>

        <?php 
        if ($logged_in): 
        if ($_SESSION['role'] === 'admin'):
        ?>
            <a class="nav-item-link <?= ($currentPage == 'overview.php') ? 'active' : '' ?>" href="overview.php" data-page="overview">
                <span class="nav-icon"><i class="fas fa-chart-line"></i></span>
                Overview
            </a>

            <a class="nav-item-link <?= ($currentPage == 'trainers.php') ? 'active' : '' ?>" href="trainers.php" data-page="trainer">
                <span class="nav-icon"><i class="fas fa-trainer"></i></span>
                Trainer
            </a>

            <div class="nav-section-label">Clients</div>
    
            <a class="nav-item-link <?= ($currentPage == 'clients.php') ? 'active' : '' ?>" href="clients.php" data-page="clients">
                <span class="nav-icon"><i class="fas fa-users"></i></span>
                Clients
                <span class="nav-badge">8</span>
            </a>
        <?php 
        endif; 
        endif;
        ?>
        <?php 
        if ($logged_in): 
        if ($_SESSION['role'] === 'trainer'):
        ?>
            <a class="nav-item-link <?= ($currentPage == 'overview.php') ? 'active' : '' ?>" href="overview.php" data-page="overview">
                <span class="nav-icon"><i class="fas fa-chart-line"></i></span>
                Overview
            </a>

            <div class="nav-section-label">MY Clients</div>
    
            <a class="nav-item-link <?= ($currentPage == 'clients.php') ? 'active' : '' ?>" href="clients.php" data-page="clients">
                <span class="nav-icon"><i class="fas fa-users"></i></span>
                Clients
                <span class="nav-badge">8</span>
            </a>
        <?php 
        endif; 
        endif;
        ?>




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