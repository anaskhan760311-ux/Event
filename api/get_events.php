<?php
/**
 * api/get_events.php - Fetch events from MySQL and return them as JSON.
 *
 * Endpoint (served by WAMP):
 *   http://localhost/Events/api/get_events.php
 *
 * Usage:
 *   fetch('api/get_events.php')
 *     .then(res => res.json())
 *     .then(events => console.log(events));
 */

// Allow cross-origin use if the frontend lives on another port/domain
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db.php';

try {
    $stmt = $pdo->prepare('
        SELECT id, name, category, event_date, location, price, image
        FROM events
        ORDER BY event_date ASC, name ASC
    ');
    $stmt->execute();
    $events = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'count'   => count($events),
        'events'  => $events,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch events: ' . $e->getMessage(),
    ]);
}
