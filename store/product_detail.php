<?php
// find root directory  to access database and functions
define('ROOT', dirname(__DIR__));
require_once ROOT . '/function/user_fn.php';
require_once ROOT . '/function/store_fn.php';

$id      = (int)($_GET['id'] ?? 0);
$product = getActiveProduct($id);
if (!$product) { header('Location: /store/product_list.php'); exit; }

$images    = getProductImages($id);
$fp        = finalPrice($product);
$loggedIn  = isLoggedIn();
$userId    = $loggedIn ? $_SESSION['user_id'] : 0;
$inWishlist = $loggedIn && isInWishlist($userId, $id);

// Handle request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$loggedIn) {
        header('Location: /system/login_register.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    if (isset($_POST['add_to_cart'])) {
        $qty = max(1, (int)($_POST['qty'] ?? 1));
        addToCart($userId, $id, $qty);
        header('Location: /store/product_detail.php?id=' . $id . '&added=1');
        exit;
    }
    if (isset($_POST['toggle_wishlist'])) {
        toggleWishlist($userId, $id);
        header('Location: /store/product_detail.php?id=' . $id);
        exit;
    }
}

$pageTitle = $product['name'];
$pageCss   = ['css/store.css'];
require ROOT . '/system/header.php';
?>

<main class="detail-wrap">
  <div class="container">

    <!-- Breadcrumb -->
    <div style="font-size:13px;color:var(--text-muted);margin-bottom:24px">
      <a href="/store/product_list.php" style="color:var(--text-muted)">Shop</a>
      <span style="margin:0 8px">›</span>
      <a href="/store/product_list.php?cat=<?= e($product['category_slug']) ?>" style="color:var(--text-muted)"><?= e($product['category_name']) ?></a>
      <span style="margin:0 8px">›</span>
      <span style="color:var(--text)"><?= e($product['name']) ?></span>
    </div>

    <?php if (!empty($_GET['added'])): ?>
    <div class="alert alert-success mb-3">✅ Added to cart! <a href="/store/cart.php">View cart →</a></div>
    <?php endif; ?>

    <div class="detail-grid">

      <!-- Images -->
      <div class="detail-images">
        <?php $mainImg = $images[0]['image_path'] ?? null; ?>
        <?php if ($mainImg): ?>
        <img id="mainImg" class="detail-main-img" src="/image/<?= e($mainImg) ?>" alt="<?= e($product['name']) ?>">
        <?php if (count($images) > 1): ?>
        <div class="detail-thumbs">
          <?php foreach ($images as $i => $img): ?>
          <img src="/image/<?= e($img['image_path']) ?>"
               class="detail-thumb <?= $i === 0 ? 'active' : '' ?>"
               data-src= '/image/<?= e($img['image_path']) ?>')"
               alt="">
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php else: ?>
        <div style="width:100%;aspect-ratio:1;background:var(--cream);border-radius:var(--r-lg);display:flex;align-items:center;justify-content:center;font-size:6rem">🎁</div>
        <?php endif; ?>
      </div>

      <!-- Info -->
      <div class="detail-info">
        <div class="detail-category"><?= e($product['category_name']) ?></div>
        <h1 class="detail-name"><?= e($product['name']) ?></h1>

        <div class="detail-price">
          <?php if ($product['discount'] > 0): ?>
          <div class="price-original">RM <?= number_format($product['price'], 2) ?></div>
          <div class="price-final">RM <?= number_format($fp, 2) ?>
            <span class="tag tag-red" style="font-size:13px;vertical-align:middle;margin-left:8px"><?= $product['discount'] ?>% off</span>
          </div>
          <?php else: ?>
          <div class="price-normal">RM <?= number_format($product['price'], 2) ?></div>
          <?php endif; ?>
        </div>

        <?php if ($product['description']): ?>
        <p class="detail-desc"><?= nl2br(e($product['description'])) ?></p>
        <?php endif; ?>

        <div class="detail-stock">
          <?php if ($product['stock'] > 0): ?>
          <span style="color:var(--mint-dark)">✅ In Stock (<?= $product['stock'] ?> available)</span>
          <?php else: ?>
          <span style="color:#ef4444">❌ Out of Stock</span>
          <?php endif; ?>
        </div>

        <?php if ($product['stock'] > 0): ?>
        <form method="POST">
          <input type="hidden" name="add_to_cart" value="1">
          <div class="detail-actions">
            <div class="qty-wrap">
              <button type="button" class="qty-btn qty-btn-minus">−</button>
              <input type="number" name="qty" id="qtyInput" class="qty-input" value="1" min="1" max="<?= $product['stock'] ?>">
              <button type="button" class="qty-btn qty-btn-plus">+</button>
            </div>
            <button type="submit" class="btn btn-primary btn-lg" style="flex:1">Add to Cart 🛍️</button>
          </div>
        </form>
        <?php endif; ?>

        <!-- Wishlist -->
        <form method="POST">
          <input type="hidden" name="toggle_wishlist" value="1">
          <button type="submit" class="btn btn-ghost" style="width:100%">
            <?= $inWishlist ? '❤️ Remove from Wishlist' : '🤍 Add to Wishlist' ?>
          </button>
        </form>

        <?php if ($product['is_top_sale']): ?>
        <div style="margin-top:16px">
          <span class="tag tag-red" style="font-size:13px">🔥 Top Sale</span>
        </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</main>

<?php $pageJs = ['js/store.js'];
require ROOT . '/system/footer.php'; ?>