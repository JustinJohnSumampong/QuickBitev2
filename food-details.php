<?php
require_once "config/database.php";require_once "includes/functions.php";
$id=(int)($_GET['id']??0);$s=$pdo->prepare("SELECT f.*,c.name category FROM foods f JOIN categories c ON c.id=f.category_id WHERE f.id=? AND f.status=1");$s->execute([$id]);$f=$s->fetch();if(!$f)die("Food not found.");
if($_SERVER['REQUEST_METHOD']==='POST'){ $q=max(1,(int)($_POST['quantity']??1)); $_SESSION['cart'][$id]=($_SESSION['cart'][$id]??0)+$q; flash('success','Food added to cart.'); redirect('cart.php');}
$page_title=$f['name'];include "includes/header.php";?>
<div class="container py-5"><div class="row g-5 align-items-center"><div class="col-md-6"><?php if($f['image']):?><img src="<?=e($f['image'])?>" class="img-fluid rounded-5 shadow"><?php else:?><div class="display-1 text-center py-5">🍔</div><?php endif;?></div>
<div class="col-md-6"><small class="text-danger fw-bold"><?=e($f['category'])?></small><h1 class="fw-bold"><?=e($f['name'])?></h1><p class="lead"><?=e($f['description'])?></p><h2 class="price"><?=money($f['price'])?></h2><p>Preparation time: <?=e($f['prep_minutes'])?> minutes</p>
<form method="post" class="d-flex gap-2"><input type="number" min="1" name="quantity" value="1" class="form-control" style="max-width:110px"><button class="btn btn-danger btn-lg">Add to Cart</button></form></div></div></div>
<?php include "includes/footer.php"; ?>