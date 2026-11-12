<?php
// find root directory  to access database and functions
define('ROOT', dirname(__DIR__));
require_once ROOT . '/function/user_fn.php';
requireAdmin();


$period = $_GET['period'] ?? '30'; // days
$from   = date('Y-m-d', strtotime("-{$period} days"));
$to     = date('Y-m-d');

// Total revenue in period
$s = $pdo->prepare(
    "SELECT COALESCE(SUM(total_amount),0) FROM orders
     WHERE status NOT IN ('pending','cancelled')
     AND DATE(created_at) BETWEEN :from AND :to"
);
$s->execute([':from' => $from, ':to' => $to]);
$revenue = (float) $s->fetchColumn();

// Total orders in period
$s = $pdo->prepare(
    "SELECT COUNT(*) FROM orders WHERE DATE(created_at) BETWEEN :from AND :to"
);
$s->execute([':from' => $from, ':to' => $to]);
$orderCount = (int) $s->fetchColumn();

// Average order value
$avgOrder = $orderCount > 0 ? $revenue / $orderCount : 0;

// New customers in period
$s = $pdo->prepare(
    "SELECT COUNT(*) FROM users WHERE DATE(created_at) BETWEEN :from AND :to"
);
$s->execute([':from' => $from, ':to' => $to]);
$newCustomers = (int) $s->fetchColumn();

// Orders by status in period
$s = $pdo->prepare(
    "SELECT status, COUNT(*) AS cnt, COALESCE(SUM(total_amount),0) AS total
     FROM orders WHERE DATE(created_at) BETWEEN :from AND :to
     GROUP BY status"
);
$s->execute([':from' => $from, ':to' => $to]);
$byStatus = [];
foreach ($s->fetchAll() as $row) {
    $byStatus[$row['status']] = $row;
}

// Top selling products in period
$s = $pdo->prepare(
    "SELECT p.name, p.price,
            SUM(oi.quantity) AS total_qty,
            SUM(oi.quantity * oi.unit_price) AS total_revenue,
            (SELECT image_path FROM product_images WHERE product_id = p.id AND sort_order = 0 LIMIT 1) AS cover_image
     FROM order_items oi
     JOIN orders o ON oi.order_id = o.id
     JOIN products p ON oi.product_id = p.id
     WHERE o.status NOT IN ('pending','cancelled')
       AND DATE(o.created_at) BETWEEN :from AND :to
     GROUP BY oi.product_id
     ORDER BY total_qty DESC
     LIMIT 8"
);
$s->execute([':from' => $from, ':to' => $to]);
$topProducts = $s->fetchAll();

// Daily revenue for the period (for bar chart data)
$s = $pdo->prepare(
    "SELECT DATE(created_at) AS day, COALESCE(SUM(total_amount),0) AS daily_rev
     FROM orders
     WHERE status NOT IN ('pending','cancelled')
       AND DATE(created_at) BETWEEN :from AND :to
     GROUP BY DATE(created_at)
     ORDER BY day ASC"
);
$s->execute([':from' => $from, ':to' => $to]);
$dailyRevenue = [];
foreach ($s->fetchAll() as $row) {
    $dailyRevenue[$row['day']] = (float) $row['daily_rev'];
}

// Revenue by category
$s = $pdo->prepare(
    "SELECT c.name AS category, c.icon,
            SUM(oi.quantity * oi.unit_price) AS total,
            SUM(oi.quantity) AS qty
     FROM order_items oi
     JOIN orders o ON oi.order_id = o.id
     JOIN products p ON oi.product_id = p.id
     JOIN categories c ON p.category_id = c.id
     WHERE o.status NOT IN ('pending','cancelled')
       AND DATE(o.created_at) BETWEEN :from AND :to
     GROUP BY p.category_id
     ORDER BY total DESC"
);
$s->execute([':from' => $from, ':to' => $to]);
$byCategory = $s->fetchAll();

$pageTitle  = 'Report';
$pageCss    = ['css/admin.css'];
$activePage = 'report';
require ROOT . '/system/header.php';
?>

<div class="admin-layout">
<?php require ROOT . '/system/admin_sidebar.php'; ?>
<main class="admin-main">

  <!-- Header -->
  <div class="admin-page-header">
    <div>
      <h1>Sales Report</h1>
      <p class="text-muted"><?= date('d M Y', strtotime($from)) ?> — <?= date('d M Y', strtotime($to)) ?></p>
    </div>
    <!-- Period filter -->
    <div style="display:flex;gap:6px">
      <?php foreach (['7'=>'7 days','30'=>'30 days','90'=>'90 days','365'=>'1 year'] as $days => $label): ?>
      <a href="/admin_manage/report.php?period=<?= $days ?>"
         class="btn btn-sm <?= $period == $days ? 'btn-primary' : 'btn-ghost' ?>">
        <?= $label ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Stats cards -->
  <div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card stat-pink">
      <div class="stat-icon">💰</div>
      <div class="stat-body">
        <p class="stat-label">Revenue</p>
        <p class="stat-value">RM <?= number_format($revenue, 2) ?></p>
      </div>
    </div>
    <div class="stat-card stat-purple">
      <div class="stat-icon">📦</div>
      <div class="stat-body">
        <p class="stat-label">Orders</p>
        <p class="stat-value"><?= $orderCount ?></p>
      </div>
    </div>
    <div class="stat-card stat-teal">
      <div class="stat-icon">🧾</div>
      <div class="stat-body">
        <p class="stat-label">Avg Order Value</p>
        <p class="stat-value">RM <?= number_format($avgOrder, 2) ?></p>
      </div>
    </div>
    <div class="stat-card stat-orange">
      <div class="stat-icon">👥</div>
      <div class="stat-body">
        <p class="stat-label">New Customers</p>
        <p class="stat-value"><?= $newCustomers ?></p>
      </div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">

    <!-- Order status breakdown -->
    <div class="card" style="padding:24px">
      <h3 style="font-size:1rem;margin-bottom:18px">Orders by Status</h3>
      <?php
      $statuses = [
        'pending'    => ['label'=>'Pending',    'color'=>'#f59e0b'],
        'paid'       => ['label'=>'Paid',        'color'=>'#8b5cf6'],
        'processing' => ['label'=>'Processing',  'color'=>'#6366f1'],
        'shipped'    => ['label'=>'Shipped',     'color'=>'#14b8a6'],
        'delivered'  => ['label'=>'Delivered',   'color'=>'#22c55e'],
        'cancelled'  => ['label'=>'Cancelled',   'color'=>'#ef4444'],
      ];
      $totalOrders = max(1, $orderCount);
      foreach ($statuses as $key => $info):
        $cnt = $byStatus[$key]['cnt'] ?? 0;
        $rev = $byStatus[$key]['total'] ?? 0;
        if ($cnt === 0) continue;
        $pct = round($cnt / $totalOrders * 100);
      ?>
      <div class="status-bar-row" style="margin-bottom:10px">
        <span class="status-bar-label"><?= $info['label'] ?></span>
        <div class="status-bar-track">
          <div class="status-bar-fill" style="width:<?= $pct ?>%;background:<?= $info['color'] ?>"></div>
        </div>
        <span class="status-bar-count"><?= $cnt ?></span>
      </div>
      <?php endforeach; ?>
      <?php if ($orderCount === 0): ?>
      <p style="color:var(--text-muted);font-size:14px;text-align:center;padding:20px 0">No orders in this period.</p>
      <?php endif; ?>
    </div>

    <!-- Revenue by category -->
    <div class="card" style="padding:24px">
      <h3 style="font-size:1rem;margin-bottom:18px">Revenue by Category</h3>
      <?php if (empty($byCategory)): ?>
      <p style="color:var(--text-muted);font-size:14px;text-align:center;padding:20px 0">No sales in this period.</p>
      <?php else: ?>
      <?php foreach ($byCategory as $cat): ?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1.5px solid var(--cream-border)">
        <div>
          <span style="font-size:1.1rem"><?= e($cat['icon']) ?></span>
          <span style="font-weight:700;margin-left:8px"><?= e($cat['category']) ?></span>
          <span style="font-size:12px;color:var(--text-muted);margin-left:8px"><?= $cat['qty'] ?> sold</span>
        </div>
        <strong style="color:var(--pink-600)">RM <?= number_format($cat['total'], 2) ?></strong>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>

  <!-- Top selling products -->
  <div class="card" style="padding:0">
    <div class="dashboard-card-header">
      <h3>Top Selling Products</h3>
      <span style="font-size:13px;color:var(--text-muted)">by quantity sold</span>
    </div>
    <?php if (empty($topProducts)): ?>
    <div class="empty-state">No sales data in this period.</div>
    <?php else: ?>
    <table class="admin-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Product</th>
          <th>Unit Price</th>
          <th>Qty Sold</th>
          <th>Revenue</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($topProducts as $i => $p): ?>
        <tr>
          <td style="font-weight:800;color:var(--text-muted)"><?= $i + 1 ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:10px">
              <?php if ($p['cover_image']): ?>
              <img src="/image/<?= e($p['cover_image']) ?>"
                   style="width:40px;height:40px;object-fit:cover;border-radius:var(--r-sm);flex-shrink:0">
              <?php else: ?>
              <div style="width:40px;height:40px;background:var(--cream);border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center">🎁</div>
              <?php endif; ?>
              <strong><?= e($p['name']) ?></strong>
            </div>
          </td>
          <td>RM <?= number_format($p['price'], 2) ?></td>
          <td>
            <span class="tag tag-pink"><?= $p['total_qty'] ?> units</span>
          </td>
          <td><strong style="color:var(--pink-600)">RM <?= number_format($p['total_revenue'], 2) ?></strong></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

</main>
</div>

<?php require ROOT . '/system/footer.php'; ?>