<?php
// find root directory  to access database and functions
define('ROOT', dirname(__DIR__));
require_once ROOT . '/function/user_fn.php';
requireLogin();

$successMsg = '';
$errorMsg   = '';
$isAdm      = isAdmin();
$table      = $isAdm ? 'admin' : 'users';

//  Handle request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Avatar upload
    if ($action === 'upload_avatar') {
        $uploadDir = ROOT . '/image/' . ($isAdm ? 'admin/' : 'profile/');
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        if (!empty($_FILES['avatar']['tmp_name'])) {
            $file     = $_FILES['avatar'];
            $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed  = ['jpg','jpeg','png','gif','webp'];

            if (!in_array($ext, $allowed)) {
                $errorMsg = 'Only JPG, PNG, GIF, WEBP allowed.';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $errorMsg = 'File too large. Max 2MB.';
            } else {
                $filename = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                    $pdo->prepare("UPDATE $table SET profile_pic = :p WHERE id = :id")
                        ->execute([':p' => $filename, ':id' => $_SESSION['user_id']]);
                    $successMsg = 'Avatar updated!';
                } else {
                    $errorMsg = 'Upload failed. Check folder permissions.';
                }
            }
        }
    }

    // Change username
    if ($action === 'update_username') {
        $newUsername = trim($_POST['username'] ?? '');
        if (strlen($newUsername) < 3 || strlen($newUsername) > 50) {
            $errorMsg = 'Username must be 3–50 characters.';
        } else {
            $s = $pdo->prepare("SELECT id FROM $table WHERE username = :u AND id != :id");
            $s->execute([':u' => $newUsername, ':id' => $_SESSION['user_id']]);
            if ($s->fetch()) {
                $errorMsg = 'Username already taken.';
            } else {
                $pdo->prepare("UPDATE $table SET username = :u WHERE id = :id")
                    ->execute([':u' => $newUsername, ':id' => $_SESSION['user_id']]);
                $_SESSION['username'] = $newUsername;
                $successMsg = 'Username updated!';
            }
        }
    }

    // Change password
    if ($action === 'update_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $s = $pdo->prepare("SELECT password FROM $table WHERE id = :id");
        $s->execute([':id' => $_SESSION['user_id']]);
        $row = $s->fetch();

        if (!password_verify($current, $row['password'])) {
            $errorMsg = 'Current password is wrong.';
        } elseif (strlen($new) < 6) {
            $errorMsg = 'New password must be at least 6 characters.';
        } elseif ($new !== $confirm) {
            $errorMsg = 'New passwords do not match.';
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare("UPDATE $table SET password = :p WHERE id = :id")
                ->execute([':p' => $hash, ':id' => $_SESSION['user_id']]);
            $successMsg = 'Password updated!';
        }
    }

    // Customer: change email
    if ($action === 'update_email' && !$isAdm) {
        $newEmail = trim(strtolower($_POST['email'] ?? ''));
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = 'Invalid email address.';
        } else {
            $s = $pdo->prepare("SELECT id FROM users WHERE email = :e AND id != :id");
            $s->execute([':e' => $newEmail, ':id' => $_SESSION['user_id']]);
            if ($s->fetch()) {
                $errorMsg = 'Email already taken.';
            } else {
                $pdo->prepare("UPDATE users SET email = :e WHERE id = :id")
                    ->execute([':e' => $newEmail, ':id' => $_SESSION['user_id']]);
                $_SESSION['email'] = $newEmail;
                $successMsg = 'Email updated!';
            }
        }
    }
}

// Fetch profile
if ($isAdm) {
    $s = $pdo->prepare("SELECT id, username, profile_pic, created_at FROM admin WHERE id = :id");
} else {
    $s = $pdo->prepare("SELECT id, username, email, profile_pic, created_at FROM users WHERE id = :id");
}
$s->execute([':id' => $_SESSION['user_id']]);
$profile = $s->fetch();

$avatarPath = '';
if (!empty($profile['profile_pic'])) {
    $subfolder  = $isAdm ? 'admin/' : 'profile/';
    $avatarPath = '/image/' . $subfolder . $profile['profile_pic'];
}

$pageTitle  = 'My Profile';
$pageCss    = ['css/admin.css'];
$activePage = 'profile';
require ROOT . '/system/header.php';
?>

<?php if ($isAdm): ?>
<main class="admin-wrap"><div class="container">
<?php else: ?>
<main class="admin-wrap"><div class="container">
<?php endif; ?>

  <div class="admin-page-header">
    <div>
      <h1><?= $isAdm ? 'Admin Profile' : 'My Profile' ?></h1>
      <p class="text-muted">Manage your account</p>
    </div>
  </div>

  <?php if ($successMsg): ?>
  <div class="alert alert-success mb-3"><?= e($successMsg) ?></div>
  <?php endif; ?>
  <?php if ($errorMsg): ?>
  <div class="alert alert-error mb-3"><?= e($errorMsg) ?></div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:220px 1fr;gap:28px;max-width:860px;align-items:start">

    <!--  Avatar upload  -->
    <div class="card" style="padding:28px;text-align:center">
      <p style="font-size:13px;font-weight:800;color:var(--text-muted);margin-bottom:16px;text-transform:uppercase;letter-spacing:.5px">Avatar</p>

      <form method="POST" enctype="multipart/form-data" id="avatarForm">
        <input type="hidden" name="action" value="upload_avatar">
        <input type="file" name="avatar" id="avatarInput" accept="image/*" style="display:none">

        <!-- Drag & drop avatar image -->
        <div class="avatar-upload-zone" id="avatarZone">
          <?php if ($avatarPath): ?>
          <img src="<?= e($avatarPath) ?>" alt="avatar" id="avatarPreview">
          <div class="avatar-overlay"><span>Change</span></div>
          <?php else: ?>
          <div class="avatar-placeholder" id="avatarPlaceholder">👤</div>
          <?php endif; ?>
        </div>

        <p class="avatar-hint">Drag & drop or click to upload</p>
        <p style="font-size:11px;color:var(--gray-400);margin-bottom:16px">JPG, PNG, GIF · Max 2MB</p>

        <button type="submit" class="btn btn-primary btn-full btn-sm" id="avatarSubmit"
                style="display:none">Save Avatar</button>
      </form>

      <p style="margin-top:16px;font-size:13px;font-weight:800;color:var(--text)">
        <?= e($profile['username']) ?>
      </p>
      <p style="font-size:12px;color:var(--text-muted)">
        <?= $isAdm ? 'Administrator' : 'Member' ?>
      </p>
    </div>

    <!-- Profile info & settings  -->
    <div style="display:flex;flex-direction:column;gap:20px">

      <!-- Change Username -->
      <div class="card" style="padding:24px">
        <h3 style="margin-bottom:18px;font-size:1rem">Change Username</h3>
        <form method="POST">
          <input type="hidden" name="action" value="update_username">
          <div class="form-group">
            <label class="form-label">Current</label>
            <input type="text" class="form-control" value="<?= e($profile['username']) ?>" disabled
                   style="background:var(--gray-100);color:var(--text-muted)">
          </div>
          <div class="form-group" style="margin-bottom:0">
            <label class="form-label">New Username</label>
            <div style="display:flex;gap:10px">
              <input type="text" name="username" class="form-control"
                     placeholder="New username" required minlength="3" maxlength="50">
              <button type="submit" class="btn btn-primary btn-sm" style="white-space:nowrap">Update</button>
            </div>
          </div>
        </form>
      </div>

      <!-- Change Password -->
      <div class="card" style="padding:24px">
        <h3 style="margin-bottom:18px;font-size:1rem">Change Password</h3>
        <form method="POST">
          <input type="hidden" name="action" value="update_password">
          <div class="form-group">
            <label class="form-label">Current Password</label>
            <div class="input-wrap">
              <input type="password" name="current_password" class="form-control"
                     placeholder="Current password" required>
              <button type="button" class="input-toggle" tabindex="-1">👁️</button>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">New Password</label>
            <div class="input-wrap">
              <input type="password" name="new_password" class="form-control"
                     placeholder="Min 6 characters" required minlength="6">
              <button type="button" class="input-toggle" tabindex="-1">👁️</button>
            </div>
          </div>
          <div class="form-group" style="margin-bottom:0">
            <label class="form-label">Confirm New Password</label>
            <div class="input-wrap">
              <input type="password" name="confirm_password" class="form-control"
                     placeholder="Repeat new password" required>
              <button type="button" class="input-toggle" tabindex="-1">👁️</button>
            </div>
          </div>
          <button type="submit" class="btn btn-primary mt-3">Update Password</button>
        </form>
      </div>

      <?php if (!$isAdm): ?>
      <!-- Customer: Change Email -->
      <div class="card" style="padding:24px">
        <h3 style="margin-bottom:18px;font-size:1rem">Change Email</h3>
        <form method="POST">
          <input type="hidden" name="action" value="update_email">
          <div class="form-group">
            <label class="form-label">Current</label>
            <input type="email" class="form-control" value="<?= e($profile['email'] ?? '') ?>" disabled
                   style="background:var(--gray-100);color:var(--text-muted)">
          </div>
          <div class="form-group" style="margin-bottom:0">
            <label class="form-label">New Email</label>
            <div style="display:flex;gap:10px">
              <input type="email" name="email" class="form-control" placeholder="New email" required>
              <button type="submit" class="btn btn-primary btn-sm" style="white-space:nowrap">Update</button>
            </div>
          </div>
        </form>
      </div>
      <?php endif; ?>

      <!-- Info -->
      <div style="padding:12px 16px;background:var(--cream);border-radius:var(--r-md);font-size:13px;color:var(--text-muted)">
        ID: <strong>#<?= $profile['id'] ?></strong>
        &nbsp;·&nbsp;
        Joined: <strong><?= date('d M Y', strtotime($profile['created_at'])) ?></strong>
      </div>

    </div>
  </div>

<?php if ($isAdm): ?>
</div></main>
<?php else: ?>
</div></main>
<?php endif; ?>

<script>
$(function () {
  var $zone    = $('#avatarZone');
  var $input   = $('#avatarInput');
  var $submit  = $('#avatarSubmit');
  var $preview = $('#avatarPreview');

  // click to select file
  $zone.on('click', function () { $input.trigger('click'); });

  // file selected
  $input.on('change', function () {
    if (this.files && this.files[0]) showPreview(this.files[0]);
  });

  // Drag & drop
  $zone.on('dragover dragenter', function (e) {
    e.preventDefault();
    $zone.addClass('drag-over');
  });
  $zone.on('dragleave drop', function (e) {
    e.preventDefault();
    $zone.removeClass('drag-over');
    if (e.type === 'drop') {
      var file = e.originalEvent.dataTransfer.files[0];
      if (file) {
        // Put file into input
        var dt = new DataTransfer();
        dt.items.add(file);
        $input[0].files = dt.files;
        showPreview(file);
      }
    }
  });

  // show preview
  function showPreview(file) {
    var reader = new FileReader();
    reader.onload = function (e) {
      if ($preview.length) {
        $preview.attr('src', e.target.result).show();
      } else {
        $zone.prepend('<img id="avatarPreview" src="' + e.target.result + '" alt="preview" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;border-radius:50%">');
      }
      $('#avatarPlaceholder').hide();
      $zone.append('<div class="avatar-overlay"><span>Change</span></div>');
      $submit.show();
    };
    reader.readAsDataURL(file);
  }
});
</script>

<?php
$pageJs = ['js/index.js'];
require ROOT . '/system/footer.php';
?>