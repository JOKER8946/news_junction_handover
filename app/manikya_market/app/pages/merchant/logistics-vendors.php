<?php

declare(strict_types=1);

auth_require_role('super_admin');

$title = 'Logistics Vendors';

$merchantId = auth_user_id();

// List all logistics users with profile and merchant-specific agreed charge
$vendors = db_fetch_all($db, '
    SELECT u.id, u.email, u.full_name, u.phone, u.created_at, lp.company_name, lp.vehicle_no
    FROM users u
    LEFT JOIN logistics_profile lp ON lp.logistics_user_id = u.id
    WHERE u.role = :role
    ORDER BY u.created_at DESC
', ['role' => 'logistics']);

// For each vendor, fetch merchant agreed charge and paid details
$vendorRows = [];
foreach ($vendors as $v) {
    $vendorId = (int)$v['id'];
    $mp = db_fetch_one($db, 'SELECT logistics_service_charge, logistics_service_charge_type FROM merchant_profile WHERE merchant_user_id = :mid AND logistics_vendor_id = :vid LIMIT 1', ['mid' => $merchantId, 'vid' => $vendorId]);
    $agreedCharge = $mp ? $mp['logistics_service_charge'] : null;
    $chargeType = $mp ? $mp['logistics_service_charge_type'] : null;

    // Paid details: sum of shipping_amounts for orders handled by this vendor
    $paid = db_fetch_one($db, 'SELECT COUNT(*) AS c, COALESCE(SUM(shipping_amount),0) AS total FROM orders WHERE logistics_user_id = :vid AND status IN ("shipped","delivered")', ['vid' => $vendorId]);

    $vendorRows[] = [
        'id' => $vendorId,
        'email' => $v['email'],
        'name' => $v['full_name'],
        'phone' => $v['phone'],
        'created_at' => $v['created_at'],
        'company' => $v['company_name'],
        'vehicle_no' => $v['vehicle_no'],
        'agreed_charge' => $agreedCharge,
        'charge_type' => $chargeType,
        'orders_count' => (int)($paid['c'] ?? 0),
        'paid_total' => (float)($paid['total'] ?? 0),
    ];
}

$content = function () use ($vendorRows) {
    ?>
    <div class="container py-4">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h5 mb-0 d-flex align-items-center gap-2"><i data-lucide="truck" class="mm-icon"></i> Logistics Vendors</h1>
        <a class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1" href="?p=merchant/logistics-onboard"><i data-lucide="plus" class="mm-icon"></i> new</a>
      </div>

      <div class="bg-white border rounded-4 p-3 mm-card">
        <div class="table-responsive">
          <table class="table small">
            <thead>
              <tr>
                <th>Vendor</th>
                <th>Company</th>
                <th>Agreed Charge</th>
                <th>Orders (handled)</th>
                <th>Paid Total</th>
                <th>Created</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($vendorRows as $r): ?>
                <tr>
                  <td>
                    <div class="fw-semibold"><?= e((string)$r['name']) ?></div>
                    <div class="text-muted small"><?= e((string)$r['email']) ?><?= $r['phone'] ? ' • ' . e((string)$r['phone']) : '' ?></div>
                  </td>
                  <td><?= e((string)$r['company']) ?><?= $r['vehicle_no'] ? ' • ' . e((string)$r['vehicle_no']) : '' ?></td>
                  <td>
                    <?php if ($r['agreed_charge'] !== null): ?>
                      <?= e((string)$r['agreed_charge']) ?> <?= e((string)$r['charge_type']) ?>
                    <?php else: ?>
                      <span class="text-muted">Not agreed</span>
                    <?php endif; ?>
                  </td>
                  <td><?= (int)$r['orders_count'] ?></td>
                  <td>₹<?= number_format((float)$r['paid_total'], 2) ?></td>
                  <td><?= date('M d, Y', strtotime((string)$r['created_at'])) ?></td>
                  <td>
                    <a class="btn btn-sm btn-outline-primary" href="?p=merchant/logistics-vendor-edit&id=<?= (int)$r['id'] ?>">Edit</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php
};

require __DIR__ . '/../../views/layout.php';
