<?php
require_once "../config/database.php";
require_once "../includes/functions.php";
require_role('rider');

$riderId = $_SESSION['user']['id'];
$statuses = ['Assigned to Rider','Picked Up','Out for Delivery','Delivered'];

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

$s = $pdo->prepare("SELECT o.*, u.name customer, a.recipient_name, a.phone, a.address FROM orders o JOIN users u ON u.id=o.user_id JOIN delivery_addresses a ON a.id=o.delivery_address_id WHERE o.rider_id=? OR (o.rider_id IS NULL AND o.order_status IN ('Ready for Pickup','Assigned to Rider')) ORDER BY (o.order_status='Delivered'), o.created_at DESC");
$s->execute([$riderId]);
$orders = $s->fetchAll();

$mine = array_filter($orders, fn($o) => (int)$o['rider_id'] === $riderId && $o['order_status'] !== 'Delivered');
$available = array_filter($orders, fn($o) => $o['rider_id'] === null);
$completed = array_filter($orders, fn($o) => (int)$o['rider_id'] === $riderId && $o['order_status'] === 'Delivered');

$stats = [
    'pending' => count(array_filter($mine, fn($o) => in_array($o['order_status'], ['Assigned to Rider','Picked Up','Out for Delivery']))),
    'completed' => count($completed),
    'earnings' => 0,
];

$page_title = "Dashboard";
include "includes/header.php";
?>
<h2 class="section-title mb-4">My Deliveries</h2>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="stat card p-4">
      <div class="d-flex justify-content-between">
        <div>
          <small>In Progress</small>
          <h2><?= $stats['pending'] ?></h2>
        </div>
        <div class="rounded-circle bg-warning bg-opacity-10 p-2 text-warning"><i class="fa-solid fa-truck-fast fa-lg"></i></div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat card p-4">
      <div class="d-flex justify-content-between">
        <div>
          <small>Completed Today</small>
          <h2><?= count($completed) ?></h2>
        </div>
        <div class="rounded-circle bg-success bg-opacity-10 p-2 text-success"><i class="fa-solid fa-circle-check fa-lg"></i></div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat card p-4">
      <div class="d-flex justify-content-between">
        <div>
          <small>Available Pool</small>
          <h2><?= count($available) ?></h2>
        </div>
        <div class="rounded-circle bg-info bg-opacity-10 p-2 text-info"><i class="fa-solid fa-clipboard-list fa-lg"></i></div>
      </div>
    </div>
  </div>
</div>

<?php if (!empty($mine)): ?>
<div class="card form-card p-4 mb-4 border-2 border-danger">
  <h5 class="fw-bold mb-3"><i class="fa-solid fa-bell me-2 text-danger"></i>My Active Deliveries (<?= count($mine) ?>)</h5>
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light"><tr><th>Order #</th><th>Customer</th><th>Address</th><th>Total</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($mine as $o): ?>
          <tr>
            <td class="fw-bold"><?= e($o['order_number']) ?></td>
            <td>
              <div class="fw-semibold"><?= e($o['recipient_name'] ?: $o['customer']) ?></div>
              <div class="small text-muted"><i class="fa-solid fa-phone me-1"></i><?= e($o['phone']) ?></div>
            </td>
            <td class="small"><?= e(substr($o['address'], 0, 60)) ?><?= strlen($o['address'])>60 ? '...' : '' ?></td>
            <td class="price fw-bold"><?= money($o['total_amount']) ?></td>
            <td><span class="pill <?= $statusClass($o['order_status']) ?>"><?= e($o['order_status']) ?></span></td>
            <td><a class="btn btn-sm btn-danger" href="delivery.php?id=<?= $o['id'] ?>"><i class="fa-solid fa-route me-1"></i>Manage</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($available)): ?>
<div class="card form-card p-4 mb-4">
  <h5 class="fw-bold mb-3"><i class="fa-solid fa-list me-2 text-info"></i>Open for Pickup — Claim one! (<?= count($available) ?>)</h5>
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light"><tr><th>Order #</th><th>Customer</th><th>Phone</th><th>Address</th><th>Total</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($available as $o): ?>
          <tr>
            <td class="fw-bold"><?= e($o['order_number']) ?></td>
            <td class="fw-semibold"><?= e($o['recipient_name'] ?: $o['customer']) ?></td>
            <td><i class="fa-solid fa-phone me-1 text-success"></i><?= e($o['phone']) ?></td>
            <td class="small"><?= e(substr($o['address'], 0, 50)) ?><?= strlen($o['address'])>50 ? '...' : '' ?></td>
            <td class="price fw-bold"><?= money($o['total_amount']) ?></td>
            <td><span class="pill <?= $statusClass($o['order_status']) ?>"><?= e($o['order_status']) ?></span></td>
            <td><a class="btn btn-sm btn-outline-danger" href="delivery.php?id=<?= $o['id'] ?>"><i class="fa-solid fa-hand me-1"></i>Claim</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($completed)): ?>
<div class="card form-card p-4">
  <h5 class="fw-bold mb-3 text-success"><i class="fa-solid fa-circle-check me-2"></i>Recently Delivered (<?= count($completed) ?>)</h5>
  <div class="table-responsive">
    <table class="table align-middle mb-0 table-sm">
      <thead class="table-light"><tr><th>Order #</th><th>Customer</th><th>Total</th><th></th></tr></thead>
      <tbody class="text-muted">
        <?php foreach (array_slice($completed, 0, 10) as $o): ?>
          <tr>
            <td><?= e($o['order_number']) ?></td>
            <td><?= e($o['recipient_name'] ?: $o['customer']) ?></td>
            <td class="fw-semibold"><?= money($o['total_amount']) ?></td>
            <td><a class="btn btn-sm btn-outline-secondary" href="delivery.php?id=<?= $o['id'] ?>">View</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if (empty($orders)): ?>
  <div class="card form-card p-5 text-center text-muted">
    <i class="fa-solid fa-motorcycle fa-3x mb-3 opacity-50"></i>
    <h5>No deliveries right now.</h5>
    <p class="mb-0">Check back soon — new orders pop up here!</p>
  </div>
<?php endif; ?>

<?php include "includes/footer.php"; ?>
