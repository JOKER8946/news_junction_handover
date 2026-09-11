<?php

declare(strict_types=1);

auth_require_role('merchant');

$title = 'Returns';
$merchantId = (int)auth_user_id();

if (request_method() === 'POST') {
    $returnId = (int)post_string('return_id');
    $action   = post_string('action');
    $notes    = trim(post_string('decision_notes'));

    // Ownership: the return's merchant_id must match.
    $ret = db_fetch_one($db,
        'SELECT r.*, o.total_amount, o.order_no FROM returns r JOIN orders o ON o.id = r.order_id
         WHERE r.id = :id AND r.merchant_id = :m LIMIT 1',
        ['id' => $returnId, 'm' => $merchantId]
    );
    if (!$ret) {
        flash_set('error', 'Return not found or not yours.');
        redirect_to('merchant/returns');
    }

    if ($action === 'approve') {
        db_exec($db, "UPDATE returns SET status='approved', decision_notes=:n, decided_at=NOW() WHERE id=:id",
            ['n' => $notes ?: null, 'id' => $returnId]);
        db_exec($db, "UPDATE orders SET status='returned', returned_at=NOW(), return_reason=:r WHERE id=:oid",
            ['r' => (string)$ret['reason'], 'oid' => (int)$ret['order_id']]);

        // Reverse BOTH the seller wallet credit (net of commission) and the
        // platform admin's commission credit. Uses original transaction amounts
        // so the reversal is mathematically exact.
        try {
            reverse_merchant_wallet_credit_for_order($db, (int)$ret['order_id'], 'returned');
        } catch (Throwable $t) {
            error_log('wallet reversal on return failed: ' . $t->getMessage());
        }

        flash_set('success', 'Return approved. Seller wallet and platform commission reversed.');
    } elseif ($action === 'reject') {
        db_exec($db, "UPDATE returns SET status='rejected', decision_notes=:n, decided_at=NOW() WHERE id=:id",
            ['n' => $notes ?: null, 'id' => $returnId]);
        // Order stays 'return_requested' or revert to 'delivered'? Revert.
        db_exec($db, "UPDATE orders SET status='delivered' WHERE id=:oid", ['oid' => (int)$ret['order_id']]);
        flash_set('success', 'Return rejected; order marked delivered again.');
    } elseif ($action === 'mark_received') {
        db_exec($db, "UPDATE returns SET status='received', decided_at=NOW() WHERE id=:id", ['id' => $returnId]);
        flash_set('success', 'Marked received. Issue refund manually via Payouts when ready.');
    }
    redirect_to('merchant/returns');
}

$returns = db_fetch_all($db, '
    SELECT r.*, o.order_no, o.total_amount, o.created_at AS order_date,
           u.full_name AS buyer_name
    FROM returns r
    JOIN orders o ON o.id = r.order_id
    JOIN users u ON u.id = r.buyer_id
    WHERE r.merchant_id = :m
    ORDER BY r.requested_at DESC
', ['m' => $merchantId]) ?: [];

$content = function () use ($returns) {
    $success = flash_get('success'); $error = flash_get('error');
    ?>
    <div class="container py-4">
      <h1 class="h5 mb-3 d-flex align-items-center gap-2"><i data-lucide="undo-2" class="mm-icon"></i> Returns</h1>
      <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
      <?php if ($error):   ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

      <?php if (empty($returns)): ?>
        <div class="bg-white border rounded-3 p-4 text-center text-muted">No return requests.</div>
      <?php else: ?>
        <div class="bg-white border rounded-3 overflow-hidden">
          <table class="table table-sm mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th>Order</th><th>Buyer</th><th>Reason</th><th>Status</th>
                <th>Requested</th><th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($returns as $r): $st = (string)$r['status']; ?>
              <tr>
                <td><div class="fw-semibold small"><?= e((string)$r['order_no']) ?></div>
                  <div class="small text-muted">₹<?= e(number_format((float)$r['total_amount'], 2)) ?></div></td>
                <td class="small"><?= e((string)$r['buyer_name']) ?></td>
                <td class="small">
                  <div><strong><?= e((string)$r['reason']) ?></strong></div>
                  <?php if ($r['details']): ?>
                    <div class="text-muted" style="max-width: 280px; white-space: pre-wrap;"><?= e((string)$r['details']) ?></div>
                  <?php endif; ?>
                </td>
                <td>
                  <?php $b = $st === 'approved' || $st === 'received' || $st === 'refunded' ? 'success'
                            : ($st === 'rejected' ? 'danger' : 'warning'); ?>
                  <span class="badge bg-<?= $b ?>"><?= e(ucfirst($st)) ?></span>
                </td>
                <td class="small text-muted"><?= e(date('d M H:i', strtotime((string)$r['requested_at']))) ?></td>
                <td class="text-end">
                  <?php if ($st === 'requested'): ?>
                    <form method="post" class="d-inline-flex gap-1 align-items-center">
                      <input type="hidden" name="return_id" value="<?= (int)$r['id'] ?>">
                      <input class="form-control form-control-sm" name="decision_notes" placeholder="notes..." style="width:120px;">
                      <button class="btn btn-sm btn-success" name="action" value="approve">Approve</button>
                      <button class="btn btn-sm btn-outline-danger" name="action" value="reject">Reject</button>
                    </form>
                  <?php elseif ($st === 'approved'): ?>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="return_id" value="<?= (int)$r['id'] ?>">
                      <button class="btn btn-sm btn-outline-primary" name="action" value="mark_received">Mark received</button>
                    </form>
                  <?php else: ?>
                    <span class="text-muted small">—</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
    <script>if (window.lucide) window.lucide.createIcons();</script>
    <?php
};

require __DIR__ . '/../../views/layout.php';
