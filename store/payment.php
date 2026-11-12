<?php
// find root directory  to access database and functions
define('ROOT', dirname(__DIR__));
require_once ROOT . '/function/user_fn.php';
require_once ROOT . '/function/store_fn.php';
requireLogin();

$userId  = $_SESSION['user_id'];
$orderId = (int)($_GET['order_id'] ?? 0);
if (!$orderId) { header('Location: /store/orders.php'); exit; }

$order = getUserOrder($orderId, $userId);
if (!$order) { header('Location: /store/orders.php'); exit; }

$pageTitle = 'Payment';
$pageCss   = ['css/store.css'];
require ROOT . '/system/header.php';
?>

<main class="payment-wrap">
  <div class="container" style="max-width:520px">

    <div class="text-center mb-4">
      <h1 style="margin-bottom:6px">Complete Payment</h1>
      <p class="text-muted">RM <?= number_format($order['total_amount'], 2) ?></p>
    </div>

    <!-- QR Card -->
    <div class="card payment-card">
      <p style="font-size:13px;font-weight:800;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">Scan to Pay</p>
      <p class="payment-amount">RM <?= number_format($order['total_amount'], 2) ?></p>
      <div class="payment-qr-box">
        <img src="/image/qr.png" alt="QR Code">
      </div>
      <p class="payment-hint">Scan with your banking app or e-wallet</p>
      <p class="payment-apps">Touch 'n Go · Maybank · CIMB · RHB · DuitNow QR</p>
    </div>

    <!-- How to pay -->
    <div class="card payment-steps-card">
      <p class="payment-steps-label">How to Pay</p>
      <ol class="payment-steps-list">
        <li>Open your banking app or e-wallet</li>
        <li>Tap <strong>Scan QR</strong></li>
        <li>Scan the code above</li>
        <li>Enter amount: <strong>RM <?= number_format($order['total_amount'], 2) ?></strong></li>
        <li>Confirm &amp; screenshot your receipt</li>
      </ol>
    </div>

    <!-- Order summary -->
    <div class="card payment-steps-card">
      <p class="payment-steps-label">Order Summary</p>
      <?php foreach ($order['items'] as $item): ?>
      <div class="summary-row">
        <span class="text-muted"><?= e($item['product_name']) ?> × <?= $item['quantity'] ?></span>
        <span>RM <?= number_format($item['unit_price'] * $item['quantity'], 2) ?></span>
      </div>
      <?php endforeach; ?>
      <?php if ($order['discount_amount'] > 0): ?>
      <div class="summary-row order-discount">
        <span>Discount</span>
        <span>− RM <?= number_format($order['discount_amount'], 2) ?></span>
      </div>
      <?php endif; ?>
      <div class="summary-row">
        <span class="summary-total">Total</span>
        <span class="summary-total">RM <?= number_format($order['total_amount'], 2) ?></span>
      </div>
    </div>

    <a href="/store/orders.php?id=<?= $orderId ?>&new=<?= $orderId ?>"
       class="btn btn-primary btn-lg btn-full">
      ✅ I've Paid — View My Order
    </a>
    <p class="payment-footer-note">Order status will be updated after payment verification.</p>

  </div>
</main>

<?php
$pageJs = ['js/store.js'];
require ROOT . '/system/footer.php';
?>