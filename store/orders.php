<?php
// find root directory  to access database and functions
define('ROOT', dirname(__DIR__));
require_once ROOT . '/function/user_fn.php';
require_once ROOT . '/function/store_fn.php';
requireLogin();

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $oid = (int)$_POST['order_id'];
    cancelOrder($oid, $userId);
    header('Location: /store/orders.php?id=' . $oid . '&cancelled=1');
    exit;
}

// Single order detail 
if (!empty($_GET['id'])) {
    $order = getUserOrder((int)$_GET['id'], $userId);
    if (!$order) { header('Location: /store/orders.php'); exit; }

    $steps   = ['pending','paid','processing','shipped','delivered'];
    $curStep = (int) array_search($order['status'], $steps);
    $isCancelled = $order['status'] === 'cancelled';
    $fillPct = $curStep > 0 ? round($curStep / (count($steps)-1) * 80) : 0;

    $pageTitle = 'Your Order';
    $pageCss   = ['css/store.css'];
    require ROOT . '/system/header.php';
?>
<main class="orders-wrap">
  <div class="container" style="max-width:820px">

    <div class="admin-page-header">
      <div>
        <h1>Your Order</h1>
        <p class="text-muted">Placed on <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></p>
      </div>
      <div class="d-flex gap-2">
        <?php if ($order['status'] === 'pending'): ?>
        <form method="POST">
          <input type="hidden" name="order_id"    value="<?= $order['id'] ?>">
          <input type="hidden" name="cancel_order" value="1">
          <button type="submit" class="btn btn-danger btn-sm"
                  onclick="return confirm('Cancel this order?')">Cancel Order</button>
        </form>
        <?php endif; ?>
        <a href="/store/orders.php" class="btn btn-ghost btn-sm">← All Orders</a>
      </div>
    </div>

    <?php if (!empty($_GET['new'])): ?>
    <div class="alert alert-success mb-3">🎉 Order placed! We'll process it soon.</div>
    <?php endif; ?>
    <?php if (!empty($_GET['cancelled'])): ?>
    <div class="alert alert-error mb-3">Order cancelled. Stock has been restored.</div>
    <?php endif; ?>

    <!-- Status tracker -->
    <div class="card mb-3" style="padding:24px">
      <div class="d-flex align-center justify-between mb-3">
        <h3 style="font-family:var(--font-body);font-size:1rem;font-weight:800">Order Status</h3>
        <?php
        $sc = statusColor($order['status']);
        ?>
        <span class="status-tag"
              style="background:<?= $sc ?>22;color:<?= $sc ?>;border-color:<?= $sc ?>44">
          <?= ucfirst($order['status']) ?>
        </span>
      </div>

      <?php if ($isCancelled): ?>
      <p class="text-center" style="color:#ef4444;font-weight:800;padding:8px 0">❌ Order Cancelled</p>
      <?php else: ?>
      <div class="order-status-tracker">
        <div class="order-steps">
          <div class="order-steps-track"></div>
          <div class="order-steps-fill" style="width:<?= $fillPct ?>%"></div>
          <?php foreach ($steps as $i => $step):
            $cls = $i < $curStep ? 'done' : ($i === $curStep ? 'active' : '');
          ?>
          <div class="order-step">
            <div class="order-step-dot <?= $cls ?>">
              <?= $i < $curStep ? '✓' : ($i === $curStep ? '●' : ($i+1)) ?>
            </div>
            <div class="order-step-label <?= $cls ?>"><?= ucfirst($step) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($order['tracking_number']): ?>
      <div class="order-tracking-number">
        📦 Tracking: <strong><?= e($order['tracking_number']) ?></strong>
      </div>
      <?php endif; ?>
    </div>

    <div class="order-detail-grid">

      <!-- Items -->
      <div class="card order-items-table">
        <div class="order-items-header">Items (<?= count($order['items']) ?>)</div>
        <?php foreach ($order['items'] as $item): ?>
        <div class="order-item-row">
          <?php if ($item['cover_image']): ?>
          <img src="/image/<?= e($item['cover_image']) ?>" class="order-item-img" alt="">
          <?php endif; ?>
          <div class="order-item-info">
            <div class="order-item-name"><?= e($item['product_name']) ?></div>
            <div class="order-item-qty">Qty: <?= $item['quantity'] ?> × RM <?= number_format($item['unit_price'], 2) ?></div>
          </div>
          <div class="order-item-price">RM <?= number_format($item['unit_price'] * $item['quantity'], 2) ?></div>
        </div>
        <?php endforeach; ?>
        <div class="order-total-row">
          <?php if ($order['discount_amount'] > 0): ?>
          <p>Subtotal: RM <?= number_format($order['subtotal'], 2) ?></p>
          <p class="order-discount">Discount: − RM <?= number_format($order['discount_amount'], 2) ?></p>
          <?php endif; ?>
          <p class="total-final">Total: RM <?= number_format($order['total_amount'], 2) ?></p>
        </div>
      </div>

      <!-- Shipping + Payment -->
      <div class="order-info-cards">
        <div class="card order-info-card">
          <p class="order-info-label">Ship To</p>
          <p class="order-address-text">
            <strong><?= e($order['recipient_name']) ?></strong><br>
            <?= e(formatMyPhone($order['phone'])) ?><br>
            <?= e($order['address_line']) ?>,<br>
            <?= e($order['city']) ?><?= $order['state'] ? ', ' . e($order['state']) : '' ?>,<br>
            <?= e($order['postcode']) ?>, <?= e($order['country']) ?>
          </p>
        </div>
        <div class="card order-info-card">
          <p class="order-info-label">Payment</p>
          <p>📱 <?= e($order['payment_method']) ?></p>
        </div>
      </div>

    </div>
  </div>
</main>
<?php
    $pageJs = ['js/store.js'];
    require ROOT . '/system/footer.php';
    exit;
}

// Order list 
$orders     = getUserOrders($userId);
$newOrderId = (int)($_GET['new'] ?? 0);

$pageTitle = 'My Orders';
$pageCss   = ['css/store.css'];
require ROOT . '/system/header.php';
?>
<main class="orders-wrap">
  <div class="container" style="max-width:820px">

    <h1 class="mb-4">My Orders</h1>

    <?php if ($newOrderId): ?>
    <div class="alert alert-success mb-3">
      🎉 Order placed successfully! <a href="/store/orders.php?id=<?= $newOrderId ?>">View →</a>
    </div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
    <div class="text-center" style="padding:80px 0">
      <p style="font-size:4rem;margin-bottom:16px">📦</p>
      <p class="text-muted" style="font-size:1.1rem;font-weight:700">No orders yet.</p>
      <a href="/store/product_list.php" class="btn btn-primary mt-3">Start Shopping</a>
    </div>
    <?php else: ?>
    <?php foreach ($orders as $o):
      $sc = statusColor($o['status']);
    ?>
    <div class="order-card">
      <div class="order-card-header">
        <div>
          <strong style="font-size:1rem">Your Order</strong>
          <span class="text-muted" style="font-size:13px;margin-left:10px">
            <?= date('d M Y', strtotime($o['created_at'])) ?>
          </span>
        </div>
        <div class="d-flex align-center gap-2">
          <span class="status-tag"
                style="background:<?= $sc ?>22;color:<?= $sc ?>;border-color:<?= $sc ?>44">
            <?= ucfirst($o['status']) ?>
          </span>
          <?php if ($o['status'] === 'pending'): ?>
          <form method="POST">
            <input type="hidden" name="order_id"    value="<?= $o['id'] ?>">
            <input type="hidden" name="cancel_order" value="1">
            <button type="submit" class="btn btn-danger btn-sm"
                    onclick="return confirm('Cancel order #<?= $o['id'] ?>?')">Cancel</button>
          </form>
          <?php endif; ?>
          <a href="/store/orders.php?id=<?= $o['id'] ?>" class="btn btn-ghost btn-sm">View</a>
        </div>
      </div>
      <div class="order-card-meta"><?= $o['item_count'] ?> item(s)</div>
      <div class="order-card-total">RM <?= number_format($o['total_amount'], 2) ?></div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

  </div>
</main>
<?php
$pageJs = ['js/store.js'];
require ROOT . '/system/footer.php';
?>