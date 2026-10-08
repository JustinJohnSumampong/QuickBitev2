<?php
require_once "../config/database.php";
require_once "../includes/functions.php";
require_role('admin');

$stats = [];
$stats['orders'] = $pdo->query("SELECT COUNT(*) c FROM orders")->fetch()['c'];
$stats['sales'] = $pdo->query("SELECT COALESCE(SUM(total_amount),0) c FROM orders WHERE order_status='Delivered'")->fetch()['c'];
$stats['customers'] = $pdo->query("SELECT COUNT(*) c FROM users WHERE role='customer'")->fetch()['c'];
$stats['pending'] = $pdo->query("SELECT COUNT(*) c FROM orders WHERE order_status IN ('Pending','Confirmed','Preparing')")->fetch()['c'];
$stats['riders'] = $pdo->query("SELECT COUNT(*) c FROM users WHERE role='rider' AND status=1")->fetch()['c'];
$stats['foods'] = $pdo->query("SELECT COUNT(*) c FROM foods WHERE status=1")->fetch()['c'];
$stats['categories'] = $pdo->query("SELECT COUNT(*) c FROM categories WHERE status=1")->fetch()['c'];
$stats['cancelled'] = $pdo->query("SELECT COUNT(*) c FROM orders WHERE order_status='Cancelled'")->fetch()['c'];

$recent = $pdo->query("SELECT o.*, u.name customer, (SELECT name FROM users WHERE id=o.rider_id) rider FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.created_at DESC LIMIT 8")->fetchAll();

$topFoods = $pdo->query("SELECT food_name, SUM(quantity) qty, SUM(subtotal) revenue FROM order_items GROUP BY food_id, food_name ORDER BY qty DESC LIMIT 5")->fetchAll();

$newCustomers = $pdo->query("SELECT name, email, created_at FROM users WHERE role='customer' ORDER BY created_at DESC LIMIT 5")->fetchAll();

$statusClass = function ($s) {
    $map = [
        'Pending' => 'pill-pending',
        'Confirmed' => 'pill-confirmed',
        'Preparing' => 'pill-preparing',
        'Ready for Pickup' => 'pill-ready',
        'Assigned to Rider' => 'pill-assigned',
        'Picked Up' => 'pill-picked',
        'Out for Delivery' => 'pill-out',
        'Delivered' => 'pill-delivered',
        'Cancelled' => 'pill-cancelled',
    ];
    return $map[$s] ?? 'pill-pending';
};

$page_title = "Dashboard";
include "includes/header.php";
?>
<h2 class="section-title mb-4">Dashboard Overview</h2>

<div class="row g-3 mb-4">
  <div class="col-md-3 col-sm-6">
    <div class="stat card p-4 h-100">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <small>Total Orders</small>
          <h2><?= $stats['orders'] ?></h2>
        </div>
        <div class="rounded-circle bg-danger bg-opacity-10 p-2 text-danger"><i class="fa-solid fa-clipboard-list fa-lg"></i></div>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-sm-6">
    <div class="stat card p-4 h-100">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <small>Total Sales</small>
          <h2><?= money($stats['sales']) ?></h2>
        </div>
        <div class="rounded-circle bg-success bg-opacity-10 p-2 text-success"><i class="fa-solid fa-peso-sign fa-lg"></i></div>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-sm-6">
    <div class="stat card p-4 h-100">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <small>Customers</small>
          <h2><?= $stats['customers'] ?></h2>
        </div>
        <div class="rounded-circle bg-primary bg-opacity-10 p-2 text-primary"><i class="fa-solid fa-users fa-lg"></i></div>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-sm-6">
    <div class="stat card p-4 h-100">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <small>Pending / Preparing</small>
          <h2><?= $stats['pending'] ?></h2>
        </div>
        <div class="rounded-circle bg-warning bg-opacity-10 p-2 text-warning"><i class="fa-solid fa-hourglass-half fa-lg"></i></div>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-sm-6">
    <div class="stat card p-4 h-100">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <small>Active Riders</small>
          <h2><?= $stats['riders'] ?></h2>
        </div>
        <div class="rounded-circle bg-info bg-opacity-10 p-2 text-info"><i class="fa-solid fa-motorcycle fa-lg"></i></div>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-sm-6">
    <div class="stat card p-4 h-100">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <small>Food Items</small>
          <h2><?= $stats['foods'] ?></h2>
        </div>
        <div class="rounded-circle bg-danger bg-opacity-10 p-2 text-danger"><i class="fa-solid fa-bowl-food fa-lg"></i></div>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-sm-6">
    <div class="stat card p-4 h-100">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <small>Categories</small>
          <h2><?= $stats['categories'] ?></h2>
        </div>
        <div class="rounded-circle bg-dark bg-opacity-10 p-2 text-dark"><i class="fa-solid fa-tags fa-lg"></i></div>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-sm-6">
    <div class="stat card p-4 h-100">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <small>Cancelled</small>
          <h2><?= $stats['cancelled'] ?></h2>
        </div>
        <div class="rounded-circle bg-danger bg-opacity-10 p-2 text-danger"><i class="fa-solid fa-circle-xmark fa-lg"></i></div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-7 recent-orders">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0 fw-bold"><i class="fa-solid fa-list-check me-2 text-danger"></i>Recent Orders</h5>
      <a href="orders.php" class="btn btn-sm btn-outline-danger">View all <i class="fa-solid fa-arrow-right ms-1"></i></a>
    </div>
    <?php if (empty($recent)): ?>
      <div class="order-card text-center text-muted py-4">
        <i class="fa-solid fa-inbox fa-2x mb-2 d-block opacity-50"></i>
        No orders yet.
      </div>
    <?php else: ?>
      <?php foreach ($recent as $o): ?>
        <div class="order-card d-flex flex-wrap align-items-center justify-content-between gap-2">
          <div>
            <div class="fw-bold"><?= e($o['order_number']) ?></div>
            <div class="small text-muted">
              <i class="fa-regular fa-user me-1"></i><?= e($o['customer']) ?>
              <?php if (!empty($o['rider'])): ?>
                <span class="ms-2"><i class="fa-solid fa-motorcycle me-1"></i><?= e($o['rider']) ?></span>
              <?php endif; ?>
              <span class="ms-2"><i class="fa-regular fa-clock me-1"></i><?= date('M j, g:ia', strtotime($o['created_at'])) ?></span>
            </div>
          </div>
          <div class="d-flex align-items-center gap-3">
            <div class="text-end">
              <div class="fw-bold price"><?= money($o['total_amount']) ?></div>
              <span class="pill <?= $statusClass($o['order_status']) ?>"><?= e($o['order_status']) ?></span>
            </div>
            <a href="orders.php" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-eye"></i></a>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="col-lg-5">
    <div class="card form-card p-4 mb-4">
      <h5 class="fw-bold mb-3"><i class="fa-solid fa-fire me-2 text-danger"></i>Best Sellers</h5>
      <?php if (empty($topFoods)): ?>
        <div class="text-center text-muted py-3 small">No data yet.</div>
      <?php else: ?>
        <?php foreach ($topFoods as $i => $t): ?>
          <div class="d-flex justify-content-between align-items-center py-2 border-bottom small">
            <div><span class="badge bg-danger me-2"><?= $i+1 ?></span><?= e($t['food_name']) ?></div>
            <div class="text-end">
              <div class="fw-bold"><?= $t['qty'] ?> sold</div>
              <div class="text-muted"><?= money($t['revenue']) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="card form-card p-4">
      <h5 class="fw-bold mb-3"><i class="fa-solid fa-user-plus me-2 text-danger"></i>New Customers</h5>
      <?php if (empty($newCustomers)): ?>
        <div class="text-center text-muted py-3 small">No customers yet.</div>
      <?php else: ?>
        <?php foreach ($newCustomers as $c): ?>
          <div class="d-flex justify-content-between align-items-center py-2 border-bottom small">
            <div>
              <div class="fw-semibold"><?= e($c['name']) ?></div>
              <div class="text-muted"><?= e($c['email']) ?></div>
            </div>
            <div class="text-muted text-end">
              <?= date('M j', strtotime($c['created_at'])) ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include "includes/footer.php"; ?>
