<?php
/**
 * api/cancel_booking.php - Cancel a booking record in the MySQL database.
 *
 * Expects JSON POST body with:
 *   id (int) - MySQL bookings table primary key
 *
 * Returns JSON: { success: true } or { success: false, message: ... }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../db.php';
if (empty($_SESSION['event_user']['email'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to manage your bookings.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input.']);
    exit;
}

$id = isset($input['id']) ? (int)$input['id'] : 0;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Booking ID is required.']);
    exit;
}

try {
    // Verify the booking exists and is not already cancelled
    $check = $pdo->prepare('SELECT id, status FROM bookings WHERE id = ? AND LOWER(email) = LOWER(?)');
    $check->execute([$id, $_SESSION['event_user']['email']]);
    $row = $check->fetch();

    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Booking not found.']);
        exit;
    }

    if ($row['status'] === 'Cancelled') {
        echo json_encode(['success' => true, 'message' => 'Booking already cancelled.']);
        exit;
    }

    $stmt = $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ? AND LOWER(email) = LOWER(?)');
    $stmt->execute(['Cancelled', $id, $_SESSION['event_user']['email']]);

    echo json_encode(['success' => true, 'message' => 'Booking cancelled.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
