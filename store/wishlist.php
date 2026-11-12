<?php
// find root directory  to access database and functions
define('ROOT', dirname(__DIR__));
require_once ROOT . '/function/user_fn.php';
require_once ROOT . '/function/store_fn.php';
requireLogin();

$userId = $_SESSION['user_id'];

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pid    = (int)($_POST['product_id'] ?? 0);

    if ($action === 'remove')       { toggleWishlist($userId, $pid); }
    if ($action === 'add_to_cart')  { addToCart($userId, $pid, 1); }

    header('Location: /store/wishlist.php'); exit;
}

$items = getWishlist($userId);

$pageTitle = 'My Wishlist';
$pageCss   = ['css/store.css'];
require ROOT . '/system/header.php';
?>

<main class="wishlist-wrap">
  <div class="container">

    <h1 style="margin-bottom:28px">My Wishlist
      <span style="font-family:var(--font-body);font-size:1rem;font-weight:600;color:var(--text-muted);margin-left:10px"><?= count($items) ?> items</span>
    </h1>

    <?php if (empty($items)): ?>
    <div style="text-align:center;padding:80px 0">
      <p style="font-size:4rem;margin-bottom:16px">🤍</p>
      <p style="font-size:1.1rem;font-weight:700;color:var(--text-muted)">Your wishlist is empty.</p>
      <a href="/store/product_list.php" class="btn btn-primary mt-3">Browse Products</a>
    </div>
    <?php else: ?>
    <div class="product-grid">
      <?php foreach ($items as $p):
        $fp = finalPrice($p);
      ?>
      <div class="product-card">
        <a href="/store/product_detail.php?id=<?= $p['id'] ?>" class="product-card-img">
          <?php if ($p['cover_image']): ?>
          <img src="/image/<?= e($p['cover_image']) ?>" alt="<?= e($p['name']) ?>">
          <?php else: ?>
          <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:4rem;background:var(--cream)">🎁</div>
          <?php endif; ?>
          <?php if ($p['discount'] > 0): ?>
          <div class="product-card-badges">
            <span class="badge-discount"><?= $p['discount'] ?>% off</span>
          </div>
          <?php endif; ?>
        </a>

        <!-- Remove from wishlist -->
        <form method="POST" style="position:absolute;top:10px;right:10px">
          <input type="hidden" name="action"     value="remove">
          <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
          <button type="submit" class="wishlist-btn active">❤️</button>
        </form>

        <div class="product-card-body">
          <div class="product-card-name"><?= e($p['name']) ?></div>
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
              <input type="hidden" name="action"     value="add_to_cart">
              <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
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

<?php require ROOT . '/system/footer.php'; ?>