<?php
/**
 * api/update_booking.php - Update a booking record in the MySQL database.
 *
 * Expects JSON POST body with:
 *   id (int) - MySQL bookings table primary key
 *   customer_name (string)
 *   email (string)
 *   ticket_quantity (int)
 *   status (string: Pending|Confirmed|Cancelled)
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

$id       = isset($input['id']) ? (int)$input['id'] : 0;
$name     = isset($input['customer_name']) ? trim((string)$input['customer_name']) : $_SESSION['event_user']['name'];
$email    = $_SESSION['event_user']['email'];
$qty      = isset($input['ticket_quantity']) ? (int)$input['ticket_quantity'] : 0;
$status   = isset($input['status']) ? trim($input['status']) : 'Confirmed';

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Booking ID is required.']);
    exit;
}
if ($name === '' || strlen($name) > 255) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Customer name is required and must be at most 255 characters.']);
    exit;
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Valid email is required.']);
    exit;
}
if ($qty <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ticket quantity must be greater than zero.']);
    exit;
}
if (!in_array($status, ['Pending', 'Confirmed', 'Cancelled'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status.']);
    exit;
}

try {
    $stmt = $pdo->prepare('
        UPDATE bookings
        SET customer_name = ?,
            email = ?,
            ticket_quantity = ?,
            status = ?
        WHERE id = ? AND LOWER(email) = LOWER(?)
    ');
    $stmt->execute([$name, $email, $qty, $status, $id, $_SESSION['event_user']['email']]);

    if ($stmt->rowCount() === 0) {
        $check = $pdo->prepare('SELECT id FROM bookings WHERE id = ? AND LOWER(email) = LOWER(?)');
        $check->execute([$id, $_SESSION['event_user']['email']]);
        if ($check->fetchColumn()) {
            echo json_encode(['success' => true, 'message' => 'Booking is already up to date.']);
            exit;
        }
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Booking not found.']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Booking updated.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
