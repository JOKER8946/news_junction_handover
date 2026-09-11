<?php

declare(strict_types=1);

auth_require_role('super_admin');

$title = 'All Orders';

$orders = db_fetch_all($db,
    "SELECT o.id, o.order_no, o.status, o.total_amount, o.created_at,
            u.full_name AS buyer_name, u.email AS buyer_email
     FROM orders o
     LEFT JOIN users u ON u.id = o.buyer_id
     ORDER BY o.created_at DESC
     LIMIT 200"
);

$content = function () use ($orders) {
    require __DIR__ . '/../../views/partials/super-admin-nav.php';
    ?>
    <div class="container">
      <h1 class="h4 mb-3">Orders (latest 200)</h1>

      <div class="bg-white border rounded-3 overflow-hidden">
        <table class="table table-sm mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>Order #</th>
              <th>Buyer</th>
              <th>Status</th>
              <th class="text-end">Total (₹)</th>
              <th>Placed</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($orders)): ?>
              <tr><td colspan="5" class="text-muted text-center py-4">No orders yet.</td></tr>
            <?php else: foreach ($orders as $o): ?>
              <tr>
                <td class="fw-semibold"><?= e((string)$o['order_no']) ?></td>
                <td>
                  <div><?= e((string)($o['buyer_name'] ?? '—')) ?></div>
                  <div class="small text-muted"><?= e((string)($o['buyer_email'] ?? '')) ?></div>
                </td>
                <td>
                  <?php
                    $status = (string)$o['status'];
                    $badge = in_array($status, ['delivered','completed'], true) ? 'success'
                           : (in_array($status, ['cancelled','refunded'], true) ? 'danger' : 'secondary');
                  ?>
                  <span class="badge bg-<?= $badge ?>"><?= e(ucfirst($status)) ?></span>
                </td>
                <td class="text-end"><?= number_format((float)$o['total_amount'], 2) ?></td>
                <td class="small text-muted"><?= e(date('d M Y H:i', strtotime((string)$o['created_at']))) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
};

require __DIR__ . '/../../views/layout.php';
