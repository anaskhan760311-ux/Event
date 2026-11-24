<?php
/** Return all database bookings with their event details for the HTML manager. */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
session_start();
if (empty($_SESSION['event_user']['email'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to view your bookings.']);
    exit;
}

require_once __DIR__ . '/../db.php';

try {
    $stmt = $pdo->prepare('
        SELECT b.id, b.customer_name, b.email, b.event_id, b.ticket_quantity,
               b.status, b.booked_at, e.name AS event_name,
               e.event_date, e.location AS event_location, e.price AS ticket_price
        FROM bookings b
        LEFT JOIN events e ON e.id = b.event_id
        WHERE LOWER(b.email) = LOWER(?)
        ORDER BY b.booked_at DESC
    ');
    $stmt->execute([$_SESSION['event_user']['email']]);
    echo json_encode(['success' => true, 'bookings' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch bookings.']);
}
