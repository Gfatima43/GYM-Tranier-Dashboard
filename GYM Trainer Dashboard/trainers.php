<?php
session_start();

$database = require 'bootstrap.php';
$pdo = $database->pdo;

$logged_in = isset($_SESSION['user_id']);
$user_name  = $_SESSION['user_name'] ?? '';
$user_role  = $_SESSION['role']      ?? '';
// $currentPage = 'trainers';

$message     = '';
$messageType = '';

// ────────────────────────────────────────────
// CRUD — handle POST actions
// ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── CREATE ──
    if ($action === 'add') {
        $name    = trim($_POST['name']               ?? '');
        $email   = trim($_POST['email']              ?? '');
        $phone   = trim($_POST['phone']              ?? '');
        $spec    = trim($_POST['specialization']     ?? '');
        $exp     = (int)($_POST['experience_years']  ?? 0);
        $bio     = trim($_POST['bio']                ?? '');
        $status  = $_POST['status']                  ?? 'Active';
        $rawPass = $_POST['password']                ?? '';
        $confPas = $_POST['confirm_password']        ?? '';

        if (empty($name) || empty($email)) {
            $message = 'Name and email are required.';
            $messageType = 'danger';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address.';
            $messageType = 'danger';
        } elseif (empty($rawPass)) {
            $message = 'Password is required for new trainers.';
            $messageType = 'danger';
        } elseif (strlen($rawPass) < 6) {
            $message = 'Password must be at least 6 characters.';
            $messageType = 'danger';
        } elseif ($rawPass !== $confPas) {
            $message = 'Passwords do not match.';
            $messageType = 'danger';
        } else {
            // Duplicate email check
            $check = $pdo->prepare("SELECT id FROM trainers WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                $message = 'A trainer with this email already exists.';
                $messageType = 'danger';
            } else {
                $stmt = $pdo->prepare("INSERT INTO trainers
                    (name, email, phone, specialization, experience_years, bio, status, password)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $name,
                    $email,
                    $phone,
                    $spec,
                    $exp,
                    $bio,
                    $status,
                    password_hash($rawPass, PASSWORD_BCRYPT)
                ]);
                $message = "Trainer {$name} added successfully!";
                $messageType = 'success';
            }
        }
    }

    // ── UPDATE ──
    elseif ($action === 'edit') {
        $id      = (int)($_POST['id'] ?? 0);
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $spec    = trim($_POST['specialization'] ?? '');
        $exp     = (int)($_POST['experience_years']  ?? 0);
        $bio     = trim($_POST['bio']                ?? '');
        $status  = $_POST['status']  ?? 'Active';
        $rawPass = $_POST['password']  ?? '';
        $confPas = $_POST['confirm_password'] ?? '';

        if (empty($name) || empty($email) || $id < 1) {
            $message = 'All required fields must be filled in.';
            $messageType = 'danger';
        } elseif ($rawPass && strlen($rawPass) < 6) {
            $message = 'Password must be at least 6 characters.';
            $messageType = 'danger';
        } elseif ($rawPass && $rawPass !== $confPas) {
            $message = 'Passwords do not match.';
            $messageType = 'danger';
        } else {
            if ($rawPass) {
                $stmt = $pdo->prepare("UPDATE trainers SET
                    name=?, email=?, phone=?, specialization=?, experience_years=?,
                    bio=?, status=?, password=? WHERE id=?");
                $stmt->execute([
                    $name,
                    $email,
                    $phone,
                    $spec,
                    $exp,
                    $bio,
                    $status,
                    password_hash($rawPass, PASSWORD_BCRYPT),
                    $id
                ]);
            } else {
                $stmt = $pdo->prepare("UPDATE trainers SET
                    name=?, email=?, phone=?, specialization=?, experience_years=?,
                    bio=?, status=? WHERE id=?");
                $stmt->execute([$name, $email, $phone, $spec, $exp, $bio, $status, $id]);
            }
            $message = "Trainer {$name} updated successfully!";
            $messageType = 'success';
        }
    }

    // ── DELETE ──
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // Unassign clients before deleting
            $pdo->prepare("UPDATE clients SET trainer_id = NULL WHERE trainer_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM trainers WHERE id = ?")->execute([$id]);
            $message = 'Trainer removed successfully.';
            $messageType = 'success';
        }
    }

    // ── ASSIGN CLIENTS ──
    elseif ($action === 'assign') {
        $trainerId  = (int)($_POST['trainer_id']   ?? 0);
        $clientIds  = $_POST['client_ids'] ?? [];   // array of client IDs to assign

        if ($trainerId > 0) {
            $now = date('Y-m-d H:i:s');

            // Unassign all clients currently linked to this trainer
            $pdo->prepare("DELETE FROM trainer_clients WHERE trainer_id = ?")->execute([$trainerId]);

            if (!empty($clientIds)) {
                $stmt = $pdo->prepare("INSERT INTO trainer_clients (trainer_id, client_id, assigned_date, status) VALUES (?, ?, ?, 'active')");
                foreach ($clientIds as $cid) {
                    $stmt->execute([$trainerId, (int)$cid, $now]);
                }

                // Re-assign selected clients
                $updateClientStmt = $pdo->prepare("UPDATE clients SET trainer_id = ? WHERE id = ?");
                foreach ($clientIds as $cid) {
                    $updateClientStmt->execute([$trainerId, (int)$cid]);
                }
            }

            $message = 'Client assigned updated successfully!';
            $messageType = 'success';
        }
    }

    // PRG — redirect to avoid form re-submission
    $_SESSION['flash'] = ['msg' => $message, 'type' => $messageType];
    header('Location: trainers.php');
    exit();
}

// ── Grab flash message ──
if (isset($_SESSION['flash'])) {
    $message     = $_SESSION['flash']['msg'];
    $messageType = $_SESSION['flash']['type'];
    unset($_SESSION['flash']);
}

// ── READ — fetch all trainers with client count ──
$stmt  = $pdo->query("SELECT t.*, COUNT(c.id) AS client_count
                        FROM trainers t
                        LEFT JOIN clients c ON c.trainer_id = t.id
                        GROUP BY t.id
                        ORDER BY t.id DESC");
$trainers = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total    = count($trainers);

// ── READ — fetch all clients (for assign modal) ──
$allClients = $pdo->query("SELECT id, firstName, lastName, email, trainer_id FROM clients ORDER BY firstName ASC")->fetchAll(PDO::FETCH_ASSOC);

// Avatar colours
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

function trainersToJson(array $trainers, array $colours): string
{
    $out = [];
    foreach ($trainers as $t) {
        $initials = strtoupper(substr($t['name'], 0, 1));
        if (strpos($t['name'], ' ') !== false) {
            $parts    = explode(' ', $t['name'], 2);
            $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
        }
        $out[] = [
            'id'    => $t['id'],
            'init'  => $initials,
            'color' => $colours[$t['id'] % count($colours)],
            'name'   => htmlspecialchars($t['name'],   ENT_QUOTES),
            'email'  => htmlspecialchars($t['email'],  ENT_QUOTES),
            'phone'  => htmlspecialchars($t['phone'] ?? '',  ENT_QUOTES),
            'specialization'   => htmlspecialchars($t['specialization'] ?? '', ENT_QUOTES),
            'experience_years' => (int)($t['experience_years'] ?? 0),
            'bio' => htmlspecialchars($t['bio'] ?? '',        ENT_QUOTES),
            'status' => $t['status'],
            'client_count'     => (int)$t['client_count'],
        ];
    }
    return json_encode($out, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
}

function clientsToJson(array $clients): string
{
    $out = [];
    foreach ($clients as $c) {
        $out[] = [
            'id'         => $c['id'],
            'name'       => htmlspecialchars($c['firstName'] . ' ' . $c['lastName'], ENT_QUOTES),
            'email'      => htmlspecialchars($c['email'], ENT_QUOTES),
            'trainer_id' => $c['trainer_id'] ? (int)$c['trainer_id'] : null,
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
    <title>Trainers - GYM Trainer</title>
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
                <div class="topbar-title">Trainers</div>
                <div class="topbar-subtitle"><?= $total ?> trainers total</div>
            </div>
        </div>
        <div class="topbar-search">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="trainerSearch" placeholder="Search trainers…">
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
                    <div class="user-avatar-sm"><?php echo substr($_SESSION['user_name'], 0, 2); ?></div>
                    <span class="user-name-sm"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <i class="fas fa-chevron-down" style="font-size:1rem;color:rgba(6,30,41,.4);margin-left:.4rem"></i>
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
                <span class="section-title">All Trainers</span>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="filter-tabs">
                        <button class="filter-tab active" onclick="filterTrainers('all',this)">All</button>
                        <button class="filter-tab" onclick="filterTrainers('Active',this)">Active</button>
                        <button class="filter-tab" onclick="filterTrainers('Inactive',this)">Inactive</button>
                    </div>
                    <button class="btn-add" onclick="document.getElementById('addModal').classList.add('open')">
                        <i class="fas fa-plus"></i>Add Trainer
                    </button>
                </div>
            </div>
            <div style="overflow-x:auto">
                <table class="client-table" id="trainerTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Trainer</th>
                            <th>Specialization</th>
                            <th>Experience</th>
                            <th>Clients</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="trainerBody"></tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- ══════════════════════════════════════════
         ADD TRAINER MODAL
    ══════════════════════════════════════════ -->
    <div class="modal-overlay" id="addModal">
        <div class="modal-box">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="modal-title">Add New Trainer</div>
                <button onclick="closeModal('addModal')" style="background:rgba(6,30,41,.08);border:none;width:3.4rem;height:3.4rem;border-radius:.8rem;cursor:pointer;font-size:1.6rem;color:var(--dark)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="trainers.php">
                <input type="hidden" name="action" value="add">
                <div class="form-row" style="margin-bottom:1.4rem">
                    <div><label class="gym-label">Full Name *</label><input type="text" name="name" class="gym-input" placeholder="e.g. Abdul Rahman" required></div>
                    <div><label class="gym-label">Email *</label><input type="email" name="email" class="gym-input" placeholder="trainer@gym.com" required></div>
                </div>
                <div class="form-row" style="margin-bottom:1.4rem">
                    <div><label class="gym-label">Phone</label><input type="text" name="phone" class="gym-input" placeholder="+92 000 0000"></div>
                    <div><label class="gym-label">Experience (years)</label><input type="number" name="experience_years" class="gym-input" placeholder="0" min="0" max="50" value="0"></div>
                </div>
                <div class="form-row" style="margin-bottom:1.4rem">
                    <div><label class="gym-label">Specialization</label><input type="text" name="specialization" class="gym-input" placeholder="e.g. Weight Loss &amp; HIIT"></div>
                    <div><label class="gym-label">Status</label>
                        <select name="status" class="gym-select">
                            <option>Active</option>
                            <option>Inactive</option>
                        </select>
                    </div>
                </div>
                <div style="margin-bottom:1.4rem">
                    <label class="gym-label">Bio</label>
                    <textarea name="bio" class="gym-input" rows="3" placeholder="Short description…" style="resize:vertical;min-height:8rem"></textarea>
                </div>
                <div class="form-row" style="margin-bottom:1.4rem">
                    <div>
                        <label class="gym-label">Password *</label>
                        <div style="position:relative">
                            <input type="password" name="password" id="add_pass" class="gym-input" placeholder="Min 6 characters" style="padding-right:4rem" required>
                            <button type="button" onclick="togglePwd('add_pass','add_pass_eye')" style="position:absolute;right:1.2rem;top:50%;transform:translateY(-50%);background:none;border:none;color:rgba(6,30,41,.4);cursor:pointer;font-size:1.5rem">
                                <i id="add_pass_eye" class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="gym-label">Confirm Password *</label>
                        <input type="password" name="confirm_password" class="gym-input" placeholder="Repeat password" required>
                    </div>
                </div>
                <div style="display:flex;gap:1rem;margin-top:2rem">
                    <button type="button" onclick="closeModal('addModal')" style="flex:1;height:4.6rem;border-radius:1rem;border:1.5px solid rgba(29,84,109,.2);background:transparent;font-family:'DM Sans',sans-serif;font-size:1.3rem;font-weight:600;color:var(--dark);cursor:pointer">Cancel</button>
                    <button type="submit" style="flex:2;height:4.6rem;border-radius:1rem;border:none;background:linear-gradient(135deg,var(--primary),var(--secondary));color:#fff;font-family:'DM Sans',sans-serif;font-size:1.3rem;font-weight:600;cursor:pointer">Add Trainer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ══════════════════════════════════════════
         EDIT TRAINER MODAL
    ══════════════════════════════════════════ -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-box">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem">
                <div class="modal-title">Edit Trainer</div>
                <button onclick="closeModal('editModal')" style="background:rgba(6,30,41,.08);border:none;width:3.4rem;height:3.4rem;border-radius:.8rem;cursor:pointer;font-size:1.6rem;color:var(--dark)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="trainers.php">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-row" style="margin-bottom:1.4rem">
                    <div><label class="gym-label">Full Name *</label><input type="text" name="name" id="edit_name" class="gym-input" required></div>
                    <div><label class="gym-label">Email *</label><input type="email" name="email" id="edit_email" class="gym-input" required></div>
                </div>
                <div class="form-row" style="margin-bottom:1.4rem">
                    <div><label class="gym-label">Phone</label><input type="text" name="phone" id="edit_phone" class="gym-input"></div>
                    <div><label class="gym-label">Experience (years)</label><input type="number" name="experience_years" id="edit_exp" class="gym-input" min="0" max="50"></div>
                </div>
                <div class="form-row" style="margin-bottom:1.4rem">
                    <div><label class="gym-label">Specialization</label><input type="text" name="specialization" id="edit_spec" class="gym-input"></div>
                    <div><label class="gym-label">Status</label>
                        <select name="status" id="edit_status" class="gym-select">
                            <option>Active</option>
                            <option>Inactive</option>
                        </select>
                    </div>
                </div>
                <div style="margin-bottom:1.4rem">
                    <label class="gym-label">Bio</label>
                    <textarea name="bio" id="edit_bio" class="gym-input" rows="3" style="resize:vertical;min-height:8rem"></textarea>
                </div>
                <div class="form-row" style="margin-bottom:1.4rem">
                    <div>
                        <label class="gym-label">Password <span style="opacity:.5;font-weight:400">(leave blank to keep)</span></label>
                        <div style="position:relative">
                            <input type="password" name="password" id="edit_pass" class="gym-input" placeholder="Min 6 characters" style="padding-right:4rem">
                            <button type="button" onclick="togglePwd('edit_pass','edit_pass_eye')" style="position:absolute;right:1.2rem;top:50%;transform:translateY(-50%);background:none;border:none;color:rgba(6,30,41,.4);cursor:pointer;font-size:1.5rem">
                                <i id="edit_pass_eye" class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div><label class="gym-label">Confirm Password</label><input type="password" name="confirm_password" class="gym-input" placeholder="Repeat password"></div>
                </div>
                <div style="display:flex;gap:1rem;margin-top:2rem">
                    <button type="button" onclick="closeModal('editModal')" style="flex:1;height:4.6rem;border-radius:1rem;border:1.5px solid rgba(29,84,109,.2);background:transparent;font-family:'DM Sans',sans-serif;font-size:1.3rem;font-weight:600;color:var(--dark);cursor:pointer">Cancel</button>
                    <button type="submit" style="flex:2;height:4.6rem;border-radius:1rem;border:none;background:linear-gradient(135deg,var(--primary),var(--secondary));color:#fff;font-family:'DM Sans',sans-serif;font-size:1.3rem;font-weight:600;cursor:pointer">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ══════════════════════════════════════════
         VIEW TRAINER MODAL (read-only)
    ══════════════════════════════════════════ -->
    <div class="modal-overlay" id="viewModal">
        <div class="modal-box">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem">
                <div class="modal-title">Trainer Details</div>
                <button onclick="closeModal('viewModal')" style="background:rgba(6,30,41,.08);border:none;width:3.4rem;height:3.4rem;border-radius:.8rem;cursor:pointer;font-size:1.6rem;color:var(--dark)">
                    <i class="fas fa-times"></i>
                </button>
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
                <div class="view-field"><label>Specialization</label>
                    <p id="view_spec"></p>
                </div>
                <div class="view-field"><label>Experience</label>
                    <p id="view_exp"></p>
                </div>
                <div class="view-field"><label>Clients Assigned</label>
                    <p id="view_clients"></p>
                </div>
                <div class="view-field"><label>Status</label>
                    <p id="view_status"></p>
                </div>
            </div>
            <div id="view_bio_wrap" style="margin-top:1.6rem">
                <div class="view-field"><label>Bio</label>
                    <p id="view_bio" style="white-space:pre-line"></p>
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
            <div class="modal-title" style="text-align:center;margin-bottom:.8rem">Remove Trainer</div>
            <p style="text-align:center;font-size:1.3rem;color:rgba(6,30,41,.6);margin-bottom:2.4rem">
                Are you sure you want to remove <strong id="delete_name"></strong>?
                All client assignments will be cleared. This action cannot be undone.
            </p>
            <form method="POST" action="trainers.php">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">
                <div style="display:flex;gap:1rem">
                    <button type="button" onclick="closeModal('deleteModal')" style="flex:1;height:4.6rem;border-radius:1rem;border:1.5px solid rgba(29,84,109,.2);background:transparent;font-family:'DM Sans',sans-serif;font-size:1.3rem;font-weight:600;color:var(--dark);cursor:pointer">Cancel</button>
                    <button type="submit" style="flex:1;height:4.6rem;border-radius:1rem;border:none;background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;font-family:'DM Sans',sans-serif;font-size:1.3rem;font-weight:600;cursor:pointer">Yes, Remove</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ══════════════════════════════════════════
         ASSIGN CLIENTS MODAL
    ══════════════════════════════════════════ -->
    <div class="modal-overlay" id="assignModal">
        <div class="modal-box" style="max-width:54rem">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem">
                <div>
                    <div class="modal-title">Assign Clients</div>
                    <div id="assign_trainer_name" style="font-size:1.2rem;color:rgba(6,30,41,.5);margin-top:.3rem"></div>
                </div>
                <button onclick="closeModal('assignModal')" style="background:rgba(6,30,41,.08);border:none;width:3.4rem;height:3.4rem;border-radius:.8rem;cursor:pointer;font-size:1.6rem;color:var(--dark)">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <input type="text" id="assignSearch" class="assign-search" placeholder="Search clients by name or email…" oninput="filterAssignList()">
            <div class="assign-meta">
                <span id="assign_selected_count">0</span> clients selected
                &nbsp;·&nbsp;
                <a href="#" onclick="selectAllAssign();return false;" style="color:var(--primary)">Select all</a>
                &nbsp;/&nbsp;
                <a href="#" onclick="clearAllAssign();return false;" style="color:rgba(6,30,41,.4)">Clear</a>
            </div>

            <form method="POST" action="trainers.php" id="assignForm">
                <input type="hidden" name="action" value="assign">
                <input type="hidden" name="trainer_id" id="assign_trainer_id">
                <div class="client-check-list" id="assignList"></div>

                <div style="display:flex;gap:1rem;margin-top:2rem">
                    <button type="button" onclick="closeModal('assignModal')" style="flex:1;height:4.6rem;border-radius:1rem;border:1.5px solid rgba(29,84,109,.2);background:transparent;font-family:'DM Sans',sans-serif;font-size:1.3rem;font-weight:600;color:var(--dark);cursor:pointer">Cancel</button>
                    <button type="submit" style="flex:2;height:4.6rem;border-radius:1rem;border:none;background:linear-gradient(135deg,var(--primary),var(--secondary));color:#fff;font-family:'DM Sans',sans-serif;font-size:1.3rem;font-weight:600;cursor:pointer">
                        <i class="fas fa-link" style="margin-right:.6rem"></i>Save Assignments
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ── Data from PHP ──
        const trainers = <?= trainersToJson($trainers, $colours) ?>;
        const allClients = <?= clientsToJson($allClients) ?>;

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

        document.querySelectorAll('.modal-overlay').forEach(m => {
            m.addEventListener('click', e => {
                if (e.target === m) m.classList.remove('open');
            });
        });

        // ── Password toggle ──
        function togglePwd(inputId, iconId) {
            const inp = document.getElementById(inputId);
            const ico = document.getElementById(iconId);
            if (inp.type === 'password') {
                inp.type = 'text';
                ico.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                inp.type = 'password';
                ico.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // ── ADD modal ──
        function openAddModal() {
            openModal('addModal');
        }

        // ── EDIT modal — populate fields ──
        function openEditModal(id) {
            const t = trainers.find(x => x.id == id);
            if (!t) return;
            document.getElementById('edit_id').value = t.id;
            document.getElementById('edit_name').value = t.name;
            document.getElementById('edit_email').value = t.email;
            document.getElementById('edit_phone').value = t.phone;
            document.getElementById('edit_exp').value = t.experience_years;
            document.getElementById('edit_spec').value = t.specialization;
            document.getElementById('edit_bio').value = t.bio;
            document.getElementById('edit_pass').value = '';
            setSelectValue('edit_status', t.status);
            openModal('editModal');
        }

        function setSelectValue(id, val) {
            const sel = document.getElementById(id);
            [...sel.options].forEach(o => o.selected = (o.value === val || o.text === val));
        }

        // ── VIEW modal ──
        function openViewModal(id) {
            const t = trainers.find(x => x.id == id);
            if (!t) return;
            document.getElementById('view_av').style.background = t.color;
            document.getElementById('view_av').textContent = t.init;
            document.getElementById('view_name').textContent = t.name;
            document.getElementById('view_email').textContent = t.email;
            document.getElementById('view_phone').textContent = t.phone || '—';
            document.getElementById('view_spec').textContent = t.specialization || '—';
            document.getElementById('view_exp').textContent = t.experience_years + (t.experience_years === 1 ? ' year' : ' years');
            document.getElementById('view_clients').textContent = t.client_count + ' assigned';
            document.getElementById('view_status').textContent = t.status;
            document.getElementById('view_bio').textContent = t.bio || '—';
            openModal('viewModal');
        }

        // ── DELETE modal ──
        function openDeleteModal(id, name) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_name').textContent = name;
            openModal('deleteModal');
        }

        // ── ASSIGN CLIENTS modal ──
        let assignTrainerId = null;

        function openAssignModal(id) {
            assignTrainerId = id;
            const t = trainers.find(x => x.id == id);
            if (!t) return;

            document.getElementById('assign_trainer_id').value = id;
            document.getElementById('assign_trainer_name').textContent = 'Trainer: ' + t.name;
            document.getElementById('assignSearch').value = '';

            renderAssignList(allClients);
            updateAssignCount();
            openModal('assignModal');
        }

        function renderAssignList(clients) {
            const list = document.getElementById('assignList');
            list.innerHTML = '';

            if (clients.length === 0) {
                list.innerHTML = `<div style="text-align:center;padding:2rem;color:rgba(6,30,41,.4);font-size:1.3rem">No clients found.</div>`;
                return;
            }

            clients.forEach(c => {
                const isChecked = (c.trainer_id == assignTrainerId);
                const isOther = (c.trainer_id !== null && c.trainer_id != assignTrainerId);
                const badge = isOther ? `<span class="badge-assigned">assigned elsewhere</span>` : '';
                const checkId = `chk_${c.id}`;

                const item = document.createElement('label');
                item.className = 'client-check-item';
                item.htmlFor = checkId;
                item.innerHTML = `
                    <input type="checkbox"
                           id="${checkId}"
                           name="client_ids[]"
                           value="${c.id}"
                           form="assignForm"
                           ${isChecked ? 'checked' : ''}
                           onchange="updateAssignCount()">
                    <div>
                        <div class="ci-name">${c.name}${badge}</div>
                        <div class="ci-email">${c.email}</div>
                    </div>`;
                list.appendChild(item);
            });
        }

        function filterAssignList() {
            const q = document.getElementById('assignSearch').value.toLowerCase();
            const filtered = allClients.filter(c =>
                c.name.toLowerCase().includes(q) || c.email.toLowerCase().includes(q)
            );
            renderAssignList(filtered);
            updateAssignCount();
        }

        function updateAssignCount() {
            const checked = document.querySelectorAll('#assignForm input[name="client_ids[]"]:checked').length;
            document.getElementById('assign_selected_count').textContent = checked;
        }

        function selectAllAssign() {
            document.querySelectorAll('#assignList input[type="checkbox"]').forEach(cb => cb.checked = true);
            updateAssignCount();
        }

        function clearAllAssign() {
            document.querySelectorAll('#assignList input[type="checkbox"]').forEach(cb => cb.checked = false);
            updateAssignCount();
        }

        // ── Render table ──
        let currentFilter = 'all';

        function renderTable(data) {
            const tb = document.getElementById('trainerBody');
            tb.innerHTML = '';
            if (data.length === 0) {
                tb.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:3rem;color:rgba(6,30,41,.4);font-size:1.3rem">No trainers found.</td></tr>`;
                return;
            }
            data.forEach(t => {
                const badgeClass = t.status === 'Active' ? 'status-active' : 'status-inactive';
                tb.innerHTML += `<tr>
                    <td>${t.id}</td>
                    <td><div style="display:flex;align-items:center;gap:1.2rem">
                        <div class="client-av" style="background:${t.color}">${t.init}</div>
                        <div>
                            <div class="client-name-cell">${t.name}</div>
                            <div class="client-email">${t.email}</div>
                        </div>
                    </div></td>
                    <td>${t.specialization || '<span style="color:rgba(6,30,41,.3)">—</span>'}</td>
                    <td><span style="font-weight:600">${t.experience_years}</span> yr${t.experience_years !== 1 ? 's' : ''}</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:.6rem">
                            <i class="fas fa-users" style="color:var(--primary);font-size:1.1rem"></i>
                            <span style="font-weight:600">${t.client_count}</span>
                        </div>
                    </td>
                    <td><span class="badge-status ${badgeClass}">${t.status}</span></td>
                    <td>
                        <button class="action-btn view"  title="View"            onclick="openViewModal(${t.id})"><i class="fas fa-eye"></i></button>
                        <button class="action-btn edit"  title="Edit"            onclick="openEditModal(${t.id})"><i class="fas fa-pen"></i></button>
                        <button class="action-btn assign" title="Assign Clients" onclick="openAssignModal(${t.id})" style="background:rgba(139,92,246,.1);color:#7c3aed"><i class="fas fa-link"></i></button>
                        <button class="action-btn del"   title="Remove"          onclick="openDeleteModal(${t.id},'${t.name}')"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>`;
            });
        }

        renderTable(trainers);

        // ── Filter tabs ──
        function filterTrainers(status, btn) {
            currentFilter = status;
            document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
            btn.classList.add('active');
            const filtered = status === 'all' ? trainers : trainers.filter(t => t.status === status);
            applyFilters();
        }

        // ── Search ──
        document.getElementById('trainerSearch').addEventListener('input', applyFilters);

        function applyFilters() {
            const q = document.getElementById('trainerSearch').value.toLowerCase();
            const filtered = trainers.filter(t =>
                (currentFilter === 'all' || t.status === currentFilter) &&
                (t.name.toLowerCase().includes(q) ||
                    t.email.toLowerCase().includes(q) ||
                    t.specialization.toLowerCase().includes(q))
            );
            renderTable(filtered);
        }

        // ── Auto-dismiss toast ──
        const toast = document.getElementById('toastWrap');
        if (toast) setTimeout(() => toast.remove(), 4200);
    </script>
</body>

</html>