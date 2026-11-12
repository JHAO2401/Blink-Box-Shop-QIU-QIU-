<?php

define('ROOT', __DIR__);
require_once ROOT . '/function/user_fn.php';

//is admin login
if (isAdmin()) {
    header('Location: /admin_manage/dashboard.php');
    exit;
}
//is customer login
if (isLoggedIn()) {
    header('Location: /store/store.php');
    exit;
}

$pageTitle = 'Home';
require ROOT . '/system/header.php';
?>

<main>
<!-- ads content container -->
  <section class="hero">
    <div class="hero-bg"></div>
    <div class="hero-overlay"></div>
    <div class="container">
      <div class="hero-content">
        <span class="hero-eyebrow">✨ New Arrivals Every Week</span>
        <h1>Discover Your<br><span>Cutest Obsession</span></h1>
        <p class="hero-sub">
          Blind boxes, adorable dolls &amp; cute collectibles —
          every unboxing is a little surprise waiting for you.
        </p>
        <div class="hero-actions">
          <?php if (!isLoggedIn()): ?>
          <a href="/system/login_register.php?tab=registerPanel" class="hero-join">Join Us</a>
          <?php endif; ?>
          <a href="/store/product_list.php" class="hero-browse">Browse Collection</a>
        </div>
      </div>
    </div>
  </section>

  <!-- category container -->
  <section class="categories">
    <div class="container">

      <div class="cat-heading">
        <h2>Shop by Category</h2>
        <p>What are you looking for today?</p>
      </div>

      <div class="cat-grid">

        <!-- Blind Box ads-->
        <a href="/store/product_list.php?cat=blind-box" class="cat-card">
          <div class="cat-card-img cat-card-img-blindbox"></div>
          <div class="cat-card-overlay"></div>
          <div class="cat-card-content">
            <div class="cat-card-title">Blind Box</div>
            <div class="cat-card-sub">Mystery surprise every time ✨</div>
            <div class="cat-card-btn">Shop Now →</div>
          </div>
        </a>

        <!-- Doll ads-->
        <a href="/store/product_list.php?cat=dolls" class="cat-card">
          <div class="cat-card-img cat-card-img-doll"></div>
          <div class="cat-card-overlay"></div>
          <div class="cat-card-content">
            <div class="cat-card-title">Doll</div>
            <div class="cat-card-sub">Cute collectibles you'll love 💕</div>
            <div class="cat-card-btn">Shop Now →</div>
          </div>
        </a>

      </div>
    </div>
  </section>

</main>

<?php require ROOT . '/system/footer.php'; ?>