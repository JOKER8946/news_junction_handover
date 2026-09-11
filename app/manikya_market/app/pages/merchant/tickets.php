<?php

declare(strict_types=1);

auth_require_role('merchant');

$title = 'Support Tickets';
$merchantId = (int)auth_user_id();

// Scope: only tickets for THIS merchant's orders (or where ticket has no order, none for this merchant).
$tickets = db_fetch_all($db,
    'SELECT t.id, t.subject, t.status, t.created_at, t.updated_at, t.order_id, u.full_name as buyer_name
     FROM tickets t
     JOIN users u ON u.id = t.buyer_id
     JOIN orders o ON o.id = t.order_id
     WHERE o.merchant_id = :mid
     ORDER BY t.updated_at DESC',
    ['mid' => $merchantId]
) ?: [];

$content = function() use ($tickets) {
    ?>
    <div class="container py-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h5 mb-0">Support Tickets</h1>
            <a class="btn btn-outline-secondary btn-sm" href="?p=merchant/dashboard">Back</a>
        </div>

        <div class="bg-white border rounded-3 p-3">
            <?php if (empty($tickets)): ?>
                <div class="text-muted">No tickets.</div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($tickets as $t): ?>
                        <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="?p=merchant/ticket-detail&id=<?= (int)$t['id'] ?>">
                            <div>
                                <div class="fw-semibold d-flex align-items-center gap-2">
                                    <?php if ($t['status'] === 'open'): ?>
                                        <div style="width: 12px; height: 12px; background: #28a745; border-radius: 50%; display: inline-block;"></div>
                                    <?php elseif ($t['status'] === 'resolved'): ?>
                                        <div style="width: 12px; height: 12px; background: #dc3545; border-radius: 50%; display: inline-block;"></div>
                                    <?php endif; ?>
                                    <?= e((string)$t['subject']) ?>
                                </div>
                                <div class="small text-muted">Buyer: <?= e((string)$t['buyer_name']) ?> · Status: <?= e((string)$t['status']) ?> · Updated <?= date('M d, Y H:i', strtotime((string)$t['updated_at'])) ?></div>
                            </div>
                            <div class="text-end small">#<?= (int)$t['id'] ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
};

require __DIR__ . '/../../views/layout.php';

?>
