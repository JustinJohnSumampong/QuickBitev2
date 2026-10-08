<?php
require_once "config/database.php"; require_once "includes/functions.php";
$page_title="QuickBite | Menu";
$cats=$pdo->query("SELECT * FROM categories WHERE status=1 ORDER BY name")->fetchAll();
$search=trim($_GET['search']??''); $cat=(int)($_GET['category']??0);
$sql="SELECT f.*,c.name category FROM foods f JOIN categories c ON c.id=f.category_id WHERE f.status=1";
$params=[];
if($search){$sql.=" AND (f.name LIKE ? OR f.description LIKE ?)";$params[]="%$search%";$params[]="%$search%";}
if($cat){$sql.=" AND f.category_id=?";$params[]=$cat;}
$sql.=" ORDER BY f.name";
$stmt=$pdo->prepare($sql);$stmt->execute($params);$foods=$stmt->fetchAll();
include "includes/header.php";
?>
<div class="container py-4"><div class="d-flex justify-content-between align-items-center flex-wrap gap-2"><h2 class="fw-bold">Our Menu</h2>
<form class="d-flex"><input name="search" value="<?=e($search)?>" class="form-control" placeholder="Search food..."><button class="btn btn-danger ms-2">Search</button></form></div>
<div class="my-4 d-flex gap-2 flex-wrap"><a class="btn btn-sm <?=!$cat?'btn-danger':'btn-outline-danger'?>" href="menu.php">All</a><?php foreach($cats as $c):?><a class="btn btn-sm <?=$cat==$c['id']?'btn-danger':'btn-outline-danger'?>" href="?category=<?=$c['id']?>"><?=e($c['name'])?></a><?php endforeach;?></div>
<div class="row g-4"><?php foreach($foods as $f):?><div class="col-12 col-sm-6 col-lg-3"><div class="card food-card h-100">
<?php if($f['image'] && str_starts_with($f['image'],'http')):?><img class="food-img w-100" src="<?=e($f['image'])?>"><?php elseif($f['image']):?><img class="food-img w-100" src="<?= $base_url ?>/assets/uploads/<?=e($f['image'])?>"><?php else:?><div class="food-img d-flex align-items-center justify-content-center display-3">🍴</div><?php endif;?>
<div class="card-body"><small class="text-muted"><?=e($f['category'])?></small><h5><?=e($f['name'])?></h5><p class="small text-muted"><?=e($f['description'])?></p><div class="price"><?=money($f['price'])?></div><a class="btn btn-danger w-100 mt-2" href="food-details.php?id=<?=$f['id']?>">Order</a></div></div></div><?php endforeach;?></div></div>
<?php include "includes/footer.php"; ?>