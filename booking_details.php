<?php
require_once __DIR__ . '/require_admin.php';
$bookingId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$bookingId) {
    http_response_code(400);
    $booking = null;
} else {
    $stmt = $pdo->prepare('SELECT b.id, b.customer_name, b.email, b.ticket_quantity, b.status, b.booked_at, e.name AS event_name, e.event_date, e.location, e.price AS ticket_price FROM bookings b LEFT JOIN events e ON e.id = b.event_id WHERE b.id = ?');
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();
    if (!$booking) http_response_code(404);
}
function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Booking Details · KhanEvent</title><link rel="stylesheet" href="style.css"><style>
body{padding:2rem 1rem}.details-wrap{max-width:760px;margin:auto}.details-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:1.6rem;box-shadow:0 8px 24px #1f29370d}.details-head{display:flex;justify-content:space-between;align-items:center;gap:1rem;margin-bottom:1.2rem}.details-head h1{font-size:1.5rem}.details-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}.detail{padding:.85rem;background:#f9fafb;border-radius:8px}.detail span{display:block;color:#6b7280;font-size:.82rem;margin-bottom:.2rem}.detail strong{overflow-wrap:anywhere}.actions{display:flex;gap:.6rem;margin-top:1.2rem;flex-wrap:wrap}.btn{display:inline-block;text-decoration:none;padding:.65rem .9rem;border-radius:7px;background:#4f46e5;color:#fff;font-weight:600}.btn.secondary{background:#e5e7eb;color:#374151}.status{display:inline-block;padding:.25rem .65rem;border-radius:99px;background:#e0e7ff;color:#3730a3}@media(max-width:520px){.details-head{align-items:flex-start;flex-direction:column}.details-grid{grid-template-columns:1fr}}
</style></head><body><main class="details-wrap"><article class="details-card">
<div class="details-head"><h1>Booking Details<?= $booking ? ' · #' . (int)$booking['id'] : '' ?></h1><span class="status"><?= $booking ? h($booking['status']) : 'Unavailable' ?></span></div>
<?php if (!$booking): ?><p>Booking not found.</p><?php else: ?><div class="details-grid">
  <div class="detail"><span>Customer</span><strong><?= h($booking['customer_name']) ?></strong></div>
  <div class="detail"><span>Email</span><strong><?= h($booking['email']) ?></strong></div>
  <div class="detail"><span>Event</span><strong><?= h($booking['event_name'] ?? 'Event unavailable') ?></strong></div>
  <div class="detail"><span>Event date</span><strong><?= h($booking['event_date'] ?? '') ?></strong></div>
  <div class="detail"><span>Location</span><strong><?= h($booking['location'] ?? '') ?></strong></div>
  <div class="detail"><span>Booking date</span><strong><?= h($booking['booked_at']) ?></strong></div>
  <div class="detail"><span>Tickets</span><strong><?= (int)$booking['ticket_quantity'] ?></strong></div>
  <div class="detail"><span>Total booking amount</span><strong>₹<?= number_format((float)($booking['ticket_price'] ?? 0) * (int)$booking['ticket_quantity'], 2) ?></strong></div>
</div><?php endif; ?>
<div class="actions"><a class="btn" href="admin_dashboard.php">Back to dashboard</a><a class="btn secondary" href="logout.php">Logout</a></div>
</article></main></body></html>
