<?php

declare(strict_types=1);

auth_require_role('super_admin');

$title = 'Buyers';

$buyers = db_fetch_all($db,
    "SELECT u.id, u.full_name, u.email, u.phone, u.status, u.created_at,
            (SELECT COUNT(*) FROM orders o WHERE o.buyer_id = u.id) AS order_count,
            (SELECT COALESCE(SUM(o.total_amount),0) FROM orders o WHERE o.buyer_id = u.id AND o.status NOT IN ('cancelled','refunded')) AS spent
     FROM users u
     WHERE u.role='buyer'
     ORDER BY u.created_at DESC"
);

$content = function () use ($buyers) {
    require __DIR__ . '/../../views/partials/super-admin-nav.php';
    ?>
    <div class="container">
      <h1 class="h4 mb-3">Buyers</h1>

      <div class="bg-white border rounded-3 overflow-hidden">
        <table class="table table-sm mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th class="text-center">Orders</th>
              <th class="text-end">Spent (₹)</th>
              <th>Status</th>
              <th>Joined</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($buyers)): ?>
              <tr><td colspan="7" class="text-muted text-center py-4">No buyers yet.</td></tr>
            <?php else: foreach ($buyers as $b): ?>
              <tr>
                <td><?= e((string)$b['full_name']) ?></td>
                <td class="small"><?= e((string)$b['email']) ?></td>
                <td class="small text-muted"><?= e((string)($b['phone'] ?? '')) ?></td>
                <td class="text-center"><?= (int)$b['order_count'] ?></td>
                <td class="text-end"><?= number_format((float)$b['spent'], 2) ?></td>
                <td>
                  <span class="badge bg-<?= $b['status'] === 'active' ? 'success' : 'secondary' ?>"><?= e(ucfirst((string)$b['status'])) ?></span>
                </td>
                <td class="small text-muted"><?= e(date('d M Y', strtotime((string)$b['created_at']))) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
};

require __DIR__ . '/../../views/layout.php';
