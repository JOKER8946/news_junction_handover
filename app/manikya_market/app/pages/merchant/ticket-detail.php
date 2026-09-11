<?php

declare(strict_types=1);

auth_require_role('merchant');

$title = 'Ticket';
$merchantId = (int)auth_user_id();
$ticketId = (int)($_GET['id'] ?? 0);

// Ticket must belong to an order that this merchant owns.
$ticket = db_fetch_one($db,
    'SELECT t.*, u.full_name as buyer_name
     FROM tickets t
     JOIN users u ON u.id = t.buyer_id
     JOIN orders o ON o.id = t.order_id
     WHERE t.id = :id AND o.merchant_id = :mid LIMIT 1',
    ['id' => $ticketId, 'mid' => $merchantId]
);
if (!$ticket) {
    flash_set('error', 'Ticket not found or not yours.');
    redirect_to('merchant/tickets');
}

if (request_method() === 'POST' && isset($_POST['action'])) {
    $action = post_string('action');
    if ($action === 'message') {
        $message = post_string('message');
        if ($message) {
            db_exec($db, 'INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, message, created_at) VALUES (:ticket_id, :sender_type, :sender_id, :message, NOW())', [
                'ticket_id' => $ticketId,
                'sender_type' => 'merchant',
                'sender_id' => $merchantId,
                'message' => $message,
            ]);
            db_exec($db, 'UPDATE tickets SET updated_at = NOW(), status = :status WHERE id = :id', ['status' => 'pending', 'id' => $ticketId]);
            flash_set('success', 'Response posted');
        }
    }
    if ($action === 'resolve') {
        db_exec($db, 'UPDATE tickets SET status = :status, updated_at = NOW() WHERE id = :id', ['status' => 'resolved', 'id' => $ticketId]);
        flash_set('success', 'Ticket marked resolved');
    }
    redirect_to('merchant/ticket-detail?id=' . $ticketId);
}

$messages = db_fetch_all($db, 'SELECT tm.*, u.full_name as user_name FROM ticket_messages tm LEFT JOIN users u ON u.id = tm.sender_id WHERE tm.ticket_id = :ticket_id ORDER BY tm.created_at ASC', ['ticket_id' => $ticketId]);

$content = function() use ($ticket, $messages) {
    $success = flash_get('success');
    ?>
    <div class="container py-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h5 mb-0">Ticket #<?= (int)$ticket['id'] ?> · <?= e((string)$ticket['subject']) ?></h1>
            <a class="btn btn-outline-secondary btn-sm" href="?p=merchant/tickets">Back</a>
        </div>

        <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

        <div class="bg-white border rounded-3 p-3 mb-3">
            <div class="small text-muted">Buyer: <?= e((string)$ticket['buyer_name']) ?> · Status: <?= e((string)$ticket['status']) ?> · Created <?= date('M d, Y H:i', strtotime((string)$ticket['created_at'])) ?><?php if (!empty($ticket['order_id'])): ?> · Order: <a href="?p=merchant/order&id=<?= (int)$ticket['order_id'] ?>">#<?= (int)$ticket['order_id'] ?></a><?php endif; ?></div>
            <hr>
            <?php foreach ($messages as $m): ?>
                <div class="mb-3">
                    <div class="small text-muted"><?= e((string)$m['sender_type']) ?> · <?= date('M d, Y H:i', strtotime((string)$m['created_at'])) ?> <?php if (!empty($m['user_name'])): ?>· <?= e((string)$m['user_name']) ?><?php endif; ?></div>
                    <div class="mt-1"><?= nl2br(e((string)$m['message'])) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="bg-white border rounded-3 p-3">
            <form method="post">
                <input type="hidden" name="action" value="message">
                <div class="mb-2">
                    <label class="form-label">Reply</label>
                    <textarea class="form-control" name="message" rows="4" required></textarea>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-mm" type="submit">Send Reply</button>
                    <button class="btn btn-outline-success" type="submit" name="action" value="resolve">Mark Resolved</button>
                </div>
            </form>
        </div>
    </div>
    <?php
};

require __DIR__ . '/../../views/layout.php';

?>
