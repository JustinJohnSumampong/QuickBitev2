<?php
require_once "../config/database.php";
require_once "../includes/functions.php";
require_role('admin');

$sales = $pdo->query("SELECT COALESCE(SUM(total_amount),0) total FROM orders WHERE order_status='Delivered'")->fetch()['total'];
$count = $pdo->query("SELECT COUNT(*) c FROM orders")->fetch()['c'];
$del = $pdo->query("SELECT COUNT(*) c FROM orders WHERE order_status='Delivered'")->fetch()['c'];
$cancelled = $pdo->query("SELECT COUNT(*) c FROM orders WHERE order_status='Cancelled'")->fetch()['c'];
$pending = $count - $del - $cancelled;

$avgOrder = $del > 0 ? ($sales / $del) : 0;

$top = $pdo->query("SELECT food_name, SUM(quantity) qty, SUM(subtotal) revenue FROM order_items GROUP BY food_id, food_name ORDER BY qty DESC LIMIT 10")->fetchAll();

$perStatusFlat = [];
foreach ($pdo->query("SELECT order_status, COUNT(*) cnt, COALESCE(SUM(total_amount),0) amt FROM orders GROUP BY order_status")->fetchAll() as $row) {
    $perStatusFlat[$row['order_status']] = ['cnt' => $row['cnt'], 'amt' => $row['amt']];
}

$topCustomers = $pdo->query("SELECT u.name, u.email, COUNT(o.id) orders, COALESCE(SUM(o.total_amount),0) spent FROM users u LEFT JOIN orders o ON o.user_id=u.id AND o.order_status='Delivered' WHERE u.role='customer' GROUP BY u.id ORDER BY spent DESC LIMIT 8")->fetchAll();

$topRiders = $pdo->query("SELECT u.name, COUNT(o.id) deliveries, COALESCE(SUM(o.total_amount),0) handled FROM users u LEFT JOIN orders o ON o.rider_id=u.id AND o.order_status='Delivered' WHERE u.role='rider' GROUP BY u.id ORDER BY deliveries DESC LIMIT 5")->fetchAll();

$monthly = $pdo->query("SELECT DATE_FORMAT(created_at,'%Y-%m') ym, COUNT(*) cnt, COALESCE(SUM(total_amount),0) amt FROM orders WHERE order_status='Delivered' GROUP BY ym ORDER BY ym DESC LIMIT 12")->fetchAll();

$page_title = "Reports";
include "includes/header.php";
?>
<h2 class="section-title mb-4">Reports & Analytics</h2>

<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="stat card p-4">
      <small>Total Orders</small>
      <h2><?= $count ?></h2>
      <div class="small text-muted mt-1"><?= $del ?> delivered · <?= $pending ?> in-progress · <?= $cancelled ?> cancelled</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat card p-4">
      <small>Delivered Sales</small>
      <h2><?= money($sales) ?></h2>
      <div class="small text-muted mt-1">from <?= $del ?> delivered orders</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat card p-4">
      <small>Avg. Order Value</small>
      <h2><?= money($avgOrder) ?></h2>
      <div class="small text-muted mt-1">per delivered order</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat card p-4">
      <small>Fulfillment Rate</small>
      <h2><?= $count > 0 ? round(($del / $count) * 100, 1) : 0 ?>%</h2>
      <div class="small text-muted mt-1"><?= $count > 0 ? round(($cancelled / $count) * 100, 1) : 0 ?>% cancellation</div>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="card form-card p-4 mb-4">
      <h5 class="fw-bold mb-3"><i class="fa-solid fa-chart-line me-2 text-danger"></i>Monthly Sales (Last 12 months)</h5>
      <?php if (empty($monthly)): ?>
        <div class="text-center text-muted py-4 small">No delivered orders yet.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table mb-0">
            <thead class="table-light">
              <tr><th>Month</th><th>Orders</th><th class="text-end">Revenue</th></tr>
            </thead>
            <tbody>
              <?php $maxAmt = max(array_column($monthly, 'amt')) ?: 1; ?>
              <?php foreach ($monthly as $m): ?>
                <tr>
                  <td class="fw-semibold"><?= date('F Y', strtotime($m['ym'] . '-01')) ?></td>
                  <td>
                    <span class="badge bg-danger"><?= $m['cnt'] ?> orders</span>
                    <div class="progress mt-2" style="height:6px">
                      <div class="progress-bar bg-danger" style="width:<?= ($m['amt'] / $maxAmt) * 100 ?>%"></div>
                    </div>
                  </td>
                  <td class="text-end fw-bold price"><?= money($m['amt']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="card form-card p-4">
      <h5 class="fw-bold mb-3"><i class="fa-solid fa-fire me-2 text-danger"></i>Best Selling Food (<?= count($top) ?>)</h5>
      <?php if (empty($top)): ?>
        <div class="text-center text-muted py-4 small">No items sold yet.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>#</th><th>Food</th><th class="text-end">Qty Sold</th><th class="text-end">Revenue</th></tr></thead>
            <tbody>
              <?php $maxQty = max(array_column($top, 'qty')) ?: 1; ?>
              <?php foreach ($top as $i => $t): ?>
                <tr>
                  <td><span class="badge bg-danger"><?= $i+1 ?></span></td>
                  <td class="fw-semibold">
                    <?= e($t['food_name']) ?>
                    <div class="progress mt-2" style="height:5px">
                      <div class="progress-bar bg-danger" style="width:<?= ($t['qty'] / $maxQty) * 100 ?>%"></div>
                    </div>
                  </td>
                  <td class="text-end fw-bold"><?= $t['qty'] ?></td>
                  <td class="text-end price fw-bold"><?= money($t['revenue']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card form-card p-4 mb-4">
      <h5 class="fw-bold mb-3"><i class="fa-solid fa-users me-2 text-danger"></i>Top Customers (by spending)</h5>
      <?php if (empty(array_filter($topCustomers, fn($c)=>$c['spent']>0))): ?>
        <div class="text-center text-muted py-4 small">No delivered orders yet.</div>
      <?php else: ?>
        <?php foreach (array_filter($topCustomers, fn($c)=>$c['spent']>0) as $i => $c): ?>
          <div class="d-flex align-items-center py-2 border-bottom small">
            <span class="badge bg-danger me-2"><?= $i+1 ?></span>
            <div class="flex-grow-1">
              <div class="fw-semibold"><?= e($c['name']) ?></div>
              <div class="text-muted"><?= $c['orders'] ?> orders</div>
            </div>
            <div class="text-end price fw-bold"><?= money($c['spent']) ?></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="card form-card p-4 mb-4">
      <h5 class="fw-bold mb-3"><i class="fa-solid fa-motorcycle me-2 text-danger"></i>Top Riders</h5>
      <?php if (empty(array_filter($topRiders, fn($r)=>$r['deliveries']>0))): ?>
        <div class="text-center text-muted py-4 small">No deliveries yet.</div>
      <?php else: ?>
        <?php foreach (array_filter($topRiders, fn($r)=>$r['deliveries']>0) as $r): ?>
          <div class="d-flex align-items-center py-2 border-bottom small">
            <div class="rounded-circle bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center me-2" style="width:32px;height:32px;font-weight:700">
              <?= strtoupper(substr($r['name'], 0, 1)) ?>
            </div>
            <div class="flex-grow-1">
              <div class="fw-semibold"><?= e($r['name']) ?></div>
              <div class="text-muted"><?= $r['deliveries'] ?> deliveries</div>
            </div>
            <div class="text-end price fw-bold"><?= money($r['handled']) ?></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="card form-card p-4">
      <h5 class="fw-bold mb-3"><i class="fa-solid fa-chart-pie me-2 text-danger"></i>Orders by Status</h5>
      <?php if (empty($perStatusFlat)): ?>
        <div class="text-center text-muted py-4 small">No orders yet.</div>
      <?php else: ?>
        <?php foreach ($perStatusFlat as $st => $v): ?>
          <div class="d-flex justify-content-between align-items-center py-2 border-bottom small">
            <span class="fw-semibold"><?= e($st) ?></span>
            <div class="text-end">
              <span class="badge bg-secondary"><?= $v['cnt'] ?></span>
              <span class="ms-2 price fw-bold"><?= money($v['amt']) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include "includes/footer.php"; ?>
