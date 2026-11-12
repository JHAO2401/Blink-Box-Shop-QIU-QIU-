<?php
// find root directory  to access database and functions
define('ROOT', dirname(__DIR__));
require_once ROOT . '/function/user_fn.php';
require_once ROOT . '/function/product_fn.php';
requireAdmin();

$msg      = '';
$msgType  = 'success';
$editProduct = null;
$editImages  = [];

// Handle product edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Save (add or edit)
    if ($action === 'save') {
        $data = [
            'name'                => trim($_POST['name']        ?? ''),
            'description'         => trim($_POST['description'] ?? ''),
            'price'               => (float)($_POST['price']    ?? 0),
            'category_id'         => (int)($_POST['category_id']         ?? 0),
            'stock'               => (int)($_POST['stock']               ?? 0),
            'low_stock_threshold' => (int)($_POST['low_stock_threshold']  ?? 5),
            'discount'            => max(0, min(100, (float)($_POST['discount'] ?? 0))),
            'is_blind_box'        => 0,
            'is_active'           => isset($_POST['is_active'])   ? 1 : 0,
            'is_featured'         => 0,
            'is_top_sale'         => isset($_POST['is_top_sale']) ? 1 : 0,
        ];

        if (!$data['name'] || !$data['price'] || !$data['category_id']) {
            $msg = 'Please fill in name, price and category.'; 
            $msgType = 'error';
        } else {
            $editId = (int)($_POST['product_id'] ?? 0);
            if ($editId) {
                updateProduct($editId, $data);
                $productId = $editId;
                $msg = 'Product updated!';
            } else {
                $productId = addProduct($data);
                $msg = 'Product added!';
            }
            // Upload images
            if (!empty($_FILES['images']['name'][0])) {
                $existing = count(getProductImages($productId));
                foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {
                    if (!$tmp) continue;
                    uploadProductImage($productId, [
                        'name'     => $_FILES['images']['name'][$i],
                        'tmp_name' => $tmp,
                        'size'     => $_FILES['images']['size'][$i],
                    ], $existing + $i);
                }
            }
            header('Location: product_manage.php?msg=' . urlencode($msg));
            exit;
        }
    }

    // Toggle active
    if ($action === 'toggle_active') {
        $pid  = (int)($_POST['product_id'] ?? 0);
        $prod = getProduct($pid);
        if ($prod) updateProduct($pid, array_merge($prod, ['is_active' => $prod['is_active'] ? 0 : 1, 'discount' => $prod['discount'] ?? 0, 'is_top_sale' => $prod['is_top_sale'] ?? 0]));
        header('Location: product_manage.php'); exit;
    }

    // Delete product
    if ($action === 'delete') {
        deleteProduct((int)($_POST['product_id'] ?? 0));
        header('Location: product_manage.php?msg=' . urlencode('Product deleted.')); exit;
    }
}

// Delete image via GET (avoids nested form issue)
if (!empty($_GET['del_img']) && !empty($_GET['pid'])) {
    deleteProductImage((int)$_GET['del_img']);
    header('Location: product_manage.php?edit=' . (int)$_GET['pid']); exit;
}

// Edit mode
if (!empty($_GET['edit'])) {
    $editProduct = getProduct((int)$_GET['edit']);
    $editImages  = $editProduct ? getProductImages((int)$_GET['edit']) : [];
}

if (!empty($_GET['msg'])) $msg = $_GET['msg'];

$categories = getAllCategories();
$products   = getProducts([
    'search'   => $_GET['search'] ?? '',
    'category' => $_GET['cat']    ?? '',
]);

$pageTitle  = 'Product Management';
$pageCss    = ['css/admin.css'];
$activePage = 'products';
require ROOT . '/system/header.php';
?>

<!--product modal -->
<div id="productModal" class="pm-backdrop" style="display:none">
  <div class="pm-modal">
    <div class="pm-modal-header">
      <h3 id="modalTitle"><?= $editProduct ? 'Edit: ' . e($editProduct['name']) : 'Add New Product' ?></h3>
      <button type="button" class="pm-close" id="closeModal">✕</button>
    </div>
    <div class="pm-modal-body">

      <!-- Main product form -->
      <form method="POST" enctype="multipart/form-data" id="productForm">
        <input type="hidden" name="action"     value="save">
        <input type="hidden" name="product_id" value="<?= $editProduct['id'] ?? 0 ?>">

        <div class="pm-grid">
          <div>
            <div class="form-group">
              <label class="form-label">Product Name *</label>
              <input type="text" name="name" class="form-control" required
                     value="<?= e($editProduct['name'] ?? '') ?>" placeholder="example: QIU QIU">
            </div>
            <div class="form-group">
              <label class="form-label">Category *</label>
              <select name="category_id" class="form-control" required>
                <option value="">Select category</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"
                  <?= ($editProduct['category_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
                  <?= e($cat['icon']) ?> <?= e($cat['name']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Price (RM) *</label>
              <input type="number" name="price" id="priceInput" class="form-control"
                     step="0.01" min="0" required value="<?= $editProduct['price'] ?? '' ?>" placeholder="0.00">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
              <div class="form-group">
                <label class="form-label">Stock</label>
                <input type="number" name="stock" class="form-control" min="0"
                       value="<?= $editProduct['stock'] ?? 0 ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Low Stock Alert</label>
                <input type="number" name="low_stock_threshold" class="form-control" min="1"
                       value="<?= $editProduct['low_stock_threshold'] ?? 5 ?>">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Discount (%)</label>
              <div class="input-wrap">
                <input type="number" name="discount" id="discountInput" class="form-control"
                       min="0" max="100" step="0.01" placeholder="0 = no discount"
                       value="<?= $editProduct['discount'] ?? 0 ?>">
                <span style="position:absolute;right:14px;top:50%;transform:translateY(-50%);font-size:13px;font-weight:800;color:var(--text-muted)">%</span>
              </div>
              <p class="form-hint" id="discountPreview" style="color:var(--pink-500);font-weight:700;margin-top:6px"></p>
            </div>
          </div>

          <div>
            <div class="form-group">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="5"
                        placeholder="Product description..."><?= e($editProduct['description'] ?? '') ?></textarea>
            </div>
            <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:18px">
              <label class="form-check">
                <input type="checkbox" name="is_top_sale" value="1"
                       <?= !empty($editProduct['is_top_sale']) ? 'checked' : '' ?>>
                🔥 Top Sale
              </label>
              <label class="form-check">
                <input type="checkbox" name="is_active" value="1"
                       <?= ($editProduct['is_active'] ?? 1) ? 'checked' : '' ?>>
                Active (visible to customers)
              </label>
            </div>
            <div class="form-group">
              <label class="form-label">Product Images <span style="font-weight:400;color:var(--text-muted)">(first = cover)</span></label>
              <input type="file" name="images[]" class="form-control" multiple accept="image/*">
              <span class="form-hint">Max 3MB · JPG PNG GIF WEBP · Select multiple</span>
            </div>

            <!-- put current images -->
            <?php if ($editImages): ?>
            <div style="margin-top:12px" class="current-images-section">
              <p class="form-label">Current Images</p>
              <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:8px">
                <?php foreach ($editImages as $img): ?>
                <div style="position:relative;width:80px">
                  <img src="/image/<?= e($img['image_path']) ?>"
                       style="width:80px;height:80px;object-fit:cover;border-radius:var(--r-md);border:1.5px solid var(--cream-border)">
                  <!-- DELETE the image-->
                  <a href="product_manage.php?del_img=<?= $img['id'] ?>&pid=<?= $editProduct['id'] ?>"
                     onclick="return confirm('Delete this image?')"
                     style="position:absolute;top:3px;right:3px;width:20px;height:20px;border-radius:50%;
                            background:#ef4444;color:white;font-size:10px;line-height:20px;
                            text-align:center;text-decoration:none;display:block;font-weight:900">✕</a>
                  <?php if ($img['sort_order'] == 0): ?>
                  <span style="position:absolute;bottom:3px;left:3px;background:var(--pink-400);color:white;font-size:9px;font-weight:800;padding:1px 5px;border-radius:3px">Cover</span>
                  <?php endif; ?>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div><!-- /.pm-grid -->

        <div class="pm-modal-footer">
          <button type="button" class="btn btn-ghost" id="cancelModal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <?= $editProduct ? 'Save Changes' : 'Add Product' ?>
          </button>
        </div>
      </form>

    </div>
  </div>
</div>

<!--MAIN PAGE -->
<div class="admin-layout">
<?php require ROOT . '/system/admin_sidebar.php'; ?>
<main class="admin-main">

  <div class="admin-page-header">
    <div>
      <h1>Product Management</h1>
      <p class="text-muted"><?= count($products) ?> products</p>
    </div>
    <button class="btn btn-primary btn-sm" id="openAddModal">+ Add Product</button>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msgType === 'error' ? 'error' : 'success' ?> mb-3"><?= e($msg) ?></div>
  <?php endif; ?>

  <!-- Filters -->
  <form method="GET" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap">
    <input type="text" name="search" class="form-control"
           placeholder="Search products…" value="<?= e($_GET['search'] ?? '') ?>"
           style="flex:1;min-width:160px">
    <select name="cat" class="form-control" style="width:160px">
      <option value="">All categories</option>
      <?php foreach ($categories as $cat): ?>
      <option value="<?= $cat['id'] ?>" <?= ($_GET['cat'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
        <?= e($cat['icon']) ?> <?= e($cat['name']) ?>
      </option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <?php if (!empty($_GET['search']) || !empty($_GET['cat'])): ?>
    <a href="/admin_manage/product_manage.php" class="btn btn-ghost btn-sm">Clear</a>
    <?php endif; ?>
  </form>

  <!-- Product list -->
  <div class="card">
    <?php if (empty($products)): ?>
    <div class="empty-state">No products yet. Click <strong>+ Add Product</strong> to get started.</div>
    <?php else: ?>
    <div class="table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($products as $p): ?>
          <tr>
            <td>
              <?php if ($p['cover_image']): ?>
              <img src="/image/<?= e($p['cover_image']) ?>"
                   style="width:48px;height:48px;object-fit:cover;border-radius:var(--r-sm);border:1.5px solid var(--cream-border)">
              <?php else: ?>
              <div style="width:48px;height:48px;background:var(--cream);border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center;font-size:1.4rem">🎁</div>
              <?php endif; ?>
            </td>
            <td>
              <strong><?= e($p['name']) ?></strong>
              <?php if ($p['is_top_sale']): ?>
              <span class="tag tag-red" style="margin-left:6px">🔥</span>
              <?php endif; ?>
            </td>
            <td><?= e($p['category_name']) ?></td>
            <td>
              <?php if ($p['discount'] > 0): ?>
              <span style="text-decoration:line-through;color:var(--text-muted);font-size:12px">RM <?= number_format($p['price'], 2) ?></span><br>
              <span style="color:var(--pink-600);font-weight:800">RM <?= number_format($p['price'] * (1 - $p['discount']/100), 2) ?></span>
              <span class="tag tag-red" style="margin-left:4px"><?= $p['discount'] ?>% off</span>
              <?php else: ?>
              RM <?= number_format($p['price'], 2) ?>
              <?php endif; ?>
            </td>
            <td>
              <?php $sc = $p['stock'] == 0 ? 'tag-red' : ($p['stock'] <= $p['low_stock_threshold'] ? 'tag-orange' : 'tag-green'); ?>
              <span class="tag <?= $sc ?>"><?= $p['stock'] == 0 ? 'Out of stock' : $p['stock'] . ($p['stock'] <= $p['low_stock_threshold'] ? ' ⚠️' : '') ?></span>
            </td>
            <td>
              <span class="tag <?= $p['is_active'] ? 'tag-green' : 'tag-red' ?>">
                <?= $p['is_active'] ? 'Active' : 'Hidden' ?>
              </span>
            </td>
            <td>
              <div style="display:flex;gap:6px;flex-wrap:wrap">
                <a href="/admin_manage/product_manage.php?edit=<?= $p['id'] ?>"
                   class="btn btn-ghost btn-sm">Edit</a>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="action"     value="toggle_active">
                  <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                  <button type="submit" class="btn btn-ghost btn-sm">
                    <?= $p['is_active'] ? 'Hide' : 'Show' ?>
                  </button>
                </form>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="action"     value="delete">
                  <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm"
                          onclick="return confirm('Delete <?= addslashes(e($p['name'])) ?>? This cannot be undone.')">
                    Delete
                  </button>
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

<script>
$(function () {
  // Open modal for Add — reset all fields
  $('#openAddModal').on('click', function () {
    $('#modalTitle').text('Add New Product');
    $('#productForm')[0].reset();
    $('input[name="product_id"]').val('0');
    $('input[name="is_active"]').prop('checked', true); // default active
    $('input[name="is_top_sale"]').prop('checked', false);
    $('#discountPreview').text('');
    $('.current-images-section').remove();
    openModal();
  });

  // Open modal for Edit
  <?php if ($editProduct): ?>
  openModal();
  <?php endif; ?>

  // Close modal
  $('#closeModal, #cancelModal').on('click', function () { closeModal(); });
  $('#productModal').on('click', function (e) {
    if ($(e.target).is('#productModal')) closeModal();
  });

  function openModal()  { $('#productModal').fadeIn(180); $('body').css('overflow','hidden'); }
  function closeModal() {
    $('#productModal').fadeOut(180);
    $('body').css('overflow','');
    // Remove ?edit from URL without reload
    if (window.location.search.indexOf('edit=') !== -1) {
      history.replaceState(null, '', 'product_manage.php');
    }
  }

  // Discount preview
  function updatePreview() {
    var price    = parseFloat($('#priceInput').val())    || 0;
    var discount = parseFloat($('#discountInput').val()) || 0;
    if (discount > 0 && price > 0) {
      var final = (price * (1 - discount / 100)).toFixed(2);
      $('#discountPreview').text('RM ' + price.toFixed(2) + ' → RM ' + final + ' after ' + discount + '% off');
    } else {
      $('#discountPreview').text('');
    }
  }
  $('#priceInput, #discountInput').on('input', updatePreview);
  updatePreview();
});
</script>

<?php require ROOT . '/system/footer.php'; ?>