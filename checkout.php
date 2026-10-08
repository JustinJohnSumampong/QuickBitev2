<?php
require_once "config/database.php";require_once "includes/functions.php";require_login();
$cart=$_SESSION['cart']??[];if(!$cart)redirect('cart.php');
$ids=array_keys($cart);$in=implode(',',array_fill(0,count($ids),'?'));$s=$pdo->prepare("SELECT * FROM foods WHERE id IN ($in) AND status=1");$s->execute($ids);$foods=$s->fetchAll();
if(!$foods){$_SESSION['cart']=[];flash('error','The items in your cart are no longer available.');redirect('cart.php');}
$subtotal=0;foreach($foods as $f)$subtotal+=$f['price']*$cart[$f['id']];$delivery=50;$total=$subtotal+$delivery;
if($_SERVER['REQUEST_METHOD']==='POST'){
$name=trim($_POST['name']??'');$phone=trim($_POST['phone']??'');$address=trim($_POST['address']??'');$payment=$_POST['payment']??'COD';
if(!$name||!$phone||!$address){$err="Please complete all required fields.";}else{
$pdo->beginTransaction();try{
$pdo->prepare("INSERT INTO delivery_addresses(user_id,recipient_name,phone,address) VALUES(?,?,?,?)")->execute([$_SESSION['user']['id'],$name,$phone,$address]);$addr=$pdo->lastInsertId();
$ord=$pdo->prepare("INSERT INTO orders(order_number,user_id,subtotal,delivery_fee,discount,total_amount,payment_method,payment_status,order_status,delivery_address_id) VALUES(?,?,?,?,0,?,?,?,?,?)");
$ord->execute([order_number(),$_SESSION['user']['id'],$subtotal,$delivery,$total,$payment,$payment==='COD'?'Pending':'Pending','Pending',$addr]);$oid=$pdo->lastInsertId();
$oi=$pdo->prepare("INSERT INTO order_items(order_id,food_id,food_name,price,quantity,subtotal) VALUES(?,?,?,?,?,?)");foreach($foods as $f)$oi->execute([$oid,$f['id'],$f['name'],$f['price'],$cart[$f['id']],$f['price']*$cart[$f['id']]]);
$pdo->commit();$_SESSION['cart']=[];flash('success','Order placed successfully.');redirect('order-success.php?id='.$oid);
}catch(Exception $e){$pdo->rollBack();$err="Unable to place order.";}}
}
$page_title="Checkout";include "includes/header.php";?>
<div class="container py-5"><div class="row g-4"><div class="col-md-7"><div class="card p-4 shadow-sm"><h3>Checkout</h3><?php if(isset($err)):?><div class="alert alert-danger"><?=e($err)?></div><?php endif;?><form method="post"><input class="form-control mb-3" name="name" value="<?=e($_SESSION['user']['name'])?>" placeholder="Recipient name" required><input class="form-control mb-3" name="phone" value="<?=e($_SESSION['user']['phone'])?>" placeholder="Phone" required><textarea class="form-control mb-3" name="address" rows="4" placeholder="Complete delivery address" required></textarea><select class="form-select mb-3" name="payment"><option value="COD">Cash on Delivery</option><option value="GCash">GCash</option><option value="Bank Transfer">Bank Transfer</option></select><button class="btn btn-danger btn-lg w-100">Place Order</button></form></div></div>
<div class="col-md-5"><div class="card p-4 shadow-sm"><h4>Order Summary</h4><?php foreach($foods as $f):?><div class="d-flex justify-content-between"><span><?=e($f['name'])?> × <?=$cart[$f['id']]?></span><b><?=money($f['price']*$cart[$f['id']])?></b></div><?php endforeach;?><hr><div class="d-flex justify-content-between"><span>Subtotal</span><b><?=money($subtotal)?></b></div><div class="d-flex justify-content-between"><span>Delivery</span><b><?=money($delivery)?></b></div><hr><div class="d-flex justify-content-between fs-4"><span>Total</span><b class="text-danger"><?=money($total)?></b></div></div></div></div></div>
<?php include "includes/footer.php"; ?>