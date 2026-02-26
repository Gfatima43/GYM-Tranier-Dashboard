<?php
// ============================================================
//  trainers.php  –  Add / Edit / Delete / View Trainers
// ============================================================
// session_start();
// if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit(); }
$database = require './bootstrap.php';
$pdo = $database->pdo;

$message = '';
$msgType = '';

// ── DELETE ───────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        // Unassign clients first (set trainer_id = NULL)
        $pdo->prepare("UPDATE clients SET trainer_id = NULL WHERE trainer_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM trainers WHERE id = ?")->execute([$id]);
        $message = 'Trainer deleted successfully.';
        $msgType = 'success';
    } catch (Exception $e) {
        $message = 'Error deleting trainer.';
        $msgType = 'error';
    }
}

// ── TOGGLE STATUS ─────────────────────────────────────────────
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE trainers SET status = IF(status='active','inactive','active') WHERE id=?")->execute([$id]);
    header('Location: trainers.php'); exit();
}

// ── ADD / EDIT (POST) ─────────────────────────────────────────
$editTrainer = null;
if (isset($_GET['edit'])) {
    $editTrainer = $pdo->prepare("SELECT * FROM trainers WHERE id=?");
    $editTrainer->execute([(int)$_GET['edit']]);
    $editTrainer = $editTrainer->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tid          = (int)($_POST['trainer_id'] ?? 0);
    $name         = trim($_POST['name']              ?? '');
    $email        = trim($_POST['email']             ?? '');
    $phone        = trim($_POST['phone']             ?? '');
    $spec         = trim($_POST['specialization']    ?? '');
    $exp          = (int)($_POST['experience_years'] ?? 0);
    $bio          = trim($_POST['bio']               ?? '');
    $status       =      $_POST['status']            ?? 'active';
    $rawPass      =      $_POST['password']          ?? '';
    $confirmPass  =      $_POST['confirm_password']  ?? '';

    $errors = [];
    if (!$name)  $errors[] = 'Name is required.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (!$tid && !$rawPass)                $errors[] = 'Password is required for new trainers.';
    if ($rawPass && strlen($rawPass) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($rawPass && $rawPass !== $confirmPass) $errors[] = 'Passwords do not match.';

    // Duplicate email check
    $dup = $pdo->prepare("SELECT id FROM trainers WHERE email=? AND id!=?");
    $dup->execute([$email, $tid]);
    if ($dup->fetch()) $errors[] = 'A trainer with this email already exists.';

    if ($errors) {
        $message = implode(' ', $errors);
        $msgType = 'error';
        // Restore edit mode so form stays open
        if ($tid) {
            $editTrainer = compact('name','email','phone','spec','exp','bio','status');
            $editTrainer['id'] = $tid;
        }
    } else {
        if ($tid) {
            // UPDATE
            if ($rawPass) {
                $pdo->prepare(
                    "UPDATE trainers SET name=?,email=?,phone=?,specialization=?,experience_years=?,bio=?,status=?,password=? WHERE id=?"
                )->execute([$name,$email,$phone,$spec,$exp,$bio,$status,password_hash($rawPass,PASSWORD_BCRYPT),$tid]);
            } else {
                $pdo->prepare(
                    "UPDATE trainers SET name=?,email=?,phone=?,specialization=?,experience_years=?,bio=?,status=? WHERE id=?"
                )->execute([$name,$email,$phone,$spec,$exp,$bio,$status,$tid]);
            }
            $message = 'Trainer updated successfully.';
        } else {
            // INSERT
            $pdo->prepare(
                "INSERT INTO trainers (name,email,phone,specialization,experience_years,bio,status,password) VALUES (?,?,?,?,?,?,?,?)"
            )->execute([$name,$email,$phone,$spec,$exp,$bio,$status,password_hash($rawPass,PASSWORD_BCRYPT)]);
            $message = 'Trainer added successfully.';
        }
        $msgType    = 'success';
        $editTrainer = null;
    }
}

// ── FETCH ALL TRAINERS with client count ──────────────────────
$search    = trim($_GET['q'] ?? '');
$filterSt  = $_GET['status'] ?? 'all';
$params    = [];
$where     = "WHERE 1=1";
if ($search) {
    $where   .= " AND (t.name LIKE ? OR t.email LIKE ? OR t.specialization LIKE ?)";
    $params   = array_merge($params, ["%$search%","%$search%","%$search%"]);
}
if ($filterSt !== 'all') {
    $where  .= " AND t.status = ?";
    $params[] = $filterSt;
}

$stmt = $pdo->prepare(
    "SELECT t.*,
            COUNT(DISTINCT tc.client_id) AS client_count
     FROM trainers t
     LEFT JOIN trainer_clients tc ON tc.trainer_id = t.id AND tc.status='active'
     $where
     GROUP BY t.id
     ORDER BY t.created_at DESC"
);
$stmt->execute($params);
$trainers = $stmt->fetchAll();

// Stats
$totalTrainers  = (int)$pdo->query("SELECT COUNT(*) FROM trainers")->fetchColumn();
$activeTrainers = (int)$pdo->query("SELECT COUNT(*) FROM trainers WHERE status='active'")->fetchColumn();
$assignedClients= (int)$pdo->query("SELECT COUNT(DISTINCT client_id) FROM trainer_clients WHERE status='active'")->fetchColumn();

// Open modal if we have an edit target or a validation error on new form
$openModal = ($editTrainer !== null || ($msgType === 'error' && isset($_POST['trainer_id'])));

$activePage   = 'trainers';
$pageTitle    = 'Trainers';
$pageSubtitle = $activeTrainers . ' active trainers';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainers – GymPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once 'assets/sidebar.php'; ?>
<?php require_once 'assets/topbar.php';  ?>

<main class="main-content">

    <?php if ($message): ?>
        <div class="alert-gym alert-<?= $msgType === 'success' ? 'success' : 'error' ?> mb-4">
            <i class="fas fa-<?= $msgType === 'success' ? 'circle-check' : 'circle-exclamation' ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- KPI Cards -->
    <div class="kpi-grid" style="grid-template-columns:repeat(3,1fr);max-width:90rem">
        <div class="kpi-card">
            <div class="kpi-top-bar" style="background:linear-gradient(90deg,#1D546D,#5F9598)"></div>
            <div class="kpi-icon" style="background:rgba(29,84,109,.1);color:var(--primary)"><i class="fas fa-user-tie"></i></div>
            <div class="kpi-label">Total Trainers</div>
            <div class="kpi-value"><?= $totalTrainers ?></div>
        </div>
        <div class="kpi-card" style="animation-delay:.07s">
            <div class="kpi-top-bar" style="background:linear-gradient(90deg,#22c55e,#16a34a)"></div>
            <div class="kpi-icon" style="background:rgba(34,197,94,.1);color:#16a34a"><i class="fas fa-circle-check"></i></div>
            <div class="kpi-label">Active Trainers</div>
            <div class="kpi-value"><?= $activeTrainers ?></div>
        </div>
        <div class="kpi-card" style="animation-delay:.14s">
            <div class="kpi-top-bar" style="background:linear-gradient(90deg,#8b5cf6,#7c3aed)"></div>
            <div class="kpi-icon" style="background:rgba(139,92,246,.1);color:#7c3aed"><i class="fas fa-users"></i></div>
            <div class="kpi-label">Assigned Clients</div>
            <div class="kpi-value"><?= $assignedClients ?></div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="section-card">
        <div class="section-header">
            <span class="section-title">All Trainers</span>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <!-- Search -->
                <form method="GET" class="d-flex" style="gap:.8rem">
                    <div style="position:relative">
                        <i class="fas fa-search" style="position:absolute;left:1.2rem;top:50%;transform:translateY(-50%);color:var(--secondary);font-size:1.2rem"></i>
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                               placeholder="Search trainers…"
                               style="height:4rem;padding:0 1.2rem 0 3.6rem;border:1.5px solid rgba(29,84,109,.15);border-radius:1rem;font-size:1.3rem;outline:none;font-family:'DM Sans',sans-serif;width:22rem">
                    </div>
                    <!-- Status filter -->
                    <select name="status" onchange="this.form.submit()"
                            style="height:4rem;padding:0 1.2rem;border:1.5px solid rgba(29,84,109,.15);border-radius:1rem;font-size:1.3rem;font-family:'DM Sans',sans-serif;outline:none;color:var(--dark)">
                        <option value="all"     <?= $filterSt==='all'     ?'selected':'' ?>>All Status</option>
                        <option value="active"  <?= $filterSt==='active'  ?'selected':'' ?>>Active</option>
                        <option value="inactive"<?= $filterSt==='inactive'?'selected':'' ?>>Inactive</option>
                    </select>
                </form>
                <button class="btn-primary-gym" onclick="openModal()">
                    <i class="fas fa-plus"></i>Add Trainer
                </button>
            </div>
        </div>

        <?php if (empty($trainers)): ?>
            <div style="padding:6rem;text-align:center;color:rgba(6,30,41,.35)">
                <i class="fas fa-user-tie" style="font-size:4rem;margin-bottom:1.6rem;display:block"></i>
                <div style="font-size:1.6rem;font-weight:600">No trainers found</div>
                <div style="font-size:1.3rem;margin-top:.6rem">Add your first trainer using the button above</div>
            </div>
        <?php else: ?>
        <div style="overflow-x:auto">
            <table class="gym-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Trainer</th>
                        <th>Specialization</th>
                        <th>Experience</th>
                        <th>Clients</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $colors = ['av-blue','av-green','av-amber','av-purple','av-cyan','av-pink','av-teal'];
                foreach ($trainers as $i => $t):
                    $initials = strtoupper(substr($t['name'],0,1));
                    $parts    = explode(' ', $t['name']);
                    if (count($parts) > 1) $initials .= strtoupper(substr(end($parts),0,1));
                    $color = $colors[$i % count($colors)];
                ?>
                <tr>
                    <td style="color:rgba(6,30,41,.4);font-weight:600"><?= $i+1 ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="av <?= $color ?>"><?= htmlspecialchars($initials) ?></div>
                            <div>
                                <div style="font-weight:600"><?= htmlspecialchars($t['name']) ?></div>
                                <div style="font-size:1.15rem;color:rgba(6,30,41,.45)"><?= htmlspecialchars($t['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($t['specialization'] ?: '—') ?></td>
                    <td><?= (int)$t['experience_years'] ?> yr<?= $t['experience_years']!=1?'s':'' ?></td>
                    <td>
                        <span style="font-weight:700;color:var(--primary)"><?= (int)$t['client_count'] ?></span>
                        <a href="assign-clients.php?trainer=<?= $t['id'] ?>"
                           style="font-size:1.1rem;color:var(--secondary);margin-left:.6rem">manage</a>
                    </td>
                    <td>
                        <span class="badge-status <?= $t['status']==='active'?'status-active':'status-inactive' ?>">
                            <?= ucfirst($t['status']) ?>
                        </span>
                    </td>
                    <td style="color:rgba(6,30,41,.5)"><?= date('M j, Y', strtotime($t['created_at'])) ?></td>
                    <td>
                        <button class="action-btn edit" title="Edit"
                                onclick="editTrainer(<?= htmlspecialchars(json_encode($t)) ?>)">
                            <i class="fas fa-pen"></i>
                        </button>
                        <a href="assign-clients.php?trainer=<?= $t['id'] ?>"
                           class="action-btn assign" title="Assign Clients">
                            <i class="fas fa-link"></i>
                        </a>
                        <a href="trainers.php?toggle=<?= $t['id'] ?>"
                           class="action-btn view" title="Toggle Status"
                           onclick="return confirm('Toggle status for <?= addslashes($t['name']) ?>?')">
                            <i class="fas fa-power-off"></i>
                        </a>
                        <a href="trainers.php?delete=<?= $t['id'] ?>"
                           class="action-btn del" title="Delete"
                           onclick="return confirm('Delete <?= addslashes($t['name']) ?>? This cannot be undone.')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</main>

<!-- ═══ ADD / EDIT MODAL ═══ -->
<div class="modal-overlay <?= $openModal ? 'open' : '' ?>" id="trainerModal">
    <div class="modal-box">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="modal-title" id="modalTitle">
                <?= $editTrainer ? 'Edit Trainer' : 'Add New Trainer' ?>
            </div>
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>

        <form method="POST" action="trainers.php" id="trainerForm">
            <input type="hidden" name="trainer_id" id="fTrainerId"
                   value="<?= htmlspecialchars($editTrainer['id'] ?? '') ?>">

            <div class="form-row-2 form-group">
                <div>
                    <label class="gym-label">Full Name *</label>
                    <input type="text" name="name" id="fName" class="gym-input"
                           placeholder="e.g. Alex Carter"
                           value="<?= htmlspecialchars($editTrainer['name'] ?? $_POST['name'] ?? '') ?>" required>
                </div>
                <div>
                    <label class="gym-label">Email *</label>
                    <input type="email" name="email" id="fEmail" class="gym-input"
                           placeholder="trainer@gympro.com"
                           value="<?= htmlspecialchars($editTrainer['email'] ?? $_POST['email'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-row-2 form-group">
                <div>
                    <label class="gym-label">Phone</label>
                    <input type="text" name="phone" id="fPhone" class="gym-input"
                           placeholder="+1 555 000 0000"
                           value="<?= htmlspecialchars($editTrainer['phone'] ?? $_POST['phone'] ?? '') ?>">
                </div>
                <div>
                    <label class="gym-label">Experience (years)</label>
                    <input type="number" name="experience_years" id="fExp" class="gym-input"
                           min="0" max="50" placeholder="0"
                           value="<?= htmlspecialchars($editTrainer['experience_years'] ?? $_POST['experience_years'] ?? '0') ?>">
                </div>
            </div>

            <div class="form-row-2 form-group">
                <div>
                    <label class="gym-label">Specialization</label>
                    <input type="text" name="specialization" id="fSpec" class="gym-input"
                           placeholder="e.g. Weight Loss & HIIT"
                           value="<?= htmlspecialchars($editTrainer['specialization'] ?? $_POST['specialization'] ?? '') ?>">
                </div>
                <div>
                    <label class="gym-label">Status</label>
                    <select name="status" id="fStatus" class="gym-select">
                        <option value="active"   <?= ($editTrainer['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($editTrainer['status'] ?? '')       === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="gym-label">Bio</label>
                <textarea name="bio" id="fBio" class="gym-textarea" rows="3"
                          placeholder="Short description…"><?= htmlspecialchars($editTrainer['bio'] ?? $_POST['bio'] ?? '') ?></textarea>
            </div>

            <div class="form-row-2 form-group">
                <div>
                    <label class="gym-label" id="passLabel">
                        Password <?= $editTrainer ? '<span style="opacity:.5;font-weight:400">(leave blank to keep)</span>' : '*' ?>
                    </label>
                    <div style="position:relative">
                        <input type="password" name="password" id="fPass" class="gym-input"
                               placeholder="Min 6 characters"
                               style="padding-right:4rem">
                        <button type="button" class="toggle-password"
                                style="position:absolute;right:1.2rem;top:50%;transform:translateY(-50%);background:none;border:none;color:rgba(6,30,41,.4);cursor:pointer;font-size:1.5rem"
                                data-target="fPass">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="gym-label">Confirm Password</label>
                    <input type="password" name="confirm_password" id="fConfirm" class="gym-input"
                           placeholder="Repeat password">
                </div>
            </div>

            <div class="d-flex gap-3 mt-2">
                <button type="button" class="btn-outline-gym flex-grow-1" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-primary-gym flex-grow-1" style="justify-content:center">
                    <i class="fas fa-check"></i>
                    <span id="submitLabel"><?= $editTrainer ? 'Update Trainer' : 'Add Trainer' ?></span>
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openModal(){ document.getElementById('trainerModal').classList.add('open'); }
function closeModal(){ document.getElementById('trainerModal').classList.remove('open'); resetModal(); }

function resetModal(){
    document.getElementById('fTrainerId').value = '';
    document.getElementById('trainerForm').reset();
    document.getElementById('modalTitle').textContent  = 'Add New Trainer';
    document.getElementById('submitLabel').textContent = 'Add Trainer';
    document.getElementById('passLabel').innerHTML     = 'Password *';
}

function editTrainer(t){
    document.getElementById('fTrainerId').value = t.id;
    document.getElementById('fName').value      = t.name;
    document.getElementById('fEmail').value     = t.email;
    document.getElementById('fPhone').value     = t.phone  || '';
    document.getElementById('fSpec').value      = t.specialization || '';
    document.getElementById('fExp').value       = t.experience_years || 0;
    document.getElementById('fBio').value       = t.bio   || '';
    document.getElementById('fStatus').value    = t.status;
    document.getElementById('fPass').value      = '';
    document.getElementById('fConfirm').value   = '';
    document.getElementById('modalTitle').textContent  = 'Edit Trainer';
    document.getElementById('submitLabel').textContent = 'Update Trainer';
    document.getElementById('passLabel').innerHTML     = 'Password <span style="opacity:.5;font-weight:400">(leave blank to keep)</span>';
    openModal();
}

// Toggle password visibility inside modal
document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', function(){
        const inp = document.getElementById(this.dataset.target);
        const ico = this.querySelector('i');
        if(inp.type==='password'){ inp.type='text'; ico.classList.replace('fa-eye','fa-eye-slash'); }
        else { inp.type='password'; ico.classList.replace('fa-eye-slash','fa-eye'); }
    });
});

// Close modal on overlay click
document.getElementById('trainerModal').addEventListener('click', function(e){
    if(e.target === this) closeModal();
});
</script>
</body>
</html>