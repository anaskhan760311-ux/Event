<?php
/** Event administration: create, view, update and delete MySQL events. */
require_once __DIR__ . '/require_admin.php';
require_once __DIR__ . '/db.php';

if (empty($_SESSION['event_admin_csrf'])) {
    $_SESSION['event_admin_csrf'] = bin2hex(random_bytes(32));
}

$errors = [];
$notice = '';
$editing = null;
$values = ['name' => '', 'category' => '', 'event_date' => '', 'location' => '', 'price' => '', 'image' => ''];

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['event_admin_csrf'], $_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        $errors[] = 'Your form session expired. Refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'delete') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (!$id) {
                $errors[] = 'Invalid event selected.';
            } else {
                $stmt = $pdo->prepare('DELETE FROM events WHERE id = ?');
                $stmt->execute([$id]);
                $notice = $stmt->rowCount() ? 'Event deleted.' : 'That event no longer exists.';
            }
        } elseif ($action === 'create' || $action === 'update') {
            foreach ($values as $field => $_) {
                $values[$field] = trim((string)($_POST[$field] ?? ''));
            }
            $eventId = $action === 'update' ? filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : null;
            if ($action === 'update' && !$eventId) $errors[] = 'Invalid event selected.';
            foreach (['name' => 'Event name', 'category' => 'Category', 'event_date' => 'Date', 'location' => 'Location'] as $field => $label) {
                if ($values[$field] === '') $errors[] = "$label is required.";
            }
            $date = DateTime::createFromFormat('!Y-m-d', $values['event_date']);
            if (!$date || $date->format('Y-m-d') !== $values['event_date']) $errors[] = 'Enter a valid event date.';
            if ($values['price'] === '' || !is_numeric($values['price']) || (float)$values['price'] < 0 || (float)$values['price'] > 99999999.99) $errors[] = 'Price must be a non-negative amount.';
            if (strlen($values['name']) > 255 || strlen($values['category']) > 100 || strlen($values['location']) > 255 || strlen($values['image']) > 255) $errors[] = 'One or more fields exceed the database length limit.';

            if (!$errors) {
                if ($action === 'create') {
                    $stmt = $pdo->prepare('INSERT INTO events (name, category, event_date, location, price, image) VALUES (?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$values['name'], $values['category'], $values['event_date'], $values['location'], $values['price'], $values['image']]);
                    $_SESSION['event_admin_notice'] = 'Event added.';
                } else {
                    $stmt = $pdo->prepare('UPDATE events SET name = ?, category = ?, event_date = ?, location = ?, price = ?, image = ? WHERE id = ?');
                    $stmt->execute([$values['name'], $values['category'], $values['event_date'], $values['location'], $values['price'], $values['image'], $eventId]);
                    $_SESSION['event_admin_notice'] = $stmt->rowCount() ? 'Event updated.' : 'Event details are unchanged.';
                }
                header('Location: admin_events.php');
                exit;
            }
            if ($action === 'update') $editing = ['id' => $eventId];
        }
    }
}

$notice = $_SESSION['event_admin_notice'] ?? $notice;
unset($_SESSION['event_admin_notice']);
if (isset($_GET['edit']) && ctype_digit((string)$_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT id, name, category, event_date, location, price, image FROM events WHERE id = ?');
    $stmt->execute([$_GET['edit']]);
    $editing = $stmt->fetch();
    if ($editing) $values = $editing;
    else $errors[] = 'Event not found.';
}
$events = $pdo->query('SELECT id, name, category, event_date, location, price, image FROM events ORDER BY event_date ASC, name ASC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Events · KhanEvent</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body{padding:2rem 1rem}.admin-wrap{max-width:1120px;margin:auto}.admin-head,.panel,.event-table-wrap{background:#fff;border:1px solid #e5e7eb;border-radius:14px;box-shadow:0 8px 24px #1f29370d}.admin-head{padding:1.5rem 1.75rem;margin-bottom:1.25rem;display:flex;align-items:center;justify-content:space-between;gap:1rem}.admin-head h1{font-size:1.65rem}.admin-head p{color:#6b7280}.panel{padding:1.5rem;margin-bottom:1.25rem}.panel h2{font-size:1.2rem;margin-bottom:1rem}.event-form{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}.event-form label{display:block;font-weight:600;font-size:.9rem;margin-bottom:.35rem}.event-form input{width:100%;padding:.7rem .8rem;border:1px solid #d1d5db;border-radius:7px;font:inherit}.event-form input:focus{outline:2px solid #a5b4fc;border-color:#6366f1}.form-actions{grid-column:1/-1;display:flex;gap:.65rem;align-items:center}.action-btn{border:0;border-radius:7px;padding:.65rem 1rem;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block}.primary{background:#4f46e5;color:white}.muted{background:#e5e7eb;color:#374151}.danger{background:#fee2e2;color:#991b1b}.success-msg,.error-msg{padding:.8rem 1rem;border-radius:8px;margin-bottom:1rem}.success-msg{background:#dcfce7;color:#166534}.error-msg{background:#fee2e2;color:#991b1b}.error-msg ul{padding-left:1.2rem}.event-table-wrap{overflow-x:auto}.event-table{width:100%;border-collapse:collapse;min-width:760px}.event-table th,.event-table td{text-align:left;padding:.85rem 1rem;border-bottom:1px solid #e5e7eb;vertical-align:middle}.event-table th{background:#f9fafb;font-size:.82rem;text-transform:uppercase;color:#6b7280}.event-table tr:last-child td{border-bottom:0}.row-actions{display:flex;gap:.45rem;align-items:center}.inline-form{display:inline}.empty{text-align:center;padding:2rem;color:#6b7280}.delete-dialog{border:0;border-radius:16px;padding:0;width:min(440px,calc(100vw - 2rem));box-shadow:0 24px 70px #0f172a55;color:#1f2937}.delete-dialog::backdrop{background:#11182799;backdrop-filter:blur(3px)}.delete-dialog-card{padding:1.6rem}.delete-icon{width:48px;height:48px;border-radius:14px;background:#fee2e2;color:#dc2626;display:grid;place-items:center;font-size:1.4rem;margin-bottom:1rem}.delete-dialog h2{margin:0 0 .4rem;font-size:1.25rem}.delete-dialog p{margin:0;color:#6b7280;line-height:1.5}.delete-dialog-actions{display:flex;justify-content:flex-end;gap:.6rem;margin-top:1.5rem}.delete-dialog-actions .danger{background:#dc2626;color:#fff}.delete-dialog-actions .danger:hover{background:#b91c1c}.delete-dialog-actions .muted:hover{background:#d1d5db}.delete-dialog:focus{outline:none}@media(max-width:700px){.event-form{grid-template-columns:1fr 1fr}.admin-head{align-items:flex-start;flex-direction:column}}@media(max-width:460px){.event-form{grid-template-columns:1fr}}
    .success-msg,.error-msg{max-width:640px;margin:0 auto 1rem;text-align:center}.delete-dialog{position:fixed;inset:0;margin:auto;max-height:calc(100vh - 2rem)}
  </style>
</head>
<body>
<main class="admin-wrap">
  <header class="admin-head"><div><h1>Manage Events</h1><p>Create and maintain events stored in MySQL.</p></div><div style="display:flex;gap:.5rem;flex-wrap:wrap"><a class="action-btn muted" href="index.html">← Back to site</a><a class="action-btn muted" href="logout.php">Logout</a></div></header>
  <?php if ($notice): ?><div class="success-msg" role="status"><?= h($notice) ?></div><?php endif; ?>
  <?php if ($errors): ?><div class="error-msg" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <section class="panel" aria-labelledby="form-title"><h2 id="form-title"><?= $editing ? 'Edit event' : 'Add an event' ?></h2>
    <form class="event-form" method="post" action="admin_events.php<?= $editing ? '?edit=' . (int)$editing['id'] : '' ?>">
      <input type="hidden" name="csrf_token" value="<?= h($_SESSION['event_admin_csrf']) ?>"><input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
      <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int)$editing['id'] ?>"><?php endif; ?>
      <div><label for="name">Event name</label><input id="name" name="name" maxlength="255" required value="<?= h($values['name']) ?>"></div>
      <div><label for="category">Category</label><input id="category" name="category" maxlength="100" required value="<?= h($values['category']) ?>"></div>
      <div><label for="event_date">Date</label><input id="event_date" type="date" name="event_date" required value="<?= h($values['event_date']) ?>"></div>
      <div><label for="location">Location</label><input id="location" name="location" maxlength="255" required value="<?= h($values['location']) ?>"></div>
      <div><label for="price">Price (₹)</label><input id="price" type="number" name="price" min="0" max="99999999.99" step="0.01" required value="<?= h($values['price']) ?>"></div>
      <div><label for="image">Image path (optional)</label><input id="image" name="image" maxlength="255" value="<?= h($values['image']) ?>" placeholder="Leave blank if no image"></div>
      <div class="form-actions"><button class="action-btn primary" type="submit"><?= $editing ? 'Save changes' : 'Add event' ?></button><?php if ($editing): ?><a class="action-btn muted" href="admin_events.php">Cancel</a><?php endif; ?></div>
    </form>
  </section>
  <section aria-labelledby="events-title"><h2 id="events-title" style="margin:0 0 .8rem">All events (<?= count($events) ?>)</h2>
    <div class="event-table-wrap"><table class="event-table"><thead><tr><th>Name</th><th>Category</th><th>Date</th><th>Location</th><th>Price</th><th>Actions</th></tr></thead><tbody>
      <?php if (!$events): ?><tr><td colspan="6" class="empty">No events yet. Add one above.</td></tr><?php endif; ?>
      <?php foreach ($events as $event): ?><tr><td><?= h($event['name']) ?></td><td><?= h($event['category']) ?></td><td><?= h($event['event_date']) ?></td><td><?= h($event['location']) ?></td><td>₹<?= number_format((float)$event['price'], 2) ?></td><td><div class="row-actions"><a class="action-btn muted" href="admin_events.php?edit=<?= (int)$event['id'] ?>">Edit</a><form class="inline-form delete-event-form" method="post" action="admin_events.php"><input type="hidden" name="csrf_token" value="<?= h($_SESSION['event_admin_csrf']) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$event['id'] ?>"><button class="action-btn danger" type="submit">Delete</button></form></div></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </section>
</main>
<dialog class="delete-dialog" id="deleteEventDialog" aria-labelledby="deleteDialogTitle" aria-describedby="deleteDialogDescription">
  <div class="delete-dialog-card"><div class="delete-icon" aria-hidden="true">!</div><h2 id="deleteDialogTitle">Delete this event?</h2><p id="deleteDialogDescription">This event will be removed from the database. Related bookings may also be removed.</p><div class="delete-dialog-actions"><button class="action-btn muted" type="button" id="cancelDeleteEvent">Keep event</button><button class="action-btn danger" type="button" id="confirmDeleteEvent">Delete event</button></div></div>
</dialog>
<script>
(() => {
  const dialog = document.getElementById('deleteEventDialog');
  const cancel = document.getElementById('cancelDeleteEvent');
  const confirm = document.getElementById('confirmDeleteEvent');
  let pendingForm = null;
  document.querySelectorAll('.delete-event-form').forEach(form => form.addEventListener('submit', event => {
    if (form.dataset.confirmed === 'true') return;
    event.preventDefault();
    pendingForm = form;
    dialog.showModal();
    cancel.focus();
  }));
  cancel.addEventListener('click', () => { pendingForm = null; dialog.close(); });
  confirm.addEventListener('click', () => {
    if (!pendingForm) return;
    pendingForm.dataset.confirmed = 'true';
    pendingForm.requestSubmit();
  });
  dialog.addEventListener('click', event => {
    if (event.target === dialog) { pendingForm = null; dialog.close(); }
  });
})();
</script>
</body>
</html>
