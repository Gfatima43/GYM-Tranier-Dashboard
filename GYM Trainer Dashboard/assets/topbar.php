<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$logged_in = isset($_SESSION['user_id']);
$user_name  = $_SESSION['user_name'] ?? '';
$user_role  = $_SESSION['role']      ?? '';


$currentPage = basename($_SERVER['PHP_SELF']);

switch ($currentPage) {
    case 'dashboard.php':
        $pageTitle = "Dashboard";
        break;

    case 'overview.php':
        $pageTitle = "Overview";
        break;

    case 'clients.php':
        $pageTitle = "My Clients";
        break;

    case 'workout-plan.php':
        $pageTitle = "Workout Plans";
        break;

    case 'schedule.php':
        $pageTitle = "Schedule";
        break;

    case 'attendance.php':
        $pageTitle = "Attendance";
        break;

    case 'message.php':
        $pageTitle = "Message";
        break;

    case 'profile.php':
        $pageTitle = "Profile";
        break;

    default:
        $pageTitle = "Dashboard";
}
?>

<header class="topbar">
    <div class="topbar-left">
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div>
            <div class="topbar-title"><?= isset($pageTitle) ? $pageTitle : "Dashboard"; ?></div>
            <!-- <div class="topbar-subtitle" id="current-date">Thursday, 19 Feb 2026</div> -->
            <div class="topbar-subtitle" id="current-date"><?= isset($pageSubtitle) ? $pageSubtitle : "Welcome back!"; ?></div>
        </div>
    </div>

    <div class="topbar-actions">
        <?php if ($logged_in): ?>

            <span style="font-size:1.1rem;font-weight:700;padding:.4rem 1rem;border-radius:10rem;
                  background:<?= $user_role === 'admin' ? 'rgba(139,92,246,.12)' : 'rgba(29,84,109,.1)' ?>;
                  color:<?= $user_role === 'admin' ? '#7c3aed' : 'var(--primary)' ?>">
                <?= $user_role === 'admin' ? '⚙ Admin' : '🏋 Trainer' ?>
            </span>
            <button class="topbar-btn" data-tip="Notifications">
                <i class="fas fa-bell"></i>
                <span class="topbar-notif">4</span>
            </button>
            <button class="topbar-btn" data-tip="Calendar">
                <i class="fas fa-calendar"></i>
            </button>
            <div class="topbar-user">
                <div class="user-avatar-sm"><?php echo substr($_SESSION['user_name'], 0, 2); ?></div>
                <span class="user-name-sm"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <i class="fas fa-chevron-down" style="font-size:1rem;color:rgba(6,30,41,0.4);margin-left:0.4rem;"></i>
            </div>
            <div class="topbar-btn">
                <button class="btn-logout" onclick="handleLogout()">
                    <i class="fas fa-arrow-right-from-bracket fa-flip-horizontal"></i>
                </button>
            </div>
        <?php else: ?>
            <button class="btn-login topbar-btn" onclick="window.location.href='login.php'">
                <i class="fas fa-sign-in-alt"></i>
                Login
            </button>
        <?php endif; ?>
    </div>
</header>