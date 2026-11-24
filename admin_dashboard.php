<?php
require_once __DIR__ . '/require_admin.php';
require_once __DIR__ . '/db.php';

if (empty($_SESSION['admin_dashboard_csrf'])) {
    $_SESSION['admin_dashboard_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['admin_dashboard_csrf'];
$message = $_SESSION['admin_dashboard_notice'] ?? '';
unset($_SESSION['admin_dashboard_notice']);
$search = trim((string)($_GET['search'] ?? ''));
$statusFilter = (string)($_GET['status'] ?? '');
if (!in_array($statusFilter, ['', 'Pending', 'Confirmed', 'Cancelled'], true)) $statusFilter = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals($csrf, $postedToken)) {
        http_response_code(400);
        $message = 'The form expired. Refresh the page and try again.';
    } else {
        $bookingId = filter_var($_POST['booking_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $newStatus = (string)($_POST['new_status'] ?? '');
        if (!$bookingId || !in_array($newStatus, ['Pending', 'Confirmed', 'Cancelled'], true)) {
            $message = 'Select a valid booking and status.';
        } else {
            $update = $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?');
            $update->execute([$newStatus, $bookingId]);
            $message = $update->rowCount() ? 'Booking status updated.' : 'Booking status is unchanged or the booking was not found.';
        }
    }
    if (http_response_code() !== 400) {
        $_SESSION['admin_dashboard_notice'] = $message;
        $query = http_build_query(['search' => $search, 'status' => $statusFilter]);
        header('Location: admin_dashboard.php' . ($query !== '' ? '?' . $query : ''));
        exit;
    }
}

$stats = [
    'events' => (int)$pdo->query('SELECT COUNT(*) FROM events')->fetchColumn(),
    'users' => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'bookings' => 0,
    'confirmed' => 0,
];
$accountBookingCount = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE LOWER(email) = LOWER(?)');
$accountBookingCount->execute([$_SESSION['event_user']['email']]);
$stats['bookings'] = (int)$accountBookingCount->fetchColumn();
$accountConfirmedCount = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE LOWER(email) = LOWER(?) AND status = 'Confirmed'");
$accountConfirmedCount->execute([$_SESSION['event_user']['email']]);
$stats['confirmed'] = (int)$accountConfirmedCount->fetchColumn();
$users = $pdo->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC, id DESC')->fetchAll();

$sql = 'SELECT b.id, b.customer_name, b.email, b.ticket_quantity, b.status, b.booked_at,
               e.name AS event_name, e.event_date, e.location, e.price AS ticket_price
        FROM bookings b LEFT JOIN events e ON e.id = b.event_id
        WHERE LOWER(b.email) = LOWER(?)';
$params = [$_SESSION['event_user']['email']];
if ($search !== '') {
    $sql .= ' AND b.customer_name LIKE ?';
    $params[] = '%' . $search . '%';
}
if ($statusFilter !== '') {
    $sql .= ' AND b.status = ?';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY b.booked_at DESC, b.id DESC';
$bookingQuery = $pdo->prepare($sql);
$bookingQuery->execute($params);
$bookings = $bookingQuery->fetchAll();

function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Dashboard · KhanEvent</title><link rel="stylesheet" href="style.css">
  <style>
    body{padding:2rem 1rem}.admin-dashboard{max-width:1200px;margin:auto}.dash-head,.panel,.stat-card,.table-wrap{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 5px 18px #1f29370d}.dash-head{padding:1.5rem;margin-bottom:1.25rem;display:flex;align-items:center;justify-content:space-between;gap:1rem}.dash-head h1{font-size:1.65rem}.dash-head p{color:#6b7280}.head-actions{display:flex;gap:.5rem;flex-wrap:wrap}.dash-btn{display:inline-block;text-decoration:none;border:0;border-radius:7px;padding:.62rem .9rem;background:#4f46e5;color:#fff;font-weight:600;cursor:pointer}.dash-btn.secondary{background:#e5e7eb;color:#374151}.stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem;margin-bottom:1.25rem}.stat-card{padding:1.15rem}.stat-card span{display:block;color:#6b7280;font-size:.9rem}.stat-card strong{font-size:1.8rem;color:#1f2937}.panel{padding:1.25rem;margin-bottom:1.25rem}.panel h2{font-size:1.2rem;margin-bottom:.9rem}.table-wrap{overflow-x:auto}.data-table{border-collapse:collapse;width:100%;min-width:700px}.data-table th,.data-table td{padding:.75rem .9rem;text-align:left;border-bottom:1px solid #e5e7eb;vertical-align:middle}.data-table th{background:#f9fafb;color:#6b7280;font-size:.8rem;text-transform:uppercase}.data-table tr:last-child td{border-bottom:0}.filters{display:flex;gap:.6rem;align-items:end;flex-wrap:wrap;margin-bottom:.9rem}.filters label{display:block;font-size:.85rem;font-weight:600;margin-bottom:.25rem}.filters input,.filters select,.status-form select{padding:.58rem .7rem;border:1px solid #d1d5db;border-radius:6px;font:inherit}.filters input{min-width:240px}.status-form{display:flex;gap:.4rem;align-items:center}.status-form select{max-width:130px}.notice{padding:.75rem 1rem;background:#eff6ff;color:#1e40af;border-radius:7px;margin-bottom:1rem}.empty{text-align:center;color:#6b7280;padding:1.5rem}.muted{color:#6b7280;font-size:.88rem}@media(max-width:760px){.stats{grid-template-columns:repeat(2,minmax(0,1fr))}.dash-head{align-items:flex-start;flex-direction:column}}@media(max-width:420px){.stats{grid-template-columns:1fr}.filters input{min-width:0;width:100%}}
    .pagination{display:flex;align-items:center;justify-content:center;gap:.9rem;margin-top:1rem}.pagination .dash-btn:disabled{opacity:.45;cursor:not-allowed}.data-table{min-width:1120px}
  </style>
</head>
<body><main class="admin-dashboard">
  <header class="dash-head"><div><h1>Admin Dashboard</h1><p>Welcome, <?= h($_SESSION['event_user']['name'] ?? '') ?>. Live totals from MySQL.</p></div><div class="head-actions"><a class="dash-btn secondary" href="index.html">Back to site</a><a class="dash-btn" href="admin_events.php">Manage events</a><a class="dash-btn secondary" href="logout.php">Logout</a></div></header>
  <?php if ($message !== ''): ?><div class="notice" role="status"><?= h($message) ?></div><?php endif; ?>
  <section class="stats" aria-label="Event platform statistics">
    <article class="stat-card"><span>Total events</span><strong id="statEvents"><?= $stats['events'] ?></strong></article>
    <article class="stat-card"><span>Registered users</span><strong id="statUsers"><?= $stats['users'] ?></strong></article>
    <article class="stat-card"><span>Total bookings</span><strong id="statBookings"><?= $stats['bookings'] ?></strong></article>
    <article class="stat-card"><span>Confirmed bookings</span><strong id="statConfirmed"><?= $stats['confirmed'] ?></strong></article>
  </section>
  <section class="panel" aria-labelledby="users-heading"><h2 id="users-heading">Registered users (<?= count($users) ?>)</h2><div class="table-wrap"><table class="data-table"><thead><tr><th>Name</th><th>Email</th><th>Registered</th><th>Role</th><th>Access</th></tr></thead><tbody>
    <?php if (!$users): ?><tr><td class="empty" colspan="5">No registered users found.</td></tr><?php endif; ?>
    <?php foreach ($users as $user): ?><tr><td><?= h($user['name']) ?></td><td><?= h($user['email']) ?></td><td><?= h($user['created_at']) ?></td><td><?= h(ucfirst($user['role'])) ?></td><td><form class="status-form" data-update-role><input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>"><select name="role" aria-label="Role for <?= h($user['name']) ?>"><option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option><option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option></select><button class="dash-btn" type="submit">Save</button></form></td></tr><?php endforeach; ?>
  </tbody></table></div></section>
  <section class="panel" aria-labelledby="bookings-heading"><h2 id="bookings-heading">Bookings for this account (<span id="bookingCount"><?= count($bookings) ?></span>)</h2>
    <form class="filters" id="bookingFilters" method="get" action="admin_dashboard.php"><div><label for="search">Search customer name</label><input id="search" name="search" value="<?= h($search) ?>" placeholder="Enter a customer name" autocomplete="off"></div><div><label for="status">Status</label><select id="status" name="status"><option value="">All statuses</option><option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option><option value="Confirmed" <?= $statusFilter === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option><option value="Cancelled" <?= $statusFilter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option></select></div><a class="dash-btn secondary" href="admin_dashboard.php">Clear</a></form>
    <div class="table-wrap"><table class="data-table"><thead><tr><th>Customer</th><th>Email</th><th>Event</th><th>Event date</th><th>Booking date</th><th>Tickets</th><th>Total amount</th><th>Status</th><th>Details</th><th>Update status</th></tr></thead><tbody id="bookingRows">
      <?php if (!$bookings): ?><tr><td class="empty" colspan="10">No bookings match these filters.</td></tr><?php endif; ?>
      <?php foreach ($bookings as $booking): ?><tr><td><?= h($booking['customer_name']) ?></td><td><?= h($booking['email']) ?></td><td><?= h($booking['event_name'] ?? 'Event unavailable') ?></td><td><?= h($booking['event_date'] ?? '') ?></td><td><?= h($booking['booked_at']) ?></td><td><?= (int)$booking['ticket_quantity'] ?></td><td>₹<?= number_format((float)($booking['ticket_price'] ?? 0) * (int)$booking['ticket_quantity'], 2) ?></td><td><?= h($booking['status']) ?></td><td><a class="dash-btn secondary" href="booking_details.php?id=<?= (int)$booking['id'] ?>">View</a></td><td><form class="status-form" method="post" action="admin_dashboard.php?<?= h(http_build_query(['search' => $search, 'status' => $statusFilter])) ?>"><input type="hidden" name="csrf_token" value="<?= h($csrf) ?>"><input type="hidden" name="booking_id" value="<?= (int)$booking['id'] ?>"><select aria-label="New status for booking <?= (int)$booking['id'] ?>" name="new_status"><option value="Pending" <?= $booking['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option><option value="Confirmed" <?= $booking['status'] === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option><option value="Cancelled" <?= $booking['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option></select><button class="dash-btn" type="submit">Save</button></form></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <nav id="bookingPagination" class="pagination" aria-label="Bookings pages"></nav>
  </section>
</main>
<script>
(() => {
  const form = document.getElementById('bookingFilters');
  const search = document.getElementById('search');
  const status = document.getElementById('status');
  const rows = document.getElementById('bookingRows');
  const pagination = document.getElementById('bookingPagination');
  const csrfToken = <?= json_encode($csrf) ?>;
  let timer;
  let currentPage = 1;
  const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
  async function refreshDashboard(page = currentPage) {
    const query = new URLSearchParams({ search: search.value, status: status.value, page: String(page) });
    try {
      const response = await fetch(`api/admin_dashboard_data.php?${query}`, { cache: 'no-store' });
      const data = await response.json();
      if (!response.ok || !data.success) return;
      document.getElementById('statEvents').textContent = data.stats.events;
      document.getElementById('statUsers').textContent = data.stats.users;
      document.getElementById('statBookings').textContent = data.stats.bookings;
      document.getElementById('statConfirmed').textContent = data.stats.confirmed;
      currentPage = data.pagination.page;
      document.getElementById('bookingCount').textContent = data.pagination.total;
      rows.innerHTML = data.bookings.length ? data.bookings.map(booking => `
        <tr><td>${escapeHtml(booking.customer_name)}</td><td>${escapeHtml(booking.email)}</td>
        <td>${escapeHtml(booking.event_name || 'Event unavailable')}</td><td>${escapeHtml(booking.event_date || '')}</td>
        <td>${escapeHtml(booking.booked_at || '')}</td><td>${Number(booking.ticket_quantity) || 0}</td>
        <td>₹${((Number(booking.ticket_price) || 0) * (Number(booking.ticket_quantity) || 0)).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
        <td>${escapeHtml(booking.status)}</td><td><a class="dash-btn secondary" href="booking_details.php?id=${Number(booking.id)}">View</a></td><td>
        <form class="status-form" data-update-booking><input type="hidden" name="booking_id" value="${Number(booking.id)}">
        <select aria-label="New status for booking ${Number(booking.id)}" name="status">
          <option value="Pending" ${booking.status === 'Pending' ? 'selected' : ''}>Pending</option>
          <option value="Confirmed" ${booking.status === 'Confirmed' ? 'selected' : ''}>Confirmed</option>
          <option value="Cancelled" ${booking.status === 'Cancelled' ? 'selected' : ''}>Cancelled</option>
        </select><button class="dash-btn" type="submit">Save</button></form></td></tr>`).join('')
        : '<tr><td class="empty" colspan="10">No bookings match these filters.</td></tr>';
      pagination.innerHTML = data.pagination.pages > 1 ? `
        <button type="button" class="dash-btn secondary" data-page="${currentPage - 1}" ${currentPage <= 1 ? 'disabled' : ''}>Previous</button>
        <span>Page ${currentPage} of ${data.pagination.pages}</span>
        <button type="button" class="dash-btn secondary" data-page="${currentPage + 1}" ${currentPage >= data.pagination.pages ? 'disabled' : ''}>Next</button>` : '';
    } catch (error) {
      console.error('Could not refresh admin dashboard data:', error);
    }
  }
  form.addEventListener('submit', event => { event.preventDefault(); refreshDashboard(); });
  status.addEventListener('change', () => { currentPage = 1; refreshDashboard(1); });
  search.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(() => { currentPage = 1; refreshDashboard(1); }, 350);
  });
  pagination.addEventListener('click', event => {
    const button = event.target.closest('[data-page]');
    if (button && !button.disabled) refreshDashboard(Number(button.dataset.page));
  });
  document.addEventListener('submit', async event => {
    const roleForm = event.target.closest('[data-update-role]');
    if (roleForm) {
      event.preventDefault();
      const button = roleForm.querySelector('button[type="submit"]');
      button.disabled = true;
      try {
        const response = await fetch('api/admin_update_role.php', {
          method: 'POST', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ user_id: roleForm.elements.user_id.value, role: roleForm.elements.role.value, csrf_token: csrfToken })
        });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Could not update user role.');
        window.location.reload();
      } catch (error) {
        alert(error.message || 'Could not update user role.');
      } finally {
        button.disabled = false;
      }
      return;
    }
    const bookingForm = event.target.closest('[data-update-booking]');
    if (!bookingForm) return;
    event.preventDefault();
    const button = bookingForm.querySelector('button[type="submit"]');
    button.disabled = true;
    try {
      const response = await fetch('api/admin_update_booking.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ booking_id: bookingForm.elements.booking_id.value, status: bookingForm.elements.status.value, csrf_token: csrfToken })
      });
      const result = await response.json();
      if (!response.ok || !result.success) throw new Error(result.message || 'Could not update status.');
      refreshDashboard(currentPage);
    } catch (error) {
      alert(error.message || 'Could not update booking status.');
    } finally {
      button.disabled = false;
    }
  });
  window.setInterval(() => { if (document.visibilityState === 'visible') refreshDashboard(); }, 15000);
  refreshDashboard();
})();
</script>
</body></html>
