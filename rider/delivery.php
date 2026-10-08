<?php
require_once "../config/database.php";
require_once "../includes/functions.php";
require_role('rider');

$riderId = $_SESSION['user']['id'];
$id = (int)($_GET['id'] ?? 0);

$s = $pdo->prepare("SELECT o.*, u.name customer, u.email customer_email, u.phone customer_phone, a.recipient_name, a.phone recipient_phone, a.address FROM orders o JOIN users u ON u.id=o.user_id JOIN delivery_addresses a ON a.id=o.delivery_address_id WHERE o.id=?");
$s->execute([$id]);
$o = $s->fetch();
if (!$o) { http_response_code(404); die("Order not found."); }

$items = $pdo->prepare("SELECT * FROM order_items WHERE order_id=?");
$items->execute([$id]);
$items = $items->fetchAll();

$canClaim = ($o['rider_id'] === null && in_array($o['order_status'], ['Ready for Pickup','Assigned to Rider']));
$isMine = ((int)$o['rider_id'] === $riderId);

// Riders may only open their own orders or orders available to claim.
if (!$isMine && !$canClaim) { http_response_code(403); die("Access denied."); }

$statuses = ['Assigned to Rider','Picked Up','Out for Delivery','Delivered'];

$statusOrder = ['Pending','Confirmed','Preparing','Ready for Pickup','Assigned to Rider','Picked Up','Out for Delivery','Delivered'];
$statusOrderFlip = array_flip($statusOrder);
$currentIdx = $statusOrderFlip[$o['order_status']] ?? -1;

$statusClass = function ($s) {
    $map = [
        'Pending' => 'pill-pending','Confirmed' => 'pill-confirmed','Preparing' => 'pill-preparing',
        'Ready for Pickup' => 'pill-ready','Assigned to Rider' => 'pill-assigned',
        'Picked Up' => 'pill-picked','Out for Delivery' => 'pill-out',
        'Delivered' => 'pill-delivered','Cancelled' => 'pill-cancelled',
    ];
    return $map[$s] ?? 'pill-pending';
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $st = $_POST['status'] ?? '';
    if (in_array($st, $statuses, true) && ($isMine || $canClaim)) {
        $pdo->prepare("UPDATE orders SET rider_id=?, order_status=? WHERE id=?")->execute([$riderId, $st, $id]);
        flash('success', 'Delivery status updated.');
        redirect('delivery.php?id='.$id);
    }
}

$page_title = "Delivery {$o['order_number']}";
include "includes/header.php";
?>
<div class="d-flex align-items-center gap-2 mb-4">
  <a href="dashboard.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Back to Deliveries</a>
</div>

<div class="card form-card p-4 mb-4 border-2 border-danger">
  <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-3">
    <div>
      <h4 class="fw-bold mb-1"><i class="fa-solid fa-receipt me-2 text-danger"></i>Order #<?= e($o['order_number']) ?></h4>
      <div class="text-muted small">
        <i class="fa-regular fa-calendar me-1"></i><?= date('F j, Y g:ia', strtotime($o['created_at'])) ?>
        <?php if ($o['updated_at'] !== $o['created_at']): ?>
          · Updated <?= date('g:ia', strtotime($o['updated_at'])) ?>
        <?php endif; ?>
      </div>
    </div>
    <div class="text-end">
      <span class="pill <?= $statusClass($o['order_status']) ?> fs-6 mb-1 d-inline-block"><?= e($o['order_status']) ?></span>
      <?php if ($o['rider_id']): ?>
        <div class="small text-muted mt-1">
          <i class="fa-solid fa-motorcycle me-1 text-success"></i>
          Rider ID #<?= $o['rider_id'] ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="order-progress mb-4">
    <?php foreach ($statusOrder as $i => $st): ?>
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

  <?php if ($o['order_status'] !== 'Delivered' && $o['order_status'] !== 'Cancelled' && ($isMine || $canClaim)): ?>
    <form method="post" class="card card-body bg-light mb-4">
      <div class="row g-3 align-items-end">
        <div class="col-md-6">
          <label class="form-label small fw-semibold text-muted">Update Delivery Status</label>
          <select name="status" class="form-select form-select-lg">
            <?php
            $allowed = $statuses;
            if ($canClaim && !$isMine) { $allowed = ['Assigned to Rider','Picked Up','Out for Delivery','Delivered']; }
            foreach ($allowed as $st): ?>
              <option <?= $o['order_status']==$st ? 'selected' : '' ?>><?= e($st) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <button class="btn btn-lg btn-danger w-100">
            <i class="fa-solid <?= $canClaim && !$isMine ? 'fa-hand' : 'fa-floppy-disk' ?> me-1"></i>
            <?= $canClaim && !$isMine ? 'Claim & Update Status' : 'Update Delivery Status' ?>
          </button>
        </div>
      </div>
    </form>
  <?php elseif ($o['order_status'] === 'Delivered'): ?>
    <div class="alert alert-success mb-4"><i class="fa-solid fa-circle-check me-2"></i><strong>Delivery complete!</strong> Nice work.</div>
  <?php else: ?>
    <div class="alert alert-warning mb-4"><i class="fa-solid fa-triangle-exclamation me-2"></i>This order can't be updated right now.</div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-7">
      <div class="card order-item p-4 mb-3">
        <h6 class="fw-bold mb-3"><i class="fa-solid fa-bag-shopping me-2 text-danger"></i>Order Items (<?= count($items) ?>)</h6>
        <div class="table-responsive">
          <table class="table mb-0">
            <thead class="table-light"><tr><th>Food</th><th class="text-center">Qty</th><th class="text-end">Subtotal</th></tr></thead>
            <tbody>
              <?php foreach ($items as $it): ?>
                <tr>
                  <td class="fw-semibold"><?= e($it['food_name']) ?></td>
                  <td class="text-center"><span class="badge bg-secondary"><?= $it['quantity'] ?></span> <span class="small text-muted">× <?= money($it['price']) ?></span></td>
                  <td class="text-end fw-bold"><?= money($it['subtotal']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light fw-bold">
              <tr><td colspan="2" class="text-end">Subtotal</td><td class="text-end"><?= money($o['subtotal']) ?></td></tr>
              <tr><td colspan="2" class="text-end">Delivery Fee</td><td class="text-end"><?= money($o['delivery_fee']) ?></td></tr>
              <?php if ($o['discount']>0): ?><tr><td colspan="2" class="text-end text-danger">Discount</td><td class="text-end text-danger">- <?= money($o['discount']) ?></td></tr><?php endif; ?>
              <tr class="fs-5"><td colspan="2" class="text-end price">TOTAL</td><td class="text-end price"><?= money($o['total_amount']) ?></td></tr>
            </tfoot>
          </table>
        </div>
      </div>

      <div class="card order-item p-4">
        <h6 class="fw-bold mb-3"><i class="fa-solid fa-credit-card me-2 text-danger"></i>Payment & Order Info</h6>
        <div class="row g-2 small">
          <div class="col-4 text-muted">Method</div>
          <div class="col-8 fw-semibold"><?= e($o['payment_method']) ?> · <span class="pill <?= $o['payment_status']==='Paid' ? 'pill-delivered' : 'pill-pending' ?>"><?= e($o['payment_status']) ?></span></div>
          <div class="col-4 text-muted">Order #</div>
          <div class="col-8 fw-semibold"><?= e($o['order_number']) ?></div>
          <div class="col-4 text-muted">Placed at</div>
          <div class="col-8 fw-semibold"><?= date('F j, Y g:ia', strtotime($o['created_at'])) ?></div>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card order-item p-4 mb-3">
        <h6 class="fw-bold mb-3"><i class="fa-solid fa-location-dot me-2 text-danger"></i>Deliver To</h6>
        <div class="fw-bold mb-1"><?= e($o['recipient_name']) ?></div>
        <div class="small mb-2">
          <a href="tel:<?= e($o['recipient_phone']) ?>" class="text-decoration-none">
            <i class="fa-solid fa-phone me-1 text-success"></i>
            <span class="fw-semibold text-success"><?= e($o['recipient_phone']) ?></span>
          </a>
        </div>
        <div class="p-3 bg-light rounded small"><?= nl2br(e($o['address'])) ?></div>
      </div>

      <div class="card order-item p-4">
        <h6 class="fw-bold mb-3"><i class="fa-solid fa-user me-2 text-danger"></i>Customer Info</h6>
        <div class="fw-semibold"><?= e($o['customer']) ?></div>
        <?php if (!empty($o['customer_phone']) && $o['customer_phone'] !== $o['recipient_phone']): ?>
          <div class="small text-muted"><i class="fa-solid fa-phone me-1"></i><?= e($o['customer_phone']) ?></div>
        <?php endif; ?>
        <div class="small text-muted"><i class="fa-regular fa-envelope me-1"></i><?= e($o['customer_email']) ?></div>
      </div>
    </div>
  </div>
</div>

<?php include "includes/footer.php"; ?>
