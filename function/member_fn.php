<?php
// find root directory  to access database and functions
if (!defined('ROOT')) define('ROOT', dirname(__DIR__));
require_once ROOT . '/system/system.php';

// Get all members 
function getMembers(string $search = ''): array {
    global $pdo;
    $where  = '1=1';
    $params = [];

    if ($search) {
        $where          = '(username LIKE :s OR email LIKE :s)';
        $params[':s']   = '%' . $search . '%';
    }

    $s = $pdo->prepare(
        "SELECT u.*,
                (SELECT COUNT(*) FROM orders WHERE user_id = u.id) AS order_count,
                (SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE user_id = u.id AND status != 'cancelled') AS total_spent
         FROM users u
         WHERE $where
         ORDER BY u.created_at DESC"
    );
    $s->execute($params);
    return $s->fetchAll();
}

// Get single member with orders 
function getMemberDetail(int $id): ?array {
    global $pdo;
    $s = $pdo->prepare(
        "SELECT u.*,
                (SELECT COUNT(*) FROM orders WHERE user_id = u.id) AS order_count,
                (SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE user_id = u.id AND status != 'cancelled') AS total_spent
         FROM users u WHERE id = :id"
    );
    $s->execute([':id' => $id]);
    $member = $s->fetch();
    if (!$member) return null;

    $s = $pdo->prepare(
        "SELECT * FROM orders WHERE user_id = :id ORDER BY created_at DESC LIMIT 10"
    );
    $s->execute([':id' => $id]);
    $member['orders'] = $s->fetchAll();

    return $member;
}

// Lock / unlock member 
function setMemberLock(int $id, int $locked): void {
    global $pdo;
    $pdo->prepare("UPDATE users SET is_locked = :lock WHERE id = :id")
        ->execute([':lock' => $locked, ':id' => $id]);
    // When unlocking, clear all login attempt records so counter resets to 0
    if ($locked === 0) {
        $s = $pdo->prepare("SELECT email FROM users WHERE id = :id");
        $s->execute([':id' => $id]);
        $user = $s->fetch();
        if ($user) {
            $pdo->prepare("DELETE FROM login_attempts WHERE email = :e")
                ->execute([':e' => $user['email']]);
        }
    }

    // When unlocking, clear failed login attempts so counter resets to 0
    if (!$locked) {
        $s = $pdo->prepare("SELECT email FROM users WHERE id = :id");
        $s->execute([':id' => $id]);
        $user = $s->fetch();
        if ($user) {
            $pdo->prepare("DELETE FROM login_attempts WHERE email = :e")
                ->execute([':e' => $user['email']]);
        }
    }
}