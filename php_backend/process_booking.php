<?php
/**
 * process_booking.php
 * ---------------------
 * PHP backend for the KhanEvent Event Discovery Platform booking form.
 *
 * Demonstrates PHP basics:
 *   - Receiving form data via the POST method ($_POST)
 *   - Server-side form validation (required fields, email format, quantity > 0)
 *   - Displaying a booking confirmation page on success
 *   - Re-displaying the form with inline error messages on failure
 *
 * URL (served by WAMP/XAMPP):
 *   http://localhost/Events/php_backend/process_booking.php
 */

session_start();
if (empty($_SESSION['event_user']['id']) || empty($_SESSION['event_user']['email'])) {
    header('Location: ../index.html?login=1');
    exit;
}
require_once __DIR__ . '/../db.php';

// Ticket price used for the total calculation (₹ per ticket).
// In a real platform this would come from the event record / database.
define('TICKET_UNIT_PRICE', 1500);

// State variables (used by both the form and the confirmation view)
$name           = htmlspecialchars($_SESSION['event_user']['name'], ENT_QUOTES, 'UTF-8');
$email          = htmlspecialchars($_SESSION['event_user']['email'], ENT_QUOTES, 'UTF-8');
$eventName      = '';
$eventDate      = '';
$eventLocation  = '';
$unitPrice      = TICKET_UNIT_PRICE;
$eventId        = '';
$ticketQty      = '';
$errors         = [];
$success        = false;
$totalAmount    = 0;

// Handle form submission (POST method)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Retrieve and sanitize submitted data.
    // htmlspecialchars() prevents XSS when we echo user input back.
    $name           = htmlspecialchars($_SESSION['event_user']['name'], ENT_QUOTES, 'UTF-8');
    $emailRaw       = $_SESSION['event_user']['email'];
    $email          = htmlspecialchars($emailRaw, ENT_QUOTES, 'UTF-8');
    $eventNameRaw   = trim($_POST['event_name'] ?? '');
    $eventName      = htmlspecialchars($eventNameRaw, ENT_QUOTES, 'UTF-8');
    $eventDate      = htmlspecialchars(trim($_POST['event_date'] ?? ''), ENT_QUOTES, 'UTF-8');
    $eventLocation  = htmlspecialchars(trim($_POST['event_location'] ?? ''), ENT_QUOTES, 'UTF-8');
    $eventId        = htmlspecialchars(trim($_POST['event_id'] ?? ''), ENT_QUOTES, 'UTF-8');
    $unitPriceRaw   = $_POST['unit_price'] ?? $unitPrice;
    $unitPrice      = is_numeric($unitPriceRaw) ? (float)$unitPriceRaw : $unitPrice;
    $ticketQtyRaw   = trim($_POST['ticket_quantity'] ?? '');

    // 2. Validate REQUIRED fields.
    if ($name === '') {
        $errors['name'] = 'Full name is required.';
    }
    if ($emailRaw === '') {
        $errors['email'] = 'Email address is required.';
    }
    if ($eventName === '') {
        $errors['event_name'] = 'Event name is required.';
    }
    if ($ticketQtyRaw === '') {
        $errors['ticket_quantity'] = 'Ticket quantity is required.';
    }

    // 3. Validate EMAIL format (only if the field is not empty).
    if ($emailRaw !== '' && !filter_var($emailRaw, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    // 4. Validate TICKET QUANTITY is greater than zero.
    $ticketQty = (int)$ticketQtyRaw;
    if ($ticketQtyRaw !== '' && (!is_numeric($ticketQtyRaw) || $ticketQty <= 0)) {
        $errors['ticket_quantity'] = 'Ticket quantity must be greater than zero.';
    }

    // 5. If there are no validation errors, the booking is confirmed.
    if (empty($errors)) {
        $success     = true;
        $ticketQty   = (int)$ticketQtyRaw;
        $totalAmount = $unitPrice * $ticketQty;

        // 6. Save the booking into the MySQL bookings table via PDO.
        $bookingId   = null;
        $dbError     = null;
        try {
            // Resolve the numeric event id from the events table using the event name.
            $stmt = $pdo->prepare('SELECT id FROM events WHERE name = ? LIMIT 1');
            $stmt->execute([$eventNameRaw]);
            $eventRow = $stmt->fetch();
            $eventIdDb = $eventRow ? (int)$eventRow['id'] : 0;

            if ($eventIdDb > 0) {
                $insert = $pdo->prepare('
                    INSERT INTO bookings (customer_name, email, event_id, ticket_quantity, status)
                    VALUES (?, ?, ?, ?, ?)
                ');
                $insert->execute([
                    $name,
                    $email,
                    $eventIdDb,
                    $ticketQty,
                    'Confirmed'
                ]);
                $bookingId = (int)$pdo->lastInsertId();
            }
        } catch (PDOException $e) {
            // Do not break the confirmation flow if the database insert fails.
            $bookingId = null;
            $dbError   = $e->getMessage();
        }
    }
} else {
    // Direct GET request: if an event name is provided, show the PHP booking
    // form pre-filled with that event; otherwise go back to the events platform.
    if (isset($_GET['event_name']) && $_GET['event_name'] !== '') {
        $eventName     = htmlspecialchars(trim($_GET['event_name']), ENT_QUOTES, 'UTF-8');
        $eventDate     = htmlspecialchars(trim($_GET['event_date'] ?? ''), ENT_QUOTES, 'UTF-8');
        $eventLocation = htmlspecialchars(trim($_GET['event_location'] ?? ''), ENT_QUOTES, 'UTF-8');
        $eventId       = htmlspecialchars(trim($_GET['event_id'] ?? ''), ENT_QUOTES, 'UTF-8');
        $unitPrice     = isset($_GET['unit_price']) && is_numeric($_GET['unit_price'])
            ? (float)$_GET['unit_price']
            : TICKET_UNIT_PRICE;
    } else {
        header('Location: ../index.html');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Booking - KhanEvent</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 2rem; }
        .container { max-width: 480px; margin: 0 auto; background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        h2 { margin-top: 0; color: #1f2937; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.4rem; font-weight: 600; color: #374151; }
        input[type="text"],
        input[type="email"],
        input[type="number"] {
            width: 100%; padding: 0.6rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem; box-sizing: border-box;
        }
        input:focus { outline: none; border-color: #4f46e5; }
        .error-text { color: #dc2626; font-size: 0.875rem; margin-top: 0.25rem; display: block; }
        input.error { border-color: #dc2626; background: #fef2f2; }
        button { background: #4f46e5; color: #fff; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; font-size: 1rem; cursor: pointer; font-weight: 600; }
        button:hover { background: #4338ca; }
        button.full-width { width: 100%; }
        .success-box { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .total-amount { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: 1.1rem; font-weight: 600; text-align: center; }
        .total-amount .total-label { color: #166534; font-size: 0.95rem; margin-bottom: 0.25rem; display: block; }
        .total-amount .total-value { color: #15803d; font-size: 1.5rem; }
        .confirmation-box { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e3a8a; padding: 1.25rem; border-radius: 8px; }
        .confirmation-box p { margin: 0.5rem 0; font-size: 1.05rem; }
        .confirmation-box strong { color: #1e40af; }
        .back-link { display: inline-block; margin-top: 1rem; color: #4f46e5; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
        .muted { color: #6b7280; font-size: 0.9rem; }
    </style>
</head>
<body>

<div class="container">
    <?php if ($success): ?>
        <div class="success-box">
            <strong style="font-size:1.25rem;">✔ Thank You!</strong><br>
            Your booking has been confirmed successfully.
        </div>

        <div class="confirmation-box">
            <p><strong>Attendee:</strong> <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Event:</strong> <?= htmlspecialchars($eventName, ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Tickets:</strong> <?= $ticketQty ?></p>
            <p><strong>Price per ticket:</strong> ₹<?= number_format($unitPrice) ?></p>
            <p><strong>Total amount:</strong> ₹<?= number_format($totalAmount) ?></p>
            <?php if ($bookingId): ?>
                <p><strong>Booking #<?= $bookingId ?></strong> saved to the database.</p>
            <?php elseif (isset($dbError)): ?>
                <p class="muted" style="color:#dc2626;">Note: booking confirmed but could not be saved to the database.</p>
            <?php endif; ?>
        </div>

        <p class="muted">A confirmation email has been sent to <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>.</p>

        <!-- Persist this booking to localStorage so it appears in Manage Bookings & Dashboard, then return to the platform -->
        <script>
            (function () {
                var STORAGE_KEY = 'khanevent_confirmed_bookings';
                var booking = {
                    bookingId: 'BK-' + Date.now().toString().slice(-6),
                    dbBookingId: <?= isset($bookingId) && $bookingId > 0 ? (int)$bookingId : 'null' ?>,
                    eventId: <?= is_numeric($eventId) ? (int)$eventId : null ?>,
                    eventName: <?= json_encode($eventName, JSON_HEX_TAG) ?>,
                    eventDate: <?= json_encode($eventDate, JSON_HEX_TAG) ?>,
                    eventLocation: <?= json_encode($eventLocation, JSON_HEX_TAG) ?>,
                    unitPrice: <?= (float)$unitPrice ?>,
                    attendeeName: <?= json_encode($name, JSON_HEX_TAG) ?>,
                    attendeeEmail: <?= json_encode($email, JSON_HEX_TAG) ?>,
                    attendeePhone: '',
                    tickets: <?= (int)$ticketQty ?>,
                    totalAmount: <?= (float)$totalAmount ?>,
                    status: 'Confirmed',
                    bookedAt: new Date().toLocaleString()
                };
                try {
                    var existing = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
                    if (!Array.isArray(existing)) existing = [];
                    existing.unshift(booking);
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(existing));
                } catch (e) {
                    console.warn('Could not persist booking to localStorage', e);
                }
            })();
        </script>

        <button type="button" class="btn-primary full-width" onclick="window.location.href='../index.html'">OK — Back to Events</button>

    <?php else: ?>
        <h2>Book Event Tickets</h2>

        <?php if (!empty($errors)): ?>
            <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:.75rem;border-radius:6px;margin-bottom:1rem;">
                Please fix the following error(s) before submitting.
            </div>
        <?php endif; ?>

        <form action="process_booking.php" method="POST" novalidate>
            <input type="hidden" name="event_name" value="<?= htmlspecialchars($eventName, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="event_date" value="<?= htmlspecialchars($eventDate, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="event_location" value="<?= htmlspecialchars($eventLocation, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="unit_price" value="<?= htmlspecialchars($unitPrice, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="event_id" value="<?= htmlspecialchars($eventId, ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group">
                <label for="name">Full Name <span style="color:#dc2626;">*</span></label>
                <input type="text" id="name" name="name" pattern="[A-Za-z\s]+" title="Use letters and spaces only" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                       <?php if (isset($errors['name'])): ?>class="error"<?php endif; ?>>
                <?php if (isset($errors['name'])): ?>
                    <span class="error-text"><?= $errors['name'] ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="email">Email Address <span style="color:#dc2626;">*</span></label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                       <?php if (isset($errors['email'])): ?>class="error"<?php endif; ?>>
                <?php if (isset($errors['email'])): ?>
                    <span class="error-text"><?= $errors['email'] ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="event_name">Event Name <span style="color:#dc2626;">*</span></label>
                <input type="text" id="event_name" name="event_name" value="<?= htmlspecialchars($eventName, ENT_QUOTES, 'UTF-8') ?>"
                       <?php if (isset($errors['event_name'])): ?>class="error"<?php endif; ?>>
                <?php if (isset($errors['event_name'])): ?>
                    <span class="error-text"><?= $errors['event_name'] ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="ticket_quantity">Number of Tickets <span style="color:#dc2626;">*</span></label>
                <input type="number" id="ticket_quantity" name="ticket_quantity" min="1" value="<?= htmlspecialchars($ticketQty, ENT_QUOTES, 'UTF-8') ?>"
                       <?php if (isset($errors['ticket_quantity'])): ?>class="error"<?php endif; ?>>
                <?php if (isset($errors['ticket_quantity'])): ?>
                    <span class="error-text"><?= $errors['ticket_quantity'] ?></span>
                <?php endif; ?>
            </div>

            <p class="muted">Ticket price: ₹<?= number_format($unitPrice) ?> per ticket</p>

            <div class="total-amount">
                <span class="total-label">Total Amount</span>
                <span class="total-value" id="total_amount">₹<?= number_format($unitPrice) ?></span>
            </div>

            <button type="submit" class="full-width">Confirm Booking</button>

            <script>
            (function () {
                var unitPrice = <?= (float)$unitPrice ?>;
                var qtyInput = document.getElementById('ticket_quantity');
                var totalInput = document.getElementById('total_amount');
                function updateTotal() {
                    var raw = qtyInput.value.trim();
                    var q = raw === '' ? 0 : (parseInt(raw, 10) || 0);
                    if (q < 0) q = 0;
                    qtyInput.value = q;
                    var total = unitPrice * q;
                    totalInput.textContent = '₹' + total.toLocaleString('en-IN');
                }
                qtyInput.addEventListener('input', updateTotal);
                qtyInput.addEventListener('change', updateTotal);
                updateTotal();
            })();
            </script>
        </form>

        <p class="muted" style="margin-top:1rem;">Fields marked with <span style="color:#dc2626;">*</span> are required.</p>
        <a href="../index.html" class="back-link">← Back to Events</a>
    <?php endif; ?>
</div>

</body>
</html>
