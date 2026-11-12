<?php
// find root directory  to access database and functions
define('ROOT', dirname(__DIR__));
require_once ROOT . '/function/user_fn.php';
require_once ROOT . '/function/order_fn.php';
require_once ROOT . '/function/store_fn.php';
requireAdmin();

$msg     = '';
$msgType = 'success';

// Handle order method update and delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $id       = (int)($_POST['order_id'] ?? 0);
        $status   = $_POST['status']          ?? '';
        $tracking = trim($_POST['tracking']   ?? '');
        updateOrderStatus($id, $status, $tracking);
        $msg = 'Order #' . $id . ' updated!';
        header('Location: order_manage.php?id=' . $id . '&msg=' . urlencode($msg));
        exit;
    }

    if ($action === 'delete_order') {
        $id = (int)($_POST['order_id'] ?? 0);
        $pdo->prepare("DELETE FROM order_items WHERE order_id = :id")->execute([':id' => $id]);
        $pdo->prepare("DELETE FROM orders WHERE id = :id")->execute([':id' => $id]);
        header('Location: order_manage.php?msg=' . urlencode('Order #' . $id . ' deleted.'));
        exit;
    }
}

if (!empty($_GET['msg'])) $msg = $_GET['msg'];

$statuses = ['pending','paid','processing','shipped','delivered','cancelled'];

// Single order view
if (!empty($_GET['id'])) {
    $order = getOrderDetail((int)$_GET['id']);
    $pageTitle  = 'Order #' . $_GET['id'];
    $pageCss    = ['css/admin.css'];
    $activePage = 'orders';
    require ROOT . '/system/header.php';
?>
<div class="admin-layout">
<?php require ROOT . '/system/admin_sidebar.php'; ?>
<main class="admin-main">

  <div class="admin-page-header">
    <div>
      <h1>Order #<?= $order['id'] ?></h1>
      <p class="text-muted">Placed on <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></p>
    </div>
    <a href="/admin_manage/order_manage.php" class="btn btn-ghost btn-sm">← All Orders</a>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-success mb-3"><?= e($msg) ?></div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 340px;gap:24px">

    <!-- Order items -->
    <div>
      <div class="card mb-3" style="padding:0">
        <div class="dashboard-card-header"><h3>Items</h3></div>
        <table class="admin-table">
          <thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
          <tbody>
            <?php foreach ($order['items'] as $item): ?>
            <tr>
              <td style="display:flex;align-items:center;gap:10px">
                <?php if ($item['cover_image']): ?>
                <img src="/image/<?= e($item['cover_image']) ?>"
                     style="width:40px;height:40px;object-fit:cover;border-radius:6px;flex-shrink:0">
                <?php endif; ?>
                <?= e($item['product_name']) ?>
              </td>
              <td><?= $item['quantity'] ?></td>
              <td>RM <?= number_format($item['unit_price'], 2) ?></td>
              <td>RM <?= number_format($item['unit_price'] * $item['quantity'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div style="padding:16px 20px;text-align:right;border-top:1.5px solid var(--cream-border)">
          <?php if ($order['discount_amount'] > 0): ?>
          <p style="font-size:13px;color:var(--text-muted);margin-bottom:4px">
            Subtotal: RM <?= number_format($order['subtotal'], 2) ?><br>
            Discount: − RM <?= number_format($order['discount_amount'], 2) ?>
          </p>
          <?php endif; ?>
          <p style="font-size:1.1rem;font-weight:800;color:var(--text)">
            Total: RM <?= number_format($order['total_amount'], 2) ?>
          </p>
        </div>
      </div>

      <!-- Shipping address -->
      <div class="card" style="padding:20px 24px">
        <h3 style="font-size:1rem;margin-bottom:12px">Shipping Address</h3>
        <p style="font-size:14px;line-height:1.8;color:var(--text-muted)">
          <strong style="color:var(--text)"><?= e($order['recipient_name']) ?></strong><br>
          <?= e(formatMyPhone($order['phone'])) ?><br>
          <?= e($order['address_line']) ?>,<br>
          <?= e($order['city']) ?><?= $order['state'] ? ', ' . e($order['state']) : '' ?>,
          <?= e($order['postcode']) ?>, <?= e($order['country']) ?>
        </p>
      </div>
    </div>

    <!-- Status and update -->
    <div style="display:flex;flex-direction:column;gap:16px">

      <!-- Current status -->
      <div class="card" style="padding:20px 24px">
        <h3 style="font-size:1rem;margin-bottom:12px">Status</h3>
        <span class="tag <?= orderStatusClass($order['status']) ?>" style="font-size:13px;padding:6px 14px">
          <?= ucfirst($order['status']) ?>
        </span>
        <?php if ($order['tracking_number']): ?>
        <p style="margin-top:10px;font-size:13px;color:var(--text-muted)">
          Tracking: <strong style="color:var(--text)"><?= e($order['tracking_number']) ?></strong>
        </p>
        <?php endif; ?>
        <p style="margin-top:8px;font-size:13px;color:var(--text-muted)">
          Customer: <strong><?= e($order['username']) ?></strong><br>
          Email: <?= e($order['email']) ?><br>
          Payment: <?= e($order['payment_method']) ?>
        </p>
      </div>

      <!-- Update form -->
      <div class="card" style="padding:20px 24px">
        <h3 style="font-size:1rem;margin-bottom:14px">Update Order</h3>
        <form method="POST">
          <input type="hidden" name="action"   value="update_status">
          <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              <?php foreach ($statuses as $s): ?>
              <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>>
                <?= ucfirst($s) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Tracking Number</label>
            <input type="text" name="tracking" class="form-control"
                   value="<?= e($order['tracking_number'] ?? '') ?>"
                   placeholder="(example) MY123456789">
          </div>
          <button type="submit" class="btn btn-primary btn-full">Update</button>
        </form>
      </div>

    </div>
  </div>

</main>
</div>
<?php
    require ROOT . '/system/footer.php';
    exit;
}

//  Order list view 
$filterStatus = $_GET['status'] ?? '';
$filterSearch = $_GET['search'] ?? '';
$orders = getOrders(['status' => $filterStatus, 'search' => $filterSearch]);

$pageTitle  = 'Order Management';
$pageCss    = ['css/admin.css'];
$activePage = 'orders';
require ROOT . '/system/header.php';
?>

<div class="admin-layout">
<?php require ROOT . '/system/admin_sidebar.php'; ?>
<main class="admin-main">

  <div class="admin-page-header">
    <div>
      <h1>Order Management</h1>
      <p class="text-muted"><?= count($orders) ?> orders</p>
    </div>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-success mb-3"><?= e($msg) ?></div>
  <?php endif; ?>

  <!-- Filters -->
  <form method="GET" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap">
    <input type="text" name="search" class="form-control"
           placeholder="Search by customer / order ID…"
           value="<?= e($filterSearch) ?>"
           style="flex:1;min-width:200px">
    <select name="status" class="form-control" style="width:160px">
      <option value="">All statuses</option>
      <?php foreach ($statuses as $s): ?>
      <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <?php if ($filterStatus || $filterSearch): ?>
    <a href="/admin_manage/order_manage.php" class="btn btn-ghost btn-sm">Clear</a>
    <?php endif; ?>
  </form>

  <div class="card">
    <?php if (empty($orders)): ?>
    <div class="empty-state">No orders found.</div>
    <?php else: ?>
    <div class="table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Customer</th>
            <th>Total</th>
            <th>Status</th>
            <th>Date</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
          <tr>
            <td><strong>#<?= $o['id'] ?></strong></td>
            <td>
              <strong><?= e($o['username']) ?></strong><br>
              <span style="font-size:12px;color:var(--text-muted)"><?= e($o['email']) ?></span>
            </td>
            <td>RM <?= number_format($o['total_amount'], 2) ?></td>
            <td><span class="tag <?= orderStatusClass($o['status']) ?>"><?= ucfirst($o['status']) ?></span></td>
            <td style="font-size:13px"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
            <td>
              <a href="/admin_manage/order_manage.php?id=<?= $o['id'] ?>"
                 class="btn btn-ghost btn-sm">View</a>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action"   value="delete_order">
                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm"
                        onclick="return confirm('Delete order #<?= $o['id'] ?>? This cannot be undone.')">
                  Delete
                </button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

</main>
</div>

<?php require ROOT . '/system/footer.php'; ?>