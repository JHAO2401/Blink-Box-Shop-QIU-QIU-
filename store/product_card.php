<?php

$_fp         = finalPrice($p);
$_inWishlist = in_array($p['id'], $wishlistIds);
?>
<div class="product-card">
  <a href="/store/product_detail.php?id=<?= $p['id'] ?>" class="product-card-img">
    <?php if (!empty($p['cover_image'])): ?>
    <img src="/image/<?= e($p['cover_image']) ?>" alt="<?= e($p['name']) ?>">
    <?php else: ?>
    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:4rem;background:var(--cream)">🎁</div>
    <?php endif; ?>
    <div class="product-card-badges">
      <?php if ($p['discount'] > 0): ?>
      <span class="badge-discount"><?= $p['discount'] ?>% off</span>
      <?php endif; ?>
      <?php if ($p['is_top_sale']): ?>
      <span class="badge-topsale">🔥</span>
      <?php endif; ?>
    </div>
  </a>

  <?php if ($loggedIn): ?>
  <form method="POST" style="position:absolute;top:10px;right:10px">
    <input type="hidden" name="product_id"      value="<?= $p['id'] ?>">
    <input type="hidden" name="toggle_wishlist"  value="1">
    <button type="submit" class="wishlist-btn <?= $_inWishlist ? 'active' : '' ?>">
      <?= $_inWishlist ? '❤️' : '🤍' ?>
    </button>
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
      <div class="price-final">RM <?= number_format($_fp, 2) ?></div>
      <?php else: ?>
      <div class="price-normal">RM <?= number_format($p['price'], 2) ?></div>
      <?php endif; ?>
    </div>
    <div class="product-card-actions">
      <a href="/store/product_detail.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm">View</a>
      <?php if ($p['stock'] > 0 && $loggedIn): ?>
      <form method="POST" style="flex:1">
        <input type="hidden" name="product_id"  value="<?= $p['id'] ?>">
        <input type="hidden" name="add_to_cart"  value="1">
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