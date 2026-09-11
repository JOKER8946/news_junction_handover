<?php

declare(strict_types=1);

auth_require_role('merchant');

$title = 'GRN Management';
$merchantId = auth_user_id();

require_once __DIR__ . '/../../lib/grn.php';
ensure_grn_tables($db);

// Get all GRNs
$grns = db_fetch_all($db, '
    SELECT g.id, g.grn_no, g.supplier_name, g.status, g.created_at,
           COUNT(gi.id) AS item_count, SUM(gi.expected_qty_kg) AS total_qty
    FROM grn g
    LEFT JOIN grn_items gi ON gi.grn_id = g.id
    WHERE g.merchant_user_id = :merchant_id
    GROUP BY g.id, g.grn_no, g.supplier_name, g.status, g.created_at
    ORDER BY g.created_at DESC
', ['merchant_id' => $merchantId]) ?: [];

$content = function() use ($grns) {
    ?>
    <div class="container py-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h5 mb-0 d-flex align-items-center gap-2"><i data-lucide="package" class="mm-icon"></i> GRN Management</h1>
            <div class="d-flex gap-2">
                <a class="btn btn-sm btn-mm" href="?p=merchant/gate-entry">+ New Gate Entry</a>
                <a class="btn btn-sm btn-outline-secondary" href="?p=merchant/dashboard">Back</a>
            </div>
        </div>

        <div class="bg-white border rounded-4 p-3 mm-card">
            <?php if (!$grns): ?>
                <div class="text-muted py-4 text-center">No GRNs yet. Start by recording a gate entry.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0 small">
                        <thead>
                            <tr>
                                <th>GRN #</th>
                                <th>Supplier</th>
                                <th>Items</th>
                                <th>Total Qty (kg)</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($grns as $grn): ?>
                                <tr>
                                    <td><strong><?= e((string)$grn['grn_no']) ?></strong></td>
                                    <td><?= e((string)$grn['supplier_name']) ?></td>
                                    <td><?= (int)($grn['item_count'] ?? 0) ?></td>
                                    <td><?= number_format((float)($grn['total_qty'] ?? 0), 2) ?> kg</td>
                                    <td>
                                        <span class="badge text-bg-<?= $grn['status'] === 'approved' ? 'success' : ($grn['status'] === 'pending_qc' ? 'warning' : 'secondary') ?>">
                                            <?= ucfirst(str_replace('_', ' ', (string)$grn['status'])) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime((string)$grn['created_at'])) ?></td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-primary" href="?p=merchant/grn-detail&id=<?= (int)$grn['id'] ?>">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script>if (window.lucide) window.lucide.createIcons();</script>
    <?php
};

require __DIR__ . '/../../views/layout.php';
