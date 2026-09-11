<?php

declare(strict_types=1);

auth_require_role('super_admin');

$title = 'Support Tickets';

$filter = (string)($_GET['status'] ?? 'all');
$valid  = ['all', 'open', 'pending', 'resolved', 'escalated'];
if (!in_array($filter, $valid, true)) {
    $filter = 'all';
}

$where  = '';
$params = [];
if ($filter !== 'all') {
    $where = ' WHERE t.status = :st';
    $params['st'] = $filter;
}

$tickets = db_fetch_all($db, "
    SELECT t.id, t.subject, t.status, t.order_id, t.buyer_id, t.created_at, t.updated_at,
           bu.full_name AS buyer_name, bu.email AS buyer_email,
           o.order_no, o.merchant_id,
           mu.full_name AS merchant_name, mp.business_name AS merchant_business,
           TIMESTAMPDIFF(HOUR, t.updated_at, NOW()) AS hours_since_update,
           (SELECT COUNT(*) FROM ticket_messages tm WHERE tm.ticket_id = t.id) AS message_count,
           (SELECT MAX(tm.sender_type) FROM ticket_messages tm WHERE tm.ticket_id = t.id ORDER BY tm.created_at DESC LIMIT 1) AS last_sender
    FROM tickets t
    JOIN users bu ON bu.id = t.buyer_id
    LEFT JOIN orders o ON o.id = t.order_id
    LEFT JOIN users mu ON mu.id = o.merchant_id
    LEFT JOIN merchant_profile mp ON mp.merchant_user_id = o.merchant_id
    $where
    ORDER BY FIELD(t.status, 'escalated', 'open', 'pending', 'resolved'),
             t.updated_at DESC
", $params);

$counts = db_fetch_one($db, "
    SELECT
      COUNT(*) AS total,
      SUM(CASE WHEN status='open'      THEN 1 ELSE 0 END) AS open_n,
      SUM(CASE WHEN status='pending'   THEN 1 ELSE 0 END) AS pending_n,
      SUM(CASE WHEN status='resolved'  THEN 1 ELSE 0 END) AS resolved_n,
      SUM(CASE WHEN status='escalated' THEN 1 ELSE 0 END) AS escalated_n,
      SUM(CASE WHEN status IN ('open','pending') AND TIMESTAMPDIFF(HOUR, updated_at, NOW()) > 48 THEN 1 ELSE 0 END) AS stale_n
    FROM tickets
") ?: [];

$content = function () use ($tickets, $counts, $filter) {
    require __DIR__ . '/../../views/partials/super-admin-nav.php';
    ?>
    <div class="container">
      <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h1 class="h4 mb-0">Support Tickets</h1>
        <div class="btn-group">
          <?php foreach (['all','open','pending','escalated','resolved'] as $opt): ?>
            <a class="btn btn-sm btn-outline-secondary <?= $filter === $opt ? 'active' : '' ?>"
               href="?p=super-admin/tickets<?= $opt === 'all' ? '' : '&status=' . $opt ?>">
              <?= e(ucfirst($opt)) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <?php
        $kpis = [
            ['Total',     $counts['total']     ?? 0, 'primary'],
            ['Open',      $counts['open_n']    ?? 0, 'warning'],
            ['Pending',   $counts['pending_n'] ?? 0, 'info'],
            ['Escalated', $counts['escalated_n']?? 0, 'danger'],
            ['Resolved',  $counts['resolved_n']?? 0, 'success'],
            ['Stale > 48h', $counts['stale_n'] ?? 0, 'danger'],
        ];
        foreach ($kpis as [$label, $val, $color]): ?>
          <div class="col-6 col-md-2">
            <div class="bg-white border rounded-3 p-2 text-center">
              <div class="small text-muted"><?= e($label) ?></div>
              <div class="h5 mb-0 text-<?= $color ?>"><?= (int)$val ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="bg-white border rounded-3 overflow-hidden">
        <table class="table table-sm mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Subject</th>
              <th>Buyer</th>
              <th>Seller</th>
              <th>Order</th>
              <th>Status</th>
              <th>Last activity</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($tickets)): ?>
              <tr><td colspan="8" class="text-center text-muted py-4">No tickets in this view.</td></tr>
            <?php else: foreach ($tickets as $t):
                $status = (string)$t['status'];
                $badge = $status === 'resolved' ? 'success'
                       : ($status === 'escalated' ? 'danger'
                          : ($status === 'open' ? 'warning' : 'info'));
                $stale = in_array($status, ['open','pending'], true) && (int)$t['hours_since_update'] > 48;
                $lastSender = (string)($t['last_sender'] ?? '');
            ?>
              <tr<?= $stale ? ' class="table-warning"' : '' ?>>
                <td class="text-muted">#<?= (int)$t['id'] ?></td>
                <td>
                  <div class="fw-semibold"><?= e((string)$t['subject']) ?></div>
                  <div class="small text-muted"><?= (int)$t['message_count'] ?> message(s)<?= $lastSender ? ' · last from ' . e($lastSender) : '' ?></div>
                </td>
                <td class="small">
                  <div><?= e((string)$t['buyer_name']) ?></div>
                  <div class="text-muted"><?= e((string)$t['buyer_email']) ?></div>
                </td>
                <td class="small">
                  <?php if (!empty($t['merchant_business'])): ?>
                    <div><?= e((string)$t['merchant_business']) ?></div>
                    <div class="text-muted"><?= e((string)$t['merchant_name']) ?></div>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
                <td class="small">
                  <?php if (!empty($t['order_no'])): ?>
                    <?= e((string)$t['order_no']) ?>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge bg-<?= $badge ?>"><?= e(ucfirst($status)) ?></span>
                  <?php if ($stale): ?>
                    <span class="badge bg-danger ms-1" title="No activity for over 48 hours">Stale</span>
                  <?php endif; ?>
                </td>
                <td class="small text-muted">
                  <?= e(date('d M H:i', strtotime((string)$t['updated_at']))) ?>
                  <div><?= (int)$t['hours_since_update'] ?>h ago</div>
                </td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-primary" href="?p=super-admin/ticket-detail&id=<?= (int)$t['id'] ?>">Open</a>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
};

require __DIR__ . '/../../views/layout.php';
