<?php
// find root directory  to access database and functions
define('ROOT', dirname(__DIR__));
require_once ROOT . '/function/user_fn.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? '/admin_manage/dashboard.php' : '/store/store.php'));
    exit;
}

$activeTab     = $_GET['tab'] ?? 'loginPanel';
$loginError    = '';
$registerError = '';
$registerOk    = '';
$adminError    = '';
$attemptsUsed  = 0;

// ── Handle POST ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action']     ?? '';
    $activeTab = $_POST['active_tab'] ?? 'loginPanel';

    // Customer login
    if ($action === 'login') {
        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password']      ?? '';
        $remember = !empty($_POST['remember']);

        if (!$email || !$password) {
            $loginError = 'Please fill in all fields.';
        } else {
            $res = loginCustomer($email, $password, $remember);
            if ($res['success']) {
                $redirect = $_GET['redirect'] ?? '/store/store.php';
                if (!$redirect || strpos($redirect, '/') === false) $redirect = '/store/store.php';
                header('Location: ' . $redirect);
                exit;
            }
            $loginError   = $res['message'];
            $attemptsUsed = is_numeric($res['attempts_left']) ? 5 - (int)$res['attempts_left'] : 0;
        }
    }

    // Register
    if ($action === 'register') {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password']      ?? '';
        $confirm  = $_POST['confirm']       ?? '';

        if (!$username || !$email || !$password || !$confirm) {
            $registerError = 'Please fill in all fields.';
        } elseif ($password !== $confirm) {
            $registerError = 'Passwords do not match.';
        } elseif (empty($_POST['accept_terms'])) {
            $registerError = 'Please accept the Terms of Service.';
        } else {
            $res = registerUser($username, $email, $password);
            if ($res['success']) {
                $registerOk = '🎉 ' . $res['message'] . ' Please sign in.';
                $activeTab  = 'loginPanel';
            } else {
                $registerError = $res['message'];
            }
        }
    }

    // Admin login
    if ($action === 'admin_login') {
        $username = trim($_POST['admin_username'] ?? '');
        $password = $_POST['password']             ?? '';

        if (!$username || !$password) {
            $adminError = 'Please fill in all fields.';
        } else {
            $res = loginAdmin($username, $password);
            if ($res['success']) {
                header('Location: /admin_manage/dashboard.php');
                exit;
            }
            $adminError = $res['message'];
        }
    }
}

$pageTitle = 'Sign In';
$pageCss   = ['css/login_register.css'];
require ROOT . '/system/header.php';
?>

<main class="lr-page">
  <span class="lr-deco">🎁</span>
  <span class="lr-deco">🌸</span>
  <span class="lr-deco">✨</span>
  <span class="lr-deco">🎀</span>
  <span class="lr-deco">🌷</span>

  <!-- Back button -->
  <a href="/index.php" class="lr-back">
    ← Back to Home
  </a>

  <div class="lr-card">

    <!-- Logo -->
    <div class="lr-logo">
      <img class="lr-logo-img" src="/image/logo.png" alt="Qiu Shop">
      <div class="lr-logo-text">QIU QIU Shop</div>
      <p class="lr-logo-sub">Your cutest blind box shop ✨</p>
    </div>

    <?php if ($registerOk): ?>
    <div class="alert alert-success"><?= e($registerOk) ?></div>
    <?php endif; ?>

    <!-- 3 Tabs -->
    <div class="lr-tabs">
      <button class="lr-tab <?= $activeTab === 'loginPanel'    ? 'active' : '' ?>"
              data-tab="loginPanel"    type="button">Sign In</button>
      <button class="lr-tab <?= $activeTab === 'registerPanel' ? 'active' : '' ?>"
              data-tab="registerPanel" type="button">Register</button>
      <button class="lr-tab admin-tab <?= $activeTab === 'adminPanel' ? 'active' : '' ?>"
              data-tab="adminPanel"    type="button">⚙️ Admin</button>
    </div>

    <!-- ═══════════ TAB 1: CUSTOMER LOGIN ═══════════ -->
    <div id="loginPanel" class="lr-panel <?= $activeTab === 'loginPanel' ? 'active' : '' ?>">

      <?php if ($loginError): ?>
      <div class="alert alert-error"><?= e($loginError) ?></div>
      <?php endif; ?>

      <form method="POST" action="/system/login_register.php<?= !empty($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>" novalidate>
        <input type="hidden" name="action"     value="login">
        <input type="hidden" name="active_tab" value="loginPanel">

        <div class="form-group">
          <label class="form-label" for="loginEmail">Email Address</label>
          <input type="email" id="loginEmail" name="email" class="form-control"
                 placeholder="hello@example.com" required autocomplete="email"
                 value="<?= e($_POST['email'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label class="form-label" for="loginPassword">Password</label>
          <div class="input-wrap">
            <input type="password" id="loginPassword" name="password" class="form-control"
                   placeholder="Your password" required autocomplete="current-password">
            <button type="button" class="input-toggle" tabindex="-1">👁️</button>
          </div>
        </div>

        <div class="d-flex align-center justify-between mb-3">
          <label class="form-check">
            <input type="checkbox" name="remember" value="1" <?= !empty($_POST['remember']) ? 'checked' : '' ?>>
            Remember me (30 days)
          </label>
          <a href="#" style="font-size:13px;font-weight:800">Forgot password?</a>
        </div>

        <?php if ($attemptsUsed > 0): ?>
        <div style="margin-bottom:14px">
          <p style="font-size:12px;color:var(--text-muted);font-weight:700;margin-bottom:6px">
            Failed attempts (<?= $attemptsUsed ?>/5):
          </p>
          <div class="attempt-dots">
            <?php for ($i = 0; $i < 5; $i++): ?>
            <div class="attempt-dot <?= $i < $attemptsUsed ? 'used' : '' ?>"></div>
            <?php endfor; ?>
          </div>
        </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary btn-full btn-lg">Sign In 💕</button>
      </form>

      <div class="lr-switch">
        Don't have an account?
        <a href="#" class="lr-switch-link" data-tab="registerPanel">Create one!</a>
      </div>
    </div>

    <!-- ═══════════ TAB 2: REGISTER ═══════════ -->
    <div id="registerPanel" class="lr-panel <?= $activeTab === 'registerPanel' ? 'active' : '' ?>">

      <?php if ($registerError): ?>
      <div class="alert alert-error"><?= e($registerError) ?></div>
      <?php endif; ?>

      <form method="POST" action="/system/login_register.php" novalidate>
        <input type="hidden" name="action"     value="register">
        <input type="hidden" name="active_tab" value="registerPanel">

        <div class="form-group">
          <label class="form-label" for="regUsername">Username</label>
          <input type="text" id="regUsername" name="username" class="form-control"
                 placeholder="qiuqiu123" required minlength="3" maxlength="50"
                 autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>">
          <span class="form-hint">3–50 characters</span>
        </div>

        <div class="form-group">
          <label class="form-label" for="regEmail">Email Address</label>
          <input type="email" id="regEmail" name="email" class="form-control"
                 placeholder="qiuqiu@example.com" required autocomplete="email"
                 value="<?= e($_POST['email'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label class="form-label" for="regPassword">Password</label>
          <div class="input-wrap">
            <input type="password" id="regPassword" name="password" class="form-control"
                   placeholder="Min 8 chars, 1 uppercase, 1 number (example: Qiu#123)"
                   required autocomplete="new-password">
            <button type="button" class="input-toggle" tabindex="-1">👁️</button>
          </div>
          <div class="pw-strength-bar"><div class="pw-strength-fill"></div></div>
          <span class="pw-strength-label"></span>
        </div>

        <div class="form-group">
          <label class="form-label" for="regConfirm">Confirm Password</label>
          <div class="input-wrap">
            <input type="password" id="regConfirm" name="confirm" class="form-control"
                   placeholder="Repeat your password" required autocomplete="new-password">
            <button type="button" class="input-toggle" tabindex="-1">👁️</button>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-check">
            <input type="checkbox" name="accept_terms" value="1"
                   <?= !empty($_POST['accept_terms']) ? 'checked' : '' ?>>
            I agree to the <a href="#">Terms</a> &amp; <a href="#">Privacy Policy</a>
          </label>
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg">Create My Account 🎀</button>
      </form>

      <div class="lr-switch">
        Already have an account?
        <a href="#" class="lr-switch-link" data-tab="loginPanel">Sign in!</a>
      </div>
    </div>

    <!-- ═══════════ TAB 3: ADMIN LOGIN ═══════════ -->
    <div id="adminPanel" class="lr-panel <?= $activeTab === 'adminPanel' ? 'active' : '' ?>">

      <div style="text-align:center;margin-bottom:18px">
        <span style="font-size:2rem">⚙️</span>
        <p style="font-size:13px;color:var(--text-muted);font-weight:700;margin-top:4px">Admin access only</p>
      </div>

      <?php if ($adminError): ?>
      <div class="alert alert-error"><?= e($adminError) ?></div>
      <?php endif; ?>

      <form method="POST" action="/system/login_register.php?tab=admin" novalidate>
        <input type="hidden" name="action"     value="admin_login">
        <input type="hidden" name="active_tab" value="adminPanel">

        <div class="form-group">
          <label class="form-label" for="adminUsername">Admin Username</label>
          <input type="text" id="adminUsername" name="admin_username" class="form-control"
                 placeholder="Enter your admin username"
                 required autocomplete="username"
                 value="<?= e($_POST['admin_username'] ?? '') ?>">
          <span class="form-hint">Your username in the database (e.g. <strong>admin</strong>)</span>
        </div>

        <div class="form-group">
          <label class="form-label" for="adminPassword">Password</label>
          <div class="input-wrap">
            <input type="password" id="adminPassword" name="password" class="form-control"
                   placeholder="Admin password" required autocomplete="current-password">
            <button type="button" class="input-toggle" tabindex="-1">👁️</button>
          </div>
        </div>

        <button type="submit" class="btn btn-full btn-lg"
                style="background:var(--lav-dark);color:white;border-color:var(--lav-dark)">
          Sign In as Admin ⚙️
        </button>
      </form>

      <div class="lr-switch">
        <a href="#" class="lr-switch-link" data-tab="loginPanel"
           style="color:var(--lav-dark)">← Back to customer login</a>
      </div>
    </div>

  </div><!-- /.lr-card -->
</main>

<?php
$pageJs = ['js/login_register.js'];
require ROOT . '/system/footer.php';
?>