<?php

declare(strict_types=1);

auth_require_role('super_admin');

$ticketId = (int)($_GET['id'] ?? 0);
$adminId  = (int)auth_user_id();

$ticket = db_fetch_one($db, '
    SELECT t.*,
           bu.full_name AS buyer_name, bu.email AS buyer_email, bu.phone AS buyer_phone,
           o.order_no, o.total_amount, o.status AS order_status, o.merchant_id,
           mu.full_name AS merchant_name, mp.business_name AS merchant_business, mu.email AS merchant_email
    FROM tickets t
    JOIN users bu ON bu.id = t.buyer_id
    LEFT JOIN orders o ON o.id = t.order_id
    LEFT JOIN users mu ON mu.id = o.merchant_id
    LEFT JOIN merchant_profile mp ON mp.merchant_user_id = o.merchant_id
    WHERE t.id = :id LIMIT 1
', ['id' => $ticketId]);

if (!$ticket) {
    flash_set('error', 'Ticket not found.');
    redirect_to('super-admin/tickets');
}

$title = 'Ticket #' . $ticketId;

if (request_method() === 'POST') {
    $action = post_string('action');

    if ($action === 'message') {
        $message = post_string('message');
        if ($message !== '') {
            db_exec($db, 'INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, message, created_at)
                          VALUES (:tid, :st, :sid, :msg, NOW())', [
                'tid' => $ticketId, 'st' => 'super_admin', 'sid' => $adminId, 'msg' => $message,
            ]);
            db_exec($db, 'UPDATE tickets SET updated_at = NOW(), status = :st WHERE id = :id', [
                'st' => 'pending', 'id' => $ticketId,
            ]);
            flash_set('success', 'Reply posted as super-admin.');
        }
    } elseif ($action === 'resolve') {
        db_exec($db, 'UPDATE tickets SET status = :st, updated_at = NOW() WHERE id = :id', [
            'st' => 'resolved', 'id' => $ticketId,
        ]);
        flash_set('success', 'Ticket marked resolved.');
    } elseif ($action === 'escalate') {
        db_exec($db, 'UPDATE tickets SET status = :st, updated_at = NOW() WHERE id = :id', [
            'st' => 'escalated', 'id' => $ticketId,
        ]);
        flash_set('success', 'Ticket escalated.');
    } elseif ($action === 'reopen') {
        db_exec($db, 'UPDATE tickets SET status = :st, updated_at = NOW() WHERE id = :id', [
            'st' => 'open', 'id' => $ticketId,
        ]);
        flash_set('success', 'Ticket reopened.');
    }
    redirect_to('super-admin/ticket-detail?id=' . $ticketId);
}

$messages = db_fetch_all($db, '
    SELECT tm.*, u.full_name AS user_name
    FROM ticket_messages tm
    LEFT JOIN users u ON u.id = tm.sender_id
    WHERE tm.ticket_id = :tid
    ORDER BY tm.created_at ASC
', ['tid' => $ticketId]) ?: [];

$content = function () use ($ticket, $messages) {
    require __DIR__ . '/../../views/partials/super-admin-nav.php';
    $success = flash_get('success');
    $error   = flash_get('error');
    $status  = (string)$ticket['status'];
    $badge   = $status === 'resolved' ? 'success'
             : ($status === 'escalated' ? 'danger'
                : ($status === 'open' ? 'warning' : 'info'));
    ?>
    <div class="container" style="max-width: 960px;">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
          <h1 class="h5 mb-1">Ticket #<?= (int)$ticket['id'] ?> · <?= e((string)$ticket['subject']) ?></h1>
          <div class="small text-muted">
            Opened <?= e(date('d M Y H:i', strtotime((string)$ticket['created_at']))) ?>
            · last updated <?= e(date('d M Y H:i', strtotime((string)$ticket['updated_at']))) ?>
          </div>
        </div>
        <a class="btn btn-sm btn-outline-secondary" href="?p=super-admin/tickets">Back</a>
      </div>

      <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
      <?php if ($error):   ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <div class="bg-white border rounded-3 p-3 h-100">
            <div class="small text-muted text-uppercase fw-semibold mb-2">Buyer</div>
            <div class="fw-semibold"><?= e((string)$ticket['buyer_name']) ?></div>
            <div class="small text-muted"><?= e((string)$ticket['buyer_email']) ?></div>
            <?php if (!empty($ticket['buyer_phone'])): ?>
              <div class="small text-muted"><?= e((string)$ticket['buyer_phone']) ?></div>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-md-4">
          <div class="bg-white border rounded-3 p-3 h-100">
            <div class="small text-muted text-uppercase fw-semibold mb-2">Seller</div>
            <?php if (!empty($ticket['merchant_business'])): ?>
              <div class="fw-semibold"><?= e((string)$ticket['merchant_business']) ?></div>
              <div class="small text-muted"><?= e((string)$ticket['merchant_name']) ?></div>
              <div class="small text-muted"><?= e((string)$ticket['merchant_email']) ?></div>
            <?php else: ?>
              <div class="text-muted">No seller (no order linked)</div>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-md-4">
          <div class="bg-white border rounded-3 p-3 h-100">
            <div class="small text-muted text-uppercase fw-semibold mb-2">Order</div>
            <?php if (!empty($ticket['order_no'])): ?>
              <div class="fw-semibold"><?= e((string)$ticket['order_no']) ?></div>
              <div class="small text-muted">Total ₹<?= e(number_format((float)$ticket['total_amount'], 2)) ?></div>
              <div class="small text-muted">Status: <?= e((string)$ticket['order_status']) ?></div>
            <?php else: ?>
              <div class="text-muted">General (not order-linked)</div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="bg-white border rounded-3 p-3 mb-3">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <div class="small">
            Status: <span class="badge bg-<?= $badge ?>"><?= e(ucfirst($status)) ?></span>
            · <?= count($messages) ?> message(s)
          </div>
          <form method="post" class="d-inline">
            <?php if ($status !== 'resolved'): ?>
              <button class="btn btn-sm btn-outline-success" name="action" value="resolve">Mark resolved</button>
            <?php else: ?>
              <button class="btn btn-sm btn-outline-secondary" name="action" value="reopen">Reopen</button>
            <?php endif; ?>
            <?php if ($status !== 'escalated' && $status !== 'resolved'): ?>
              <button class="btn btn-sm btn-outline-danger ms-1" name="action" value="escalate">Escalate</button>
            <?php endif; ?>
          </form>
        </div>

        <div style="max-height: 480px; overflow-y: auto;">
          <?php if (empty($messages)): ?>
            <div class="text-muted text-center py-4">No replies yet.</div>
          <?php else: foreach ($messages as $m):
              $senderType = (string)$m['sender_type'];
              $align = $senderType === 'buyer' ? 'start' : ($senderType === 'super_admin' ? 'center' : 'end');
              $bgClass = $senderType === 'buyer' ? 'bg-light'
                       : ($senderType === 'super_admin' ? 'bg-warning bg-opacity-25' : 'bg-primary bg-opacity-10');
          ?>
            <div class="d-flex justify-content-<?= $align ?> mb-2">
              <div class="<?= $bgClass ?> rounded-3 p-2" style="max-width: 75%;">
                <div class="small text-muted mb-1">
                  <strong><?= e(ucfirst($senderType)) ?></strong>
                  <?php if (!empty($m['user_name'])): ?> · <?= e((string)$m['user_name']) ?><?php endif; ?>
                  · <?= e(date('d M H:i', strtotime((string)$m['created_at']))) ?>
                </div>
                <div><?= nl2br(e((string)$m['message'])) ?></div>
              </div>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>

      <div class="bg-white border rounded-3 p-3">
        <h2 class="h6 mb-3">Reply as platform mediator</h2>
        <form method="post">
          <input type="hidden" name="action" value="message">
          <textarea class="form-control mb-2" name="message" rows="4" placeholder="Your reply will appear to both the buyer and the seller." required></textarea>
          <button class="btn btn-mm" type="submit">Send reply</button>
        </form>
      </div>
    </div>
    <?php
};

require __DIR__ . '/../../views/layout.php';
