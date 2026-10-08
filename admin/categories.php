<?php
require_once "../config/database.php";
require_once "../includes/functions.php";
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    if (isset($_POST['edit_id']) && $_POST['edit_id']) {
        $id = (int)$_POST['edit_id'];
        if ($name) {
            $pdo->prepare("UPDATE categories SET name=? WHERE id=?")->execute([$name, $id]);
            flash('success', 'Category updated.');
        } else {
            flash('error', 'Category name is required.');
        }
    } else {
        if ($name) {
            $pdo->prepare("INSERT INTO categories(name,status) VALUES(?,1)")->execute([$name]);
            flash('success', 'Category added.');
        } else {
            flash('error', 'Category name is required.');
        }
    }
    redirect('categories.php');
}

if (isset($_GET['delete'])) {
    $pdo->prepare("UPDATE categories SET status=0 WHERE id=?")->execute([(int)$_GET['delete']]);
    flash('success', 'Category removed.');
    redirect('categories.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $e = $pdo->prepare("SELECT * FROM categories WHERE id=?");
    $e->execute([(int)$_GET['edit']]);
    $edit = $e->fetch();
}

$cats = $pdo->query("SELECT *,(SELECT COUNT(*) FROM foods WHERE category_id=categories.id AND status=1) items FROM categories WHERE status=1 ORDER BY name")->fetchAll();
$inactive = $pdo->query("SELECT * FROM categories WHERE status=0 ORDER BY name")->fetchAll();

$page_title = "Categories";
include "includes/header.php";
?>
<h2 class="section-title mb-4">Categories Management</h2>

<div class="card form-card p-4 mb-4">
  <h5 class="fw-bold mb-3"><i class="fa-solid fa-pen-to-square me-2 text-danger"></i><?= $edit ? 'Edit Category' : 'Add New Category' ?></h5>
  <form method="post" class="row g-2 align-items-end">
    <?php if ($edit): ?><input type="hidden" name="edit_id" value="<?= $edit['id'] ?>"><?php endif; ?>
    <div class="col-md-8">
      <input name="name" class="form-control form-control-lg" placeholder="Category name (e.g. Burgers, Pizza, Beverages)" value="<?= $edit ? e($edit['name']) : '' ?>" required>
    </div>
    <div class="col-md-4 d-flex gap-2">
      <?php if ($edit): ?><a href="categories.php" class="btn btn-lg btn-outline-secondary flex-grow-1">Cancel</a><?php endif; ?>
      <button class="btn btn-lg btn-danger flex-grow-1"><i class="fa-solid <?= $edit ? 'fa-floppy-disk' : 'fa-plus' ?> me-1"></i><?= $edit ? 'Save Changes' : 'Add Category' ?></button>
    </div>
  </form>
</div>

<div class="card form-card p-4 mb-4">
  <h5 class="fw-bold mb-3"><i class="fa-solid fa-tags me-2 text-danger"></i>Active Categories (<?= count($cats) ?>)</h5>
  <?php if (empty($cats)): ?>
    <div class="text-center text-muted py-5"><i class="fa-solid fa-inbox fa-2x mb-2 d-block opacity-50"></i>No categories yet.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Food Items</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($cats as $i => $c): ?>
            <tr>
              <td><?= $i+1 ?></td>
              <td class="fw-semibold"><?= e($c['name']) ?></td>
              <td>
                <span class="badge bg-secondary"><?= $c['items'] ?> items</span>
              </td>
              <td class="text-end">
                <a href="?edit=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen me-1"></i>Edit</a>
                <a data-confirm="Remove category <?= e($c['name']) ?>?" href="?delete=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash me-1"></i>Remove</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php if (!empty($inactive)): ?>
  <div class="card form-card p-4">
    <h5 class="fw-bold mb-3 text-muted"><i class="fa-solid fa-trash-can me-2"></i>Inactive Categories (<?= count($inactive) ?>)</h5>
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <tbody>
          <?php foreach ($inactive as $c): ?>
            <tr class="text-muted">
              <td class="fw-semibold"><?= e($c['name']) ?></td>
              <td class="text-end"><small>Removed</small></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php include "includes/footer.php"; ?>
