<?php
/** Registration, login, session lookup and logout API. */
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
require_once __DIR__ . '/../db.php';
$bootstrapAdminEmails = require __DIR__ . '/../admin_config.php';
$bootstrapAdminEmails = array_map('strtolower', $bootstrapAdminEmails['bootstrap_admin_emails']);

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('user','admin') NOT NULL DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $roleColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch();
    if (!$roleColumn) {
        $pdo->exec("ALTER TABLE users ADD role ENUM('user','admin') NOT NULL DEFAULT 'user' AFTER password_hash");
    }
    foreach ($bootstrapAdminEmails as $adminEmail) {
        $promote = $pdo->prepare("UPDATE users SET role = 'admin' WHERE LOWER(email) = LOWER(?)");
        $promote->execute([$adminEmail]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not prepare the user database.']);
    exit;
}

function authResponse(int $status, array $body): void {
    http_response_code($status);
    echo json_encode($body);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET') {
    $user = null;
    if (!empty($_SESSION['event_user']['id'])) {
        $stmt = $pdo->prepare('SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([(int)$_SESSION['event_user']['id']]);
        $user = $stmt->fetch() ?: null;
        if ($user) {
            $user['id'] = (int)$user['id'];
            $_SESSION['event_user'] = $user;
        } else {
            unset($_SESSION['event_user']);
        }
    }
    authResponse(200, ['success' => true, 'user' => $user]);
}
if ($method !== 'POST') {
    header('Allow: GET, POST');
    authResponse(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = $_POST;
$action = $input['action'] ?? '';

if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    authResponse(200, ['success' => true, 'message' => 'Logged out.']);
}

$email = strtolower(trim((string)($input['email'] ?? '')));
$password = (string)($input['password'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255 || $password === '') {
    authResponse(422, ['success' => false, 'message' => 'Enter a valid email and password.']);
}

if ($action === 'signup') {
    $name = trim((string)($input['name'] ?? ''));
    if ($name === '' || strlen($name) > 255 || strlen($password) < 6) {
        authResponse(422, ['success' => false, 'message' => 'Enter your name and a password of at least 6 characters.']);
    }
    try {
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
        $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') authResponse(409, ['success' => false, 'message' => 'An account with that email already exists.']);
        authResponse(500, ['success' => false, 'message' => 'Could not create your account.']);
    }
    $role = in_array($email, $bootstrapAdminEmails, true) ? 'admin' : 'user';
    if ($role === 'admin') {
        $promote = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
        $promote->execute([$pdo->lastInsertId()]);
    }
    $user = ['id' => (int)$pdo->lastInsertId(), 'name' => $name, 'email' => $email, 'role' => $role];
    session_regenerate_id(true);
    $_SESSION['event_user'] = $user;
    authResponse(201, ['success' => true, 'user' => $user, 'message' => 'Account created.']);
}

if ($action === 'login') {
    $stmt = $pdo->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    if (!$row || !password_verify($password, $row['password_hash'])) {
        authResponse(401, ['success' => false, 'message' => 'Email or password is incorrect.']);
    }
    if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
        $rehash = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $rehash->execute([password_hash($password, PASSWORD_DEFAULT), $row['id']]);
    }
    $user = ['id' => (int)$row['id'], 'name' => $row['name'], 'email' => $row['email'], 'role' => $row['role']];
    session_regenerate_id(true);
    $_SESSION['event_user'] = $user;
    authResponse(200, ['success' => true, 'user' => $user, 'message' => 'Logged in.']);
}

authResponse(400, ['success' => false, 'message' => 'Unknown authentication action.']);
