<?php
// find root directory  to access database and functions
if (!defined('ROOT')) define('ROOT', dirname(__DIR__));
require_once ROOT . '/system/system.php';

//oreder functions (admin & user)
function getOrders(array $filters = []): array {
    global $pdo;
    $where  = ['1=1'];
    $params = [];

    if (!empty($filters['status'])) {
        $where[]           = 'o.status = :status';
        $params[':status'] = $filters['status'];
    }
    if (!empty($filters['search'])) {
        $where[]            = '(u.username LIKE :s OR o.id LIKE :s OR o.tracking_number LIKE :s)';
        $params[':s']       = '%' . $filters['search'] . '%';
    }

    $sql = "SELECT o.*, u.username, u.email
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY o.created_at ASC";

    $s = $pdo->prepare($sql);
    $s->execute($params);
    return $s->fetchAll();
}

// Get single order with items 
function getOrderDetail(int $id): ?array {
    global $pdo;

    $s = $pdo->prepare(
        "SELECT o.*, u.username, u.email,
                a.recipient_name, a.phone, a.address_line, a.city, a.state, a.postcode, a.country
         FROM orders o
         JOIN users u ON o.user_id = u.id
         JOIN addresses a ON o.address_id = a.id
         WHERE o.id = :id"
    );
    $s->execute([':id' => $id]);
    $order = $s->fetch();
    if (!$order) return null;

    $s = $pdo->prepare(
        "SELECT oi.*, p.name AS product_name,
                (SELECT image_path FROM product_images WHERE product_id = p.id AND sort_order = 0 LIMIT 1) AS cover_image
         FROM order_items oi
         JOIN products p ON oi.product_id = p.id
         WHERE oi.order_id = :id"
    );
    $s->execute([':id' => $id]);
    $order['items'] = $s->fetchAll();

    return $order;
}

// Update order status 
function updateOrderStatus(int $id, string $status, string $tracking = ''): void {
    global $pdo;
    $pdo->prepare(
        "UPDATE orders SET status = :status, tracking_number = :tracking WHERE id = :id"
    )->execute([':status' => $status, ':tracking' => $tracking, ':id' => $id]);
}

// Status badge color 
function orderStatusClass(string $status): string {
    return [
        'pending'    => 'tag-orange',
        'paid'       => 'tag-purple',
        'processing' => 'tag-purple',
        'shipped'    => 'tag-teal',
        'delivered'  => 'tag-green',
        'cancelled'  => 'tag-red',
    ][$status] ?? 'tag-pink';
}