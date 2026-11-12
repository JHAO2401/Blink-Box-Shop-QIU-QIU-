<?php
// find root directory  to access database and functions
define('ROOT', dirname(__DIR__));
require_once ROOT . '/function/user_fn.php';
requireAdmin();

// Total customers
$s = $pdo->query("SELECT COUNT(*) FROM users");
$totalCustomers = (int) $s->fetchColumn();

// Total products (active)
$s = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1");
$totalProducts = (int) $s->fetchColumn();

// Total orders
$s = $pdo->query("SELECT COUNT(*) FROM orders");
$totalOrders = (int) $s->fetchColumn();

// Total revenue (paid/processing/shipped/delivered)
$s = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status NOT IN ('pending','cancelled')");
$totalRevenue = (float) $s->fetchColumn();

// Pending orders
$s = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
$pendingOrders = (int) $s->fetchColumn();

// Low stock products (stock <= low_stock_threshold)
$s = $pdo->query(
    "SELECT p.id, p.name, p.stock, p.low_stock_threshold,
            c.name AS category
     FROM products p
     JOIN categories c ON p.category_id = c.id
     WHERE p.stock <= p.low_stock_threshold AND p.is_active = 1
     ORDER BY p.stock ASC
     LIMIT 8"
);
$lowStock = $s->fetchAll();

// Recent 8 orders
$s = $pdo->query(
    "SELECT o.id, o.total_amount, o.status, o.created_at,
            u.username
     FROM orders o
     JOIN users u ON o.user_id = u.id
     ORDER BY o.created_at DESC
     LIMIT 8"
);
$recentOrders = $s->fetchAll();

// Orders by status (for mini chart)
$s = $pdo->query(
    "SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status"
);
$ordersByStatus = [];
foreach ($s->fetchAll() as $row) {
    $ordersByStatus[$row['status']] = $row['cnt'];
}

$pageTitle = 'Dashboard';
$pageCss   = ['css/admin.css'];
$activePage = 'dashboard';
require ROOT . '/system/header.php';
?>

<div class="admin-layout">
<?php require ROOT . '/system/admin_sidebar.php'; ?>
<main class="admin-main">

    <!-- Page header -->
    <div class="admin-page-header">
      <div>
        <h1>Dashboard</h1>
        <p class="text-muted">Welcome back, <strong><?= e($_SESSION['username']) ?></strong>! Here's what's happening.</p>
      </div>
    </div>

    <!-- Stats cards  -->
    <div class="stats-grid">

      <div class="stat-card stat-pink">
        <div class="stat-icon">💰</div>
        <div class="stat-body">
          <p class="stat-label">Total Revenue</p>
          <p class="stat-value">RM <?= number_format($totalRevenue, 2) ?></p>
        </div>
      </div>

      <div class="stat-card stat-purple">
        <div class="stat-icon">📦</div>
        <div class="stat-body">
          <p class="stat-label">Total Orders</p>
          <p class="stat-value"><?= $totalOrders ?></p>
          <?php if ($pendingOrders > 0): ?>
          <p class="stat-sub"><span class="tag tag-red"><?= $pendingOrders ?> pending</span></p>
          <?php endif; ?>
        </div>
      </div>

      <div class="stat-card stat-teal">
        <div class="stat-icon">🎁</div>
        <div class="stat-body">
          <p class="stat-label">Active Products</p>
          <p class="stat-value"><?= $totalProducts ?></p>
          <?php if (count($lowStock) > 0): ?>
          <p class="stat-sub"><span class="tag tag-red"><?= count($lowStock) ?> low stock</span></p>
          <?php endif; ?>
        </div>
      </div>

      <div class="stat-card stat-orange">
        <div class="stat-icon">👥</div>
        <div class="stat-body">
          <p class="stat-label">Customers</p>
          <p class="stat-value"><?= $totalCustomers ?></p>
        </div>
      </div>

    </div><!-- /.stats-grid -->

    <!--  Main grid: orders + low stock-->
    <div class="dashboard-grid">

      <!-- Recent Orders -->
      <div class="card dashboard-card">
        <div class="dashboard-card-header">
          <h3>Recent Orders</h3>
          <a href="/admin_manage/order_manage.php" class="btn btn-ghost btn-sm">View all</a>
        </div>

        <?php if (empty($recentOrders)): ?>
        <div class="empty-state">No orders yet.</div>
        <?php else: ?>
        <div class="table-wrap">
          <table class="admin-table"> 
            <thead>
              <tr>
                <th>#</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentOrders as $order): ?>
              <tr>
                <td><a href="/admin_manage/order_manage.php?id=<?= $order['id'] ?>">#<?= $order['id'] ?></a></td>
                <td><?= e($order['username']) ?></td>
                <td>RM <?= number_format($order['total_amount'], 2) ?></td>
                <td><?php
                  $statusClass = [
                    'pending'    => 'tag-orange',
                    'paid'       => 'tag-purple',
                    'processing' => 'tag-purple',
                    'shipped'    => 'tag-teal',
                    'delivered'  => 'tag-green',
                    'cancelled'  => 'tag-red',
                  ][$order['status']] ?? 'tag-pink';
                ?><span class="tag <?= $statusClass ?>"><?= $order['status'] ?></span></td>
                <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

      <!-- Low Stock Alert -->
      <div class="card dashboard-card">
        <div class="dashboard-card-header">
          <h3>⚠️ Low Stock Alert</h3>
          <a href="/admin_manage/product_manage.php" class="btn btn-ghost btn-sm">Manage</a>
        </div>

        <?php if (empty($lowStock)): ?>
        <div class="empty-state">✅ All products have sufficient stock.</div>
        <?php else: ?>
        <div class="table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Stock</th>
                <th>Threshold</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($lowStock as $p): ?>
              <tr>
                <td><a href="/admin_manage/product_manage.php?edit=<?= $p['id'] ?>"><?= e($p['name']) ?></a></td>
                <td><?= e($p['category']) ?></td>
                <td>
                  <span class="tag <?= $p['stock'] == 0 ? 'tag-red' : 'tag-orange' ?>">
                    <?= $p['stock'] == 0 ? 'Out of stock' : $p['stock'] . ' left' ?>
                  </span>
                </td>
                <td class="text-muted"><?= $p['low_stock_threshold'] ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

    </div><!-- /.dashboard-grid -->

    <!-- Order status summary -->
    <?php if ($totalOrders > 0): ?>
    <div class="card mt-4" style="padding:24px">
      <h3 style="margin-bottom:20px">Order Status Summary</h3>
      <div class="status-bars">
        <?php
        $statuses = [ 
          'pending'    => ['label' => 'Pending',    'color' => '#f59e0b'],
          'paid'       => ['label' => 'Paid',        'color' => '#8b5cf6'],
          'processing' => ['label' => 'Processing',  'color' => '#6366f1'],
          'shipped'    => ['label' => 'Shipped',     'color' => '#14b8a6'],
          'delivered'  => ['label' => 'Delivered',   'color' => '#22c55e'],
          'cancelled'  => ['label' => 'Cancelled',   'color' => '#ef4444'],
        ];
        foreach ($statuses as $key => $info):
          $cnt  = $ordersByStatus[$key] ?? 0;
          $pct  = $totalOrders > 0 ? round($cnt / $totalOrders * 100) : 0;
          if ($cnt === 0) continue;
        ?>
        <div class="status-bar-row">
          <span class="status-bar-label"><?= $info['label'] ?></span>
          <div class="status-bar-track">
            <div class="status-bar-fill" style="width:<?= $pct ?>%;background:<?= $info['color'] ?>"></div>
          </div>
          <span class="status-bar-count"><?= $cnt ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

</main>
</div><!-- /.admin-layout -->

<?php require ROOT . '/system/footer.php'; ?>