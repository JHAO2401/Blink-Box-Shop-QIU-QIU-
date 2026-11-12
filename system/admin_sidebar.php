<?php
$activePage = $activePage ?? '';
?>
<!-- admin checklist sidebar -->
<aside class="admin-sidebar">

  <div class="sidebar-section">
    <span class="sidebar-label">Main</span>
    <a href="/admin_manage/dashboard.php"
       class="sidebar-link <?= $activePage === 'dashboard' ? 'active' : '' ?>">
      <i class="fa-solid fa-gauge"></i> Dashboard
    </a>
    <a href="/admin_manage/report.php"
       class="sidebar-link <?= $activePage === 'report' ? 'active' : '' ?>">
      <i class="fa-solid fa-chart-bar"></i> Report
    </a>
  </div>

<!-- admin management sidebar -->
  <hr class="sidebar-divider">

  <div class="sidebar-section">
    <span class="sidebar-label">Manage</span>
    <a href="/admin_manage/product_manage.php"
       class="sidebar-link <?= $activePage === 'products' ? 'active' : '' ?>">
      <i class="fa-solid fa-box-open"></i> Products
    </a>
    <a href="/admin_manage/order_manage.php"
       class="sidebar-link <?= $activePage === 'orders' ? 'active' : '' ?>">
      <i class="fa-solid fa-receipt"></i> Orders
    </a>
    <a href="/admin_manage/member_manage.php"
       class="sidebar-link <?= $activePage === 'members' ? 'active' : '' ?>">
      <i class="fa-solid fa-users"></i> Members
    </a>
  </div>

</aside>