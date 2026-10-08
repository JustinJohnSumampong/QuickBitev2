<?php
require_once __DIR__.'/functions.php';
$base_url = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$current_script = basename($_SERVER['PHP_SELF']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($page_title ?? 'QuickBite') ?></title>
<link rel="icon" type="image/png" href="<?= $base_url ?>/assets/img/logo.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="<?= $base_url ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="landing-body">

<nav class="navbar landing-nav navbar-expand-lg border-bottom sticky-top py-3">
<div class="container">
<a class="navbar-brand d-inline-flex align-items-center py-0" href="<?= $base_url ?>/index.php">
  <img class="site-logo" src="<?= $base_url ?>/assets/img/logo.png?v=2" alt="QuickBite">
</a>
<button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#topnav"><span class="navbar-toggler-icon"></span></button>
<div class="collapse navbar-collapse" id="topnav">

<form class="d-flex mx-auto my-2 my-lg-0 landing-search order-lg-1" role="search" method="get" action="<?= $base_url ?>/menu.php">
  <i class="fa-solid fa-magnifying-glass me-2 mt-auto mb-auto text-muted"></i>
  <input class="form-control border-0 bg-transparent py-1" placeholder="Search for a food." name="search"
    <?php if($current_script==='menu.php' && !empty($_GET['search'])): ?>value="<?=e($_GET['search'])?>"<?php endif; ?>>
</form>

<ul class="navbar-nav ms-auto ms-lg-0 me-lg-auto gap-2 align-items-lg-center landing-pill-nav order-lg-2">
<li class="nav-item">
  <a class="nav-link pill-btn <?=$current_script==='index.php'?'active':''?>" href="<?= $base_url ?>/index.php">
    <i class="fa-solid fa-house me-1"></i>HOME
  </a>
</li>
<li class="nav-item">
  <a class="nav-link pill-btn <?=$current_script==='menu.php'?'active':''?>" href="<?= $base_url ?>/menu.php">
    <i class="fa-solid fa-book me-1"></i>MENU
  </a>
</li>
<li class="nav-item">
  <a class="nav-link pill-btn <?=(is_logged_in() && in_array($current_script,['my-orders.php','track-order.php']))?'active':''?>"
     href="<?= is_logged_in() ? $base_url.'/my-orders.php' : $base_url.'/login.php' ?>">
    <i class="fa-solid fa-truck-fast me-1"></i>ORDERS
  </a>
</li>
</ul>

<div class="d-flex align-items-center gap-2 ms-lg-auto order-lg-3 profile-zone">
<?php if(is_logged_in()): ?>
  <a class="cart-badge d-none d-lg-inline-flex align-items-center gap-1 small text-muted" href="<?= $base_url ?>/cart.php">
    <i class="fa-solid fa-receipt me-1"></i><?= strtoupper(substr(session_id(), -8)) ?>
  </a>
  <a class="profile-btn" href="<?= $base_url ?>/my-orders.php" title="<?= e($_SESSION['user']['name'] ?? '') ?>">
    <div class="avatar">
      <i class="fa-solid fa-user"></i>
    </div>
  </a>
  <a class="btn btn-sm btn-outline-dark d-none d-lg-inline" href="<?= $base_url ?>/logout.php"><i class="fa-solid fa-right-from-bracket"></i></a>
<?php else: ?>
  <a class="cart-badge d-none d-lg-inline-flex align-items-center gap-1 small text-muted" href="<?= $base_url ?>/login.php">
    <i class="fa-solid fa-receipt me-1"></i>SAMPLE_ID
  </a>
  <a class="profile-btn" href="<?= $base_url ?>/login.php" title="Login">
    <div class="avatar"><i class="fa-solid fa-user"></i></div>
  </a>
  <a class="btn btn-sm btn-danger d-none d-lg-inline" href="<?= $base_url ?>/login.php">Login</a>
<?php endif; ?>
<a class="btn btn-outline-danger position-relative ms-2 d-none d-sm-inline-flex align-items-center" href="<?= $base_url ?>/cart.php">
  <i class="fa-solid fa-cart-shopping"></i>
  <?php if(cart_count()): ?>
    <span class="position-absolute top-0 start-100 translate-middle badge bg-orange rounded-pill"><?= cart_count() ?></span>
  <?php endif; ?>
</a>
</div>

</div>
</div>
</nav>

<?php if($m=flash('success')): ?>
  <div class="container mt-3"><div class="alert alert-success border-0 rounded-4"><i class="fa-solid fa-circle-check me-2"></i><?= e($m) ?></div></div>
<?php endif; ?>
<?php if($m=flash('error')): ?>
  <div class="container mt-3"><div class="alert alert-danger border-0 rounded-4"><i class="fa-solid fa-circle-exclamation me-2"></i><?= e($m) ?></div></div>
<?php endif; ?>
