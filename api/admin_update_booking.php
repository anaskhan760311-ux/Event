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
$id = filter_var($input['booking_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$status = (string)($input['status'] ?? '');
if (!$id || !in_array($status, ['Pending', 'Confirmed', 'Cancelled'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Select a valid booking and status.']);
    exit;
}
try {
    $stmt = $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ? AND LOWER(email) = LOWER(?)');
    $stmt->execute([$status, $id, $_SESSION['event_user']['email']]);
    if ($stmt->rowCount() === 0) {
        $check = $pdo->prepare('SELECT id FROM bookings WHERE id = ? AND LOWER(email) = LOWER(?)');
        $check->execute([$id, $_SESSION['event_user']['email']]);
        if (!$check->fetchColumn()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Booking not found.']);
            exit;
        }
    }
    echo json_encode(['success' => true, 'message' => 'Booking status updated.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not update booking status.']);
}
