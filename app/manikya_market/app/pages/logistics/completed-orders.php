<?php

declare(strict_types=1);

auth_require_role('logistics');

$title = 'Completed Deliveries';

$logisticsUserId = auth_user_id();

// ensure invoice_file column exists
ensure_order_column($db, 'invoice_file', "VARCHAR(255) NULL");
// ensure delivered_at and logistics user id exist
ensure_order_column($db, 'delivered_at');
ensure_order_column($db, 'logistics_user_id', 'INT NULL');

$orders = db_fetch_all($db, '
    SELECT o.id, o.order_no, o.status, o.created_at, o.delivered_at, o.shipping_tracking_no, o.awb_number, o.invoice_file, o.invoice_generated_at, u.full_name AS buyer_name, l.full_name AS delivered_by
    FROM orders o
    JOIN users u ON u.id = o.buyer_id
    LEFT JOIN users l ON l.id = o.logistics_user_id
    WHERE (LOWER(TRIM(o.status)) = "delivered" OR o.delivered_at IS NOT NULL)
    AND o.logistics_user_id = :logistics_user_id
    ORDER BY o.delivered_at DESC, o.created_at DESC
', ['logistics_user_id' => $logisticsUserId]) ?: [];

$content = function() use ($orders) {
    ?>
    <div class="container py-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h5 mb-0 d-flex align-items-center gap-2"><i data-lucide="check" class="mm-icon"></i> Completed Deliveries</h1>
            <a class="btn btn-sm btn-outline-secondary" href="?p=logistics/dashboard">Back</a>
        </div>

        <div class="bg-white border rounded-4 p-3 mm-card">
            <?php if (!$orders): ?>
                <div class="text-muted py-4 text-center">No delivered orders yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Buyer</th>
                                <th>Delivered At</th>
                                <th>Tracking</th>
                                <th>Delivered By</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $o): ?>
                                <tr>
                                    <td><strong><?= e((string)$o['order_no']) ?></strong></td>
                                    <td><?= e((string)$o['buyer_name']) ?></td>
                                    <?php
                                        $ts = null;
                                        if (!empty($o['delivered_at'])) {
                                            $ts = strtotime((string)$o['delivered_at']);
                                        } elseif (!empty($o['created_at'])) {
                                            $ts = strtotime((string)$o['created_at']);
                                        }
                                    ?>
                                    <td><?= $ts ? e(date('M d, Y', $ts)) : '<span class="text-muted">-</span>' ?></td>
                                    <td>
                                        <?php if (!empty($o['shipping_tracking_no']) || !empty($o['awb_number'])): ?>
                                            <div class="small">
                                                <?php if (!empty($o['shipping_tracking_no'])): ?>
                                                    <div>TRK: <?= e((string)$o['shipping_tracking_no']) ?></div>
                                                <?php endif; ?>
                                                <?php if (!empty($o['awb_number'])): ?>
                                                    <div>AWB: <?= e((string)$o['awb_number']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= !empty($o['delivered_by']) ? e((string)$o['delivered_by']) : '<span class="text-muted small">-</span>' ?></td>
                                    <td class="text-end">
                                        <div class="d-flex gap-2 justify-content-end flex-wrap">
                                            <a href="?p=logistics/order-invoice&id=<?= (int)$o['id'] ?>" class="btn btn-sm btn-outline-primary">Invoice</a>
                                            <a class="btn btn-sm btn-primary" href="?p=logistics/order-detail&id=<?= (int)$o['id'] ?>">Details</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
};

require __DIR__ . '/../../views/layout.php';
