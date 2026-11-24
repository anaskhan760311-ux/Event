<?php
require_once __DIR__ . '/require_admin.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input) || !hash_equals($_SESSION['admin_dashboard_csrf'] ?? '', (string)($input['csrf_token'] ?? ''))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired form token.']);
    exit;
}
$adminConfig = require __DIR__ . '/../admin_config.php';
$bootstrapAdminEmails = array_map('strtolower', $adminConfig['bootstrap_admin_emails']);
$userId = filter_var($input['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$role = (string)($input['role'] ?? '');
if (!$userId || !in_array($role, ['user', 'admin'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Select a valid account and role.']);
    exit;
}
if ($userId === (int)$_SESSION['event_user']['id'] && $role !== 'admin') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'You cannot remove the admin role from your own active account.']);
    exit;
}
try {
    $userQuery = $pdo->prepare('SELECT id, email FROM users WHERE id = ?');
    $userQuery->execute([$userId]);
    $target = $userQuery->fetch();
    if (!$target) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }
    if ($role === 'user' && in_array(strtolower($target['email']), $bootstrapAdminEmails, true)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'The configured bootstrap admin account cannot be demoted here.']);
        exit;
    }
    if ($role === 'user') {
        $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
        $currentRole = $pdo->prepare('SELECT role FROM users WHERE id = ?');
        $currentRole->execute([$userId]);
        if ($currentRole->fetchColumn() === 'admin' && $adminCount <= 1) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'At least one admin account must remain.']);
            exit;
        }
    }
    $update = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
    $update->execute([$role, $userId]);
    echo json_encode(['success' => true, 'message' => 'User role updated.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not update user role.']);
}
