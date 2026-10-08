<?php require_once __DIR__.'/../../includes/functions.php';
$base_url = rtrim(dirname($_SERVER['SCRIPT_NAME'], 2), '/');
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($page_title ?? 'Admin') ?> · QuickBite</title>
<link rel="icon" type="image/png" href="<?= $base_url ?>/assets/img/logo.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="<?= $base_url ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
<div class="container-fluid">
<a class="navbar-brand d-inline-flex align-items-center gap-2 py-0" href="dashboard.php"><img class="site-logo site-logo-sm" src="<?= $base_url ?>/assets/img/logo.png?v=2" alt="QuickBite"><span class="badge bg-danger">Admin</span></a>
<button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#topnav"><span class="navbar-toggler-icon"></span></button>
<div class="collapse navbar-collapse" id="topnav">
<ul class="navbar-nav d-md-none me-auto">
<li class="nav-item"><a class="nav-link <?=$current_page==='dashboard.php'?'active text-danger fw-bold':''?>" href="dashboard.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></li>
<li class="nav-item"><a class="nav-link <?=$current_page==='orders.php'?'active text-danger fw-bold':''?>" href="orders.php"><i class="fa-solid fa-clipboard-list"></i> Orders</a></li>
<li class="nav-item"><a class="nav-link <?=$current_page==='foods.php'?'active text-danger fw-bold':''?>" href="foods.php"><i class="fa-solid fa-bowl-food"></i> Foods</a></li>
<li class="nav-item"><a class="nav-link <?=$current_page==='categories.php'?'active text-danger fw-bold':''?>" href="categories.php"><i class="fa-solid fa-tags"></i> Categories</a></li>
<li class="nav-item"><a class="nav-link <?=$current_page==='riders.php'?'active text-danger fw-bold':''?>" href="riders.php"><i class="fa-solid fa-motorcycle"></i> Riders</a></li>
<li class="nav-item"><a class="nav-link <?=$current_page==='reports.php'?'active text-danger fw-bold':''?>" href="reports.php"><i class="fa-solid fa-chart-line"></i> Reports</a></li>
<li class="nav-item"><a class="nav-link" href="<?= $base_url ?>/index.php"><i class="fa-solid fa-store"></i> View Store</a></li>
</ul>
<div class="d-flex align-items-center gap-2 ms-auto">
<span class="small text-muted d-none d-md-inline">Hi, <?= e($_SESSION['user']['name'] ?? '') ?></span>
<a class="btn btn-outline-dark btn-sm" href="<?= $base_url ?>/index.php" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> Store</a>
<a class="btn btn-danger btn-sm" href="<?= $base_url ?>/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>
</div>
</div>
</nav>

<div class="container-fluid">
<div class="row">
<aside class="col-md-3 col-lg-2 d-none d-md-block admin-side p-3 border-end bg-white min-vh-100">
<ul class="nav flex-column gap-1">
<li class="nav-item">
<a class="nav-link rounded <?=$current_page==='dashboard.php'?'active bg-danger text-white':'text-dark'?>" href="dashboard.php">
<i class="fa-solid fa-gauge-high me-2"></i>Dashboard
</a>
</li>
<li class="nav-item">
<a class="nav-link rounded <?=$current_page==='orders.php'?'active bg-danger text-white':'text-dark'?>" href="orders.php">
<i class="fa-solid fa-clipboard-list me-2"></i>Orders
</a>
</li>
<li class="nav-item">
<a class="nav-link rounded <?=$current_page==='foods.php'?'active bg-danger text-white':'text-dark'?>" href="foods.php">
<i class="fa-solid fa-bowl-food me-2"></i>Foods
</a>
</li>
<li class="nav-item">
<a class="nav-link rounded <?=$current_page==='categories.php'?'active bg-danger text-white':'text-dark'?>" href="categories.php">
<i class="fa-solid fa-tags me-2"></i>Categories
</a>
</li>
<li class="nav-item">
<a class="nav-link rounded <?=$current_page==='riders.php'?'active bg-danger text-white':'text-dark'?>" href="riders.php">
<i class="fa-solid fa-motorcycle me-2"></i>Riders
</a>
</li>
<li class="nav-item">
<a class="nav-link rounded <?=$current_page==='reports.php'?'active bg-danger text-white':'text-dark'?>" href="reports.php">
<i class="fa-solid fa-chart-line me-2"></i>Reports
</a>
</li>
</ul>
</aside>
<main class="col-md-9 col-lg-10 p-4">
<?php if($m=flash('success')): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i><?= e($m) ?></div><?php endif; ?>
<?php if($m=flash('error')): ?><div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation me-2"></i><?= e($m) ?></div><?php endif; ?>
