<?php
/** Require an authenticated user with the administrator role for HTML pages. */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['event_user']['id'])) {
    $target = basename($_SERVER['PHP_SELF'] ?? 'admin_dashboard.php');
    header('Location: index.html?login=1&next=' . rawurlencode($target));
    exit;
}
require_once __DIR__ . '/db.php';
$adminConfig = require __DIR__ . '/admin_config.php';
$bootstrapAdminEmails = array_map('strtolower', $adminConfig['bootstrap_admin_emails']);
$roleColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch();
if (!$roleColumn) {
    $pdo->exec("ALTER TABLE users ADD role ENUM('user','admin') NOT NULL DEFAULT 'user' AFTER password_hash");
}
$bootstrapAdmin = $pdo->prepare("UPDATE users SET role = 'admin' WHERE LOWER(email) = LOWER(?)");
foreach ($bootstrapAdminEmails as $adminEmail) $bootstrapAdmin->execute([$adminEmail]);
$roleQuery = $pdo->prepare('SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1');
$roleQuery->execute([(int)$_SESSION['event_user']['id']]);
$freshUser = $roleQuery->fetch();
if ($freshUser) {
    $freshUser['id'] = (int)$freshUser['id'];
    $_SESSION['event_user'] = $freshUser;
} else {
    unset($_SESSION['event_user']);
}
if (empty($_SESSION['event_user']['id'])) {
    header('Location: index.html?login=1&next=' . rawurlencode(basename($_SERVER['PHP_SELF'] ?? 'admin_dashboard.php')));
    exit;
}
if (($_SESSION['event_user']['role'] ?? 'user') !== 'admin') {
    http_response_code(403);
    ?>
    <!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Admin access required</title><link rel="stylesheet" href="style.css"></head><body><main style="max-width:600px;margin:4rem auto;padding:2rem;background:#fff;border-radius:12px;text-align:center"><h1>Admin access required</h1><p>Your account is signed in but does not have the administrator role.</p><a href="index.html">Return to KhanEvent</a></main></body></html>
    <?php
    exit;
}
