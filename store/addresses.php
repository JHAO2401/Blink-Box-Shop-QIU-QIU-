<?php
// find root directory  to access database and functions
define('ROOT', dirname(__DIR__));
require_once ROOT . '/function/user_fn.php';
require_once ROOT . '/function/store_fn.php';
requireLogin();

$userId   = $_SESSION['user_id'];
$msg      = '';
$msgType  = 'success';
$editAddr = null;

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $data = [
            'label'          => trim($_POST['label']          ?? 'Home'),
            'recipient_name' => trim($_POST['recipient_name'] ?? ''),
            'phone'          => trim($_POST['phone']          ?? ''),
            'address_line'   => trim($_POST['address_line']   ?? ''),
            'city'           => trim($_POST['city']           ?? ''),
            'state'          => trim($_POST['state']          ?? ''),
            'postcode'       => trim($_POST['postcode']       ?? ''),
            'country'        => trim($_POST['country']        ?? 'Malaysia'),
        ];
        $editId = (int)($_POST['edit_id'] ?? 0);

        if (!$data['recipient_name'] || !$data['phone'] || !$data['address_line'] || !$data['city'] || !$data['postcode']) {
            $msg = 'Please fill in all required fields.'; $msgType = 'error';
            $editAddr = $data; $editAddr['id'] = $editId;
        } elseif (!preg_match('/^\d{5}$/', $data['postcode'])) {
            $msg = 'Postcode must be exactly 5 digits (exp 50000).'; $msgType = 'error';
            $editAddr = $data; $editAddr['id'] = $editId;
        } elseif (!validateMyPhone($data['phone'])) {
            $msg = 'Invalid phone. Use 011-XXXXXXXX (11 digits) or 01X-XXXXXXX (10 digits).'; $msgType = 'error';
            $editAddr = $data; $editAddr['id'] = $editId;
        } else {
            saveAddress($userId, $data, $editId);
            $redirect = $_GET['redirect'] ?? '';
            $msg = $editId ? 'Address updated!' : 'Address added!';
            if ($redirect === 'checkout') { header('Location: /store/checkout.php'); exit; }
            header('Location: /store/addresses.php?msg=' . urlencode($msg)); exit;
        }
    }

    if ($action === 'set_default') {
        setDefaultAddress((int)$_POST['address_id'], $userId);
        header('Location: /store/addresses.php'); exit;
    }

    if ($action === 'delete') {
        deleteAddress((int)$_POST['address_id'], $userId);
        header('Location: /store/addresses.php?msg=' . urlencode('Address deleted.')); exit;
    }
}

if (!empty($_GET['edit'])) {
    $editAddr = getAddress((int)$_GET['edit'], $userId);
}
if (!empty($_GET['msg'])) $msg = $_GET['msg'];

$addresses = getUserAddresses($userId);

$pageTitle = 'My Addresses';
$pageCss   = ['css/store.css'];
require ROOT . '/system/header.php';
?>

<main class="addresses-wrap">
  <div class="container" style="max-width:900px">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:28px">
      <h1>My Addresses</h1>
      <?php if (!$editAddr): ?>
      <a href="/store/addresses.php?add=1" class="btn btn-primary btn-sm">+ Add Address</a>
      <?php endif; ?>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?= $msgType === 'error' ? 'error' : 'success' ?> mb-3"><?= e($msg) ?></div>
    <?php endif; ?>

    <!-- Add/Edit form -->
    <?php if ($editAddr || !empty($_GET['add'])): ?>
    <div class="card mb-4" style="padding:28px">
      <h3 style="font-size:1rem;font-family:var(--font-body);font-weight:800;margin-bottom:20px">
        <?= $editAddr && !empty($editAddr['id']) ? 'Edit Address' : 'Add New Address' ?>
      </h3>
      <form method="POST">
        <input type="hidden" name="action"  value="save">
        <input type="hidden" name="edit_id" value="<?= $editAddr['id'] ?? 0 ?>">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <div class="form-group">
            <label class="form-label">Label</label>
            <select name="label" class="form-control">
              <?php foreach (['Home','Office','Other'] as $lbl): ?>
              <option value="<?= $lbl ?>" <?= ($editAddr['label'] ?? 'Home') === $lbl ? 'selected' : '' ?>><?= $lbl ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Recipient Name *</label>
            <input type="text" name="recipient_name" class="form-control" required
                   value="<?= e($editAddr['recipient_name'] ?? '') ?>" placeholder="Full name">
          </div>
          <div class="form-group">
            <label class="form-label">Phone *</label>
            <input type="text" name="phone" class="form-control" required
                   value="<?= e($editAddr['phone'] ?? '') ?>"
                   placeholder="example → 01x-XXXXXXXX or 011-XXXXXXXX"
                   maxlength="12">
            <span class="form-hint">012/016/017/019: 10 digits · 011: 11 digits</span>
          </div>
          <div class="form-group" style="grid-column:1/-1">
            <label class="form-label">Address Line *</label>
            <input type="text" name="address_line" class="form-control" required
                   value="<?= e($editAddr['address_line'] ?? '') ?>" placeholder="Street, unit, block...">
          </div>
          <div class="form-group">
            <label class="form-label">State *</label>
            <select name="state" id="stateSelect" class="form-control" required>
              <option value="">Select state</option>
              <?php
              $myStates = ['Johor','Kedah','Kelantan','Melaka','Negeri Sembilan','Pahang','Perak','Perlis','Pulau Pinang','Sabah','Sarawak','Selangor','Terengganu','Wilayah Persekutuan Kuala Lumpur','Wilayah Persekutuan Labuan','Wilayah Persekutuan Putrajaya'];
              $selState = $editAddr['state'] ?? '';
              foreach ($myStates as $st):
              ?>
              <option value="<?= e($st) ?>" <?= $selState === $st ? 'selected' : '' ?>><?= e($st) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">City *</label>
            <select name="city" id="citySelect" class="form-control" required>
              <?php if (!empty($editAddr['city'])): ?>
              <option value="<?= e($editAddr['city']) ?>" selected><?= e($editAddr['city']) ?></option>
              <?php else: ?>
              <option value="">Select state first</option>
              <?php endif; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Postcode *</label>
            <input type="text" name="postcode" class="form-control" required
                   value="<?= e($editAddr['postcode'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Country</label>
            <select name="country" class="form-control">
              <option value="Malaysia" selected>Malaysia</option>
            </select>
          </div>
        </div>

        <div style="display:flex;gap:10px;margin-top:8px">
          <button type="submit" class="btn btn-primary">Save Address</button>
          <a href="/store/addresses.php" class="btn btn-ghost">Cancel</a>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <!-- Address cards -->
    <?php if (empty($addresses) && !$editAddr && empty($_GET['add'])): ?>
    <div style="text-align:center;padding:60px 0;color:var(--text-muted)">
      <p style="font-size:3rem;margin-bottom:16px">📍</p>
      <p style="font-weight:700">No addresses saved yet.</p>
    </div>
    <?php else: ?>
    <div class="address-grid">
      <?php foreach ($addresses as $addr): ?>
      <div class="address-card <?= $addr['is_default'] ? 'is-default' : '' ?>">
        <?php if ($addr['is_default']): ?>
        <span class="tag tag-pink" style="position:absolute;top:14px;right:14px">Default</span>
        <?php endif; ?>
        <div class="address-card-label"><?= e($addr['label']) ?></div>
        <div class="address-card-name"><?= e($addr['recipient_name']) ?></div>
        <div class="address-card-text">
          <?= e(formatMyPhone($addr['phone'])) ?><br>
          <?= e($addr['address_line']) ?><br>
          <?= e($addr['city']) ?><?= $addr['state'] ? ', ' . e($addr['state']) : '' ?>,
          <?= e($addr['postcode']) ?><br>
          <?= e($addr['country']) ?>
        </div>
        <div class="address-card-actions">
          <a href="/store/addresses.php?edit=<?= $addr['id'] ?>" class="btn btn-ghost btn-sm">Edit</a>
          <?php if (!$addr['is_default']): ?>
          <form method="POST">
            <input type="hidden" name="action"     value="set_default">
            <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
            <button type="submit" class="btn btn-outline btn-sm">Set Default</button>
          </form>
          <form method="POST">
            <input type="hidden" name="action"     value="delete">
            <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm"
                    onclick="return confirm('Delete this address?')">Delete</button>
          </form>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>
</main>

<?php ?>
<script>
var _savedCity = <?= json_encode($editAddr['city'] ?? '') ?>;
$(function(){
  var s = $('#stateSelect').val();
  if (s) { updateCities(s); if (_savedCity) $('#citySelect').val(_savedCity); }
});
</script>
<?php
$pageJs = ['js/store.js'];
$_savedCity = '';
require ROOT . '/system/footer.php'; ?>