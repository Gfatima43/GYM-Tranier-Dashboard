<?php
// ============================================================
//  assign-clients.php  –  Assign / Unassign clients to trainers
// ============================================================
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit(); }
$database = require './bootstrap.php';
$pdo = $database->pdo;

$message = '';
$msgType = '';

// ── AJAX: assign single client ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $action     = $_POST['ajax_action'];
    $trainer_id = (int)($_POST['trainer_id'] ?? 0);
    $client_id  = (int)($_POST['client_id']  ?? 0);

    try {
        if ($action === 'assign') {
            $pdo->prepare(
                "INSERT INTO trainer_clients (trainer_id, client_id, assigned_date, status)
                 VALUES (?, ?, CURDATE(), 'active')
                 ON DUPLICATE KEY UPDATE status='active', assigned_date=CURDATE()"
            )->execute([$trainer_id, $client_id]);
            // Also update clients.trainer_id for quick lookups
            $pdo->prepare("UPDATE clients SET trainer_id=? WHERE id=?")->execute([$trainer_id, $client_id]);
            echo json_encode(['ok' => true, 'msg' => 'Client assigned.']);

        } elseif ($action === 'unassign') {
            $pdo->prepare(
                "UPDATE trainer_clients SET status='inactive' WHERE trainer_id=? AND client_id=?"
            )->execute([$trainer_id, $client_id]);
            $pdo->prepare("UPDATE clients SET trainer_id=NULL WHERE id=? AND trainer_id=?")->execute([$client_id, $trainer_id]);
            echo json_encode(['ok' => true, 'msg' => 'Client unassigned.']);

        } elseif ($action === 'assign_bulk') {
            // Bulk: reassign all selected client IDs to trainer
            $ids = array_map('intval', json_decode($_POST['client_ids'] ?? '[]', true));
            foreach ($ids as $cid) {
                $pdo->prepare(
                    "INSERT INTO trainer_clients (trainer_id, client_id, assigned_date, status)
                     VALUES (?, ?, CURDATE(), 'active')
                     ON DUPLICATE KEY UPDATE status='active', assigned_date=CURDATE()"
                )->execute([$trainer_id, $cid]);
                $pdo->prepare("UPDATE clients SET trainer_id=? WHERE id=?")->execute([$trainer_id, $cid]);
            }
            echo json_encode(['ok' => true, 'msg' => count($ids) . ' clients assigned.']);
        }
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
    }
    exit();
}

// ── Fetch all trainers for dropdown ──────────────────────────
$allTrainers = $pdo->query("SELECT id, name, specialization FROM trainers WHERE status='active' ORDER BY name")->fetchAll();

// Selected trainer (from ?trainer=ID or POST)
$selectedTrainerId = (int)($_GET['trainer'] ?? $_POST['sel_trainer'] ?? ($allTrainers[0]['id'] ?? 0));

$selectedTrainer = null;
if ($selectedTrainerId) {
    $s = $pdo->prepare("SELECT * FROM trainers WHERE id=?");
    $s->execute([$selectedTrainerId]);
    $selectedTrainer = $s->fetch();
}

// Clients already assigned to this trainer
$assignedIds = [];
if ($selectedTrainerId) {
    $rows = $pdo->prepare("SELECT client_id FROM trainer_clients WHERE trainer_id=? AND status='active'");
    $rows->execute([$selectedTrainerId]);
    $assignedIds = array_column($rows->fetchAll(), 'client_id');
}

// All clients
$allClients = $pdo->query("SELECT id, name, email, status FROM clients ORDER BY name")->fetchAll();

$activePage   = 'assign';
$pageTitle    = 'Assign Clients';
$pageSubtitle = $selectedTrainer ? 'Trainer: ' . $selectedTrainer['name'] : 'Select a trainer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Clients – GymPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .client-row { display:flex; align-items:center; gap:1.4rem; padding:1.4rem 2rem; border-bottom:1px solid rgba(29,84,109,.05); transition:background .15s; }
        .client-row:last-child { border-bottom:none; }
        .client-row:hover { background:rgba(29,84,109,.02); }
        .toggle-switch { position:relative; width:4.8rem; height:2.6rem; flex-shrink:0; }
        .toggle-switch input { opacity:0; width:0; height:0; }
        .slider { position:absolute; inset:0; background:rgba(6,30,41,.12); border-radius:10rem; cursor:pointer; transition:.3s; }
        .slider::before { content:''; position:absolute; width:2rem; height:2rem; left:.3rem; bottom:.3rem; background:#fff; border-radius:50%; transition:.3s; box-shadow:0 1px 4px rgba(0,0,0,.2); }
        input:checked + .slider { background:var(--primary); }
        input:checked + .slider::before { transform:translateX(2.2rem); }
        .trainer-select-card { background:#fff; border-radius:var(--radius); border:var(--border); padding:2.4rem; margin-bottom:2rem; box-shadow:var(--shadow-sm); animation:fadeUp .5s ease both; }
        .trainer-preview { display:flex; align-items:center; gap:1.6rem; padding:2rem; background:rgba(29,84,109,.04); border-radius:1.2rem; margin-top:1.6rem; }
        .stat-pill { display:inline-flex; align-items:center; gap:.5rem; padding:.4rem 1.1rem; background:rgba(29,84,109,.08); color:var(--primary); border-radius:10rem; font-size:1.2rem; font-weight:600; }
        .search-input { height:4rem; padding:0 1.2rem 0 3.6rem; border:1.5px solid rgba(29,84,109,.15); border-radius:1rem; font-size:1.3rem; outline:none; font-family:'DM Sans',sans-serif; width:100%; }
        .filter-bar { display:flex; gap:1rem; padding:1.6rem 2rem; border-bottom:1px solid rgba(29,84,109,.06); flex-wrap:wrap; align-items:center; }
        .toast-msg { position:fixed; bottom:2.4rem; right:2.4rem; background:var(--dark); color:#fff; padding:1.2rem 2rem; border-radius:1.2rem; font-size:1.3rem; font-weight:500; z-index:9999; opacity:0; transform:translateY(1rem); transition:all .3s; pointer-events:none; display:flex; align-items:center; gap:.8rem; }
        .toast-msg.show { opacity:1; transform:translateY(0); }
        .toast-msg.success { border-left:4px solid #22c55e; }
        .toast-msg.error   { border-left:4px solid #ef4444; }
    </style>
</head>
<body>
<?php require_once 'assets/sidebar.php'; ?>
<?php require_once 'assets/topbar.php';  ?>

<!-- Toast -->
<div class="toast-msg" id="toastMsg"></div>

<main class="main-content">

    <!-- Trainer Selector -->
    <div class="trainer-select-card">
        <div class="d-flex align-items-center gap-2 mb-1">
            <i class="fas fa-user-tie" style="color:var(--secondary);font-size:1.6rem"></i>
            <span class="section-title" style="font-size:1.7rem">Select Trainer</span>
        </div>
        <p style="font-size:1.3rem;color:rgba(6,30,41,.5);margin-bottom:1.4rem">Choose a trainer to view and manage their assigned clients</p>

        <select id="trainerSelect"
                style="height:4.6rem;padding:0 1.4rem;border:1.5px solid rgba(29,84,109,.15);border-radius:1.2rem;font-size:1.4rem;font-family:'DM Sans',sans-serif;outline:none;color:var(--dark);width:100%;max-width:48rem">
            <option value="">— Pick a trainer —</option>
            <?php foreach ($allTrainers as $tr): ?>
                <option value="<?= $tr['id'] ?>"
                        <?= $tr['id'] == $selectedTrainerId ? 'selected' : '' ?>>
                    <?= htmlspecialchars($tr['name']) ?>
                    <?= $tr['specialization'] ? ' · '.htmlspecialchars($tr['specialization']) : '' ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if ($selectedTrainer): ?>
        <?php
            $initials = strtoupper(substr($selectedTrainer['name'],0,1));
            $parts = explode(' ', $selectedTrainer['name']);
            if (count($parts)>1) $initials .= strtoupper(substr(end($parts),0,1));
            $assignedCount = count($assignedIds);
            $totalClients  = count($allClients);
        ?>
        <div class="trainer-preview">
            <div class="av av-lg av-blue"><?= $initials ?></div>
            <div style="flex:1">
                <div style="font-family:'Barlow Condensed',sans-serif;font-size:2.2rem;font-weight:800;color:var(--dark)"><?= htmlspecialchars($selectedTrainer['name']) ?></div>
                <div style="font-size:1.25rem;color:var(--secondary);margin-bottom:.8rem"><?= htmlspecialchars($selectedTrainer['specialization'] ?: 'General Trainer') ?></div>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="stat-pill"><i class="fas fa-users"></i><?= $assignedCount ?> Assigned</span>
                    <span class="stat-pill"><i class="fas fa-calendar"></i><?= (int)$selectedTrainer['experience_years'] ?> yrs exp</span>
                    <span class="stat-pill <?= $selectedTrainer['status']==='active'?'':'stat-pill-off' ?>"
                          style="<?= $selectedTrainer['status']==='active'?'background:rgba(34,197,94,.1);color:#16a34a':'background:rgba(239,68,68,.1);color:#ef4444' ?>">
                        <i class="fas fa-circle" style="font-size:.7rem"></i><?= ucfirst($selectedTrainer['status']) ?>
                    </span>
                </div>
            </div>
            <div style="text-align:right">
                <div style="font-family:'Barlow Condensed',sans-serif;font-size:3.6rem;font-weight:800;color:var(--primary);line-height:1"><?= $assignedCount ?></div>
                <div style="font-size:1.15rem;color:rgba(6,30,41,.4)">of <?= $totalClients ?> clients</div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($selectedTrainer): ?>
    <!-- Clients List -->
    <div class="section-card" id="clientsCard">
        <div class="section-header">
            <span class="section-title">Manage Client Assignments</span>
            <div class="d-flex gap-2">
                <button class="btn-outline-gym" id="btnAssignAll" onclick="bulkAction('assign')">
                    <i class="fas fa-check-double"></i>Assign All Filtered
                </button>
                <button class="btn-outline-gym" style="color:#ef4444;border-color:rgba(239,68,68,.3)" onclick="bulkAction('unassign')">
                    <i class="fas fa-xmark"></i>Unassign All Filtered
                </button>
            </div>
        </div>

        <!-- Filter bar -->
        <div class="filter-bar">
            <div style="position:relative;flex:1;max-width:32rem">
                <i class="fas fa-search" style="position:absolute;left:1.2rem;top:50%;transform:translateY(-50%);color:var(--secondary);font-size:1.2rem"></i>
                <input type="text" id="clientSearch" class="search-input" placeholder="Search clients…">
            </div>
            <div class="filter-tabs" id="filterTabs">
                <button class="filter-tab active" data-filter="all">All (<?= count($allClients) ?>)</button>
                <button class="filter-tab" data-filter="assigned">Assigned (<?= $assignedCount ?>)</button>
                <button class="filter-tab" data-filter="unassigned">Unassigned (<?= count($allClients)-$assignedCount ?>)</button>
            </div>
        </div>

        <div id="clientList">
        <?php if (empty($allClients)): ?>
            <div style="padding:4rem;text-align:center;color:rgba(6,30,41,.35);font-size:1.4rem">No clients found in the system.</div>
        <?php else: ?>
        <?php
        $clientColors = ['av-blue','av-green','av-amber','av-purple','av-cyan','av-pink','av-teal','av-red'];
        foreach ($allClients as $ci => $client):
            $isAssigned = in_array($client['id'], $assignedIds);
            $cInitials  = strtoupper(substr($client['name'],0,1));
            $cp = explode(' ', $client['name']);
            if (count($cp)>1) $cInitials .= strtoupper(substr(end($cp),0,1));
            $cColor = $clientColors[$ci % count($clientColors)];
        ?>
        <div class="client-row"
             data-name="<?= strtolower(htmlspecialchars($client['name'])) ?>"
             data-email="<?= strtolower(htmlspecialchars($client['email'] ?? '')) ?>"
             data-assigned="<?= $isAssigned ? '1' : '0' ?>"
             data-client-id="<?= $client['id'] ?>">

            <div class="av <?= $cColor ?>"><?= $cInitials ?></div>

            <div style="flex:1">
                <div style="font-weight:600;font-size:1.35rem"><?= htmlspecialchars($client['name']) ?></div>
                <div style="font-size:1.15rem;color:rgba(6,30,41,.45)"><?= htmlspecialchars($client['email'] ?? '') ?></div>
            </div>

            <span class="badge-status <?= $client['status']==='active'?'status-active':'status-inactive' ?>"
                  style="margin-right:1rem">
                <?= ucfirst($client['status'] ?? 'active') ?>
            </span>

            <span class="assigned-label" style="font-size:1.2rem;font-weight:600;width:9rem;text-align:right;margin-right:1.4rem;
                  color:<?= $isAssigned ? '#16a34a' : 'rgba(6,30,41,.3)' ?>">
                <?= $isAssigned ? '<i class="fas fa-link"></i> Assigned' : 'Not assigned' ?>
            </span>

            <label class="toggle-switch">
                <input type="checkbox"
                       class="assign-toggle"
                       data-client-id="<?= $client['id'] ?>"
                       data-trainer-id="<?= $selectedTrainerId ?>"
                       <?= $isAssigned ? 'checked' : '' ?>>
                <span class="slider"></span>
            </label>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
        <div class="section-card" style="padding:6rem;text-align:center">
            <i class="fas fa-arrow-up" style="font-size:3rem;color:rgba(6,30,41,.2);display:block;margin-bottom:1.2rem"></i>
            <div style="font-size:1.6rem;font-weight:600;color:rgba(6,30,41,.35)">Select a trainer above to manage their clients</div>
        </div>
    <?php endif; ?>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Trainer dropdown change ──────────────────────────────────
document.getElementById('trainerSelect').addEventListener('change', function(){
    if (this.value) window.location.href = 'assign-clients.php?trainer=' + this.value;
});

// ── Toast helper ─────────────────────────────────────────────
function showToast(msg, type='success'){
    const t = document.getElementById('toastMsg');
    t.textContent = '';
    const ico = document.createElement('i');
    ico.className = type==='success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
    t.appendChild(ico);
    t.appendChild(document.createTextNode(' ' + msg));
    t.className = 'toast-msg show ' + type;
    setTimeout(() => t.classList.remove('show'), 3000);
}

// ── Toggle switch (AJAX) ─────────────────────────────────────
document.querySelectorAll('.assign-toggle').forEach(chk => {
    chk.addEventListener('change', async function(){
        const action     = this.checked ? 'assign' : 'unassign';
        const trainerId  = this.dataset.trainerId;
        const clientId   = this.dataset.clientId;
        const row        = this.closest('.client-row');
        const label      = row.querySelector('.assigned-label');

        try {
            const fd = new FormData();
            fd.append('ajax_action', action);
            fd.append('trainer_id',  trainerId);
            fd.append('client_id',   clientId);

            const res  = await fetch('assign-clients.php', { method:'POST', body:fd });
            const data = await res.json();

            if (data.ok) {
                row.dataset.assigned = this.checked ? '1' : '0';
                if (this.checked) {
                    label.innerHTML = '<i class="fas fa-link"></i> Assigned';
                    label.style.color = '#16a34a';
                } else {
                    label.innerHTML = 'Not assigned';
                    label.style.color = 'rgba(6,30,41,.3)';
                }
                updateCountBadges();
                showToast(data.msg, 'success');
            } else {
                this.checked = !this.checked; // revert
                showToast(data.msg, 'error');
            }
        } catch(e){
            this.checked = !this.checked;
            showToast('Network error, please try again.','error');
        }
    });
});

// ── Search ───────────────────────────────────────────────────
document.getElementById('clientSearch')?.addEventListener('input', function(){
    applyFilters();
});

// ── Filter tabs ──────────────────────────────────────────────
document.querySelectorAll('#filterTabs .filter-tab').forEach(btn => {
    btn.addEventListener('click', function(){
        document.querySelectorAll('#filterTabs .filter-tab').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        applyFilters();
    });
});

function applyFilters(){
    const q      = (document.getElementById('clientSearch')?.value || '').toLowerCase();
    const filter = document.querySelector('#filterTabs .filter-tab.active')?.dataset.filter || 'all';
    document.querySelectorAll('.client-row').forEach(row => {
        const matchQ = !q || row.dataset.name.includes(q) || row.dataset.email.includes(q);
        const matchF = filter==='all' ||
                      (filter==='assigned'   && row.dataset.assigned==='1') ||
                      (filter==='unassigned' && row.dataset.assigned==='0');
        row.style.display = (matchQ && matchF) ? '' : 'none';
    });
}

// ── Bulk assign/unassign visible rows ────────────────────────
async function bulkAction(action){
    const visible = [...document.querySelectorAll('.client-row')]
        .filter(r => r.style.display !== 'none')
        .map(r => parseInt(r.dataset.clientId));

    if (!visible.length) { showToast('No visible clients to ' + action,'error'); return; }
    if (!confirm((action==='assign'?'Assign':'Unassign') + ' ' + visible.length + ' client(s)?')) return;

    const trainerId = <?= $selectedTrainerId ?>;
    const fd = new FormData();
    fd.append('ajax_action', 'assign_bulk');
    fd.append('trainer_id', trainerId);
    fd.append('client_ids', JSON.stringify(visible));

    try {
        const res  = await fetch('assign-clients.php', { method:'POST', body:fd });
        const data = await res.json();
        if (data.ok) {
            // Update UI
            visible.forEach(cid => {
                const row = document.querySelector(`.client-row[data-client-id="${cid}"]`);
                if (!row) return;
                const chk   = row.querySelector('.assign-toggle');
                const label = row.querySelector('.assigned-label');
                chk.checked = (action==='assign');
                row.dataset.assigned = (action==='assign') ? '1' : '0';
                if (action==='assign'){
                    label.innerHTML   = '<i class="fas fa-link"></i> Assigned';
                    label.style.color = '#16a34a';
                } else {
                    label.innerHTML   = 'Not assigned';
                    label.style.color = 'rgba(6,30,41,.3)';
                }
            });
            updateCountBadges();
            showToast(data.msg,'success');
        } else { showToast(data.msg,'error'); }
    } catch(e){ showToast('Network error.','error'); }
}

// ── Re-count badges after toggle ─────────────────────────────
function updateCountBadges(){
    const all      = document.querySelectorAll('.client-row').length;
    const assigned = [...document.querySelectorAll('.client-row')].filter(r=>r.dataset.assigned==='1').length;
    const tabs     = document.querySelectorAll('#filterTabs .filter-tab');
    if (tabs[0]) tabs[0].textContent = `All (${all})`;
    if (tabs[1]) tabs[1].textContent = `Assigned (${assigned})`;
    if (tabs[2]) tabs[2].textContent = `Unassigned (${all-assigned})`;
}
</script>
</body>
</html>