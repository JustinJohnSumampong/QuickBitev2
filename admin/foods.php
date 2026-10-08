<?php
require_once "../config/database.php";
require_once "../includes/functions.php";
require_role('admin');

$base_url = rtrim(dirname($_SERVER['SCRIPT_NAME'], 2), '/');
$uploadDir = __DIR__ . '/../assets/uploads';
if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0755, true); }

$statusPill = function ($s) {
    return $s ? '<span class="pill pill-delivered">Active</span>' : '<span class="pill pill-cancelled">Inactive</span>';
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $cat = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $desc = trim($_POST['description'] ?? '');
    $prep = (int)($_POST['prep_minutes'] ?? 15);
    $id = (int)($_POST['id'] ?? 0);
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;

    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
        if (!in_array($_FILES['image']['type'], $allowed, true)) {
            flash('error', 'Only JPG/PNG/WebP images allowed.');
            redirect('foods.php');
        }
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $ext = strtolower($ext);
        if (!in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
            flash('error', 'Invalid image extension.');
            redirect('foods.php');
        }
        $fname = 'food-' . date('YmdHis') . '-' . substr(uniqid(), -5) . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . '/' . $fname)) {
            $image = $fname;
        }
    }

    if ($id) {
        $fields = "category_id=?, name=?, description=?, price=?, prep_minutes=?, status=?";
        $params = [$cat, $name, $desc, $price, $prep, $status, $id];
        if ($image) {
            $fields .= ", image=?";
            $params = [$cat, $name, $desc, $price, $prep, $status, $image, $id];
        }
        $pdo->prepare("UPDATE foods SET $fields WHERE id=?")->execute($params);
        flash('success', 'Food item updated.');
    } else {
        if (!$name || !$cat || !$price) {
            flash('error', 'Please fill required fields.');
            redirect('foods.php');
        }
        if ($image) {
            $s = $pdo->prepare("INSERT INTO foods(category_id,name,description,price,prep_minutes,status,image) VALUES(?,?,?,?,?,?,?)");
            $s->execute([$cat, $name, $desc, $price, $prep, $status, $image]);
        } else {
            $s = $pdo->prepare("INSERT INTO foods(category_id,name,description,price,prep_minutes,status) VALUES(?,?,?,?,?,?)");
            $s->execute([$cat, $name, $desc, $price, $prep, $status]);
        }
        flash('success', 'Food item added.');
    }
    redirect('foods.php');
}

if (isset($_GET['delete'])) {
    $pdo->prepare("UPDATE foods SET status=0 WHERE id=?")->execute([(int)$_GET['delete']]);
    flash('success', 'Food item removed.');
    redirect('foods.php');
}
if (isset($_GET['restore'])) {
    $pdo->prepare("UPDATE foods SET status=1 WHERE id=?")->execute([(int)$_GET['restore']]);
    flash('success', 'Food item restored.');
    redirect('foods.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM foods WHERE id=?");
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch();
}

$cats = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$catFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$q = trim($_GET['q'] ?? '');

$sql = "SELECT f.*, c.name category FROM foods f JOIN categories c ON c.id=f.category_id WHERE 1=1";
$params = [];
if ($catFilter) { $sql .= " AND f.category_id=?"; $params[] = $catFilter; }
if ($q) { $sql .= " AND (f.name LIKE ? OR f.description LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
$showAll = isset($_GET['show_all']);
if (!$showAll) { $sql .= " AND f.status=1"; }
$sql .= " ORDER BY f.id DESC";
$s = $pdo->prepare($sql);
$s->execute($params);
$foods = $s->fetchAll();

$page_title = "Foods";
include "includes/header.php";
?>
<h2 class="section-title mb-4">Food Management</h2>

<div class="card form-card p-4 mb-4">
  <h5 class="fw-bold mb-3"><i class="fa-solid fa-utensils me-2 text-danger"></i><?= $edit ? 'Edit Food Item' : 'Add New Food' ?></h5>
  <form method="post" enctype="multipart/form-data" class="row g-3">
    <?php if ($edit): ?>
      <input type="hidden" name="id" value="<?= $edit['id'] ?>">
    <?php endif; ?>
    <div class="col-md-4">
      <label class="form-label small fw-semibold text-muted">Food Name *</label>
      <input name="name" class="form-control form-control-lg" placeholder="Classic Burger" value="<?= $edit ? e($edit['name']) : '' ?>" required>
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold text-muted">Category *</label>
      <select name="category_id" class="form-select form-select-lg" required>
        <?php foreach ($cats as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $edit && $edit['category_id']==$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label small fw-semibold text-muted">Price (₱) *</label>
      <input name="price" type="number" step=".01" min="0" class="form-control form-control-lg" placeholder="149.00" value="<?= $edit ? $edit['price'] : '' ?>" required>
    </div>
    <div class="col-md-1">
      <label class="form-label small fw-semibold text-muted">Prep (min)</label>
      <input name="prep_minutes" type="number" min="0" class="form-control form-control-lg" value="<?= $edit ? $edit['prep_minutes'] : 15 ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label small fw-semibold text-muted">Status</label>
      <select name="status" class="form-select form-select-lg">
        <option value="1" <?= $edit && $edit['status']==1 ? 'selected' : '' ?>>Active</option>
        <option value="0" <?= $edit && $edit['status']==0 ? 'selected' : '' ?>>Inactive</option>
      </select>
    </div>
    <div class="col-md-7">
      <label class="form-label small fw-semibold text-muted">Description</label>
      <input name="description" class="form-control" placeholder="Juicy beef patty, lettuce, tomato..." value="<?= $edit ? e($edit['description']) : '' ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold text-muted">Image Upload</label>
      <input name="image" type="file" accept="image/*" class="form-control">
    </div>
    <div class="col-md-2">
      <label class="form-label small fw-semibold text-muted">Preview</label>
      <div class="d-flex align-items-center h-100">
        <?php if ($edit && !empty($edit['image'])): ?>
          <img src="<?= $base_url ?>/assets/uploads/<?= e($edit['image']) ?>" class="admin-img-preview" alt="preview">
        <?php else: ?>
          <div class="text-muted small"><i class="fa-solid fa-image me-1"></i>No image</div>
        <?php endif; ?>
      </div>
    </div>
    <div class="col-12 d-flex gap-2 pt-2">
      <?php if ($edit): ?><a href="foods.php" class="btn btn-lg btn-outline-secondary">Cancel Edit</a><?php endif; ?>
      <button class="btn btn-lg btn-danger"><i class="fa-solid <?= $edit ? 'fa-floppy-disk' : 'fa-plus' ?> me-1"></i><?= $edit ? 'Save Changes' : 'Add Food Item' ?></button>
      <?php if (empty($cats)): ?>
        <span class="align-self-center text-danger small"><i class="fa-solid fa-triangle-exclamation me-1"></i>No categories yet — <a href="categories.php">create one first</a></span>
      <?php endif; ?>
    </div>
  </form>
</div>

<div class="card form-card p-4 mb-4">
  <div class="row g-3 mb-3 align-items-end">
    <div class="col-md-4">
      <h5 class="fw-bold mb-0"><i class="fa-solid fa-bowl-food me-2 text-danger"></i>Food Items (<?= count($foods) ?>)</h5>
    </div>
    <div class="col-md-3">
      <select class="form-select form-select-sm" onchange="if(this.value) location='?category='+this.value+(document.getElementById('sq').value?'&q='+encodeURIComponent(document.getElementById('sq').value):''); else location='foods.php'+(document.getElementById('sq').value?'?q='+encodeURIComponent(document.getElementById('sq').value):'');">
        <option value="">All Categories</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $catFilter==$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <form class="input-group input-group-sm">
        <?php if ($catFilter): ?><input type="hidden" name="category" value="<?= $catFilter ?>"><?php endif; ?>
        <input id="sq" class="form-control" name="q" placeholder="Search foods..." value="<?= e($q) ?>">
        <button class="btn btn-outline-danger"><i class="fa-solid fa-magnifying-glass"></i></button>
      </form>
    </div>
    <div class="col-md-2 text-end">
      <a href="?show_all=1<?= $catFilter ? '&category='.$catFilter : '' ?><?= $q ? '&q='.urlencode($q) : '' ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fa-solid fa-eye me-1"></i><?= $showAll ? 'Active only' : 'Show all' ?>
      </a>
    </div>
  </div>

  <?php if (empty($foods)): ?>
    <div class="text-center text-muted py-5">
      <i class="fa-solid fa-burger fa-2x mb-2 d-block opacity-50"></i>
      No food items found. <?= $showAll ? '' : 'Try "Show all" to include inactive items.' ?>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:70px">Image</th>
            <th>Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Prep</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($foods as $f): ?>
            <tr class="<?= $f['status']==0 ? 'table-light text-muted' : '' ?>">
              <td>
                <?php if (!empty($f['image'])): ?>
                  <img src="<?= $base_url ?>/assets/uploads/<?= e($f['image']) ?>" class="food-thumb" alt="<?= e($f['name']) ?>">
                <?php else: ?>
                  <div class="food-thumb d-flex align-items-center justify-content-center text-muted opacity-50"><i class="fa-solid fa-utensils"></i></div>
                <?php endif; ?>
              </td>
              <td>
                <div class="fw-semibold"><?= e($f['name']) ?></div>
                <?php if (!empty($f['description'])): ?><div class="small text-muted"><?= e($f['description']) ?></div><?php endif; ?>
              </td>
              <td><span class="badge bg-secondary"><?= e($f['category']) ?></span></td>
              <td class="price fw-bold"><?= money($f['price']) ?></td>
              <td><small><?= $f['prep_minutes'] ?> min</small></td>
              <td><?= $statusPill($f['status']) ?></td>
              <td class="text-end">
                <a href="?edit=<?= $f['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen me-1"></i>Edit</a>
                <?php if ($f['status']==1): ?>
                  <a data-confirm="Remove food item: <?= e($f['name']) ?>?" href="?delete=<?= $f['id'] ?>" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash me-1"></i>Remove</a>
                <?php else: ?>
                  <a href="?restore=<?= $f['id'] ?>" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-rotate-left me-1"></i>Restore</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php include "includes/footer.php"; ?>
