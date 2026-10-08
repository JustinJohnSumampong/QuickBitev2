<?php
require_once "../config/database.php";
require_once "../includes/functions.php";
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if (!$name || !$email) {
        flash('error', 'Name and email are required.');
        redirect('riders.php');
    }

    // Reject emails already used by another account (email is UNIQUE).
    $check = $pdo->prepare("SELECT id FROM users WHERE email=? AND id<>?");
    $check->execute([$email, $id]);
    if ($check->fetch()) {
        flash('error', 'Email already in use.');
        redirect('riders.php');
    }

    if ($id) {
        $fields = "name=?, email=?, phone=?";
        $params = [$name, $email, $phone, $id];
        if (!empty($_POST['password'])) {
            $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $fields .= ", password=?";
            $params = [$name, $email, $phone, $pass, $id];
        }
        $pdo->prepare("UPDATE users SET $fields WHERE id=?")->execute($params);
        flash('success', 'Rider updated.');
    } else {
        if (empty($_POST['password'])) {
            flash('error', 'Password is required for new rider.');
            redirect('riders.php');
        }
        $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users(name,email,phone,password,role,status) VALUES(?,?,?,?,'rider',1)")->execute([$name, $email, $phone, $pass]);
        flash('success', 'Rider created.');
    }
    redirect('riders.php');
}

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE users SET status = CASE WHEN status=1 THEN 0 ELSE 1 END WHERE id=? AND role='rider'")->execute([$id]);
    flash('success', 'Rider status updated.');
    redirect('riders.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT id,name,email,phone FROM users WHERE id=? AND role='rider'");
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch();
}

$riders = $pdo->query("SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.rider_id=u.id) deliveries FROM users u WHERE u.role='rider' ORDER BY u.status DESC, u.name")->fetchAll();

$page_title = "Riders";
include "includes/header.php";
?>
<h2 class="section-title mb-4">Delivery Riders</h2>

<div class="card form-card p-4 mb-4">
  <h5 class="fw-bold mb-3"><i class="fa-solid fa-motorcycle me-2 text-danger"></i><?= $edit ? 'Edit Rider' : 'Add New Rider' ?></h5>
  <form method="post" class="row g-3">
    <?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
    <div class="col-md-3">
      <label class="form-label small fw-semibold text-muted">Full Name</label>
      <input name="name" class="form-control" placeholder="Juan Dela Cruz" value="<?= $edit ? e($edit['name']) : '' ?>" required>
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold text-muted">Email (login)</label>
      <input name="email" type="email" class="form-control" placeholder="rider@email.com" value="<?= $edit ? e($edit['email']) : '' ?>" required>
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold text-muted">Phone</label>
      <input name="phone" class="form-control" placeholder="09XXXXXXXXX" value="<?= $edit ? e($edit['phone']) : '' ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold text-muted"><?= $edit ? 'New Password (leave blank to keep)' : 'Password' ?></label>
      <input name="password" type="password" class="form-control" placeholder="<?= $edit ? '••••••••' : 'at least 6 chars' ?>" <?= $edit ? '' : 'required' ?>>
    </div>
    <div class="col-12 d-flex gap-2">
      <?php if ($edit): ?><a href="riders.php" class="btn btn-outline-secondary">Cancel</a><?php endif; ?>
      <button class="btn btn-danger"><i class="fa-solid <?= $edit ? 'fa-floppy-disk' : 'fa-plus' ?> me-1"></i><?= $edit ? 'Save Rider' : 'Add Rider' ?></button>
    </div>
  </form>
</div>

<div class="card form-card p-4">
  <h5 class="fw-bold mb-3"><i class="fa-solid fa-list me-2 text-danger"></i>All Riders (<?= count($riders) ?>)</h5>
  <?php if (empty($riders)): ?>
    <div class="text-center text-muted py-5"><i class="fa-solid fa-inbox fa-2x mb-2 d-block opacity-50"></i>No riders yet. Add your first rider above.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Deliveries</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($riders as $r): ?>
            <tr class="<?= !$r['status'] ? 'table-light text-muted' : '' ?>">
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div class="rounded-circle bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center" style="width:38px;height:38px;font-weight:700">
                    <?= strtoupper(substr($r['name'], 0, 1)) ?>
                  </div>
                  <span class="fw-semibold"><?= e($r['name']) ?></span>
                </div>
              </td>
              <td><?= e($r['email']) ?></td>
              <td><?= e($r['phone']) ?: '<span class="text-muted">—</span>' ?></td>
              <td><span class="badge bg-danger"><?= $r['deliveries'] ?></span></td>
              <td>
                <?php if ($r['status']): ?>
                  <span class="pill pill-delivered">Active</span>
                <?php else: ?>
                  <span class="pill pill-cancelled">Inactive</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <a href="?edit=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen me-1"></i>Edit</a>
                <a data-confirm="<?= $r['status'] ? 'Deactivate rider?' : 'Reactivate rider?' ?>" href="?toggle=<?= $r['id'] ?>" class="btn btn-sm btn-outline-<?= $r['status'] ? 'warning' : 'success' ?>">
                  <i class="fa-solid <?= $r['status'] ? 'fa-pause' : 'fa-play' ?> me-1"></i><?= $r['status'] ? 'Deactivate' : 'Activate' ?>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php include "includes/footer.php"; ?>
