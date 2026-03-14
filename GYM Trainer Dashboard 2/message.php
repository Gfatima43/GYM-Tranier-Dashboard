<?php
session_start();

$database = require 'bootstrap.php';
$pdo = $database->pdo;

$logged_in = isset($_SESSION['user_id']);
$user_name  = $_SESSION['user_name'] ?? '';
$user_role  = $_SESSION['role']      ?? '';
// $currentPage = 'message';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - GYM Trainer</title>
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
    <header class="topbar">
        <div class="topbar-left">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div>
                <div class="topbar-title">Messages</div>
                <div class="topbar-subtitle">5 unread</div>
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

    <div class="msg-layout">
        <!-- Contacts list -->
        <div class="msg-list-panel">
            <div class="msg-list-header">
                <div style="font-family:'Barlow Condensed',sans-serif;font-size:2rem;font-weight:800;color:var(--dark);text-transform:uppercase">Chats</div>
                <div class="msg-list-search"><i class="fas fa-search"></i><input type="text" placeholder="Search conversations…"></div>
            </div>
            <div class="msg-list-scroll" id="contactList"></div>
        </div>

        <!-- Chat area -->
        <div class="msg-chat-panel open" id="chatPanel">
            <div class="chat-header">
                <div class="chat-av" id="chatAv" style="background:linear-gradient(135deg,#1D546D,#5F9598)">MK</div>
                <div>
                    <div class="chat-name" id="chatName">Mike Khan</div>
                    <div class="chat-status"><i class="fas fa-circle" style="font-size:.7rem;margin-right:.4rem"></i>Online</div>
                </div>
                <div class="chat-actions">
                    <button class="chat-action-btn"><i class="fas fa-phone"></i></button>
                    <button class="chat-action-btn"><i class="fas fa-video"></i></button>
                    <button class="chat-action-btn"><i class="fas fa-ellipsis"></i></button>
                </div>
            </div>
            <div class="chat-messages" id="chatMessages">
                <div class="date-divider">Today</div>
                <div>
                    <div class="msg-bubble recv">Hey Coach! Quick question about today's session. What time should I arrive?</div>
                    <div class="msg-meta">Mike · 9:42 AM</div>
                </div>
                <div>
                    <div class="msg-bubble sent">Hey Mike! Today's session is at 10 AM. Try to be 5 minutes early to warm up 💪</div>
                    <div class="msg-meta sent-meta">9:45 AM · ✓✓</div>
                </div>
                <div>
                    <div class="msg-bubble recv">Perfect! Also, should I bring my protein shake for after the session?</div>
                    <div class="msg-meta">Mike · 9:46 AM</div>
                </div>
                <div>
                    <div class="msg-bubble sent">Absolutely! Make sure it's a high-protein one. We're doing compound lifts today so you'll need it for recovery 🏋️</div>
                    <div class="msg-meta sent-meta">9:48 AM · ✓✓</div>
                </div>
                <div>
                    <div class="msg-bubble recv">Got it. See you at 10! My legs are still sore from Tuesday lol</div>
                    <div class="msg-meta">Mike · 9:50 AM</div>
                </div>
                <div>
                    <div class="msg-bubble sent">Haha that's a good sign! It means the workout is working. Upper body today so your legs will get a break 😄</div>
                    <div class="msg-meta sent-meta">9:52 AM · ✓✓</div>
                </div>
            </div>
            <div class="chat-input-wrap">
                <button style="width:4rem;height:4rem;border-radius:1rem;border:1.5px solid rgba(29,84,109,.12);background:transparent;color:var(--secondary);font-size:1.6rem;cursor:pointer;display:flex;align-items:center;justify-content:center;"><i class="fas fa-paperclip"></i></button>
                <input type="text" class="chat-input" id="msgInput" placeholder="Type a message…" onkeydown="if(event.key==='Enter')sendMsg()">
                <button class="chat-send" onclick="sendMsg()"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>

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

        const contacts = [{
                init: 'MK',
                color: 'linear-gradient(135deg,#22c55e,#16a34a)',
                name: 'Mike Khan',
                preview: 'Haha that\'s a good sign!',
                time: '9:52 AM',
                unread: 0,
                online: true
            },
            {
                init: 'SA',
                color: 'linear-gradient(135deg,#1D546D,#5F9598)',
                name: 'Sara Ahmed',
                preview: 'Thank you Coach! I\'ll follow...',
                time: '8:15 AM',
                unread: 2,
                online: true
            },
            {
                init: 'LR',
                color: 'linear-gradient(135deg,#f59e0b,#d97706)',
                name: 'Layla Rahman',
                preview: 'Can we reschedule Friday?',
                time: 'Yesterday',
                unread: 1,
                online: false
            },
            {
                init: 'AR',
                color: 'linear-gradient(135deg,#8b5cf6,#7c3aed)',
                name: 'Ali Raza',
                preview: 'My knee is feeling better now',
                time: 'Yesterday',
                unread: 1,
                online: false
            },
            {
                init: 'OF',
                color: 'linear-gradient(135deg,#06b6d4,#0891b2)',
                name: 'Omar Farooq',
                preview: 'When does my plan start?',
                time: 'Mon',
                unread: 1,
                online: true
            },
            {
                init: 'HS',
                color: 'linear-gradient(135deg,#ec4899,#db2777)',
                name: 'Hina Shah',
                preview: 'Great session today! 💪',
                time: 'Mon',
                unread: 0,
                online: false
            },
            {
                init: 'BM',
                color: 'linear-gradient(135deg,#14b8a6,#0d9488)',
                name: 'Bilal Mahmood',
                preview: 'I\'ll be 10 min late tomorrow',
                time: 'Sun',
                unread: 0,
                online: false
            },
        ];

        const cl = document.getElementById('contactList');
        contacts.forEach((c, i) => {
            cl.innerHTML += `<div class="msg-contact${i===0?' active':''}" onclick="selectContact(this,'${c.name}','${c.color}','${c.init}')">
        <div class="msg-av" style="background:${c.color}">${c.init}${c.online?'<div class="msg-online"></div>':''}</div>
        <div style="flex:1;min-width:0">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.2rem">
                <span class="msg-name">${c.name}</span>
                <span class="msg-time">${c.time}</span>
            </div>
            <div class="msg-preview">${c.preview}</div>
        </div>
        ${c.unread?`<div class="msg-unread">${c.unread}</div>`:''}
    </div>`;
        });

        function selectContact(el, name, color, init) {
            document.querySelectorAll('.msg-contact').forEach(c => c.classList.remove('active'));
            el.classList.add('active');
            document.getElementById('chatName').textContent = name;
            document.getElementById('chatAv').style.background = color;
            document.getElementById('chatAv').textContent = init;
            const unread = el.querySelector('.msg-unread');
            if (unread) unread.remove();
        }

        function sendMsg() {
            const input = document.getElementById('msgInput');
            const txt = input.value.trim();
            if (!txt) return;
            const msgs = document.getElementById('chatMessages');
            const now = new Date();
            const time = now.getHours() + ':' + String(now.getMinutes()).padStart(2, '0');
            msgs.innerHTML += `<div style="align-self:flex-end"><div class="msg-bubble sent">${txt}</div><div class="msg-meta sent-meta">${time} AM · ✓✓</div></div>`;
            input.value = '';
            msgs.scrollTop = msgs.scrollHeight;
        }
    </script>
</body>

</html>