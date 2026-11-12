<?php
// find root directory  to access database and functions
if (!defined('ROOT')) define('ROOT', dirname(__DIR__));
require_once ROOT . '/function/user_fn.php';

$pageCss   = $pageCss   ?? [];

$loggedIn  = isLoggedIn();
$adminUser = isAdmin();
$me        = $loggedIn ? getCurrentUser() : null;
$cartCount = getCartCount();

// active current page 
$_curPage  = basename($_SERVER['PHP_SELF']);
$_curDir   = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="/image/logo.png"— ><title>QIU QIU Shop</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Bubblegum+Sans&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="/css/index.css">
  <!-- load page-specific CSS files -->
  <?php foreach ($pageCss as $f): ?>
  <link rel="stylesheet" href="/<?= e($f) ?>">
  <?php endforeach; ?>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>
<body>

<nav class="navbar">
  <div class="container">
    <div class="nav-inner">

      <?php
      $logoHref = $adminUser ? '/admin_manage/dashboard.php' : ($loggedIn ? '/store/store.php' : '/index.php');
      ?>
      <a href="<?= $logoHref ?>" class="nav-logo">
        <img src="/image/logo.png" alt="Qiu Shop" style="height:42px;width:auto">
       QIU QIU Shop
      </a>

      <?php if (!$adminUser): ?>
      <!-- inside store: only for customer menu list-->
      <div class="nav-links">
        <a href="/store/store.php"
           class="nav-link <?= $_curPage === 'store.php' ? 'active' : '' ?>">Home</a>
        <a href="/store/product_list.php"
           class="nav-link <?= $_curPage === 'product_list.php' ? 'active' : '' ?>">Shop</a>
        <?php if ($loggedIn): ?>
        <a href="/store/orders.php"
           class="nav-link <?= $_curPage === 'orders.php' ? 'active' : '' ?>">My Orders</a>
        <?php endif; ?>
      </div>

      <!-- Search bar -->
      <form action="/store/product_list.php" method="GET" class="nav-search">
        <input type="text" name="search" placeholder="Search products…"
               value="<?= e($_GET['search'] ?? '') ?>" autocomplete="off">
        <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
      </form>
      <?php endif; ?>

      <div class="nav-actions">

        <?php if ($adminUser): ?>
        <!-- admin profile -->
        <?php
        $s2 = $pdo->prepare("SELECT profile_pic FROM admin WHERE id = :id");
        // execute id parameter with current admin user id
        $s2->execute([':id' => $_SESSION['user_id']]);
        $adminRow    = $s2->fetch();
        $adminAvatar = $adminRow['profile_pic'] ?? '';
        ?>

        <div class="user-menu">
          <button class="nav-icon" id="userMenuBtn" title="Admin Profile">
            <?php if ($adminAvatar): ?>
            <img src="/image/admin/<?= e($adminAvatar) ?>"
                 style="width:28px;height:28px;border-radius:50%;object-fit:cover" alt="">
            <?php else: ?>
            <i class="fa-solid fa-circle-user"></i>
            <?php endif; ?>
          </button>
          <!-- for admin, only show profile and logout options -->
          <div class="user-dropdown" id="userDropdown">
            <div class="user-dropdown__name">Hi, <?= e($_SESSION['username'] ?? 'Admin') ?> ! 💕</div>
            <hr>
            <a href="/system/profile.php">
              <i class="fa-solid fa-user fa-sm" style="width:16px"></i> My Profile
            </a>
            <hr>
            <a href="/system/logout.php" class="danger">
              <i class="fa-solid fa-right-from-bracket fa-sm" style="width:16px"></i> Sign Out
            </a>
          </div>
        </div>

        <?php elseif ($loggedIn): ?>
        <a href="/store/wishlist.php" class="nav-icon" title="Wishlist">
          <i class="fa-regular fa-heart"></i>
        </a>
        <!-- to check if user has items in cart -->
        <a href="/store/cart.php" class="nav-icon" title="Cart">
          <i class="fa-solid fa-bag-shopping"></i>
          <?php if ($cartCount > 0): ?>
          <span class="badge"><?= $cartCount ?></span>
          <?php endif; ?>
        </a>
        
        <div class="user-menu">
          <button class="nav-icon" id="userMenuBtn" title="My Account">
            <?php if (!empty($me['profile_pic'])): ?>
            <img src="/image/profile/<?= e($me['profile_pic']) ?>"
                 style="width:28px;height:28px;border-radius:50%;object-fit:cover" alt="">
            <?php else: ?>
            <i class="fa-solid fa-circle-user"></i>
            <?php endif; ?>
          </button>

          <!-- for user, show more options my profile, wishlist, addresses etc -->
          <div class="user-dropdown" id="userDropdown">
            <div class="user-dropdown__name">Hi, <?= e($me['username'] ?? '') ?> ! 💕</div>
            <hr>
            <a href="/system/profile.php">
              <i class="fa-solid fa-user fa-sm" style="width:16px"></i> My Profile
            </a>
            <a href="/store/orders.php">
              <i class="fa-solid fa-box fa-sm" style="width:16px"></i> My Orders
            </a>
            <a href="/store/wishlist.php">
              <i class="fa-regular fa-heart fa-sm" style="width:16px"></i> Wishlist
            </a>
            <a href="/store/addresses.php">
              <i class="fa-solid fa-location-dot fa-sm" style="width:16px"></i> Addresses
            </a>
            <hr>
            <a href="/system/logout.php" class="danger">
              <i class="fa-solid fa-right-from-bracket fa-sm" style="width:16px"></i> Sign Out
            </a>
          </div>
        </div>

        <?php else: ?>
        <a href="/system/login_register.php" class="btn btn-primary">Join Us</a>
        <?php endif; ?>

      </div>
    </div>
  </div>
</nav>