<?php
// find root directory  to access database and functions
define('ROOT', dirname(__DIR__));
require_once ROOT . '/function/user_fn.php';
require_once ROOT . '/function/store_fn.php';
requireLogin();

$userId    = $_SESSION['user_id'];
$items     = getCartItems($userId);
if (empty($items)) { header('Location: /store/cart.php'); exit; }

$addresses = getUserAddresses($userId);
$subtotal  = cartTotal($items);
$voucher   = $_SESSION['voucher'] ?? null;
$discount  = $voucher['discount'] ?? 0;
$total     = max(0, $subtotal - $discount);

$error = '';

// Handle POST — place order
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $addressId = (int)($_POST['address_id'] ?? 0);
    $notes     = trim($_POST['notes'] ?? '');

    if (!$addressId) {
        $error = 'Please select a shipping address.';
    } else {
        $orderId = placeOrder(
            $userId, $addressId, $items,
            $subtotal, $discount,
            $voucher ? $voucher['id'] : null,
            $notes
        );
        clearCart($userId);
        unset($_SESSION['voucher']);
        header('Location: /store/payment.php?order_id=' . $orderId);
        exit;
    }
}

$pageTitle = 'Checkout';
$pageCss   = ['css/store.css'];
require ROOT . '/system/header.php';
?>

<main class="checkout-wrap">
  <div class="container">

    <h1 style="margin-bottom:28px">Checkout</h1>

    <?php if ($error): ?>
    <div class="alert alert-error mb-3"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST">
    <div class="checkout-grid">

      <!-- Left: address + notes -->
      <div>

        <!-- Address selection -->
        <div class="card mb-3" style="padding:24px">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
            <h3 style="font-size:1rem;font-family:var(--font-body);font-weight:800">Shipping Address</h3>
            <a href="/store/addresses.php?redirect=checkout" class="btn btn-ghost btn-sm">+ Add Address</a>
          </div>

          <?php if (empty($addresses)): ?>
          <div style="text-align:center;padding:20px;color:var(--text-muted)">
            <p>No addresses saved. <a href="/store/addresses.php?redirect=checkout">Add one</a></p>
          </div>
          <?php else: ?>
          <div style="display:flex;flex-direction:column;gap:12px">
            <?php foreach ($addresses as $addr): ?>
            <label style="cursor:pointer">
              <div class="address-card <?= $addr['is_default'] ? 'is-default' : '' ?>"
                   style="display:flex;gap:14px;align-items:flex-start">
                <input type="radio" name="address_id" value="<?= $addr['id'] ?>"
                       <?= $addr['is_default'] ? 'checked' : '' ?>
                       style="margin-top:4px;accent-color:var(--pink-400)">
                <div>
                  <div style="font-weight:800;font-size:13px;color:var(--pink-500);margin-bottom:4px">
                    <?= e($addr['label']) ?>
                    <?php if ($addr['is_default']): ?>
                    <span class="tag tag-pink" style="margin-left:6px">Default</span>
                    <?php endif; ?>
                  </div>
                  <div style="font-weight:800"><?= e($addr['recipient_name']) ?></div>
                  <div style="font-size:13px;color:var(--text-muted)">
                    <?= e($addr['phone']) ?><br>
                    <?= e($addr['address_line']) ?>, <?= e($addr['city']) ?><?= $addr['state'] ? ', ' . e($addr['state']) : '' ?>,
                    <?= e($addr['postcode']) ?>, <?= e($addr['country']) ?>
                  </div>
                </div>
              </div>
            </label>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

        <!-- Notes -->
        <div class="card" style="padding:24px">
          <h3 style="font-size:1rem;font-family:var(--font-body);font-weight:800;margin-bottom:14px">Order Notes (optional)</h3>
          <textarea name="notes" class="form-control" rows="3"
                    placeholder="Special instructions for your order..."></textarea>
        </div>

      </div>

      <!-- Right: order summary + payment -->
      <div>
        <div class="cart-summary">
          <h3>Order Summary</h3>

          <!-- Items -->
          <?php foreach ($items as $item): ?>
          <div style="display:flex;justify-content:space-between;font-size:13px;padding:6px 0;border-bottom:1.5px solid var(--cream-border)">
            <span style="color:var(--text-muted)"><?= e($item['name']) ?> × <?= $item['quantity'] ?></span>
            <span>RM <?= number_format(finalPrice($item) * $item['quantity'], 2) ?></span>
          </div>
          <?php endforeach; ?>

          <div class="summary-row">
            <span>Subtotal</span>
            <span>RM <?= number_format($subtotal, 2) ?></span>
          </div>
          <?php if ($discount > 0): ?>
          <div class="summary-row" style="color:var(--mint-dark)">
            <span>Discount</span>
            <span>− RM <?= number_format($discount, 2) ?></span>
          </div>
          <?php endif; ?>
          <div class="summary-row">
            <span class="summary-total">Total</span>
            <span class="summary-total">RM <?= number_format($total, 2) ?></span>
          </div>

          <!-- Payment method -->
          <div style="margin:20px 0 16px">
            <p style="font-size:13px;font-weight:800;color:var(--gray-600);margin-bottom:10px">Payment Method</p>
            <div style="background:var(--cream);border-radius:var(--r-md);padding:14px;text-align:center">
              <div style="font-size:2rem;margin-bottom:6px">📱</div>
              <div style="font-weight:800;font-size:14px">QR Pay</div>
              <div style="font-size:12px;color:var(--text-muted)">Scan QR code after placing order</div>
            </div>
          </div>

          <button type="submit" class="btn btn-primary btn-full btn-lg">
            Place Order — RM <?= number_format($total, 2) ?>
          </button>
        </div>
      </div>

    </div>
    </form>

  </div>
</main>

<?php $pageJs = ['js/index.js'];
require ROOT . '/system/footer.php'; ?>