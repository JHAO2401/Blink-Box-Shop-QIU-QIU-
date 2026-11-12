<?php
// find root directory  to access database and functions
if (!defined('ROOT')) define('ROOT', dirname(__DIR__));
require_once ROOT . '/system/system.php';

// Safe HTML output 
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// Session 
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

// Auth checks 
function isLoggedIn(): bool {
    startSession();
    if (!isset($_SESSION['user_id'])) {
        autoLoginFromCookie();
    }
    return isset($_SESSION['user_id']);
}
//check user is amdmin
function isAdmin(): bool {
    startSession();
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}
// require login
function requireLogin(): void {
    // Prevent browser from caching protected pages
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    if (!isLoggedIn()) {
        header('Location: /system/login_register.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}
// login for admin
function requireAdmin(): void {
    startSession();
    // Prevent browser from caching protected pages
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    if (!isLoggedIn() || !isAdmin()) {
        header('Location: /system/login_register.php?tab=admin');
        exit;
    }
}

//  Login attempt tracking (customers only) 
function countRecentAttempts(string $email): int {
    global $pdo;
    $s = $pdo->prepare(
        "SELECT COUNT(*) FROM login_attempts
         WHERE email = :e
         AND attempt_time >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)"
    );
    $s->execute([':e' => $email]);
    return (int) $s->fetchColumn();
}
// record enter wrong password (only 3 attempt)
function recordAttempt(string $email): void {
    global $pdo;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $pdo->prepare(
        "INSERT INTO login_attempts (email, attempt_time, ip_address)
         VALUES (:e, NOW(), :ip)"
    )->execute([':e' => $email, ':ip' => $ip]);
}
// clear attempt (let user try again attempt)
function clearAttempts(string $email): void {
    global $pdo;
    $pdo->prepare("DELETE FROM login_attempts WHERE email = :e")
        ->execute([':e' => $email]);
}
// lock account
function lockCustomerAccount(string $email): void {
    global $pdo;
    $pdo->prepare("UPDATE users SET is_locked = 1 WHERE email = :e")
        ->execute([':e' => $email]);
}

// customer login
function loginCustomer(string $email, string $password, bool $remember = false): array {
    global $pdo;
    $email = trim(strtolower($email));

    $s = $pdo->prepare("SELECT * FROM users WHERE email = :e LIMIT 1");
    $s->execute([':e' => $email]);
    $user = $s->fetch();

    if (!$user) {
        recordAttempt($email);
        return ['success' => false, 'message' => 'Invalid email or password.', 'attempts_left' => null];
    }

    if ($user['is_locked']) {
        return ['success' => false, 'message' => 'This account is locked. Please contact support.', 'attempts_left' => 0];
    }

    $attempts = countRecentAttempts($email);

    if ($attempts >= 5) {
        lockCustomerAccount($email);
        return ['success' => false, 'message' => 'Account locked after 5 failed attempts.', 'attempts_left' => 0];
    }

    if (!password_verify($password, $user['password'])) {
        recordAttempt($email);
        $now  = $attempts + 1;
        $left = max(0, 5 - $now);
        if ($now >= 5) {
            lockCustomerAccount($email);
            return ['success' => false, 'message' => 'Account locked after 5 failed attempts.', 'attempts_left' => 0];
        }
        return ['success' => false, 'message' => "Wrong password. {$left} attempt(s) remaining.", 'attempts_left' => $left];
    }

    // Success
    clearAttempts($email);
    setCustomerSession($user);
    if ($remember) setRememberCookie($user);

    return ['success' => true, 'user' => $user];
}

//admin login 
function loginAdmin(string $username, string $password): array {
    global $pdo;
    $username = trim($username);

    if ($username === '' || $password === '') {
        return ['success' => false, 'message' => 'Please fill in all fields.'];
    }

    $s = $pdo->prepare("SELECT * FROM admin WHERE username = :u LIMIT 1");
    $s->execute([':u' => $username]);
    $admin = $s->fetch();

    if (!$admin) {
        return ['success' => false, 'message' => 'Username not found.'];
    }

    if (!password_verify($password, $admin['password'])) {
        return ['success' => false, 'message' => 'Wrong password.'];
    }

    // Set admin session
    startSession();
    session_regenerate_id(true);
    $_SESSION['user_id']  = $admin['id'];
    $_SESSION['username'] = $admin['username'];
    $_SESSION['role']     = 'admin';

    return ['success' => true, 'admin' => $admin];
}

// customer register 
function registerUser(string $username, string $email, string $password): array {
    global $pdo;
    $username = trim($username);
    $email    = trim(strtolower($email));

    if (strlen($username) < 3 || strlen($username) > 50)
        return ['success' => false, 'message' => 'Username must be 3–50 characters.'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        return ['success' => false, 'message' => 'Invalid email address.'];
    if (strlen($password) < 8)
        return ['success' => false, 'message' => 'Password must be at least 8 characters.'];
    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password))
        return ['success' => false, 'message' => 'Password needs at least 1 uppercase letter and 1 number.'];

    $s = $pdo->prepare("SELECT id FROM users WHERE email = :e OR username = :u LIMIT 1");
    $s->execute([':e' => $email, ':u' => $username]);
    if ($s->fetch()) {
        return ['success' => false, 'message' => 'Email or username already taken.'];
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $pdo->prepare("INSERT INTO users (username, email, password) VALUES (:u, :e, :p)")
        ->execute([':u' => $username, ':e' => $email, ':p' => $hash]);

    return ['success' => true, 'message' => 'Account created successfully!'];
}

//  logout 
function logoutUser(): void {
    global $pdo;
    startSession();

    // Clear remember-me token (customers only)
    if (!empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'customer') {
        $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = :id")
            ->execute([':id' => $_SESSION['user_id']]);
        setcookie('remember_me', '', time() - 3600, '/');
    }

    $_SESSION = [];
    session_destroy();
    setcookie(session_name(), '', time() - 3600, '/');
}

// cookie (remember me)
function autoLoginFromCookie(): void {
    global $pdo;
    if (empty($_COOKIE['remember_me'])) return;

    $parts = explode(':', $_COOKIE['remember_me'], 2);
    if (count($parts) !== 2) return;

    [$uid, $token] = $parts;
    $hash = hash('sha256', $token);

    $s = $pdo->prepare(
        "SELECT * FROM users WHERE id = :id AND remember_token = :h AND is_locked = 0 LIMIT 1"
    );
    $s->execute([':id' => (int)$uid, ':h' => $hash]);
    $user = $s->fetch();

    if ($user) {
        setCustomerSession($user);
    } else {
        setcookie('remember_me', '', time() - 3600, '/');
    }
}

// Session helpers 
function setCustomerSession(array $user): void {
    startSession();
    session_regenerate_id(true);
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email']    = $user['email'];
    $_SESSION['role']     = 'customer';
}

// set remember me 
function setRememberCookie(array $user): void {
    global $pdo;
    $token   = bin2hex(random_bytes(32));
    $hash    = hash('sha256', $token);
    $expires = time() + 30 * 24 * 3600;

    $pdo->prepare("UPDATE users SET remember_token = :h WHERE id = :id")
        ->execute([':h' => $hash, ':id' => $user['id']]);

    setcookie('remember_me', $user['id'] . ':' . $token, [
        'expires'  => $expires,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}

// get current logged-in user info 
function getCurrentUser(): ?array {
    global $pdo;
    if (!isLoggedIn()) return null;

    if (isAdmin()) {
        $s = $pdo->prepare(
            "SELECT id, username, '' AS email, 'admin' AS role,
                    IFNULL(profile_pic,'') AS profile_pic, created_at
             FROM admin WHERE id = :id"
        );
    } else {
        $s = $pdo->prepare(
            "SELECT id, username, email, 'customer' AS role,
                    IFNULL(profile_pic,'') AS profile_pic, created_at
             FROM users WHERE id = :id"
        );
    }

    $s->execute([':id' => $_SESSION['user_id']]);
    return $s->fetch() ?: null;
}

// Cart count 
function getCartCount(): int {
    global $pdo;
    if (!isLoggedIn() || isAdmin()) return 0;
    $s = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = :uid");
    $s->execute([':uid' => $_SESSION['user_id']]);
    return (int) $s->fetchColumn();
}