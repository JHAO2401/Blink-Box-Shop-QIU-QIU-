<?php
// find root directory  to access database and functions
define('ROOT', dirname(__DIR__));
require_once ROOT . '/function/user_fn.php';
require_once ROOT . '/function/store_fn.php';

$loggedIn = isLoggedIn() && !isAdmin();
$userId   = $loggedIn ? $_SESSION['user_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!$loggedIn) { header('Location: /system/login_register.php?redirect=/store/store.php'); exit; }
    addToCart($userId, (int)$_POST['product_id'], 1);
    header('Location: /store/store.php?added=1'); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_wishlist'])) {
    if (!$loggedIn) { header('Location: /system/login_register.php?redirect=/store/store.php'); exit; }
    toggleWishlist($userId, (int)$_POST['product_id']);
    header('Location: /store/store.php'); exit;
}

$wishlistIds = [];
if ($loggedIn) {
    $s = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = :u");
    $s->execute([':u' => $userId]);
    $wishlistIds = array_column($s->fetchAll(), 'product_id');
}

$allProducts = getActiveProducts(['sort' => 'newest']);
$topSale     = array_filter($allProducts, function($p){ return $p['is_top_sale']; });
$topSale     = array_slice($topSale, 0, 4);
$latest      = array_slice($allProducts, 0, 8);

$pageTitle = 'Home';
$pageCss   = ['css/store.css'];
require ROOT . '/system/header.php';

// Helper: render product card inline
function renderCard($p, $loggedIn, $wishlistIds) {
    $fp = finalPrice($p);
    $inW = in_array($p['id'], $wishlistIds);
    ob_start(); ?>
    <div class="product-card">
      <a href="/store/product_detail.php?id=<?= $p['id'] ?>" class="product-card-img">
        <?php if (!empty($p['cover_image'])): ?>
        <img src="/image/<?= e($p['cover_image']) ?>" alt="<?= e($p['name']) ?>">
        <?php else: ?>
        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:4rem;background:var(--cream)">🎁</div>
        <?php endif; ?>
        <div class="product-card-badges">
          <?php if ($p['discount'] > 0): ?><span class="badge-discount"><?= $p['discount'] ?>% off</span><?php endif; ?>
          <?php if ($p['is_top_sale']): ?><span class="badge-topsale">🔥 Top Sale</span><?php endif; ?>
        </div>
      </a>
      <?php if ($loggedIn): ?>
      <form method="POST" style="position:absolute;top:10px;right:10px">
        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
        <input type="hidden" name="toggle_wishlist" value="1">
        <button type="submit" class="wishlist-btn <?= $inW ? 'active' : '' ?>"><?= $inW ? '❤️' : '🤍' ?></button>
      </form>
      <?php endif; ?>
      <div class="product-card-body">
        <a href="/store/product_detail.php?id=<?= $p['id'] ?>" style="text-decoration:none">
          <div class="product-card-name"><?= e($p['name']) ?></div>
        </a>
        <div class="product-card-cat"><?= e($p['category_name']) ?></div>
        <div class="product-card-price">
          <?php if ($p['discount'] > 0): ?>
          <div class="price-original">RM <?= number_format($p['price'], 2) ?></div>
          <div class="price-final">RM <?= number_format($fp, 2) ?></div>
          <?php else: ?>
          <div class="price-normal">RM <?= number_format($p['price'], 2) ?></div>
          <?php endif; ?>
        </div>
        <div class="product-card-actions">
          <a href="/store/product_detail.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm">View</a>
          <?php if ($p['stock'] > 0 && $loggedIn): ?>
          <form method="POST" style="flex:1">
            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
            <input type="hidden" name="add_to_cart" value="1">
            <button type="submit" class="btn btn-primary btn-sm w-full">Add to Cart</button>
          </form>
          <?php elseif ($p['stock'] <= 0): ?>
          <button class="btn btn-ghost btn-sm" disabled style="flex:1">Out of Stock</button>
          <?php else: ?>
          <a href="/system/login_register.php" class="btn btn-primary btn-sm" style="flex:1;text-align:center">Login</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php return ob_get_clean();
}
?>

<main class="store-wrap">
  <div class="container">

    <?php if (!empty($_GET['added'])): ?>
    <div class="alert alert-success mb-3">✅ Added to cart! <a href="/store/cart.php">View cart →</a></div>
    <?php endif; ?>

    <!-- Ads Banner — full background image -->
    <div class="store-ads-banner" style="margin-bottom:36px">
      <div class="store-ads-bg" style="background-image:url('/image/huh.gif')"></div>
      <div class="store-ads-overlay"></div>
      <div class="store-ads-text">
        <p class="store-ads-eyebrow">✨ New Arrivals Every Week</p>
        <h2 class="store-ads-title">Discover Your<br><span>Cutest Obsession</span></h2>
        <p class="store-ads-sub">Blind boxes &amp; adorable dolls waiting for you</p>
        <a href="/store/product_list.php" class="btn btn-primary btn-lg">Shop Now →</a>
      </div>
    </div>

    <!-- Top Sale -->
    <?php if (!empty($topSale)): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
      <h2 style="font-size:1.3rem">🔥 Top Sale</h2>
      <a href="/store/product_list.php" style="font-size:13px;font-weight:800;color:var(--pink-500)">View all →</a>
    </div>
    <div class="product-grid" style="margin-bottom:40px">
      <?php foreach ($topSale as $p) echo renderCard($p, $loggedIn, $wishlistIds); ?>
    </div>
    <?php endif; ?>

    <!-- Latest -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
      <h2 style="font-size:1.3rem">✨ Latest Products</h2>
      <a href="/store/product_list.php?sort=newest" style="font-size:13px;font-weight:800;color:var(--pink-500)">View all →</a>
    </div>
    <?php if (!empty($latest)): ?>
    <div class="product-grid">
      <?php foreach ($latest as $p) echo renderCard($p, $loggedIn, $wishlistIds); ?>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:60px 0;color:var(--text-muted)">
      <p style="font-size:3rem;margin-bottom:12px">🎁</p>
      <p style="font-weight:700">No products yet. Check back soon!</p>
    </div>
    <?php endif; ?>

  </div>
</main>

<?php require ROOT . '/system/footer.php'; ?>