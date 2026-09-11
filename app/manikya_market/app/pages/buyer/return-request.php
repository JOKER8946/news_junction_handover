<?php

declare(strict_types=1);

auth_require_role('buyer');

$buyerId = (int)auth_user_id();
$orderId = (int)($_GET['order_id'] ?? $_POST['order_id'] ?? 0);

$order = db_fetch_one($db,
    'SELECT id, order_no, status, total_amount, merchant_id, delivered_at FROM orders WHERE id = :id AND buyer_id = :b LIMIT 1',
    ['id' => $orderId, 'b' => $buyerId]
);
if (!$order) {
    flash_set('error', 'Order not found.');
    redirect_to('buyer/orders');
}

// Buyer can only return delivered orders within 7 days
if ($order['status'] !== 'delivered') {
    flash_set('error', 'Only delivered orders can be returned.');
    redirect_to('buyer/orders');
}
$deliveredAt = $order['delivered_at'] ? strtotime((string)$order['delivered_at']) : null;
if ($deliveredAt && (time() - $deliveredAt) > 7 * 24 * 3600) {
    flash_set('error', 'Return window (7 days from delivery) has passed.');
    redirect_to('buyer/orders');
}

// Check existing
$existing = db_fetch_one($db, 'SELECT id, status FROM returns WHERE order_id = :id LIMIT 1', ['id' => $orderId]);
if ($existing) {
    flash_set('info', 'A return request for this order already exists.');
    redirect_to('buyer/orders');
}

if (request_method() === 'POST') {
    $reason  = trim(post_string('reason'));
    $details = trim(post_string('details'));
    if ($reason === '') {
        flash_set('error', 'Pick a reason.');
        redirect_to('buyer/return-request?order_id=' . $orderId);
    }

    db_exec($db, '
        INSERT INTO returns (order_id, buyer_id, merchant_id, reason, details, status, requested_at)
        VALUES (:o, :b, :m, :r, :d, :st, NOW())
    ', [
        'o' => $orderId, 'b' => $buyerId, 'm' => (int)$order['merchant_id'],
        'r' => $reason, 'd' => $details ?: null, 'st' => 'requested',
    ]);

    db_exec($db, "UPDATE orders SET status='return_requested' WHERE id = :id", ['id' => $orderId]);

    flash_set('success', 'Return request submitted. The seller will review it.');
    redirect_to('buyer/orders');
}

$title = 'Request return — ' . $order['order_no'];

$content = function () use ($order) {
    ?>
    <div class="container py-4" style="max-width: 640px;">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h5 mb-0">Request return — <?= e((string)$order['order_no']) ?></h1>
        <a class="btn btn-sm btn-outline-secondary" href="?p=buyer/orders">Back</a>
      </div>
      <div class="bg-white border rounded-3 p-4">
        <form method="post">
          <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
          <div class="mb-3">
            <label class="form-label">Reason *</label>
            <select class="form-select" name="reason" required>
              <option value="">— Pick one —</option>
              <option value="Damaged on arrival">Damaged on arrival</option>
              <option value="Wrong item delivered">Wrong item delivered</option>
              <option value="Defective / not working">Defective / not working</option>
              <option value="Doesn't match description">Doesn't match description</option>
              <option value="No longer needed">No longer needed</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Details <span class="text-muted">(helpful for the seller)</span></label>
            <textarea class="form-control" name="details" rows="4" placeholder="Explain what went wrong..."></textarea>
          </div>
          <button class="btn btn-mm" type="submit">Submit return request</button>
        </form>
      </div>
    </div>
    <?php
};

require __DIR__ . '/../../views/layout.php';
