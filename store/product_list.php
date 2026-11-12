<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
// find root directory  to access database and functions
define('ROOT', dirname(__DIR__));
require_once ROOT . '/function/user_fn.php';
require_once ROOT . '/function/store_fn.php';

$filters  = [
    'cat'    => $_GET['cat']    ?? '',
    'search' => $_GET['search'] ?? '',
    'sort'   => $_GET['sort']   ?? 'newest',
];
$products   = getActiveProducts($filters);
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$loggedIn   = isLoggedIn();
$userId     = $loggedIn ? $_SESSION['user_id'] : 0;

// Wishlist IDs for current user
$wishlistIds = [];
if ($loggedIn) {
    $s = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = :u");
    $s->execute([':u' => $userId]);
    $wishlistIds = array_column($s->fetchAll(), 'product_id');
}

// Handle add to cart POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!$loggedIn) {
        header('Location: /system/login_register.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    addToCart($userId, (int)$_POST['product_id'], 1);
    header('Location: /store/product_list.php?' . http_build_query($filters) . '&added=1');
    exit;
}

// Handle wishlist toggle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_wishlist'])) {
    if (!$loggedIn) {
        header('Location: /system/login_register.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    toggleWishlist($userId, (int)$_POST['product_id']);
    header('Location: /store/product_list.php?' . http_build_query($filters));
    exit;
}

$pageTitle = 'Shop';
$pageCss   = ['css/store.css'];
require ROOT . '/system/header.php';
?>

<main class="store-wrap">
  <div class="container">

    <div class="store-header">
      <h1>
        <?php if ($filters['cat']): ?>
          <?php foreach ($categories as $c): if ($c['slug'] === $filters['cat']): ?>
          <?= e($c['icon']) ?> <?= e($c['name']) ?>
          <?php endif; endforeach; ?>
        <?php elseif ($filters['search']): ?>
          Results for "<?= e($filters['search']) ?>"
        <?php else: ?>
          All Products
        <?php endif; ?>
        <span style="font-family:var(--font-body);font-size:1rem;font-weight:600;color:var(--text-muted);margin-left:10px">
          (<?= count($products) ?>)
        </span>
      </h1>
    </div>

    <?php if (!empty($_GET['added'])): ?>
    <div class="alert alert-success mb-3">✅ Added to cart! <a href="/store/cart.php">View cart →</a></div>
    <?php endif; ?>

    <!-- Filter bar -->
    <div class="filter-bar">
      <a href="/store/product_list.php" class="filter-pill <?= !$filters['cat'] && !$filters['search'] ? 'active' : '' ?>">All</a>
      <?php foreach ($categories as $c): ?>
      <a href="/store/product_list.php?cat=<?= e($c['slug']) ?>"
         class="filter-pill <?= $filters['cat'] === $c['slug'] ? 'active' : '' ?>">
        <?= e($c['icon']) ?> <?= e($c['name']) ?>
      </a>
      <?php endforeach; ?>

      <form method="GET" style="display:flex;gap:8px;margin-left:auto">
        <?php if ($filters['cat']): ?><input type="hidden" name="cat" value="<?= e($filters['cat']) ?>"><?php endif; ?>
        <input type="text" name="search" class="form-control" placeholder="Search…"
               value="<?= e($filters['search']) ?>" style="width:180px">
        <select name="sort" class="form-control" style="width:140px" onchange="this.form.submit()">
          <option value="newest"     <?= $filters['sort']==='newest'     ? 'selected':'' ?>>Newest</option>
          <option value="price_asc"  <?= $filters['sort']==='price_asc'  ? 'selected':'' ?>>Price ↓↑</option>
          <option value="price_desc" <?= $filters['sort']==='price_desc' ? 'selected':'' ?>>Price ↑↓</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Go</button>
        <?php if ($filters['search']): ?>
        <a href="/store/product_list.php<?= $filters['cat'] ? '?cat='.e($filters['cat']) : '' ?>" class="btn btn-ghost btn-sm">✕</a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Product grid -->
    <?php if (empty($products)): ?>
    <div style="text-align:center;padding:80px 0;color:var(--text-muted)">
      <p style="font-size:3rem;margin-bottom:16px">🔍</p>
      <p style="font-size:1.1rem;font-weight:700">No products found.</p>
    </div>
    <?php else: ?>
    <div class="product-grid">
      <?php foreach ($products as $p):
        $fp = finalPrice($p);
        $inWishlist = in_array($p['id'], $wishlistIds);
      ?>
      <div class="product-card">

        <!-- Image -->
        <a href="/store/product_detail.php?id=<?= $p['id'] ?>" class="product-card-img">
          <?php if ($p['cover_image']): ?>
          <img src="/image/<?= e($p['cover_image']) ?>" alt="<?= e($p['name']) ?>">
          <?php else: ?>
          <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:4rem;background:var(--cream)">🎁</div>
          <?php endif; ?>

          <!-- Badges -->
          <div class="product-card-badges">
            <?php if ($p['discount'] > 0): ?>
            <span class="badge-discount"><?= $p['discount'] ?>% off</span>
            <?php endif; ?>
            <?php if ($p['is_top_sale']): ?>
            <span class="badge-topsale">🔥 Top Sale</span>
            <?php endif; ?>
          </div>
        </a>

        <!-- Wishlist button -->
        <form method="POST" style="position:absolute;top:10px;right:10px">
          <input type="hidden" name="product_id"     value="<?= $p['id'] ?>">
          <input type="hidden" name="toggle_wishlist" value="1">
          <button type="submit" class="wishlist-btn <?= $inWishlist ? 'active' : '' ?>">
            <?= $inWishlist ? '❤️' : '🤍' ?>
          </button>
        </form>

        <!-- Info -->
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
            <?php if ($p['stock'] > 0): ?>
            <form method="POST" style="flex:1">
              <input type="hidden" name="product_id"  value="<?= $p['id'] ?>">
              <input type="hidden" name="add_to_cart"  value="1">
              <button type="submit" class="btn btn-primary btn-sm w-full">Add to Cart</button>
            </form>
            <?php else: ?>
            <button class="btn btn-ghost btn-sm" disabled style="flex:1">Out of Stock</button>
            <?php endif; ?>
          </div>
        </div>

      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>
</main>

<?php
$pageJs = ['js/index.js'];
require ROOT . '/system/footer.php';
?>