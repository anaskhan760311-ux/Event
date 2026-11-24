<?php
/**
 * view_bookings.php - Display booking records from the MySQL database.
 *
 * Signed-in user's booking management page
 *   - Search bookings by customer name or email (GET ?search=)
 *   - Filter by booking status (GET ?status=)
 *   - Cancel a booking (POST ?action=cancel&id=)
 *   - Update booking status (POST ?action=update_status&id=)
 *   - All filtering happens in MySQL via PDO prepared statements.
 */

session_start();
if (empty($_SESSION['event_user']['id']) || empty($_SESSION['event_user']['email'])) {
    header('Location: index.html?login=1');
    exit;
}
require_once __DIR__ . '/db.php';
$currentUser = $_SESSION['event_user'];

$bookings = [];
$statuses = [];
$summary = ['total' => 0, 'confirmed' => 0, 'pending' => 0, 'cancelled' => 0, 'confirmed_amount' => 0];
$fetchError = null;
$search    = isset($_GET['search']) ? trim($_GET['search']) : '';
$status    = isset($_GET['status']) ? trim($_GET['status']) : '';
$perPage   = 6;
$page      = max(1, (int)($_GET['page'] ?? 1));
$totalBookings = 0;
$totalPages = 1;

// ---------------------------------------------------------
// Handle POST actions: cancel or update booking status
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? trim($_POST['action']) : '';
    $id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    // Preserve the current listing filters when the form submits.
    $search = isset($_POST['search']) ? trim($_POST['search']) : $search;
    $status = isset($_POST['filter_status']) ? trim($_POST['filter_status']) : $status;
    $page = max(1, (int)($_POST['page'] ?? $page));

    try {
        if ($action === 'cancel' && $id > 0) {
            $stmt = $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ? AND LOWER(email) = LOWER(?) AND status != ?');
            $stmt->execute(['Cancelled', $id, $currentUser['email'], 'Cancelled']);
            if ($stmt->rowCount() > 0) {
                $msg = 'Booking #' . $id . ' cancelled successfully.';
            } else {
                $msg = 'Booking #' . $id . ' not found or already cancelled.';
            }
        } elseif ($action === 'edit_booking' && $id > 0) {
            $name = $currentUser['name'];
            $email = $currentUser['email'];
            $quantity = isset($_POST['ticket_quantity']) ? (int)$_POST['ticket_quantity'] : 0;
            $newStatus = isset($_POST['status']) ? trim($_POST['status']) : '';
            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $quantity < 1 ||
                !in_array($newStatus, ['Pending', 'Confirmed', 'Cancelled'], true)) {
                $msg = 'Please provide a name, valid email, ticket quantity greater than zero, and valid status.';
            } else {
                $stmt = $pdo->prepare('UPDATE bookings SET customer_name = ?, ticket_quantity = ?, status = ? WHERE id = ? AND LOWER(email) = LOWER(?)');
                $stmt->execute([$name, $quantity, $newStatus, $id, $currentUser['email']]);
                $msg = 'Booking #' . $id . ' saved successfully.';
            }
        } else {
            $msg = 'Invalid action.';
        }
    } catch (PDOException $e) {
        $msg = 'Database error: ' . $e->getMessage();
    }

    // Redirect back to preserve GET params and avoid form resubmission
    $query = http_build_query(array_filter(['search' => $search, 'status' => $status, 'page' => $page, 'msg' => $msg]));
    header('Location: view_bookings.php?' . $query);
    exit;
}

// ---------------------------------------------------------
// Fetch bookings with server-side search and status filter
// ---------------------------------------------------------
try {
    $sql = '
        SELECT b.id,
               b.customer_name,
               b.email,
               b.event_id,
               b.ticket_quantity,
               b.status,
               b.booked_at,
               e.name AS event_name,
               e.event_date AS event_date,
               e.location AS event_location,
               e.price AS ticket_price
        FROM bookings b
        LEFT JOIN events e ON e.id = b.event_id
        WHERE LOWER(b.email) = LOWER(?)
    ';
    $params = [$currentUser['email']];

    if ($search !== '') {
        $sql .= ' AND (b.customer_name LIKE ? OR b.email LIKE ?)';
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
    }

    if ($status !== '' && $status !== 'All') {
        $sql .= ' AND b.status = ?';
        $params[] = $status;
    }

    $countSql = 'SELECT COUNT(*) FROM bookings b WHERE LOWER(b.email) = LOWER(?)';
    $countParams = [$currentUser['email']];
    if ($search !== '') {
        $countSql .= ' AND (b.customer_name LIKE ? OR b.email LIKE ?)';
        $like = '%' . $search . '%';
        $countParams[] = $like;
        $countParams[] = $like;
    }
    if ($status !== '' && $status !== 'All') {
        $countSql .= ' AND b.status = ?';
        $countParams[] = $status;
    }
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($countParams);
    $totalBookings = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalBookings / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;
    $sql .= ' ORDER BY b.booked_at DESC, b.id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();

    // Summary cards include only bookings associated with the signed-in account.
    $summaryStmt = $pdo->prepare("
        SELECT COUNT(*) AS total,
               COALESCE(SUM(status = 'Confirmed'), 0) AS confirmed,
               COALESCE(SUM(status = 'Pending'), 0) AS pending,
               COALESCE(SUM(status = 'Cancelled'), 0) AS cancelled,
               COALESCE(SUM(CASE WHEN b.status = 'Confirmed' THEN b.ticket_quantity * COALESCE(e.price, 0) ELSE 0 END), 0) AS confirmed_amount
        FROM bookings b
        LEFT JOIN events e ON e.id = b.event_id
        WHERE LOWER(b.email) = LOWER(?)
    ");
    $summaryStmt->execute([$currentUser['email']]);
    $summary = $summaryStmt->fetch() ?: $summary;

    // Fetch distinct statuses for the filter dropdown
    $statusStmt = $pdo->prepare("SELECT DISTINCT status FROM bookings WHERE LOWER(email) = LOWER(?) ORDER BY status");
    $statusStmt->execute([$currentUser['email']]);
    $statuses = $statusStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $fetchError = $e->getMessage();
    $statuses   = [];
}

// Display message after redirect
$msg = isset($_GET['msg']) ? trim($_GET['msg']) : null;
$pageUrl = function (int $targetPage) use ($search, $status): string {
    $query = http_build_query(array_filter(['search' => $search, 'status' => $status, 'page' => $targetPage], static fn($value) => $value !== ''));
    return 'view_bookings.php' . ($query !== '' ? '?' . $query : '');
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Records - KhanEvent</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 2rem; }
        .container { max-width: 1100px; margin: 0 auto; }
        .header { background: #4f46e5; color: #fff; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; }
        .header h1 { margin: 0; font-size: 1.75rem; }
        .header p { margin: 0.25rem 0 0; opacity: 0.9; }
        .btn { display: inline-block; background: #4f46e5; color: #fff; text-decoration: none;
               padding: 0.6rem 1.25rem; border-radius: 6px; font-weight: 600; margin-right: 0.5rem; }
        .btn:hover { background: #4338ca; }
        .btn-secondary { background: #6b7280; }
        .btn-secondary:hover { background: #4b5563; }
        .error { background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; border: 1px solid #fecaca; }
        .success-msg { background: #dcfce7; color: #166534; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; border: 1px solid #bbf7d0; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        th, td { text-align: left; padding: 0.85rem 1rem; border-bottom: 1px solid #e5e7eb; }
        th { background: #4f46e5; color: #fff; font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #f3f4f6; }
        .badge { display: inline-block; padding: 0.15rem 0.6rem; border-radius: 999px; font-size: 0.8rem; font-weight: 700; }
        .badge-confirmed { background: #dcfce7; color: #166534; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .empty-state { text-align: center; padding: 3rem; background: #fff; border-radius: 8px; color: #6b7280; }
        .meta { color: #6b7280; font-size: 0.9rem; }
        .filter-bar { display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center; background: #fff; padding: 1rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .filter-bar label { font-weight: 600; color: #374151; font-size: 0.95rem; margin-bottom: 0; }
        .filter-bar input, .filter-bar select { padding: 0.6rem 1rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem; }
        .filter-bar button { background: #4f46e5; color: #fff; border: none; padding: 0.6rem 1.25rem; border-radius: 6px; font-size: 1rem; cursor: pointer; font-weight: 600; }
        .filter-bar button:hover { background: #4338ca; }
        .filter-bar .btn-reset { background: #6b7280; text-decoration: none; }
        .filter-bar .btn-reset:hover { background: #4b5563; }
        .btn-danger { background: #dc2626; color: #fff; border: none; padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.85rem; cursor: pointer; font-weight: 600; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-sm { padding: 0.35rem 0.7rem; font-size: 0.85rem; margin-left: 0.3rem; }
        .status-form { display: flex; align-items: center; justify-content: flex-end; gap: 0.35rem; }
        .status-form select { padding: 0.35rem 0.6rem; font-size: 0.85rem; border: 1px solid #d1d5db; border-radius: 4px; }
        .status-form button { padding: 0.35rem 0.7rem; font-size: 0.85rem; margin-left: 0; }
        .edit-modal { display: none; position: fixed; inset: 0; z-index: 1000; background: rgba(17,24,39,0.6); align-items: center; justify-content: center; padding: 1rem; }
        .edit-modal.is-open { display: flex; }
        .edit-modal-content { background: #fff; width: min(100%, 460px); padding: 1.5rem; border-radius: 10px; box-shadow: 0 12px 35px rgba(0,0,0,.2); }
        .edit-modal-content h2 { margin: 0 0 1rem; }
        .edit-modal-content label { display: block; margin: .85rem 0 .3rem; font-weight: 600; }
        .edit-modal-content input, .edit-modal-content select { width: 100%; box-sizing: border-box; padding: .65rem .75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem; }
        .edit-total { margin-top: 1rem; padding: .85rem; border-radius: 6px; background: #eef2ff; color: #312e81; font-weight: 700; display: flex; justify-content: space-between; }
        .edit-modal-actions { display: flex; justify-content: flex-end; gap: .6rem; margin-top: 1.25rem; }
        .edit-modal-actions button { border: 0; cursor: pointer; }
        .pagination { display:flex; align-items:center; justify-content:center; gap:.75rem; margin:1rem 0 1.5rem; }
        .pagination .disabled { opacity:.45; pointer-events:none; }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <h1>KhanEvent &middot; Booking Records</h1>
            <p>Booking records stored in the MySQL <code>event_platform</code> database.</p>
        </div>

        <div style="margin-bottom: 1rem;">
            <a href="index.html" class="btn">&#8592; Back to Event Platform</a>
            <a href="events_db.php" class="btn btn-secondary">View Events</a>
        </div>

        <div class="booking-stats-bar">
            <div class="booking-stat-pill">
                <span class="stat-label">Total Bookings</span>
                <span class="stat-value"><?= (int)$summary['total'] ?></span>
            </div>
            <div class="booking-stat-pill confirmed">
                <span class="stat-label">Confirmed Bookings</span>
                <span class="stat-value"><?= (int)$summary['confirmed'] ?></span>
            </div>
            <div class="booking-stat-pill pending">
                <span class="stat-label">Pending Bookings</span>
                <span class="stat-value"><?= (int)$summary['pending'] ?></span>
            </div>
            <div class="booking-stat-pill cancelled">
                <span class="stat-label">Cancelled Bookings</span>
                <span class="stat-value"><?= (int)$summary['cancelled'] ?></span>
            </div>
            <div class="booking-stat-pill amount">
                <span class="stat-label">Total Booking Amount</span>
                <span class="stat-value">₹<?= number_format((float)$summary['confirmed_amount'], 0, '.', ',') ?></span>
            </div>
        </div>

        <?php if ($msg): ?>
            <div class="success-msg"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($fetchError): ?>
            <div class="error">
                <strong>Could not load bookings from database:</strong><br>
                <?= htmlspecialchars($fetchError, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <!-- Server-side Search + Status Filter -->
        <div class="filter-bar">
            <form method="GET" action="view_bookings.php" style="margin:0; flex:1; min-width: 260px;">
                <label for="search">Search:</label>
                <input type="text" id="search" name="search" placeholder="Customer name or email..."
                       value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit">Search</button>
                <a href="view_bookings.php" class="btn btn-reset" style="text-decoration:none; margin-left:0.5rem;">Reset</a>
            </form>

            <form method="GET" action="view_bookings.php" style="margin:0;">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                <label for="status">Status:</label>
                <select id="status" name="status" onchange="this.form.submit()">
                    <option value="All" <?= ($status === '' || $status === 'All') ? 'selected' : '' ?>>All Statuses</option>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>" <?= ($status === $s) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if (empty($bookings)): ?>
            <div class="empty-state">
                <p>No booking records found.</p>
                <?php if ($search !== '' || $status !== ''): ?>
                    <p class="meta">Try adjusting your search or status filter.</p>
                <?php else: ?>
                    <p class="meta">Submit a booking from an event page to create your first record.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p style="margin-bottom: 1rem;"><strong><?= $totalBookings ?> booking record(s) · Page <?= $page ?> of <?= $totalPages ?></strong></p>
            <table>
                <thead>
                    <tr>
                        <th>Booking #</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Event</th>
                        <th>Tickets</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Update Status</th>
                        <th>Actions</th>
                        <th>Booked At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $b): ?>
                        <?php
                        $isCancelled = ($b['status'] ?? '') === 'Cancelled';
                        $statusClass = 'badge-' . strtolower((string)($b['status'] ?? 'pending'));
                        $badgeHtml = '<span class="badge ' . htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') . '">' .
                            htmlspecialchars((string)($b['status'] ?? 'Pending'), ENT_QUOTES, 'UTF-8') . '</span>';
                        ?>
                        <tr data-booking-id="<?= (int)$b['id'] ?>" data-booking-status="<?= htmlspecialchars((string)($b['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <td>#<?= (int)$b['id'] ?></td>
                            <td><?= htmlspecialchars($b['customer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="meta"><?= htmlspecialchars($b['email'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <div><?= $b['event_name'] ? htmlspecialchars($b['event_name'], ENT_QUOTES, 'UTF-8') : 'Unknown Event' ?></div>
                                <div class="meta"><?= $b['event_date'] ? htmlspecialchars($b['event_date'], ENT_QUOTES, 'UTF-8') : '' ?> &middot; <?= $b['event_location'] ? htmlspecialchars($b['event_location'], ENT_QUOTES, 'UTF-8') : '' ?></div>
                            </td>
                            <td><?= (int)$b['ticket_quantity'] ?></td>
                            <td>₹<?= number_format((float)$b['ticket_price'] * (int)$b['ticket_quantity'], 2, '.', ',') ?></td>
                            <td><?= $badgeHtml ?></td>
                            <td><button type="button" class="btn-sm btn-secondary" onclick="openBookingEditor(this)" data-id="<?= (int)$b['id'] ?>" data-name="<?= htmlspecialchars($b['customer_name'], ENT_QUOTES, 'UTF-8') ?>" data-email="<?= htmlspecialchars($b['email'], ENT_QUOTES, 'UTF-8') ?>" data-quantity="<?= (int)$b['ticket_quantity'] ?>" data-price="<?= (float)$b['ticket_price'] ?>" data-status="<?= htmlspecialchars($b['status'], ENT_QUOTES, 'UTF-8') ?>">Edit</button></td>
                            <td>
                                <?php if (!$isCancelled): ?>
                                    <form method="POST" action="view_bookings.php" style="display:inline;" onsubmit="return confirm('Cancel booking #<?= (int)$b['id'] ?>?');">
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                        <input type="hidden" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="filter_status" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="page" value="<?= $page ?>">
                                        <button type="submit" class="btn-danger btn-sm">Cancel</button>
                                    </form>
                                <?php else: ?>
                                    <span class="btn-danger btn-sm" disabled style="opacity:0.6;">Cancelled</span>
                                <?php endif; ?>
                            </td>
                            <td class="meta"><?= $b['booked_at'] ? htmlspecialchars($b['booked_at'], ENT_QUOTES, 'UTF-8') : '' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($totalPages > 1): ?><nav class="pagination" aria-label="Booking records pages">
                <a class="btn btn-secondary <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= htmlspecialchars($pageUrl(max(1, $page - 1)), ENT_QUOTES, 'UTF-8') ?>" <?= $page <= 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>>Previous</a>
                <span>Page <?= $page ?> of <?= $totalPages ?></span>
                <a class="btn btn-secondary <?= $page >= $totalPages ? 'disabled' : '' ?>" href="<?= htmlspecialchars($pageUrl(min($totalPages, $page + 1)), ENT_QUOTES, 'UTF-8') ?>" <?= $page >= $totalPages ? 'aria-disabled="true" tabindex="-1"' : '' ?>>Next</a>
            </nav><?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="edit-modal" id="bookingEditModal" role="dialog" aria-modal="true" aria-labelledby="bookingEditTitle" onclick="if(event.target===this) closeBookingEditor()">
        <div class="edit-modal-content">
            <h2 id="bookingEditTitle">Edit Booking Details</h2>
            <form method="POST" action="view_bookings.php">
                <input type="hidden" name="action" value="edit_booking">
                <input type="hidden" name="id" id="editBookingId">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="filter_status" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="page" value="<?= $page ?>">
                <label for="editCustomerName">Customer name</label>
                <input id="editCustomerName" name="customer_name" required>
                <label for="editCustomerEmail">Email</label>
                <input id="editCustomerEmail" name="email" type="email" required>
                <label for="editTicketQuantity">Number of tickets</label>
                <input id="editTicketQuantity" name="ticket_quantity" type="number" min="1" required>
                <div class="edit-total"><span>Total amount</span><span>₹<span id="editBookingTotal">0.00</span></span></div>
                <label for="editBookingStatus">Booking status</label>
                <select id="editBookingStatus" name="status" required>
                    <option value="Pending">Pending</option>
                    <option value="Confirmed">Confirmed</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
                <div class="edit-modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeBookingEditor()">Close</button>
                    <button type="submit" class="btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        let editTicketPrice = 0;
        const editQuantityInput = document.getElementById('editTicketQuantity');
        function updateBookingTotal() {
            const quantity = Math.max(0, parseInt(editQuantityInput.value, 10) || 0);
            document.getElementById('editBookingTotal').textContent = (editTicketPrice * quantity).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
        editQuantityInput.addEventListener('input', updateBookingTotal);
        function openBookingEditor(button) {
            document.getElementById('editBookingId').value = button.dataset.id;
            document.getElementById('editCustomerName').value = button.dataset.name;
            document.getElementById('editCustomerEmail').value = button.dataset.email;
            document.getElementById('editTicketQuantity').value = button.dataset.quantity;
            editTicketPrice = parseFloat(button.dataset.price) || 0;
            document.getElementById('editBookingStatus').value = button.dataset.status;
            updateBookingTotal();
            document.getElementById('bookingEditModal').classList.add('is-open');
            document.getElementById('editCustomerName').focus();
        }
        function closeBookingEditor() {
            document.getElementById('bookingEditModal').classList.remove('is-open');
        }
        async function checkForBookingStatusChanges() {
            const editor = document.getElementById('bookingEditModal');
            if (document.visibilityState !== 'visible' || editor.classList.contains('is-open')) return;
            try {
                const response = await fetch('api/get_bookings.php', {cache: 'no-store'});
                if (!response.ok) return;
                const data = await response.json();
                if (!data.success || !Array.isArray(data.bookings)) return;
                const statusById = new Map(data.bookings.map(booking => [String(booking.id), booking.status]));
                const changed = Array.from(document.querySelectorAll('tr[data-booking-id]')).some(row =>
                    statusById.has(row.dataset.bookingId) && statusById.get(row.dataset.bookingId) !== row.dataset.bookingStatus
                );
                if (changed) window.location.reload();
            } catch (error) {
                console.warn('Could not check booking status updates:', error);
            }
        }
        window.setInterval(checkForBookingStatusChanges, 5000);
        window.addEventListener('focus', checkForBookingStatusChanges);
    </script>

</body>
</html>
