<?php
/**
 * Search events by name, optionally filter by category, and sort by price.
 *
 * GET /api/search_events.php?name=concert&category=Music&sort=price_asc
 * Query parameters are optional. sort accepts price_asc or price_desc.
 */
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db.php';

$name = isset($_GET['name']) ? trim((string) $_GET['name']) : '';
$category = isset($_GET['category']) ? trim((string) $_GET['category']) : '';
$sort = isset($_GET['sort']) ? strtolower(trim((string) $_GET['sort'])) : '';

if ($sort !== '' && !in_array($sort, ['price_asc', 'price_desc'], true)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid sort value. Use price_asc or price_desc.',
    ]);
    exit;
}

try {
    $conditions = [];
    $params = [];

    if ($name !== '') {
        $conditions[] = 'name LIKE :name';
        $params[':name'] = '%' . $name . '%';
    }
    if ($category !== '') {
        $conditions[] = 'category = :category';
        $params[':category'] = $category;
    }

    $sql = 'SELECT id, name, category, event_date, location, price, image FROM events';
    if ($conditions) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    if ($sort === 'price_asc') {
        $sql .= ' ORDER BY price ASC, name ASC';
    } elseif ($sort === 'price_desc') {
        $sql .= ' ORDER BY price DESC, name ASC';
    } else {
        $sql .= ' ORDER BY event_date ASC, name ASC';
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'count' => count($events),
        'events' => $events,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to search events.',
    ]);
}
