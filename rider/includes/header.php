<?php require_once __DIR__.'/../../includes/functions.php';
$base_url = rtrim(dirname($_SERVER['SCRIPT_NAME'], 2), '/');
$current_page = basename($_SERVER['PHP_SELF']);
$rider = $_SESSION['user'] ?? [];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($page_title ?? 'Rider') ?> · QuickBite</title>
<link rel="icon" type="image/png" href="<?= $base_url ?>/assets/img/logo.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="<?= $base_url ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
<div class="container-fluid">
<a class="navbar-brand d-inline-flex align-items-center gap-2 py-0" href="dashboard.php"><img class="site-logo site-logo-sm" src="<?= $base_url ?>/assets/img/logo.png?v=2" alt="QuickBite"><span class="badge bg-dark">Rider</span></a>
<button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#topnav"><span class="navbar-toggler-icon"></span></button>
<div class="collapse navbar-collapse" id="topnav">
<ul class="navbar-nav me-auto">
<li class="nav-item"><a class="nav-link <?= $current_page==='dashboard.php' ? 'active text-danger fw-bold' : '' ?>" href="dashboard.php"><i class="fa-solid fa-gauge-high me-1"></i>My Deliveries</a></li>
<li class="nav-item d-md-none"><a class="nav-link" href="<?= $base_url ?>/index.php"><i class="fa-solid fa-store me-1"></i>View Store</a></li>
</ul>
<div class="d-flex align-items-center gap-2 ms-auto">
<span class="small text-muted d-none d-md-inline">
  <i class="fa-solid fa-motorcycle me-1 text-success"></i>Hi, <?= e($rider['name'] ?? '') ?>
</span>
<a class="btn btn-danger btn-sm" href="<?= $base_url ?>/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>
</div>
</div>
</nav>

<main class="container py-4">
<?php if($m=flash('success')): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i><?= e($m) ?></div><?php endif; ?>
<?php if($m=flash('error')): ?><div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation me-2"></i><?= e($m) ?></div><?php endif; ?>
