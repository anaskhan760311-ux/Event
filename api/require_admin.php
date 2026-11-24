<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
if (empty($_SESSION['event_user']['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to continue.']);
    exit;
}
require_once __DIR__ . '/../db.php';
$adminConfig = require __DIR__ . '/../admin_config.php';
$bootstrapAdminEmails = array_map('strtolower', $adminConfig['bootstrap_admin_emails']);
try {
    $roleColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch();
    if (!$roleColumn) {
        $pdo->exec("ALTER TABLE users ADD role ENUM('user','admin') NOT NULL DEFAULT 'user' AFTER password_hash");
    }
    $promote = $pdo->prepare("UPDATE users SET role = 'admin' WHERE LOWER(email) = LOWER(?)");
    foreach ($bootstrapAdminEmails as $adminEmail) $promote->execute([$adminEmail]);
    $stmt = $pdo->prepare('SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$_SESSION['event_user']['id']]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not verify administrator access.']);
    exit;
}
if (!$user) {
    unset($_SESSION['event_user']);
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to continue.']);
    exit;
}
$user['id'] = (int)$user['id'];
$_SESSION['event_user'] = $user;
if ($user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Administrator access required.']);
    exit;
}
