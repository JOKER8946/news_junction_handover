<?php

declare(strict_types=1);

auth_require_role('super_admin');

$title = 'Sellers';

if (request_method() === 'POST') {
    $action = post_string('action');
    $merchantId = (int)post_string('merchant_id');

    if ($merchantId <= 0) {
        flash_set('error', 'Invalid seller.');
        redirect_to('super-admin/merchants');
    }

    if ($action === 'register_ekart_pickup') {
        $res = ekart_register_pickup_location($db, $merchantId);
        if ($res['ok']) {
            flash_set('success', 'Pickup registered with Ekart — alias: ' . ($res['alias'] ?: '(none)') . ' (' . ($res['mode'] ?? '') . ')');
        } else {
            flash_set('error', 'Ekart pickup registration failed: ' . ($res['error'] ?? 'unknown error'));
        }
        $filterStatus = (string)($_GET['status'] ?? '');
        redirect_to('super-admin/merchants' . ($filterStatus !== '' ? '?status=' . urlencode($filterStatus) : ''));
    }

    $allowedTransitions = ['approve' => 'approved', 'disable' => 'disabled', 'reenable' => 'approved'];
    if (!isset($allowedTransitions[$action])) {
        flash_set('error', 'Unknown action.');
        redirect_to('super-admin/merchants');
    }

    $newStatus = $allowedTransitions[$action];
    db_exec($db, 'UPDATE merchant_profile SET status = :s WHERE merchant_user_id = :uid', [
        's'   => $newStatus,
        'uid' => $merchantId,
    ]);

    flash_set('success', 'Seller status updated to ' . $newStatus . '.');
    $filterStatus = (string)($_GET['status'] ?? '');
    redirect_to('super-admin/merchants' . ($filterStatus !== '' ? '?status=' . urlencode($filterStatus) : ''));
}

$filter = (string)($_GET['status'] ?? 'all');
$validFilters = ['all', 'pending', 'approved', 'disabled'];
if (!in_array($filter, $validFilters, true)) {
    $filter = 'all';
}

// Only real merchants. Super-admin accounts may carry a merchant_profile row
// (used for the platform's invoice issuer details + Ekart warehouse fallback),
// but they should never appear in the Sellers list — they don't ship products
// and showing a "Register Ekart pickup" button on their row is misleading.
$where  = " WHERE u.role = 'merchant'";
$params = [];
if ($filter !== 'all') {
    $where .= ' AND mp.status = :status';
    $params['status'] = $filter;
}

$merchants = db_fetch_all($db,
    "SELECT u.id, u.full_name, u.email, u.phone, u.created_at,
            mp.status, mp.business_name, mp.business_state, mp.business_pincode,
            mp.ekart_pickup_alias,
            mp.delhivery_warehouse_pincode, mp.delhivery_warehouse_phone, mp.delhivery_warehouse_address,
            c.name AS primary_category,
            (SELECT COUNT(*) FROM products p WHERE p.merchant_id = u.id) AS product_count
     FROM users u
     INNER JOIN merchant_profile mp ON mp.merchant_user_id = u.id
     LEFT JOIN categories c ON c.id = mp.primary_category_id
     $where
     ORDER BY FIELD(mp.status, 'pending', 'approved', 'disabled'), u.created_at DESC",
    $params
);

$content = function () use ($merchants, $filter) {
    require __DIR__ . '/../../views/partials/super-admin-nav.php';
    $success = flash_get('success');
    $error   = flash_get('error');
    ?>
    <div class="container">
      <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h1 class="h4 mb-0">Sellers</h1>
        <div class="btn-group" role="group">
          <?php foreach (['all', 'pending', 'approved', 'disabled'] as $opt): ?>
            <a class="btn btn-sm btn-outline-secondary <?= $filter === $opt ? 'active' : '' ?>"
               href="?p=super-admin/merchants&status=<?= e($opt) ?>"><?= e(ucfirst($opt)) ?></a>
          <?php endforeach; ?>
        </div>
      </div>

      <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
      <?php if ($error):   ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

      <div class="bg-white border rounded-3 overflow-hidden">
        <table class="table table-sm mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>Seller</th>
              <th>Business</th>
              <th>Primary category</th>
              <th>Contact</th>
              <th class="text-center">Products</th>
              <th>Status</th>
              <th>Joined</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($merchants)): ?>
              <tr><td colspan="8" class="text-muted text-center py-4">No sellers found.</td></tr>
            <?php else: foreach ($merchants as $m): ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?= e((string)$m['full_name']) ?></div>
                  <div class="small text-muted">ID: <?= (int)$m['id'] ?></div>
                </td>
                <td>
                  <div><?= e((string)($m['business_name'] ?? '—')) ?></div>
                  <div class="small text-muted"><?= e((string)($m['business_state'] ?? '')) ?> <?= e((string)($m['business_pincode'] ?? '')) ?></div>
                </td>
                <td>
                  <?php if (!empty($m['primary_category'])): ?>
                    <span class="badge bg-light text-dark border"><?= e((string)$m['primary_category']) ?></span>
                  <?php else: ?>
                    <span class="text-muted small">—</span>
                  <?php endif; ?>
                </td>
                <td class="small">
                  <div><?= e((string)$m['email']) ?></div>
                  <div class="text-muted"><?= e((string)($m['phone'] ?? '')) ?></div>
                </td>
                <td class="text-center"><?= (int)$m['product_count'] ?></td>
                <td>
                  <?php
                  $status = (string)$m['status'];
                  $badge = $status === 'approved' ? 'success' : ($status === 'pending' ? 'warning' : 'secondary');
                  ?>
                  <span class="badge bg-<?= $badge ?>"><?= e(ucfirst($status)) ?></span>
                </td>
                <td class="small text-muted"><?= e(date('d M Y', strtotime((string)$m['created_at']))) ?></td>
                <td class="text-end">
                  <form method="post" class="d-inline">
                    <input type="hidden" name="merchant_id" value="<?= (int)$m['id'] ?>">
                    <?php if ($status === 'pending'): ?>
                      <button class="btn btn-sm btn-success" name="action" value="approve">Approve</button>
                      <button class="btn btn-sm btn-outline-danger" name="action" value="disable">Reject</button>
                    <?php elseif ($status === 'approved'): ?>
                      <button class="btn btn-sm btn-outline-danger" name="action" value="disable">Disable</button>
                    <?php else: ?>
                      <button class="btn btn-sm btn-outline-success" name="action" value="reenable">Re-enable</button>
                    <?php endif; ?>
                  </form>
                  <?php if ($status === 'approved'): ?>
                    <form method="post" class="d-inline ms-1">
                      <input type="hidden" name="merchant_id" value="<?= (int)$m['id'] ?>">
                      <?php $hasAlias = !empty($m['ekart_pickup_alias']); ?>
                      <button class="btn btn-sm <?= $hasAlias ? 'btn-outline-secondary' : 'btn-outline-primary' ?>"
                              name="action" value="register_ekart_pickup"
                              title="<?= $hasAlias ? 'Re-register pickup (alias: ' . e((string)$m['ekart_pickup_alias']) . ')' : 'Register this merchant\'s warehouse with Ekart' ?>">
                        <?= $hasAlias ? '✓ Ekart pickup' : 'Register Ekart pickup' ?>
                      </button>
                    </form>
                  <?php endif; ?>
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
