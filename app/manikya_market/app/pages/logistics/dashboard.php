<?php

declare(strict_types=1);

auth_require_role('logistics');

$title = 'Logistics Dashboard';

$logisticsId = auth_user_id();

// Get orders ready for pickup
// ensure invoice columns exist
ensure_order_column($db, 'invoice_file', "VARCHAR(255) NULL");
ensure_order_column($db, 'invoice_generated_at');

$readyOrders = db_fetch_all($db, '
  SELECT 
    o.id, o.order_no, o.status, o.created_at, o.buyer_id,
    o.invoice_file, o.invoice_generated_at,
    u.full_name AS buyer_name, u.phone AS buyer_phone, u.email AS buyer_email,
    ba.address_line1, ba.address_line2, ba.city, ba.state, ba.pincode,
    COUNT(oi.id) AS item_count, SUM(oi.qty_kg) AS total_kg
  FROM orders o
    JOIN users u ON u.id = o.buyer_id
    LEFT JOIN buyer_addresses ba ON ba.id = o.delivery_address_id
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE o.status IN ("packed", "ready_to_pick")
      AND (o.logistics_user_id IS NULL OR o.logistics_user_id = :logistics_id)
    GROUP BY o.id
    ORDER BY o.created_at DESC
', ['logistics_id' => $logisticsId]);

// Get picked/in-transit orders
$pickedOrders = db_fetch_all($db, '
  SELECT 
    o.id, o.order_no, o.status, o.picked_at, o.awb_number, 
    o.shipping_tracking_no, o.expected_delivery_date, o.buyer_id,
    o.invoice_file, o.invoice_generated_at,
    u.full_name AS buyer_name, u.phone AS buyer_phone
  FROM orders o
  JOIN users u ON u.id = o.buyer_id
  WHERE o.status IN ("shipped", "in_transit")
    AND (o.logistics_user_id IS NULL OR o.logistics_user_id = :logistics_id)
  ORDER BY o.picked_at DESC
  LIMIT 20
', ['logistics_id' => $logisticsId]);

$returnRequests = []; // return flow removed

$content = function () use ($readyOrders, $pickedOrders) {
    $success = flash_get('success');
    $error = flash_get('error');
    ?>
    <div class="container py-4">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h5 mb-0 d-flex align-items-center gap-2"><i data-lucide="truck" class="mm-icon"></i> Logistics Dashboard</h1>
      </div>

      <?php if ($success): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
      <?php endif; ?>

      <!-- Stats -->
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <div class="bg-white border rounded-4 p-3 mm-card">
            <div class="d-flex align-items-center gap-2">
              <div style="font-size: 1.5rem;">📦</div>
              <div>
                <div class="text-muted small">Ready to Pick</div>
                <div class="h6 mb-0"><?= count($readyOrders) ?> Orders</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="bg-white border rounded-4 p-3 mm-card">
            <div class="d-flex align-items-center gap-2">
              <div style="font-size: 1.5rem;">🚚</div>
              <div>
                <div class="text-muted small">In Transit</div>
                <div class="h6 mb-0"><?= count($pickedOrders) ?> Orders</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="bg-white border rounded-4 p-3 mm-card">
            <div class="d-flex align-items-center gap-2">
              <div style="font-size: 1.5rem;">✅</div>
              <div>
                <div class="text-muted small">Total Capacity</div>
                <div class="h6 mb-0"><?= array_sum(array_map(fn($o) => (float)($o['total_kg'] ?? 0), array_merge($readyOrders, $pickedOrders))) ?> kg</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Ready to Pick Orders -->
      <div class="bg-white border rounded-4 p-4 mm-card mb-4">
        <h3 class="h6 mb-3 d-flex align-items-center gap-2">
          <i data-lucide="package" style="width:20px;height:20px;"></i> Orders Ready for Pickup
        </h3>

        <?php if (!$readyOrders): ?>
          <div class="text-muted small">No orders ready for pickup</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table align-middle mb-0 small">
              <thead>
                <tr>
                  <th>Order #</th>
                  <th>Buyer</th>
                  <th>Phone</th>
                  <th>Items</th>
                  <th>Delivery Location</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($readyOrders as $order): ?>
                  <tr>
                    <td><strong><?= e((string)$order['order_no']) ?></strong></td>
                    <td><?= e((string)$order['buyer_name']) ?></td>
                    <td><?= e((string)$order['buyer_phone']) ?></td>
                    <td><?= (int)$order['item_count'] ?> items • <?= e((string)$order['total_kg']) ?>kg</td>
                    <td>
                      <div class="small">
                        <?= e((string)($order['address_line1'] ?? '')) ?>
                        <br>
                        <?= e((string)($order['city'] ?? '')) ?>, <?= e((string)($order['state'] ?? '')) ?> <?= e((string)($order['pincode'] ?? '')) ?>
                      </div>
                    </td>
                    <td class="text-end">
                      <div class="d-inline-flex align-items-center gap-2">
                        <a class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1" href="?p=logistics/order-detail&id=<?= (int)$order['id'] ?>">
                          <i data-lucide="eye" class="mm-icon"></i> View
                        </a>
                        <?php if (!empty($order['invoice_file']) || !empty($order['invoice_generated_at'])): ?>
                          <?php $lts = !empty($order['invoice_generated_at']) ? date('M d, Y H:i', strtotime((string)$order['invoice_generated_at'])) : 'Invoice available'; ?>
                          <?php if (!empty($order['invoice_file'])): ?>
                            <a class="btn btn-sm btn-outline-primary" href="<?= e($order['invoice_file']) ?>" target="_blank" title="Invoice generated at <?= e($lts) ?>">Invoice</a>
                          <?php else: ?>
                            <a class="btn btn-sm btn-outline-primary" href="?p=logistics/order-invoice&id=<?= (int)$order['id'] ?>" title="Invoice generated at <?= e($lts) ?>">Invoice</a>
                          <?php endif; ?>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <!-- Return Pickup Requests -->
          <!-- Return pickup section removed -->

      <!-- In Transit Orders -->
      <div class="bg-white border rounded-4 p-4 mm-card">
        <h3 class="h6 mb-3 d-flex align-items-center gap-2">
          <i data-lucide="send" style="width:20px;height:20px;"></i> In Transit / Shipped Orders
        </h3>

        <?php if (!$pickedOrders): ?>
          <div class="text-muted small">No orders in transit</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table align-middle mb-0 small">
              <thead>
                <tr>
                  <th>Order #</th>
                  <th>Buyer</th>
                  <th>Tracking Number</th>
                  <th>Expected Delivery</th>
                  <th>Status</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pickedOrders as $order): ?>
                  <tr>
                    <td><strong><?= e((string)$order['order_no']) ?></strong></td>
                    <td><?= e((string)$order['buyer_name']) ?></td>
                    <td>
                      <?php if (!empty($order['shipping_tracking_no'])): ?>
                        <span class="badge text-bg-info"><?= e((string)$order['shipping_tracking_no']) ?></span>
                      <?php else: ?>
                        <span class="badge text-bg-secondary">Pending</span>
                      <?php endif; ?>
                    </td>
                    <td><?= !empty($order['expected_delivery_date']) ? date('M d, Y', strtotime((string)$order['expected_delivery_date'])) : '-' ?></td>
                    <td><span class="badge text-bg-warning"><?= e((string)$order['status']) ?></span></td>
                    <td class="text-end">
                      <div class="d-inline-flex align-items-center gap-2">
                        <a class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1" href="?p=logistics/order-detail&id=<?= (int)$order['id'] ?>">
                          <i data-lucide="edit" class="mm-icon"></i> Update
                        </a>
                        <?php if (!empty($order['invoice_file']) || !empty($order['invoice_generated_at'])): ?>
                          <?php if (!empty($order['invoice_file'])): ?>
                            <a class="btn btn-sm btn-outline-primary" href="<?= e($order['invoice_file']) ?>" target="_blank">Invoice</a>
                          <?php else: ?>
                            <a class="btn btn-sm btn-outline-primary" href="?p=logistics/order-invoice&id=<?= (int)$order['id'] ?>">Invoice</a>
                          <?php endif; ?>
                        <?php endif; ?>
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
?>
