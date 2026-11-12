<?php
// find root directory  to access database and functions
if (!defined('ROOT')) define('ROOT', dirname(__DIR__));
require_once ROOT . '/system/system.php';

// Get all categories ──────────────────────────────────────
function getAllCategories(): array {
    global $pdo;
    return $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
}

// Get products (with filters)
function getProducts(array $filters = []): array {
    global $pdo;
    $where  = ['1=1'];
    $params = [];

    if (!empty($filters['search'])) {
        $where[]            = 'p.name LIKE :search';
        $params[':search']  = '%' . $filters['search'] . '%';
    }
    if (!empty($filters['category'])) {
        $where[]            = 'p.category_id = :cat';
        $params[':cat']     = $filters['category'];
    }
    if (isset($filters['is_active'])) {
        $where[]            = 'p.is_active = :active';
        $params[':active']  = $filters['is_active'];
    }

    $sql = "SELECT p.*, c.name AS category_name,
                   (SELECT image_path FROM product_images WHERE product_id = p.id AND sort_order = 0 LIMIT 1) AS cover_image
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY p.created_at DESC";

    $s = $pdo->prepare($sql);
    $s->execute($params);
    return $s->fetchAll();
}

// Get single product
function getProduct(int $id): ?array {
    global $pdo;
    $s = $pdo->prepare(
        "SELECT p.*, c.name AS category_name
         FROM products p
         JOIN categories c ON p.category_id = c.id
         WHERE p.id = :id"
    );
    $s->execute([':id' => $id]);
    return $s->fetch() ?: null;
}

// Get product images 
function getProductImages(int $productId): array {
    global $pdo;
    $s = $pdo->prepare(
        "SELECT * FROM product_images WHERE product_id = :id ORDER BY sort_order ASC"
    );
    $s->execute([':id' => $productId]);
    return $s->fetchAll();
}

//Add product 
function addProduct(array $data): int {
    global $pdo;
    $pdo->prepare(
        "INSERT INTO products (name, description, price, category_id, stock, low_stock_threshold,
                               discount, is_blind_box, is_active, is_featured, is_top_sale)
         VALUES (:name, :desc, :price, :cat, :stock, :threshold,
                 :discount, :blind, :active, :featured, :topsale)"
    )->execute([
        ':name'      => $data['name'],
        ':desc'      => $data['description'] ?? '',
        ':price'     => $data['price'],
        ':cat'       => $data['category_id'],
        ':stock'     => $data['stock'] ?? 0,
        ':threshold' => $data['low_stock_threshold'] ?? 5,
        ':discount'  => $data['discount'] ?? 0,
        ':blind'     => $data['is_blind_box']  ?? 0,
        ':active'    => $data['is_active']     ?? 1,
        ':featured'  => $data['is_featured']   ?? 0,
        ':topsale'   => $data['is_top_sale']   ?? 0,
    ]);
    return (int) $pdo->lastInsertId();
}

// Update product 
function updateProduct(int $id, array $data): void {
    global $pdo;
    $pdo->prepare(
        "UPDATE products SET name=:name, description=:desc, price=:price,
         category_id=:cat, stock=:stock, low_stock_threshold=:threshold,
         discount=:discount, is_blind_box=:blind, is_active=:active,
         is_featured=:featured, is_top_sale=:topsale
         WHERE id=:id"
    )->execute([
        ':name'      => $data['name'],
        ':desc'      => $data['description'] ?? '',
        ':price'     => $data['price'],
        ':cat'       => $data['category_id'],
        ':stock'     => $data['stock'] ?? 0,
        ':threshold' => $data['low_stock_threshold'] ?? 5,
        ':discount'  => $data['discount'] ?? 0,
        ':blind'     => $data['is_blind_box']  ?? 0,
        ':active'    => $data['is_active']     ?? 1,
        ':featured'  => $data['is_featured']   ?? 0,
        ':topsale'   => $data['is_top_sale']   ?? 0,
        ':id'        => $id,
    ]);
}

// Delete product 
function deleteProduct(int $id): void {
    global $pdo;
    // Delete images from disk
    $images = getProductImages($id);
    foreach ($images as $img) {
        $path = ROOT . '/image/' . $img['image_path'];
        if (file_exists($path)) unlink($path);
    }
    //Delete product from cart table
    $pdo->prepare("DELETE FROM cart WHERE product_id = :id")->execute([':id' => $id]);

    //Delete product from wishlist table
    $pdo->prepare("DELETE FROM wishlist WHERE product_id = :id")->execute([':id' => $id]);

    //Delete inventory log entries for this product
    $pdo->prepare("DELETE FROM inventory_log WHERE product_id = :id")->execute([':id' => $id]);
    // order_items: keep row but set product_id to NULL to preserve order totals
    try {
        $pdo->prepare("UPDATE order_items SET product_id = NULL WHERE product_id = :id")
            ->execute([':id' => $id]);
    } catch (Exception $e) {
         //If foreign key constraint doesn't allow NULL, skip update and leave records as-is
    }
    $pdo->prepare("DELETE FROM product_images WHERE product_id = :id")->execute([':id' => $id]);
    $pdo->prepare("DELETE FROM products WHERE id = :id")->execute([':id' => $id]);
}

//  Upload product image
function uploadProductImage(int $productId, array $file, int $sortOrder = 0): array {
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp'];

    if (!in_array($ext, $allowed))
        return ['success' => false, 'message' => 'Only JPG, PNG, GIF, WEBP allowed.'];
    if ($file['size'] > 3 * 1024 * 1024)
        return ['success' => false, 'message' => 'File too large. Max 3MB.'];

    $subdir  = 'products/';
    $dir     = ROOT . '/image/' . $subdir;
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $filename = 'product_' . $productId . '_' . time() . '_' . $sortOrder . '.' . $ext;

    if (!move_uploaded_file($file['tmp_name'], $dir . $filename))
        return ['success' => false, 'message' => 'Upload failed.'];

    global $pdo;
    $pdo->prepare(
        "INSERT INTO product_images (product_id, image_path, sort_order) VALUES (:pid, :path, :order)"
    )->execute([':pid' => $productId, ':path' => $subdir . $filename, ':order' => $sortOrder]);

    return ['success' => true, 'path' => $subdir . $filename];
}

// ── Delete product image ─────────────────────────────────────
function deleteProductImage(int $imageId): void {
    global $pdo;
    $s = $pdo->prepare("SELECT image_path FROM product_images WHERE id = :id");
    $s->execute([':id' => $imageId]);
    $img = $s->fetch();
    if ($img) {
        $path = ROOT . '/image/' . $img['image_path'];
        if (file_exists($path)) unlink($path);
        $pdo->prepare("DELETE FROM product_images WHERE id = :id")->execute([':id' => $imageId]);
    }
}