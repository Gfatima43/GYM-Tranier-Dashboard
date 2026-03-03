<?php
session_start();

$database = require 'bootstrap.php';
$pdo = $database->pdo;

$logged_in = isset($_SESSION['user_id']);
$user_name  = $_SESSION['user_name'] ?? '';
$user_role  = $_SESSION['role']      ?? '';

// $currentPage = 'clients';

$message = '';
$messageType = '';

// ────────────────────────────────────────────
// CRUD — handle POST actions
// ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── CREATE ──
    if ($action === 'add') {
        $first    = trim($_POST['firstName']  ?? '');
        $last     = trim($_POST['lastName']   ?? '');
        $email    = trim($_POST['email']       ?? '');
        $phone    = trim($_POST['phone']       ?? '');
        $plan     = $_POST['plan']     ?? 'Weight Loss';
        $status   = $_POST['status']   ?? 'Active';
        $progress = (int)($_POST['progress'] ?? 0);
        $sessions = (int)($_POST['sessions'] ?? 0);

        if (empty($first) || empty($last) || empty($email)) {
            $message = 'First name, last name and email are required.';
            $messageType = 'danger';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address.';
            $messageType = 'danger';
        } else {
            // Duplicate email check
            $check = $pdo->prepare("SELECT id FROM clients WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                $message = 'A client with this email already exists.';
                $messageType = 'danger';
            } else {
                $stmt = $pdo->prepare("INSERT INTO clients
                    (firstName, lastName, email, phone, plan, progress, sessions, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$first, $last, $email, $phone, $plan, $progress, $sessions, $status]);
                $message = "Client {$first} {$last} added successfully!";
                $messageType = 'success';
            }
        }
    }

    // ── UPDATE ──
    elseif ($action === 'edit') {
        $id       = (int)($_POST['id'] ?? 0);
        $first    = trim($_POST['firstName']  ?? '');
        $last     = trim($_POST['lastName']   ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $plan     = $_POST['plan']     ?? 'Weight Loss';
        $status   = $_POST['status']   ?? 'Active';
        $progress = (int)($_POST['progress'] ?? 0);
        $sessions = (int)($_POST['sessions'] ?? 0);

        if (empty($first) || empty($last) || empty($email) || $id < 1) {
            $message = 'All required fields must be filled in.';
            $messageType = 'danger';
        } else {
            $stmt = $pdo->prepare("UPDATE clients SET
                firstName=?, lastName=?, email=?, phone=?, plan=?, progress=?, sessions=?, status=?
                WHERE id=?");
            $stmt->execute([$first, $last, $email, $phone, $plan, $progress, $sessions, $status, $id]);
            $message = "Client {$first} {$last} updated successfully!";
            $messageType = 'success';
        }
    }

    // ── DELETE ──
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'Client removed successfully.';
            $messageType = 'success';
        }
    }

    // PRG — redirect to avoid form re-submission
    $_SESSION['flash'] = ['msg' => $message, 'type' => $messageType];
    header('Location: clients.php');
    exit();
}

// ── Grab flash message ──
if (isset($_SESSION['flash'])) {
    $message     = $_SESSION['flash']['msg'];
    $messageType = $_SESSION['flash']['type'];
    unset($_SESSION['flash']);
}

// ── READ — fetch all clients ──
$stmt = $pdo->query("SELECT * FROM clients ORDER BY id DESC");
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total   = count($clients);

// Avatar colours — cycle through palette by id
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

// Helper — JSON-safe client array for JS
function clientsToJson(array $clients, array $colours): string
{
    $out = [];
    foreach ($clients as $c) {
        $initials = strtoupper(substr($c['firstName'], 0, 1) . substr($c['lastName'], 0, 1));
        $out[] = [
            'id'       => $c['id'],
            'init'     => $initials,
            'color'    => $colours[$c['id'] % count($colours)],
            'name'     => htmlspecialchars($c['firstName'] . ' ' . $c['lastName'], ENT_QUOTES),
            'firstName' => htmlspecialchars($c['firstName'], ENT_QUOTES),
            'lastName' => htmlspecialchars($c['lastName'],  ENT_QUOTES),
            'email'    => htmlspecialchars($c['email'],      ENT_QUOTES),
            'phone'    => htmlspecialchars($c['phone'],      ENT_QUOTES),
            'plan'     => htmlspecialchars($c['plan'],       ENT_QUOTES),
            'progress' => (int)$c['progress'],
            'sessions' => (int)$c['sessions'],
            'join'     => date('M j, Y', strtotime($c['join_date'])),
            'status'   => $c['status'],
        ];
    }
    return json_encode($out, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients - GYM Trainer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <!-- ── Toast ── -->
    <?php if ($message): ?>
        <div class="toast-wrap" id="toastWrap">
            <div class="toast-msg toast-<?= htmlspecialchars($messageType) ?>">
                <i class="fas fa-<?= $messageType === 'success' ? 'circle-check' : 'circle-exclamation' ?> me-2"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ═════════════════ SIDEBAR OVERLAY (mobile) ═════════════════ -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- ═══════════════════ SIDEBAR ═══════════════════ -->
    <?php include 'assets/sidebar.php'; ?>

    <header class="topbar">
        <div class="topbar-left">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div>
                <div class="topbar-title">My Clients</div>
                <div class="topbar-subtitle"><?= $total ?> active members</div>
            </div>
        </div>
        <div class="topbar-search"><i class="fas fa-search search-icon"></i><input type="text" id="clientSearch" placeholder="Search clients…"></div>
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
        <div class="section-card">
            <div class="section-header">
                <span class="section-title">All Clients</span>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="filter-tabs">
                        <button class="filter-tab active" onclick="filterClients('all',this)">All</button>
                        <button class="filter-tab" onclick="filterClients('Active',this)">Active</button>
                        <button class="filter-tab" onclick="filterClients('Inactive',this)">Inactive</button>
                        <button class="filter-tab" onclick="filterClients('Pending',this)">Pending</button>
                    </div>
                    <button class="btn-add" onclick="document.getElementById('addModal').classList.add('open')"><i class="fas fa-plus"></i>Add Client</button>
                </div>
            </div>
            <div style="overflow-x:auto">
                <table class="client-table" id="clientTable">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Plan</th>
                            <th>Progress</th>
                            <th>Sessions</th>
                            <th>Join Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="clientBody"></tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- ══════════════════════════════════════════
     ADD CLIENT MODAL
    ══════════════════════════════════════════ -->
    <div class="modal-overlay" id="addModal">
        <div class="modal-box">
            <div class="d-flex justify-content-between align-items-center mb-4">>
                <div class="modal-title">Add New Client</div>
                <button onclick="closeModal('addModal')" style="background:rgba(6,30,41,.08);border:none;width:3.4rem;height:3.4rem;border-radius:.8rem;cursor:pointer;font-size:1.6rem;color:var(--dark)"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" action="clients.php">
                <input type="hidden" name="action" value="add">
                <div class="form-row" style="margin-bottom:1.4rem">
                    <div><label class="gym-label">First Name *</label><input type="text" name="firstName" class="gym-input" placeholder="First name" required></div>
                    <div><label class="gym-label">Last Name *</label><input type="text" name="lastName" class="gym-input" placeholder="Last name" required></div>
                </div>
                <div style="margin-bottom:1.4rem"><label class="gym-label">Email *</label><input type="email" name="email" class="gym-input" placeholder="email@example.com" required></div>
                <div style="margin-bottom:1.4rem"><label class="gym-label">Phone</label><input type="text" name="phone" class="gym-input" placeholder="+92 000 0000"></div>
                <div class="form-row" style="margin-bottom:1.4rem">
                    <div><label class="gym-label">Plan</label>
                        <select name="plan" class="gym-select">
                            <option>Weight Loss</option>
                            <option>Muscle Gain</option>
                            <option>Cardio Endurance</option>
                            <option>Strength &amp; Power</option>
                            <option>Flexibility</option>
                        </select>
                    </div>
                    <div><label class="gym-label">Status</label>
                        <select name="status" class="gym-select">
                            <option>Active</option>
                            <option>Pending</option>
                            <option>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-row" style="margin-bottom:1.4rem">
                    <div><label class="gym-label">Progress (%)</label><input type="number" name="progress" class="gym-input" placeholder="0" min="0" max="100" value="0"></div>
                    <div><label class="gym-label">Sessions</label><input type="number" name="sessions" class="gym-input" placeholder="0" min="0" value="0"></div>
                </div>
                <div style="display:flex;gap:1rem;margin-top:2rem">
                    <button type="button" onclick="closeModal('addModal')" style="flex:1;height:4.6rem;border-radius:1rem;border:1.5px solid rgba(29,84,109,.2);background:transparent;font-family:'DM Sans',sans-serif;font-size:1.3rem;font-weight:600;color:var(--dark);cursor:pointer">Cancel</button>
                    <button type="submit" style="flex:2;height:4.6rem;border-radius:1rem;border:none;background:linear-gradient(135deg,var(--primary),var(--secondary));color:#fff;font-family:'DM Sans',sans-serif;font-size:1.3rem;font-weight:600;cursor:pointer">Add Client</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ══════════════════════════════════════════
     EDIT CLIENT MODAL
    ══════════════════════════════════════════ -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-box">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem">
                <div class="modal-title">Edit Client</div>
                <button onclick="closeModal('editModal')" style="background:rgba(6,30,41,.08);border:none;width:3.4rem;height:3.4rem;border-radius:.8rem;cursor:pointer;font-size:1.6rem;color:var(--dark)"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" action="clients.php">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-row" style="margin-bottom:1.4rem">
                    <div><label class="gym-label">First Name *</label><input type="text" name="firstName" id="edit_first" class="gym-input" required></div>
                    <div><label class="gym-label">Last Name *</label><input type="text" name="lastName" id="edit_last" class="gym-input" required></div>
                </div>
                <div style="margin-bottom:1.4rem"><label class="gym-label">Email *</label><input type="email" name="email" id="edit_email" class="gym-input" required></div>
                <div style="margin-bottom:1.4rem"><label class="gym-label">Phone</label><input type="text" name="phone" id="edit_phone" class="gym-input"></div>
                <div class="form-row" style="margin-bottom:1.4rem">
                    <div><label class="gym-label">Plan</label>
                        <select name="plan" id="edit_plan" class="gym-select">
                            <option>Weight Loss</option>
                            <option>Muscle Gain</option>
                            <option>Cardio Endurance</option>
                            <option>Strength &amp; Power</option>
                            <option>Flexibility</option>
                        </select>
                    </div>
                    <div><label class="gym-label">Status</label>
                        <select name="status" id="edit_status" class="gym-select">
                            <option>Active</option>
                            <option>Pending</option>
                            <option>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-row" style="margin-bottom:1.4rem">
                    <div><label class="gym-label">Progress (%)</label><input type="number" name="progress" id="edit_progress" class="gym-input" min="0" max="100"></div>
                    <div><label class="gym-label">Sessions</label><input type="number" name="sessions" id="edit_sessions" class="gym-input" min="0"></div>
                </div>
                <div style="display:flex;gap:1rem;margin-top:2rem">
                    <button type="button" onclick="closeModal('editModal')" style="flex:1;height:4.6rem;border-radius:1rem;border:1.5px solid rgba(29,84,109,.2);background:transparent;font-family:'DM Sans',sans-serif;font-size:1.3rem;font-weight:600;color:var(--dark);cursor:pointer">Cancel</button>
                    <button type="submit" style="flex:2;height:4.6rem;border-radius:1rem;border:none;background:linear-gradient(135deg,var(--primary),var(--secondary));color:#fff;font-family:'DM Sans',sans-serif;font-size:1.3rem;font-weight:600;cursor:pointer">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ══════════════════════════════════════════
     VIEW CLIENT MODAL (read-only)
    ══════════════════════════════════════════ -->
    <div class="modal-overlay" id="viewModal">
        <div class="modal-box">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem">
                <div class="modal-title">Client Details</div>
                <button onclick="closeModal('viewModal')" style="background:rgba(6,30,41,.08);border:none;width:3.4rem;height:3.4rem;border-radius:.8rem;cursor:pointer;font-size:1.6rem;color:var(--dark)"><i class="fas fa-times"></i></button>
            </div>
            <div style="display:flex;align-items:center;gap:1.6rem;margin-bottom:2.4rem">
                <div id="view_av" class="client-av" style="width:6rem;height:6rem;font-size:2rem"></div>
                <div>
                    <div id="view_name" style="font-size:1.8rem;font-weight:700;color:var(--dark)"></div>
                    <div id="view_email" style="font-size:1.2rem;color:rgba(6,30,41,.5)"></div>
                </div>
            </div>
            <div class="view-grid">
                <div class="view-field"><label>Phone</label>
                    <p id="view_phone"></p>
                </div>
                <div class="view-field"><label>Plan</label>
                    <p id="view_plan"></p>
                </div>
                <div class="view-field"><label>Progress</label>
                    <p id="view_progress"></p>
                </div>
                <div class="view-field"><label>Sessions</label>
                    <p id="view_sessions"></p>
                </div>
                <div class="view-field"><label>Join Date</label>
                    <p id="view_join"></p>
                </div>
                <div class="view-field"><label>Status</label>
                    <p id="view_status"></p>
                </div>
            </div>
            <div style="margin-top:2.4rem;text-align:right">
                <button onclick="closeModal('viewModal')" style="padding:.9rem 2.4rem;border-radius:1rem;border:1.5px solid rgba(29,84,109,.2);background:transparent;font-family:'DM Sans',sans-serif;font-size:1.3rem;font-weight:600;color:var(--dark);cursor:pointer">Close</button>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════
     DELETE CONFIRM MODAL
    ══════════════════════════════════════════ -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-box">
            <div class="del-icon"><i class="fas fa-trash"></i></div>
            <div class="modal-title" style="text-align:center;margin-bottom:.8rem">Remove Client</div>
            <p style="text-align:center;font-size:1.3rem;color:rgba(6,30,41,.6);margin-bottom:2.4rem">
                Are you sure you want to remove <strong id="delete_name"></strong>? This action cannot be undone.
            </p>
            <form method="POST" action="clients.php">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">
                <div style="display:flex;gap:1rem">
                    <button type="button" onclick="closeModal('deleteModal')" style="flex:1;height:4.6rem;border-radius:1rem;border:1.5px solid rgba(29,84,109,.2);background:transparent;font-family:'DM Sans',sans-serif;font-size:1.3rem;font-weight:600;color:var(--dark);cursor:pointer">Cancel</button>
                    <button type="submit" style="flex:1;height:4.6rem;border-radius:1rem;border:none;background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;font-family:'DM Sans',sans-serif;font-size:1.3rem;font-weight:600;cursor:pointer">Yes, Remove</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- <script src="js/script.js"></script> -->
    <script>
        // ── Data from PHP ──
        const clients = <?= clientsToJson($clients, $colours) ?>;
        // ── Logout ──
        function handleLogout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'login.php';
            }
        }

        // ── Sidebar ──
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

        // ── Modal helpers ──
        function openModal(id) {
            document.getElementById(id).classList.add('open');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('open');
        }

        // Close modal on overlay click
        document.querySelectorAll('.modal-overlay').forEach(m => {
            m.addEventListener('click', e => {
                if (e.target === m) m.classList.remove('open');
            });
        });

        // ── ADD modal ──
        function openAddModal() {
            openModal('addModal');
        }

        // ── EDIT modal — populate fields ──
        function openEditModal(id) {
            const c = clients.find(x => x.id == id);
            if (!c) return;
            document.getElementById('edit_id').value = c.id;
            document.getElementById('edit_first').value = c.firstName;
            document.getElementById('edit_last').value = c.lastName;
            document.getElementById('edit_email').value = c.email;
            document.getElementById('edit_phone').value = c.phone;
            document.getElementById('edit_progress').value = c.progress;
            document.getElementById('edit_sessions').value = c.sessions;
            setSelectValue('edit_plan', c.plan);
            setSelectValue('edit_status', c.status);
            openModal('editModal');
        }

        function setSelectValue(id, val) {
            const sel = document.getElementById(id);
            [...sel.options].forEach(o => o.selected = (o.value === val || o.text === val));
        }

        // ── VIEW modal ──
        function openViewModal(id) {
            const c = clients.find(x => x.id == id);
            if (!c) return;
            document.getElementById('view_av').style.background = c.color;
            document.getElementById('view_av').textContent = c.init;
            document.getElementById('view_name').textContent = c.name;
            document.getElementById('view_email').textContent = c.email;
            document.getElementById('view_phone').textContent = c.phone || '—';
            document.getElementById('view_plan').textContent = c.plan;
            document.getElementById('view_progress').textContent = c.progress + '%';
            document.getElementById('view_sessions').textContent = c.sessions;
            document.getElementById('view_join').textContent = c.join;
            document.getElementById('view_status').textContent = c.status;
            openModal('viewModal');
        }

        // ── DELETE modal ──
        function openDeleteModal(id, name) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_name').textContent = name;
            openModal('deleteModal');
        }

        let currentFilter = 'all';

        function renderTable(data) {
            const tb = document.getElementById('clientBody');
            tb.innerHTML = '';
            if (data.length === 0) {
                tb.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:3rem;color:rgba(6,30,41,.4);font-size:1.3rem">No clients found.</td></tr>`;
                return;
            }
            data.forEach(c => {
                const badgeClass = c.status === 'Active' ? 'status-active' : c.status === 'Inactive' ? 'status-inactive' : 'status-pending';
                tb.innerHTML += `<tr data-status="${c.status}">
            <td><div style="display:flex;align-items:center;gap:1.2rem">
                <div class="client-av" style="background:${c.color}">${c.init}</div>
                <div><div class="client-name-cell">${c.name}</div><div class="client-email">${c.email}</div></div>
            </div></td>
            <td>${c.plan}</td>
            <td><div style="display:flex;align-items:center;gap:.8rem"><div class="progress-sm"><div class="progress-sm-fill" style="width:${c.progress}%"></div></div><span style="font-size:1.15rem;font-weight:600;color:var(--primary)">${c.progress}%</span></div></td>
            <td><span style="font-weight:600">${c.sessions}</span></td>
            <td>${c.join}</td>
            <td><span class="badge-status ${badgeClass}">${c.status}</span></td>
            <td>
                <button class="action-btn view" title="View"   onclick="openViewModal(${c.id})"><i class="fas fa-eye"></i></button>
                <button class="action-btn edit" title="Edit"   onclick="openEditModal(${c.id})"><i class="fas fa-pen"></i></button>
                <button class="action-btn del"  title="Remove" onclick="openDeleteModal(${c.id}, '${c.name}')"><i class="fas fa-trash"></i></button>
            </td>
        </tr>`;
            });
        }

        renderTable(clients);

        // ── Filter tabs ──
        function filterClients(status, btn) {
            currentFilter = status;
            document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
            btn.classList.add('active');
            const filtered = status === 'all' ? clients : clients.filter(c => c.status === status);
            applyFilters();
        }

        // ── Search ──
        document.getElementById('clientSearch').addEventListener('input', applyFilters);

        function applyFilters() {
            const q = document.getElementById('clientSearch').value.toLowerCase();
            const filtered = clients.filter(c =>
                (currentFilter === 'all' || c.status === currentFilter) &&
                (c.name.toLowerCase().includes(q) || c.plan.toLowerCase().includes(q) || c.email.toLowerCase().includes(q))
            );
            renderTable(filtered);
        }

        // ── Auto-dismiss toast ──
        const toast = document.getElementById('toastWrap');
        if (toast) setTimeout(() => toast.remove(), 4200);
    </script>
</body>

</html>