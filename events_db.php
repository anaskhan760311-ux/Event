<?php
/**
 * events_db.php - Display events fetched from the MySQL database.
 *
 * This page demonstrates Task 2: fetching events from MySQL via PDO
 * and rendering them on the website. It does not modify index.html,
 * the event grid, booking management or dashboard.
 */

require_once __DIR__ . '/db.php';

$pageTitle = 'Events from Database - KhanEvent';
$events    = [];
$categories = [];
$fetchError = null;
$selectedCategory = isset($_GET['category']) ? trim($_GET['category']) : '';

try {
    // Fetch distinct categories for the filter dropdown
    $catStmt = $pdo->query('SELECT DISTINCT category FROM events ORDER BY category ASC');
    $categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

    // Fetch events (with optional category filter)
    if ($selectedCategory !== '') {
        $stmt = $pdo->prepare('
            SELECT id, name, category, event_date, location, price, image
            FROM events
            WHERE category = ?
            ORDER BY event_date ASC, name ASC
        ');
        $stmt->execute([$selectedCategory]);
        $events = $stmt->fetchAll();
    } else {
        $stmt = $pdo->query('
            SELECT id, name, category, event_date, location, price, image
            FROM events
            ORDER BY event_date ASC, name ASC
        ');
        $events = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $fetchError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 2rem; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { background: #4f46e5; color: #fff; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; }
        .header h1 { margin: 0; font-size: 1.75rem; }
        .header p { margin: 0.25rem 0 0; opacity: 0.9; }
        .btn { display: inline-block; background: #4f46e5; color: #fff; text-decoration: none;
               padding: 0.6rem 1.25rem; border-radius: 6px; font-weight: 600; margin-right: 0.5rem; }
        .btn:hover { background: #4338ca; }
        .btn-secondary { background: #6b7280; }
        .btn-secondary:hover { background: #4b5563; }
        .error { background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; border: 1px solid #fecaca; }
        .filter-bar { display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center; background: #fff; padding: 1rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .filter-bar label { font-weight: 600; color: #374151; font-size: 0.95rem; }
        .filter-bar select { padding: 0.6rem 1rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem; min-width: 200px; }
        .filter-bar button { background: #4f46e5; color: #fff; border: none; padding: 0.6rem 1.25rem; border-radius: 6px; font-size: 1rem; cursor: pointer; font-weight: 600; }
        .filter-bar button:hover { background: #4338ca; }
        .filter-bar .btn-reset { background: #6b7280; text-decoration: none; }
        .filter-bar .btn-reset:hover { background: #4b5563; }
        .event-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem; }
        .event-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.25rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .event-card h3 { margin: 0 0 0.5rem; color: #1f2937; font-size: 1.25rem; }
        .event-card .category { display: inline-block; background: #e0e7ff; color: #3730a3; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.75rem; }
        .event-card .price { font-size: 1.25rem; font-weight: 700; color: #15803d; margin-bottom: 0.5rem; }
        .event-card .meta { color: #6b7280; font-size: 0.9rem; margin-bottom: 1rem; }
        .event-card .btn-book { background: #4f46e5; color: #fff; border: none; padding: 0.6rem 1rem; border-radius: 6px; font-size: 0.95rem; cursor: pointer; font-weight: 600; width: 100%; }
        .event-card .btn-book:hover { background: #4338ca; }
        .empty-state { text-align: center; padding: 3rem; background: #fff; border-radius: 8px; color: #6b7280; }
        .bottom-actions { margin-top: 2rem; text-align: center; }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <h1>KhanEvent &middot; Events from Database</h1>
            <p>Events are fetched live from the MySQL <code>event_platform</code> database using PDO.</p>
        </div>

        <!-- Category Filter + Reset -->
        <div class="filter-bar">
            <form method="GET" action="events_db.php" style="margin:0; flex:1;">
                <label for="category">Filter by Category:</label>
                <select id="category" name="category" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>" <?= ($selectedCategory === $cat) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <a href="events_db.php" class="btn btn-reset" style="text-decoration:none;">Reset Filter</a>
        </div>

        <?php if ($fetchError): ?>
            <div class="error">
                <strong>Could not load events from database:</strong><br>
                <?= htmlspecialchars($fetchError, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (empty($events)): ?>
            <div class="empty-state">
                <p>No events found in the database.</p>
            </div>
        <?php else: ?>
            <p style="margin-bottom: 1rem;"><strong><?= count($events) ?> event(s) loaded from MySQL</strong></p>
            <div class="event-grid">
                <?php foreach ($events as $event): ?>
                    <div class="event-card">
                        <h3><?= htmlspecialchars($event['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <span class="category"><?= htmlspecialchars($event['category'], ENT_QUOTES, 'UTF-8') ?></span>
                        <p class="price">₹<?= number_format($event['price']) ?></p>
                        <p class="meta">
                            <strong>Date:</strong> <?= htmlspecialchars($event['event_date'], ENT_QUOTES, 'UTF-8') ?><br>
                            <strong>Location:</strong> <?= htmlspecialchars($event['location'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <a href="php_backend/process_booking.php?event_name=<?= urlencode($event['name']) ?>
                           &amp;event_date=<?= urlencode($event['event_date']) ?>
                           &amp;event_location=<?= urlencode($event['location']) ?>
                           &amp;unit_price=<?= (float)$event['price'] ?>
                           &amp;event_id=<?= $event['id'] ?>" class="btn-book">Book Now</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Back to Event Platform button at the bottom -->
        <div class="bottom-actions">
            <a href="index.html" class="btn">&#8592; Back to Event Platform</a>
            <a href="view_bookings.php" class="btn btn-secondary">View Booking Records</a>
        </div>
    </div>

</body>
</html>
