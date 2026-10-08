<?php
require_once "../config/database.php";
require_once "../includes/functions.php";
require_role('admin');

$statuses = ['Pending','Confirmed','Preparing','Ready for Pickup','Assigned to Rider','Picked Up','Out for Delivery','Delivered','Cancelled'];
$statusOrder = array_flip($statuses);

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $riderId = !empty($_POST['rider_id']) ? (int)$_POST['rider_id'] : null;
    if (in_array($status, $statuses, true)) {
        if (array_key_exists('rider_id', $_POST)) {
            // Form includes the rider selector: update both, clearing the rider
            // when "— No rider —" is chosen.
            $pdo->prepare("UPDATE orders SET order_status=?, rider_id=? WHERE id=?")->execute([$status, $riderId, $id]);
        } else {
            $pdo->prepare("UPDATE orders SET order_status=? WHERE id=?")->execute([$status, $id]);
        }
        flash('success', 'Order updated.');
    } else {
        flash('error', 'Invalid order status.');
    }
    redirect('orders.php' . (isset($_GET['id']) ? '?id='.(int)$_GET['id'] : ''));
}

$riders = $pdo->query("SELECT id, name, email FROM users WHERE role='rider' AND status=1 ORDER BY name")->fetchAll();

$orderDetails = null;
if (isset($_GET['id'])) {
    $oid = (int)$_GET['id'];
    $s = $pdo->prepare("SELECT o.*, u.name customer, u.email customer_email, u.phone customer_phone, r.name rider_name FROM orders o JOIN users u ON u.id=o.user_id LEFT JOIN users r ON r.id=o.rider_id WHERE o.id=?");
    $s->execute([$oid]);
    $orderDetails = $s->fetch();
    if ($orderDetails) {
        $items = $pdo->prepare("SELECT * FROM order_items WHERE order_id=?");
        $items->execute([$oid]);
        $orderDetails['items'] = $items->fetchAll();
        $addr = $pdo->prepare("SELECT * FROM delivery_addresses WHERE id=?");
        $addr->execute([$orderDetails['delivery_address_id']]);
        $orderDetails['address'] = $addr->fetch();
    }
}

$filterStatus = $_GET['status'] ?? '';
$whereSql = "1=1";
$params = [];
if ($filterStatus) {
    $whereSql = "o.order_status=?";
    $params[] = $filterStatus;
}

$orders = $pdo->prepare("SELECT o.*, u.name customer, r.name rider_name, (SELECT SUM(quantity) FROM order_items WHERE order_id=o.id) items_cnt
    FROM orders o JOIN users u ON u.id=o.user_id LEFT JOIN users r ON r.id=o.rider_id
    WHERE $whereSql ORDER BY o.created_at DESC LIMIT 100");
$orders->execute($params);
$orders = $orders->fetchAll();

$counts = $pdo->query("SELECT order_status, COUNT(*) c FROM orders GROUP BY order_status")->fetchAll(PDO::FETCH_KEY_PAIR);
$totalOrders = array_sum($counts);

$page_title = "Orders";
include "includes/header.php";
?>
<h2 class="section-title mb-4">Manage Orders</h2>

<div class="d-flex flex-wrap gap-2 mb-4 align-items-center">
  <a href="orders.php" class="btn btn-sm <?= !$filterStatus ? 'btn-danger' : 'btn-outline-secondary' ?>">
    All <span class="badge bg-white text-danger ms-1"><?= $totalOrders ?></span>
  </a>
  <?php foreach ($statuses as $st): ?>
    <?php $cnt = $counts[$st] ?? 0; if ($cnt==0 && $st!==$filterStatus) continue; ?>
    <a href="?status=<?= urlencode($st) ?>" class="btn btn-sm <?= $filterStatus===$st ? 'btn-danger' : 'btn-outline-secondary' ?>">
      <?= e($st) ?> <span class="badge bg-white text-danger ms-1"><?= $cnt ?></span>
    </a>
  <?php endforeach; ?>
</div>

<?php if ($orderDetails): ?>
<div class="card form-card p-4 mb-4 border-2 border-danger">
  <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-3">
    <div>
      <h4 class="fw-bold mb-1"><i class="fa-solid fa-receipt me-2 text-danger"></i>Order #<?= e($orderDetails['order_number']) ?></h4>
      <div class="text-muted small">
        <i class="fa-regular fa-calendar me-1"></i><?= date('F j, Y g:ia', strtotime($orderDetails['created_at'])) ?>
        <?php if (!empty($orderDetails['updated_at']) && $orderDetails['updated_at']!==$orderDetails['created_at']): ?>
          · <i class="fa-regular fa-clock me-1"></i>Updated <?= date('g:ia', strtotime($orderDetails['updated_at'])) ?>
        <?php endif; ?>
      </div>
    </div>
    <div class="text-end">
      <span class="pill <?= $statusClass($orderDetails['order_status']) ?> fs-6"><?= e($orderDetails['order_status']) ?></span>
      <div class="mt-1"><a href="orders.php" class="btn btn-sm btn-outline-secondary mt-1"><i class="fa-solid fa-xmark me-1"></i>Close details</a></div>
    </div>
  </div>

  <div class="order-progress mb-4">
    <?php
    $currentIdx = $statusOrder[$orderDetails['order_status']] ?? -1;
    $showStatuses = array_slice($statuses, 0, 8);
    ?>
    <?php foreach ($showStatuses as $i => $st): ?>
      <?php
      $cls = '';
      if ($i < $currentIdx) $cls = 'done';
      elseif ($i == $currentIdx) $cls = 'active';
      ?>
      <div class="step <?= $cls ?>">
        <i class="fa-solid <?= $i <= $currentIdx ? 'fa-circle-check' : 'fa-circle' ?> me-1"></i>
        <?= e($st) ?>
      </div>
    <?php endforeach; ?>
  </div>

  <form method="post" class="card card-body bg-light mb-4">
    <div class="row g-3 align-items-end">
      <div class="col-md-5">
        <label class="form-label small fw-semibold text-muted">Update Status</label>
        <select name="status" class="form-select form-select-lg">
          <?php foreach ($statuses as $st): ?>
            <option <?= $orderDetails['order_status']===$st ? 'selected' : '' ?>><?= e($st) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-semibold text-muted">Assign Rider</label>
        <select name="rider_id" class="form-select form-select-lg">
          <option value="">— No rider —</option>
          <?php foreach ($riders as $r): ?>
            <option value="<?= $r['id'] ?>" <?= $orderDetails['rider_id']==$r['id'] ? 'selected' : '' ?>>
              <?= e($r['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <input type="hidden" name="id" value="<?= $orderDetails['id'] ?>">
        <button type="submit" name="update_status" value="1" class="btn btn-lg btn-danger w-100"><i class="fa-solid fa-floppy-disk me-1"></i>Save Order</button>
      </div>
    </div>
  </form>

  <div class="row g-4">
    <div class="col-lg-7">
      <div class="card order-item p-4 mb-3">
        <h6 class="fw-bold mb-3"><i class="fa-solid fa-bag-shopping me-2 text-danger"></i>Order Items (<?= count($orderDetails['items']) ?>)</h6>
        <div class="table-responsive">
          <table class="table mb-0">
            <thead class="table-light">
              <tr><th>Food</th><th class="text-center">Qty</th><th class="text-end">Price</th><th class="text-end">Subtotal</th></tr>
            </thead>
            <tbody>
              <?php foreach ($orderDetails['items'] as $it): ?>
                <tr>
                  <td class="fw-semibold"><?= e($it['food_name']) ?></td>
                  <td class="text-center"><span class="badge bg-secondary"><?= $it['quantity'] ?></span></td>
                  <td class="text-end"><?= money($it['price']) ?></td>
                  <td class="text-end fw-bold"><?= money($it['subtotal']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light fw-bold">
              <tr><td colspan="3" class="text-end">Subtotal</td><td class="text-end"><?= money($orderDetails['subtotal']) ?></td></tr>
              <tr><td colspan="3" class="text-end">Delivery Fee</td><td class="text-end"><?= money($orderDetails['delivery_fee']) ?></td></tr>
              <?php if ($orderDetails['discount']>0): ?>
                <tr><td colspan="3" class="text-end text-danger">Discount</td><td class="text-end text-danger">- <?= money($orderDetails['discount']) ?></td></tr>
              <?php endif; ?>
              <tr class="fs-5"><td colspan="3" class="text-end price">TOTAL</td><td class="text-end price"><?= money($orderDetails['total_amount']) ?></td></tr>
            </tfoot>
          </table>
        </div>
      </div>

      <div class="card order-item p-4">
        <h6 class="fw-bold mb-3"><i class="fa-solid fa-credit-card me-2 text-danger"></i>Payment</h6>
        <div class="row g-2 small">
          <div class="col-sm-4 text-muted">Method</div>
          <div class="col-sm-8 fw-semibold"><?= e($orderDetails['payment_method']) ?></div>
          <div class="col-sm-4 text-muted">Status</div>
          <div class="col-sm-8 fw-semibold"><span class="pill <?= $orderDetails['payment_status']==='Paid' ? 'pill-delivered' : 'pill-pending' ?>"><?= e($orderDetails['payment_status']) ?></span></div>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card order-item p-4 mb-3">
        <h6 class="fw-bold mb-3"><i class="fa-solid fa-user me-2 text-danger"></i>Customer</h6>
        <div class="fw-bold"><?= e($orderDetails['customer']) ?></div>
        <div class="small text-muted"><i class="fa-regular fa-envelope me-1"></i><?= e($orderDetails['customer_email']) ?></div>
        <?php if (!empty($orderDetails['customer_phone'])): ?>
          <div class="small text-muted"><i class="fa-solid fa-phone me-1"></i><?= e($orderDetails['customer_phone']) ?></div>
        <?php endif; ?>
      </div>

      <div class="card order-item p-4 mb-3">
        <h6 class="fw-bold mb-3"><i class="fa-solid fa-location-dot me-2 text-danger"></i>Delivery Address</h6>
        <?php if ($orderDetails['address']): ?>
          <div class="fw-bold"><?= e($orderDetails['address']['recipient_name']) ?></div>
          <div class="small text-muted mb-2"><i class="fa-solid fa-phone me-1"></i><?= e($orderDetails['address']['phone']) ?></div>
          <div class="small"><?= nl2br(e($orderDetails['address']['address'])) ?></div>
        <?php else: ?>
          <div class="text-muted small">Address not found.</div>
        <?php endif; ?>
      </div>

      <div class="card order-item p-4">
        <h6 class="fw-bold mb-3"><i class="fa-solid fa-motorcycle me-2 text-danger"></i>Assigned Rider</h6>
        <?php if (!empty($orderDetails['rider_name'])): ?>
          <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width:40px;height:40px;font-weight:700">
              <?= strtoupper(substr($orderDetails['rider_name'], 0, 1)) ?>
            </div>
            <div>
              <div class="fw-bold"><?= e($orderDetails['rider_name']) ?></div>
              <div class="small text-success"><i class="fa-solid fa-circle-check me-1"></i>Assigned</div>
            </div>
          </div>
        <?php else: ?>
          <div class="text-muted small"><i class="fa-solid fa-circle-xmark me-1"></i>No rider assigned yet.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="card form-card p-4">
  <h5 class="fw-bold mb-3"><i class="fa-solid fa-list-check me-2 text-danger"></i>
    <?= $filterStatus ? e($filterStatus) . ' Orders' : 'All Orders' ?> (<?= count($orders) ?>)
  </h5>
  <?php if (empty($orders)): ?>
    <div class="text-center text-muted py-5"><i class="fa-solid fa-inbox fa-2x mb-2 d-block opacity-50"></i>No orders found.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Order #</th>
            <th>Customer</th>
            <th>Items</th>
            <th>Total</th>
            <th>Payment</th>
            <th>Status</th>
            <th>Rider</th>
            <th>Date</th>
            <th class="text-end">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
            <tr class="<?= ($orderDetails && $orderDetails['id']==$o['id']) ? 'table-danger' : '' ?>">
              <td class="fw-bold"><?= e($o['order_number']) ?></td>
              <td><?= e($o['customer']) ?></td>
              <td><span class="badge bg-secondary"><?= $o['items_cnt'] ?> items</span></td>
              <td class="price fw-bold"><?= money($o['total_amount']) ?></td>
              <td>
                <div class="small fw-semibold"><?= e($o['payment_method']) ?></div>
                <div class="small"><span class="pill <?= $o['payment_status']==='Paid' ? 'pill-delivered' : 'pill-pending' ?>"><?= e($o['payment_status']) ?></span></div>
              </td>
              <td><span class="pill <?= $statusClass($o['order_status']) ?>"><?= e($o['order_status']) ?></span></td>
              <td class="small">
                <?php if (!empty($o['rider_name'])): ?>
                  <span class="fw-semibold"><i class="fa-solid fa-motorcycle me-1 text-success"></i><?= e($o['rider_name']) ?></span>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td><small class="text-muted"><?= date('M j · g:ia', strtotime($o['created_at'])) ?></small></td>
              <td class="text-end">
                <form method="post" class="d-flex flex-wrap gap-1 justify-content-end">
                  <input type="hidden" name="id" value="<?= $o['id'] ?>">
                  <input type="hidden" name="update_status" value="1">
                  <select name="status" class="form-select form-select-sm" style="width:auto">
                    <?php foreach ($statuses as $st): ?>
                      <option <?= $o['order_status']==$st ? 'selected' : '' ?>><?= e($st) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <?php if (!empty($riders)): ?>
                    <select name="rider_id" class="form-select form-select-sm" style="width:auto" title="Assign rider">
                      <option value="">Rider</option>
                      <?php foreach ($riders as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= $o['rider_id']==$r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  <?php endif; ?>
                  <button class="btn btn-sm btn-danger" title="Save"><i class="fa-solid fa-check"></i></button>
                  <a href="?id=<?= $o['id'] ?><?= $filterStatus ? '&status='.urlencode($filterStatus) : '' ?>" class="btn btn-sm btn-outline-primary" title="View details"><i class="fa-solid fa-eye"></i></a>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php include "includes/footer.php"; ?>
