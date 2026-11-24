<?php
require_once __DIR__ . '/require_admin.php';

$search = trim((string)($_GET['search'] ?? ''));
$status = (string)($_GET['status'] ?? '');
if (!in_array($status, ['', 'Pending', 'Confirmed', 'Cancelled'], true)) $status = '';
$perPage = 6;
$requestedPage = max(1, (int)($_GET['page'] ?? 1));

try {
    $where = ' FROM bookings b LEFT JOIN events e ON e.id = b.event_id WHERE LOWER(b.email) = LOWER(?)';
    $params = [$_SESSION['event_user']['email']];
    if ($search !== '') {
        $where .= ' AND b.customer_name LIKE ?';
        $params[] = '%' . $search . '%';
    }
    if ($status !== '') {
        $where .= ' AND b.status = ?';
        $params[] = $status;
    }
    $countStmt = $pdo->prepare('SELECT COUNT(*)' . $where);
    $countStmt->execute($params);
    $totalBookings = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalBookings / $perPage));
    $page = min($requestedPage, $totalPages);
    $offset = ($page - 1) * $perPage;
    $sql = 'SELECT b.id, b.customer_name, b.email, b.ticket_quantity, b.status, b.booked_at,
                   e.name AS event_name, e.event_date, e.location, e.price AS ticket_price'
           . $where . ' ORDER BY b.booked_at DESC, b.id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $stats = [
        'events' => (int)$pdo->query('SELECT COUNT(*) FROM events')->fetchColumn(),
        'users' => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'bookings' => 0,
        'confirmed' => 0,
    ];
    $countBookings = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE LOWER(email) = LOWER(?)');
    $countBookings->execute([$_SESSION['event_user']['email']]);
    $stats['bookings'] = (int)$countBookings->fetchColumn();
    $countConfirmed = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE LOWER(email) = LOWER(?) AND status = 'Confirmed'");
    $countConfirmed->execute([$_SESSION['event_user']['email']]);
    $stats['confirmed'] = (int)$countConfirmed->fetchColumn();
    echo json_encode(['success' => true, 'bookings' => $stmt->fetchAll(), 'stats' => $stats, 'pagination' => ['page' => $page, 'per_page' => $perPage, 'total' => $totalBookings, 'pages' => $totalPages]]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not refresh dashboard data.']);
}
