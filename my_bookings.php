<?php
session_start();
if (empty($_SESSION['event_user']['id']) || empty($_SESSION['event_user']['email'])) {
    header('Location: index.html?login=1');
    exit;
}
require_once __DIR__ . '/db.php';
$user = $_SESSION['event_user'];
$stmt = $pdo->prepare('SELECT b.id, b.ticket_quantity, b.status, b.booked_at, e.name AS event_name, e.event_date, e.location, e.price FROM bookings b JOIN events e ON e.id = b.event_id WHERE LOWER(b.email) = LOWER(?) ORDER BY b.booked_at DESC');
$stmt->execute([$user['email']]);
$bookings = $stmt->fetchAll();
function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Bookings · KhanEvent</title><link rel="stylesheet" href="style.css">
  <style>
    body{padding:2rem 1rem}.my-bookings{max-width:1050px;margin:auto}.page-head,.booking-card,.empty{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 5px 18px #1f29370d}.page-head{padding:1.5rem;margin-bottom:1.25rem;display:flex;justify-content:space-between;align-items:center;gap:1rem}.page-head h1{font-size:1.6rem}.page-head p{color:#6b7280;margin-top:.25rem}.booking-card{padding:1.1rem 1.25rem;margin-bottom:.8rem;display:grid;grid-template-columns:1fr auto;gap:.4rem 1rem}.booking-card h2{font-size:1.1rem}.booking-meta{color:#6b7280;font-size:.92rem}.status{align-self:start;padding:.2rem .6rem;border-radius:999px;background:#e0e7ff;color:#3730a3;font-weight:600;font-size:.82rem}.empty{padding:2rem;text-align:center;color:#6b7280}.btn{display:inline-block;text-decoration:none;padding:.65rem 1rem;border-radius:7px;background:#4f46e5;color:white;font-weight:600}.btn-secondary{background:#e5e7eb;color:#374151}@media(max-width:600px){.page-head{align-items:flex-start;flex-direction:column}}
  </style>
</head>
<body><main class="my-bookings">
  <header class="page-head"><div><h1>My Bookings</h1><p>Welcome, <?= h($user['name']) ?>. These are the bookings associated with your account.</p></div><a class="btn btn-secondary" href="index.html">← Back to events</a></header>
  <?php if (!$bookings): ?><div class="empty">You don't have any bookings yet. <a href="index.html">Explore events</a> to book your first one.</div>
  <?php else: foreach ($bookings as $booking): ?><article class="booking-card"><div><h2><?= h($booking['event_name']) ?></h2><p class="booking-meta">Date: <?= h($booking['event_date']) ?> · <?= h($booking['location']) ?></p><p class="booking-meta"><?= (int)$booking['ticket_quantity'] ?> ticket(s) · ₹<?= number_format((float)$booking['price'] * (int)$booking['ticket_quantity'], 2) ?> · Booked <?= h($booking['booked_at']) ?></p></div><span class="status"><?= h($booking['status']) ?></span></article><?php endforeach; endif; ?>
</main></body></html>
