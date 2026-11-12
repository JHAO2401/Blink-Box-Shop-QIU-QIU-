<?php
// find root directory  to access database and functions
define('ROOT', dirname(__DIR__));
require_once ROOT . '/function/user_fn.php';
require_once ROOT . '/function/store_fn.php';
requireLogin();

$userId = $_SESSION['user_id'];
$msg    = '';

// Handle request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_qty') {
        updateCartQty((int)$_POST['cart_id'], $userId, (int)$_POST['qty']);
        header('Location: /store/cart.php'); exit;
    }
    if ($action === 'remove') {
        removeFromCart((int)$_POST['cart_id'], $userId);
        header('Location: /store/cart.php'); exit;
    }
    if ($action === 'apply_voucher') {
        $code     = trim($_POST['voucher_code'] ?? '');
        $items    = getCartItems($userId);
        $subtotal = cartTotal($items);
        $result   = applyVoucher($code, $subtotal);
        if ($result['success']) {
            $_SESSION['voucher'] = [
                'id'       => $result['voucher']['id'],
                'code'     => $result['voucher']['code'],
                'discount' => $result['discount'],
            ];
        } else {
            $msg = $result['message'];
            unset($_SESSION['voucher']);
        }
        header('Location: /store/cart.php' . ($msg ? '?err=' . urlencode($msg) : '')); exit;
    }
    if ($action === 'remove_voucher') {
        unset($_SESSION['voucher']);
        header('Location: /store/cart.php'); exit;
    }
}

if (!empty($_GET['err'])) $msg = $_GET['err'];

$items    = getCartItems($userId);
$subtotal = cartTotal($items);
$voucher  = $_SESSION['voucher'] ?? null;
$discount = $voucher['discount'] ?? 0;
$total    = max(0, $subtotal - $discount);

$pageTitle = 'My Cart';
$pageCss   = ['css/store.css'];
require ROOT . '/system/header.php';
?>

<main class="cart-wrap">
  <div class="container">

    <h1 style="margin-bottom:28px">My Cart
      <span style="font-family:var(--font-body);font-size:1rem;font-weight:600;color:var(--text-muted);margin-left:10px"><?= count($items) ?> items</span>
    </h1>

    <?php if ($msg): ?>
    <div class="alert alert-error mb-3"><?= e($msg) ?></div>
    <?php endif; ?>

    <?php if (empty($items)): ?>
    <div style="text-align:center;padding:80px 0">
      <p style="font-size:4rem;margin-bottom:16px">🛍️</p>
      <p style="font-size:1.1rem;font-weight:700;color:var(--text-muted)">Your cart is empty.</p>
      <a href="/store/product_list.php" class="btn btn-primary mt-3">Start Shopping</a>
    </div>
    <?php else: ?>

    <div class="cart-grid">

      <!-- Cart items -->
      <div class="cart-items">
        <?php foreach ($items as $item):
          $fp = finalPrice($item);
        ?>
        <div class="cart-item">
          <a href="/store/product_detail.php?id=<?= $item['id'] ?>">
            <?php if ($item['cover_image']): ?>
            <img src="/image/<?= e($item['cover_image']) ?>" class="cart-item-img" alt="<?= e($item['name']) ?>">
            <?php else: ?>
            <div class="cart-item-img" style="display:flex;align-items:center;justify-content:center;font-size:2rem;background:var(--cream)">🎁</div>
            <?php endif; ?>
          </a>
          <div class="cart-item-info">
            <div class="cart-item-name"><?= e($item['name']) ?></div>
            <div class="cart-item-price">RM <?= number_format($fp, 2) ?> each</div>
            <div class="cart-item-actions">
              <!-- Qty -->
              <form method="POST" style="display:flex;align-items:center;gap:0">
                <input type="hidden" name="action"  value="update_qty">
                <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                <div class="qty-wrap" style="transform:scale(.85);transform-origin:left">
                  <button type="submit" name="qty" value="<?= $item['quantity'] - 1 ?>" class="qty-btn">−</button>
                  <span class="qty-input"><?= $item['quantity'] ?></span>
                  <button type="submit" name="qty" value="<?= $item['quantity'] + 1 ?>" class="qty-btn"
                          <?= $item['quantity'] >= $item['stock'] ? 'disabled' : '' ?>>+</button>
                </div>
              </form>
              <!-- Remove -->
              <form method="POST">
                <input type="hidden" name="action"  value="remove">
                <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                <button type="submit" style="background:none;border:none;cursor:pointer;color:var(--gray-400);font-size:.85rem;font-weight:700"
                        onclick="return confirm('Remove this item?')">Remove</button>
              </form>
            </div>
          </div>
          <div style="font-weight:900;font-size:1rem;color:var(--pink-600);white-space:nowrap">
            RM <?= number_format($fp * $item['quantity'], 2) ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Summary -->
      <div class="cart-summary">
        <h3>Order Summary</h3>

        <div class="summary-row">
          <span>Subtotal</span>
          <span>RM <?= number_format($subtotal, 2) ?></span>
        </div>

        <?php if ($voucher): ?>
        <div class="summary-row" style="color:var(--mint-dark)">
          <span>Voucher (<?= e($voucher['code']) ?>)</span>
          <span>− RM <?= number_format($discount, 2) ?></span>
        </div>
        <?php endif; ?>

        <div class="summary-row">
          <span class="summary-total">Total</span>
          <span class="summary-total">RM <?= number_format($total, 2) ?></span>
        </div>

        <!-- Voucher -->
        <?php if (!$voucher): ?>
        <form method="POST" class="voucher-wrap">
          <input type="hidden" name="action" value="apply_voucher">
          <input type="text" name="voucher_code" class="form-control"
                 placeholder="Voucher code" style="text-transform:uppercase">
          <button type="submit" class="btn btn-outline btn-sm" style="white-space:nowrap">Apply</button>
        </form>
        <?php else: ?>
        <div style="background:var(--mint);border-radius:var(--r-md);padding:10px 14px;font-size:13px;margin:14px 0;display:flex;justify-content:space-between;align-items:center">
          <span style="color:var(--mint-dark);font-weight:800">✅ <?= e($voucher['code']) ?> applied!</span>
          <form method="POST">
            <input type="hidden" name="action" value="remove_voucher">
            <button type="submit" style="background:none;border:none;cursor:pointer;color:var(--gray-400);font-size:12px">Remove</button>
          </form>
        </div>
        <?php endif; ?>

        <a href="/store/checkout.php" class="btn btn-primary btn-full btn-lg" style="margin-top:8px">
          Proceed to Checkout →
        </a>
        <a href="/store/product_list.php" class="btn btn-ghost btn-full mt-2">Continue Shopping</a>
      </div>

    </div>
    <?php endif; ?>

  </div>
</main>

<?php $pageJs = ['js/store.js'];
require ROOT . '/system/footer.php'; ?>