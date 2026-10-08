<?php
require_once "config/database.php";
require_once "includes/functions.php";
$page_title = "QuickBite — Crave It. Love It.";
$base_url = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

$cats = $pdo->query("SELECT * FROM categories WHERE status=1 ORDER BY name")->fetchAll();
$favs = $pdo->query("SELECT f.*, c.name category FROM foods f JOIN categories c ON c.id=f.category_id WHERE f.status=1 ORDER BY f.id DESC LIMIT 3")->fetchAll();

// Pick a generated cover image per category for foods without an upload.
$catImageKey = [
    'Burgers' => 'chicken_burger', 'Pizza' => 'pepperoni_pizza',
    'Desserts' => 'chocolate_ice', 'Beverages' => 'cat_drink',
];

$prompts = [
    'hero' => 'juicy double cheeseburger stacked with lettuce onion tomato cheese plus crispy golden fried chicken legs plus splashing iced cola in tall glass with ice cubes dynamic food composition on pink background with sauce splash and floating ingredients',
    'chicken_burger' => 'crispy spicy chicken burger sandwich with fresh lettuce tomato sauce dripping on sesame seed bun top view on pink pastel background',
    'pepperoni_pizza' => 'two stacked slices of pepperoni pizza with melted mozzarella cheese dripping tomato sauce top view on pink background',
    'chocolate_ice' => 'chocolate sundae ice cream with whipped cream cherry on top chocolate syrup drizzle and chocolate shavings in cup on pink background',
    'cat_burger' => 'juicy cheeseburger with dripping cheese and splash composition top view vibrant colors isolated on white circle',
    'cat_drink' => 'cold iced cola drink in tall glass with ice cubes and splash droplets dynamic refreshing look',
    'cat_pizza' => 'top view of tasty pepperoni pizza slices with melted mozzarella cheese on wooden board vibrant colors',
    'cat_dessert' => 'creme caramel flan pudding with caramel sauce and cherry on top elegant dessert on plate',
];
$img = function ($key, $size='square') use ($prompts) {
    return "https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=".urlencode($prompts[$key])."&image_size=".$size;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_food'])) {
    require_login();
    $fid = (int)$_POST['add_food'];
    $chk = $pdo->prepare("SELECT id FROM foods WHERE id=? AND status=1");
    $chk->execute([$fid]);
    if ($chk->fetch()) {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        $_SESSION['cart'][$fid] = ($_SESSION['cart'][$fid] ?? 0) + 1;
        flash('success', 'Added to cart!');
    } else {
        flash('error', 'That food is no longer available.');
    }
    redirect('index.php#favorites');
}

include "includes/header.php";
?>

<section class="hero-wrap">
  <div class="container">
    <div class="hero-landing row align-items-center g-4 py-5">
      <div class="col-lg-6 col-md-7">
        <div class="eyebrow-row d-flex align-items-center gap-3 mb-3">
          <span class="eyebrow-bar"></span>
          <span class="eyebrow-text">GOOD FOOD, FAST DELIVERY</span>
        </div>
        <h1 class="drip-title mb-4">
          <span class="drip-word line1">CRAVE IT.</span>
          <span class="drip-word line2">LOVE IT.</span>
        </h1>
        <p class="hero-desc mb-4 fw-semibold">
          JUICY BURGERS, CRISPY FRIES, DELICIOUS<br>
          PIZZA, AND SHAKES MADE FRESH, MADE FOR YOU.
        </p>
        <div class="d-flex gap-3 flex-wrap">
          <a href="menu.php" class="btn btn-order-now">
            <i class="fa-solid fa-bag-shopping me-2"></i>ORDER NOW!
          </a>
          <a href="<?= is_logged_in() ? 'my-orders.php' : 'login.php' ?>" class="btn btn-view-order">
            <i class="fa-solid fa-magnifying-glass me-2"></i>VIEW ORDER
          </a>
        </div>
      </div>
      <div class="col-lg-6 col-md-5 text-center position-relative">
        <div class="hero-ring">
          <div class="ring-circle rc-small rc-1"></div>
          <div class="ring-circle rc-small rc-2"></div>
          <div class="ring-circle rc-small rc-3"></div>
          <div class="hero-img-oval">
            <img src="<?= $img('hero','landscape_16_9') ?>" alt="Burger, fried chicken, and cola" class="hero-img">
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="favorites" class="fav-wrap py-5">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end mb-4">
      <h2 class="fav-title mb-0">Your favorite.</h2>
      <a href="menu.php" class="link-view-all">View all <i class="fa-solid fa-arrow-right ms-1"></i></a>
    </div>

    <div class="row g-4">
      <?php foreach ($favs as $f): ?>
      <div class="col-12 col-md-6 col-lg-4">
        <form method="post" class="h-100">
          <input type="hidden" name="add_food" value="<?= (int)$f['id'] ?>">
          <div class="fav-card h-100">
            <div class="fav-img-wrap">
              <?php if (!empty($f['image']) && str_starts_with($f['image'], 'http')): ?>
                <img src="<?= e($f['image']) ?>" alt="<?= e($f['name']) ?>" class="fav-img">
              <?php elseif (!empty($f['image'])): ?>
                <img src="<?= $base_url ?>/assets/uploads/<?= e($f['image']) ?>" alt="<?= e($f['name']) ?>" class="fav-img">
              <?php else: ?>
                <img src="<?= $img($catImageKey[$f['category']] ?? 'cat_burger', 'square') ?>" alt="<?= e($f['name']) ?>" class="fav-img">
              <?php endif; ?>
            </div>
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
              <div>
                <h4 class="fav-name mb-1"><?= e($f['name']) ?></h4>
                <div class="fav-price"><?= money($f['price']) ?></div>
              </div>
              <button type="submit" class="btn btn-add"><i class="fa-solid fa-plus me-1"></i>ADD</button>
            </div>
          </div>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="crave-wrap py-5 position-relative overflow-hidden">
  <div class="container">
    <h2 class="crave-title mb-5">What are you craving?</h2>

    <div class="row crave-grid g-4 mb-4">
      <div class="col-6 col-md-4 crave-col crave-col-tl">
        <a href="menu.php?category=1" class="crave-item d-flex align-items-center gap-3 text-decoration-none">
          <div class="crave-circle"><img src="<?= $img('cat_burger','square_hd') ?>" alt="Burgers"></div>
          <div class="crave-label">BURGER'S</div>
        </a>
      </div>
      <div class="col-6 col-md-4 crave-col crave-col-tr offset-md-4">
        <a href="menu.php?category=8" class="crave-item d-flex align-items-center gap-3 text-decoration-none">
          <div class="crave-circle"><img src="<?= $img('cat_drink','square_hd') ?>" alt="Drinks"></div>
          <div class="crave-label">DRINK'S</div>
        </a>
      </div>
      <div class="col-6 col-md-4 crave-col crave-col-bl offset-md-2">
        <a href="menu.php?category=2" class="crave-item d-flex align-items-center gap-3 text-decoration-none">
          <div class="crave-circle"><img src="<?= $img('cat_pizza','square_hd') ?>" alt="Pizza"></div>
          <div class="crave-label">PIZZA'S</div>
        </a>
      </div>
      <div class="col-6 col-md-4 crave-col crave-col-br offset-md-2">
        <a href="menu.php?category=7" class="crave-item d-flex align-items-center gap-3 text-decoration-none">
          <div class="crave-circle"><img src="<?= $img('cat_dessert','square_hd') ?>" alt="Desserts"></div>
          <div class="crave-label">DESSERT'S</div>
        </a>
      </div>
    </div>

    <div class="text-end">
      <a href="menu.php" class="link-view-all">View all <i class="fa-solid fa-arrow-right ms-1"></i></a>
    </div>
  </div>
</section>

<?php include "includes/footer.php"; ?>
