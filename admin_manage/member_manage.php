<?php
// find root directory  to access database and functions
define('ROOT', dirname(__DIR__));
require_once ROOT . '/function/user_fn.php';
require_once ROOT . '/function/member_fn.php';
require_once ROOT . '/function/order_fn.php';
requireAdmin();

$msg = '';

//handle account lock and unlock
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $uid    = (int)($_POST['user_id'] ?? 0);

    if ($action === 'lock')   { setMemberLock($uid, 1); $msg = 'Account locked.'; }
    if ($action === 'unlock') { setMemberLock($uid, 0); $msg = 'Account unlocked.'; }

    header('Location: member_manage.php?msg=' . urlencode($msg));
    exit;
}

if (!empty($_GET['msg'])) $msg = $_GET['msg'];

// member detail
if (!empty($_GET['id'])) {
    $member = getMemberDetail((int)$_GET['id']);
    $pageTitle  = 'Member: ' . ($member['username'] ?? '');
    $pageCss    = ['css/admin.css'];
    $activePage = 'members';
    require ROOT . '/system/header.php';
?>
<div class="admin-layout">
<?php require ROOT . '/system/admin_sidebar.php'; ?>
<main class="admin-main">

  <div class="admin-page-header">
    <div>
      <h1>Member: <?= e($member['username']) ?></h1>
      <p class="text-muted">Joined <?= date('d M Y', strtotime($member['created_at'])) ?></p>
    </div>
    <a href="/admin_manage/member_manage.php" class="btn btn-ghost btn-sm">← All Members</a>
  </div>

  <div style="display:grid;grid-template-columns:280px 1fr;gap:24px">

    <!-- Member info -->
    <div class="card" style="padding:24px">
      <div style="text-align:center;margin-bottom:20px">
        <?php if (!empty($member['profile_pic'])): ?>
        <img src="/image/profile/<?= e($member['profile_pic']) ?>"
             style="width:80px;height:80px;border-radius:50%;object-fit:cover;margin:0 auto 10px">
        <?php else: ?>
        <div style="width:80px;height:80px;border-radius:50%;background:var(--pink-100);display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 10px">👤</div>
        <?php endif; ?>
        <h3 style="font-size:1rem"><?= e($member['username']) ?></h3>
        <p style="font-size:13px;color:var(--text-muted)"><?= e($member['email']) ?></p>
      </div>

      <div style="font-size:13px;line-height:2;border-top:1.5px solid var(--cream-border);padding-top:16px">
        <div style="display:flex;justify-content:space-between">
          <span style="color:var(--text-muted)">Status</span>
          <span class="tag <?= $member['is_locked'] ? 'tag-red' : 'tag-green' ?>">
            <?= $member['is_locked'] ? 'Locked' : 'Active' ?>
          </span>
        </div>
        <div style="display:flex;justify-content:space-between">
          <span style="color:var(--text-muted)">Orders</span>
          <strong><?= $member['order_count'] ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between">
          <span style="color:var(--text-muted)">Total Spent</span>
          <strong>RM <?= number_format($member['total_spent'], 2) ?></strong>
        </div>
      </div>

      <div style="margin-top:16px">
        <form method="POST">
          <input type="hidden" name="user_id" value="<?= $member['id'] ?>">
          <?php if ($member['is_locked']): ?>
          <input type="hidden" name="action" value="unlock">
          <button type="submit" class="btn btn-success btn-full btn-sm">Unlock Account</button>
          <?php else: ?>
          <input type="hidden" name="action" value="lock">
          <button type="submit" class="btn btn-danger btn-full btn-sm"
                  onclick="return confirm('Lock this account?')">Lock Account</button>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <!-- Recent orders -->
    <div class="card" style="padding:0">
      <div class="dashboard-card-header"><h3>Recent Orders</h3></div>
      <?php if (empty($member['orders'])): ?>
      <div class="empty-state">No orders yet.</div>
      <?php else: ?>
      <table class="admin-table">
        <thead><tr><th>#</th><th>Total</th><th>Status</th><th>Date</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($member['orders'] as $o): ?>
          <tr>
            <td>#<?= $o['id'] ?></td>
            <td>RM <?= number_format($o['total_amount'], 2) ?></td>
            <td><span class="tag <?= orderStatusClass($o['status']) ?>"><?= ucfirst($o['status']) ?></span></td>
            <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
            <td><a href="/admin_manage/order_manage.php?id=<?= $o['id'] ?>" class="btn btn-ghost btn-sm">View</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

  </div>

</main>
</div>
<?php
    require ROOT . '/system/footer.php';
    exit;
}

// member list
$search  = $_GET['search'] ?? '';
$members = getMembers($search);

$pageTitle  = 'Member Management';
$pageCss    = ['css/admin.css'];
$activePage = 'members';
require ROOT . '/system/header.php';
?>

<div class="admin-layout">
<?php require ROOT . '/system/admin_sidebar.php'; ?>
<main class="admin-main">
    <!-- member management list -->
  <div class="admin-page-header">
    <div>
      <h1>Member Management</h1>
      <p class="text-muted"><?= count($members) ?> members</p>
    </div>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-success mb-3"><?= e($msg) ?></div>
  <?php endif; ?>

  <form method="GET" style="display:flex;gap:10px;margin-bottom:20px">
    <input type="text" name="search" class="form-control"
           placeholder="Search by username or email…"
           value="<?= e($search) ?>" style="max-width:320px">
    <button type="submit" class="btn btn-primary btn-sm">Search</button>
    <?php if ($search): ?>
    <a href="/admin_manage/member_manage.php" class="btn btn-ghost btn-sm">Clear</a>
    <?php endif; ?>
  </form>

  <div class="card">
    <?php if (empty($members)): ?>
    <div class="empty-state">No members found.</div>
    <?php else: ?>
    <div class="table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Member</th>
            <th>Email</th>
            <th>Orders</th>
            <th>Total Spent</th>
            <th>Joined</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($members as $m): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <?php if (!empty($m['profile_pic'])): ?>
                <img src="/image/profile/<?= e($m['profile_pic']) ?>"
                     style="width:34px;height:34px;border-radius:50%;object-fit:cover">
                <?php else: ?>
                <div style="width:34px;height:34px;border-radius:50%;background:var(--pink-100);display:flex;align-items:center;justify-content:center;font-size:.9rem">👤</div>
                <?php endif; ?>
                <strong><?= e($m['username']) ?></strong>
              </div>
            </td>
            <td style="font-size:13px"><?= e($m['email']) ?></td>
            <td><?= $m['order_count'] ?></td>
            <td>RM <?= number_format($m['total_spent'], 2) ?></td>
            <td style="font-size:13px"><?= date('d M Y', strtotime($m['created_at'])) ?></td>
            <td>
              <span class="tag <?= $m['is_locked'] ? 'tag-red' : 'tag-green' ?>">
                <?= $m['is_locked'] ? 'Locked' : 'Active' ?>
              </span>
            </td>
            <td>
              <div style="display:flex;gap:6px">
                <a href="/admin_manage/member_manage.php?id=<?= $m['id'] ?>"
                   class="btn btn-ghost btn-sm">View</a>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="user_id" value="<?= $m['id'] ?>">
                  <?php if ($m['is_locked']): ?>
                  <input type="hidden" name="action" value="unlock">
                  <button type="submit" class="btn btn-success btn-sm">Unlock</button>
                  <?php else: ?>
                  <input type="hidden" name="action" value="lock">
                  <button type="submit" class="btn btn-danger btn-sm"
                          onclick="return confirm('Lock <?= e($m['username']) ?>?')">Lock</button>
                  <?php endif; ?>
                </form>
              </div>
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