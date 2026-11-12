<?php
// find root directory  to access database and functions
ini_set('display_errors', 1);
error_reporting(E_ALL);
if (!defined('ROOT')) define('ROOT', dirname(__DIR__));
require_once ROOT . '/system/system.php';

// Products
function getActiveProducts(array $filters = []): array {
    global $pdo;
    $where  = ['p.is_active = 1'];
    $params = [];

    if (!empty($filters['cat'])) {
        $where[]        = 'c.slug = :cat';
        $params[':cat'] = $filters['cat'];
    }
    if (!empty($filters['search'])) {
        $where[]           = 'p.name LIKE :s';
        $params[':s']      = '%' . $filters['search'] . '%';
    }
    if (!empty($filters['sort'])) {
        switch ($filters['sort']) {
            case 'price_asc':  $order = '(p.price * (1 - p.discount/100)) ASC'; break;
            case 'price_desc': $order = '(p.price * (1 - p.discount/100)) DESC'; break;
            case 'newest':     $order = 'p.created_at DESC'; break;
            default:           $order = 'p.created_at DESC';
        }
    } else {
        $order = 'p.created_at DESC';
    }

    $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                   (SELECT image_path FROM product_images WHERE product_id = p.id AND sort_order = 0 LIMIT 1) AS cover_image
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY $order";

    $s = $pdo->prepare($sql);
    $s->execute($params);
    return $s->fetchAll();
}
// get current product is active
function getActiveProduct(int $id): ?array {
    global $pdo;
    $s = $pdo->prepare(
        "SELECT p.*, c.name AS category_name, c.slug AS category_slug
         FROM products p
         JOIN categories c ON p.category_id = c.id
         WHERE p.id = :id AND p.is_active = 1"
    );
    $s->execute([':id' => $id]);
    return $s->fetch() ?: null;
}
// get product image
function getProductImages(int $productId): array {
    global $pdo;
    $s = $pdo->prepare("SELECT * FROM product_images WHERE product_id = :id ORDER BY sort_order ASC");
    $s->execute([':id' => $productId]);
    return $s->fetchAll();
}

// Final price after discount
function finalPrice(array $product): float {
    if ($product['discount'] > 0) {
        return round($product['price'] * (1 - $product['discount'] / 100), 2);
    }
    return (float)$product['price'];
}

// load cart item
function getCartItems(int $userId): array {
    global $pdo;
    $s = $pdo->prepare(
        "SELECT c.id AS cart_id, c.quantity, p.*,
                (SELECT image_path FROM product_images WHERE product_id = p.id AND sort_order = 0 LIMIT 1) AS cover_image
         FROM cart c
         JOIN products p ON c.product_id = p.id
         WHERE c.user_id = :uid AND p.is_active = 1
         ORDER BY c.added_at DESC"
    );
    $s->execute([':uid' => $userId]);
    return $s->fetchAll();
}
// add item to ocart
function addToCart(int $userId, int $productId, int $qty = 1): void {
    global $pdo;
    // Check stock
    $s = $pdo->prepare("SELECT stock FROM products WHERE id = :id");
    $s->execute([':id' => $productId]);
    $stock = (int)$s->fetchColumn();

    $s = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = :u AND product_id = :p");
    $s->execute([':u' => $userId, ':p' => $productId]);
    $existing = $s->fetch();

    if ($existing) {
        $newQty = min($existing['quantity'] + $qty, $stock);
        $pdo->prepare("UPDATE cart SET quantity = :q WHERE id = :id")
            ->execute([':q' => $newQty, ':id' => $existing['id']]);
    } else {
        $newQty = min($qty, $stock);
        if ($newQty > 0) {
            $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (:u, :p, :q)")
                ->execute([':u' => $userId, ':p' => $productId, ':q' => $newQty]);
        }
    }
}
// update cart quantity
function updateCartQty(int $cartId, int $userId, int $qty): void {
    global $pdo;
    if ($qty <= 0) {
        $pdo->prepare("DELETE FROM cart WHERE id = :id AND user_id = :u")
            ->execute([':id' => $cartId, ':u' => $userId]);
    } else {
        // Check stock limit
        $s = $pdo->prepare("SELECT p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = :id");
        $s->execute([':id' => $cartId]);
        $row = $s->fetch();
        $maxQty = $row ? (int)$row['stock'] : $qty;
        $qty = min($qty, $maxQty);
        $pdo->prepare("UPDATE cart SET quantity = :q WHERE id = :id AND user_id = :u")
            ->execute([':q' => $qty, ':id' => $cartId, ':u' => $userId]);
    }
}
// remove item from cart
function removeFromCart(int $cartId, int $userId): void {
    global $pdo;
    $pdo->prepare("DELETE FROM cart WHERE id = :id AND user_id = :u")
        ->execute([':id' => $cartId, ':u' => $userId]);
}
// clear cart 
function clearCart(int $userId): void {
    global $pdo;
    $pdo->prepare("DELETE FROM cart WHERE user_id = :u")->execute([':u' => $userId]);
}
//total item from cart 
function cartTotal(array $items): float {
    $total = 0;
    foreach ($items as $item) {
        $total += finalPrice($item) * $item['quantity'];
    }
    return round($total, 2);
}

// Wishlist 
function getWishlist(int $userId): array {
    global $pdo;
    $s = $pdo->prepare(
        "SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                (SELECT image_path FROM product_images WHERE product_id = p.id AND sort_order = 0 LIMIT 1) AS cover_image
         FROM wishlist w
         JOIN products p ON w.product_id = p.id
         JOIN categories c ON p.category_id = c.id
         WHERE w.user_id = :u AND p.is_active = 1
         ORDER BY w.added_at DESC"
    );
    $s->execute([':u' => $userId]);
    return $s->fetchAll();
}
// like whisglist
function isInWishlist(int $userId, int $productId): bool {
    global $pdo;
    $s = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = :u AND product_id = :p");
    $s->execute([':u' => $userId, ':p' => $productId]);
    return (bool) $s->fetch();
}
// toggle whishlist
function toggleWishlist(int $userId, int $productId): string {
    global $pdo;
    if (isInWishlist($userId, $productId)) {
        $pdo->prepare("DELETE FROM wishlist WHERE user_id = :u AND product_id = :p")
            ->execute([':u' => $userId, ':p' => $productId]);
        return 'removed';
    } else {
        $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (:u, :p)")
            ->execute([':u' => $userId, ':p' => $productId]);
        return 'added';
    }
}

//  use Voucher 
function applyVoucher(string $code, float $subtotal): array {
    global $pdo;
    $s = $pdo->prepare(
        "SELECT * FROM vouchers
         WHERE code = :code AND is_active = 1
           AND (expiry_date IS NULL OR expiry_date >= CURDATE())
           AND (usage_limit = 0 OR used_count < usage_limit)"
    );
    $s->execute([':code' => strtoupper(trim($code))]);
    $v = $s->fetch();

    if (!$v) return ['success' => false, 'message' => 'Invalid or expired voucher.'];
    if ($subtotal < $v['min_purchase'])
        return ['success' => false, 'message' => 'Minimum purchase RM ' . number_format($v['min_purchase'], 2) . ' required.'];

    $discount = $v['discount_type'] === 'percent'
        ? round($subtotal * $v['discount_value'] / 100, 2)
        : min($v['discount_value'], $subtotal);

    return ['success' => true, 'voucher' => $v, 'discount' => $discount];
}

//  Addresses 
function getUserAddresses(int $userId): array {
    global $pdo;
    $s = $pdo->prepare("SELECT * FROM addresses WHERE user_id = :u ORDER BY is_default DESC, id DESC");
    $s->execute([':u' => $userId]);
    return $s->fetchAll();
}
// load address
function getAddress(int $id, int $userId): ?array {
    global $pdo;
    $s = $pdo->prepare("SELECT * FROM addresses WHERE id = :id AND user_id = :u");
    $s->execute([':id' => $id, ':u' => $userId]);
    return $s->fetch() ?: null;
}
// save address
function saveAddress(int $userId, array $data, int $editId = 0): int {
    global $pdo;

    if ($editId) {
        $pdo->prepare(
            "UPDATE addresses SET label=:label, recipient_name=:name, phone=:phone,
             address_line=:addr, city=:city, state=:state, postcode=:post, country=:country
             WHERE id=:id AND user_id=:u"
        )->execute([
            ':label' => $data['label'],   ':name'  => $data['recipient_name'],
            ':phone' => $data['phone'],   ':addr'  => $data['address_line'],
            ':city'  => $data['city'],    ':state' => $data['state'] ?? '',
            ':post'  => $data['postcode'],':country'=> $data['country'] ?? 'Malaysia',
            ':id'    => $editId,          ':u'     => $userId,
        ]);
        return $editId;
    } else {
        $pdo->prepare(
            "INSERT INTO addresses (user_id, label, recipient_name, phone, address_line, city, state, postcode, country)
             VALUES (:u, :label, :name, :phone, :addr, :city, :state, :post, :country)"
        )->execute([
            ':u'     => $userId,          ':label' => $data['label'],
            ':name'  => $data['recipient_name'], ':phone' => $data['phone'],
            ':addr'  => $data['address_line'],   ':city'  => $data['city'],
            ':state' => $data['state'] ?? '',    ':post'  => $data['postcode'],
            ':country'=> $data['country'] ?? 'Malaysia',
        ]);
        return (int) $pdo->lastInsertId();
    }
}
// set default address
function setDefaultAddress(int $id, int $userId): void {
    global $pdo;
    $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = :u")->execute([':u' => $userId]);
    $pdo->prepare("UPDATE addresses SET is_default = 1 WHERE id = :id AND user_id = :u")->execute([':id' => $id, ':u' => $userId]);
}

// delete address 
function deleteAddress(int $id, int $userId): void {
    global $pdo;
    $pdo->prepare("DELETE FROM addresses WHERE id = :id AND user_id = :u")->execute([':id' => $id, ':u' => $userId]);
}

// Orders 
function getUserOrders(int $userId): array {
    global $pdo;
    $s = $pdo->prepare(
        "SELECT o.*,
                (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count
         FROM orders o
         WHERE o.user_id = :u
         ORDER BY o.created_at DESC"
    );
    $s->execute([':u' => $userId]);
    return $s->fetchAll();
}

function getUserOrder(int $orderId, int $userId): ?array {
    global $pdo;
    $s = $pdo->prepare(
        "SELECT o.*, a.recipient_name, a.phone, a.address_line, a.city, a.state, a.postcode, a.country
         FROM orders o
         JOIN addresses a ON o.address_id = a.id
         WHERE o.id = :id AND o.user_id = :u"
    );
    $s->execute([':id' => $orderId, ':u' => $userId]);
    $order = $s->fetch();
    if (!$order) return null;

    $s = $pdo->prepare(
        "SELECT oi.*, p.name AS product_name,
                (SELECT image_path FROM product_images WHERE product_id = p.id AND sort_order = 0 LIMIT 1) AS cover_image
         FROM order_items oi JOIN products p ON oi.product_id = p.id
         WHERE oi.order_id = :id"
    );
    $s->execute([':id' => $orderId]);
    $order['items'] = $s->fetchAll();
    return $order;
}

function placeOrder(int $userId, int $addressId, array $items, float $subtotal, float $discount, ?int $voucherId, string $notes = ''): int {
    global $pdo;
    $total        = max(0, $subtotal - $discount);
    $receiptToken = bin2hex(random_bytes(16));

    $pdo->prepare(
        "INSERT INTO orders (user_id, address_id, voucher_id, subtotal, discount_amount, total_amount,
                             status, payment_method, receipt_token, notes)
         VALUES (:u, :addr, :v, :sub, :disc, :total, 'pending', 'QR Pay', :token, :notes)"
    )->execute([
        ':u'     => $userId,     ':addr'  => $addressId,
        ':v'     => $voucherId,  ':sub'   => $subtotal,
        ':disc'  => $discount,   ':total' => $total,
        ':token' => $receiptToken, ':notes' => $notes,
    ]);
    $orderId = (int) $pdo->lastInsertId();

    foreach ($items as $item) {
        $pdo->prepare(
            "INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (:oid, :pid, :qty, :price)"
        )->execute([
            ':oid'   => $orderId,
            ':pid'   => $item['id'],
            ':qty'   => $item['quantity'],
            ':price' => finalPrice($item),
        ]);
        // Deduct stock
        $pdo->prepare("UPDATE products SET stock = stock - :q WHERE id = :id")
            ->execute([':q' => $item['quantity'], ':id' => $item['id']]);
    }

    // Mark voucher used
    if ($voucherId) {
        $pdo->prepare("UPDATE vouchers SET used_count = used_count + 1 WHERE id = :id")
            ->execute([':id' => $voucherId]);
    }

    return $orderId;
}

function statusColor(string $status): string {
    switch ($status) {
        case 'pending':    return '#f59e0b';
        case 'paid':       return '#8b5cf6';
        case 'processing': return '#6366f1';
        case 'shipped':    return '#14b8a6';
        case 'delivered':  return '#22c55e';
        case 'cancelled':  return '#ef4444';
        default:           return '#a3a3a3';
    }
}

//  Cancel order (customer) 
function cancelOrder(int $orderId, int $userId): bool {
    global $pdo;
    // Only allow cancel if status is pending
    $s = $pdo->prepare("SELECT id, status FROM orders WHERE id = :id AND user_id = :u");
    $s->execute([':id' => $orderId, ':u' => $userId]);
    $order = $s->fetch();

    if (!$order || $order['status'] !== 'pending') return false;

    // Restore stock
    $items = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = :id");
    $items->execute([':id' => $orderId]);
    foreach ($items->fetchAll() as $item) {
        $pdo->prepare("UPDATE products SET stock = stock + :q WHERE id = :id")
            ->execute([':q' => $item['quantity'], ':id' => $item['product_id']]);
    }

    $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = :id AND user_id = :u")
        ->execute([':id' => $orderId, ':u' => $userId]);

    return true;
}

//  Format Malaysia phone number 
function formatMyPhone(string $phone): string {
    // Strip everything except digits and leading +
    $clean = preg_replace('/[^\d]/', '', $phone);

    // Remove country code 60 if present
    if (substr($clean, 0, 2) === '60') {
        $clean = '0' . substr($clean, 2);
    }

    $len = strlen($clean);

    // 011 numbers: 11 digits → 011-XXXX XXXX
    if (substr($clean, 0, 3) === '011' && $len === 11) {
        return substr($clean, 0, 3) . '-' . substr($clean, 3, 4) . ' ' . substr($clean, 7);
    }

    // 010, 012-019: 10 digits → 01X-XXX XXXX
    if (substr($clean, 0, 2) === '01' && $len === 10) {
        return substr($clean, 0, 3) . '-' . substr($clean, 3, 3) . ' ' . substr($clean, 6);
    }

    // Landline 03: 03-XXXX XXXX
    if (substr($clean, 0, 2) === '03' && $len === 10) {
        return '03-' . substr($clean, 2, 4) . ' ' . substr($clean, 6);
    }

    // Other landlines: 0X-XXX XXXX (9 digits)
    if (substr($clean, 0, 1) === '0' && $len === 9) {
        return substr($clean, 0, 2) . '-' . substr($clean, 2, 3) . ' ' . substr($clean, 5);
    }

    return $phone; // fallback original
}

//  Validate Malaysia phone number 
function validateMyPhone(string $phone): bool {
    $clean = preg_replace('/[^\d]/', '', $phone);
    // Remove country code 60
    if (substr($clean, 0, 2) === '60') $clean = '0' . substr($clean, 2);

    if (!preg_match('/^\d+$/', $clean)) return false;

    $len    = strlen($clean);
    $prefix = substr($clean, 0, 3);

    // 011: must be 11 digits
    if ($prefix === '011') return $len === 11;

    // 010,012,013,014,015,016,017,018,019: must be 10 digits
    if (substr($clean, 0, 2) === '01') return $len === 10;

    // Landline 03/04/05/06/07/08/09: 9-10 digits
    if (substr($clean, 0, 1) === '0') return $len >= 9 && $len <= 10;

    return false;
}