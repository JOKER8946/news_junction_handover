<?php

declare(strict_types=1);

auth_require_role('merchant');

$title = 'Order';
$merchantId = (int)auth_user_id();

$id = (int)($_GET['id'] ?? 0);

$order = db_fetch_one($db, 'SELECT o.*, u.full_name AS buyer_name, u.phone AS buyer_phone, u.email AS buyer_email FROM orders o JOIN users u ON u.id = o.buyer_id WHERE o.id = :id AND o.merchant_id = :mid LIMIT 1', ['id' => $id, 'mid' => $merchantId]);
if (!$order) {
    flash_set('error', 'Order not found or you do not have access to it.');
    redirect_to('merchant/dashboard');
}

$address = null;
if (!empty($order['delivery_address_id'])) {
    $address = db_fetch_one($db, 'SELECT * FROM buyer_addresses WHERE id = :id LIMIT 1', ['id' => (int)$order['delivery_address_id']]);
}

$items = db_fetch_all($db, 'SELECT oi.*, p.name, p.image_path, p.unit FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = :order_id ORDER BY oi.id ASC', [
    'order_id' => $id,
]);

// Fetch available logistics vendors for merchant to choose from
$vendors = db_fetch_all($db, '
  SELECT u.id, u.full_name, COALESCE(lp.company_name, "") AS company_name
  FROM users u
  LEFT JOIN logistics_profile lp ON lp.logistics_user_id = u.id
  WHERE u.role = :role
  ORDER BY u.full_name ASC
', ['role' => 'logistics']) ?: [];

// Allow merchant to view/update more statuses including delivered and returned
$allowedStatuses = ['ready_to_pick', 'packed', 'paid', 'shipped', 'delivered', 'cancelled', 'returned', 'pending_return', 'pending_logistics_pickup', 'return_picked_up'];

// Friendly labels for statuses shown to merchant
$statusLabels = [
  'ready_to_pick' => 'Ready to pick',
  'packed' => 'Packed',
  'paid' => 'Paid',
  'cancelled' => 'Cancelled',
  'shipped' => 'Shipped',
  'delivered' => 'Delivered',
  'returned' => 'Returned',
  'pending_return' => 'Pending Return (Awaiting Seller Approval)',
  'pending_logistics_pickup' => 'Pending Return Pickup',
  'return_picked_up' => 'Return Picked Up',
];

// If a payment record exists and is marked paid, ensure order status is set to paid (but don't downgrade advanced statuses or terminal statuses)
$paymentPaid = db_fetch_one($db, 'SELECT id FROM payments WHERE order_id = :order_id AND status = :st LIMIT 1', ['order_id' => $id, 'st' => 'paid']);
$advancedStatuses = ['packed', 'ready_to_pick', 'shipped', 'in_transit', 'delivered'];
$terminalStatuses = ['cancelled', 'returned']; // don't change these once set
$currentStatusIsAdvanced = in_array($order['status'] ?? '', $advancedStatuses, true);
$currentStatusIsTerminal = in_array($order['status'] ?? '', $terminalStatuses, true);
if ($paymentPaid && ($order['status'] ?? '') !== 'paid' && !$currentStatusIsAdvanced && !$currentStatusIsTerminal) {
  try {
    db_exec($db, 'UPDATE orders SET status = :status WHERE id = :id', ['status' => 'paid', 'id' => $id]);
    // run inventory sale operations only when moving into paid
    try {
      inventory_sale_for_order($db, $id);
    } catch (Throwable $t) {
      // inventory may not be configured on fresh installs
    }
    // create tracking record
    try {
      db_exec($db, '
        INSERT INTO order_tracking (order_id, status, updated_by_type, updated_by_id, notes, updated_at) 
        VALUES (:order_id, :status, :updated_by_type, :updated_by_id, :notes, NOW())
      ', [
        'order_id' => $id,
        'status' => 'paid',
        'updated_by_type' => 'system',
        'updated_by_id' => 0,
        'notes' => 'Auto-set to paid based on payment record',
      ]);
    } catch (Throwable $t) {
      // ignore if tracking table missing
    }
    // refresh order data
    $order = db_fetch_one($db, 'SELECT o.*, u.full_name AS buyer_name, u.phone AS buyer_phone, u.email AS buyer_email FROM orders o JOIN users u ON u.id = o.buyer_id WHERE o.id = :id LIMIT 1', ['id' => $id]);
  } catch (Throwable $t) {
    // ignore failures here; page will continue to render with existing status
  }
}

$error = '';
$success = '';
$viewOnly = isset($_GET['view']) && $_GET['view'] === '1';

if (request_method() === 'POST') {
    $action = post_string('action');
    $oldStatus = (string)($order['status'] ?? '');
    
    if ($action === 'update_status') {
      $status = post_string('status');
      if (in_array($status, $allowedStatuses, true)) {
        // set status and timestamp for the stage
        $statusTsMap = [
          'paid' => 'paid_at',
          'packed' => 'packed_at',
          'ready_to_pick' => 'ready_to_pick_at',
          'shipped' => 'shipped_at',
          'in_transit' => 'in_transit_at',
          'delivered' => 'delivered_at',
          'returned' => 'returned_at',
          'cancelled' => 'cancelled_at',
        ];
        // :status_cur carries the same value as :status. Native prepares are on
        // (ATTR_EMULATE_PREPARES => false), so a placeholder cannot be reused
        // within one statement — each occurrence needs its own name.
        $params = ['status' => $status, 'id' => $id, 'status_cur' => $status];
        // Atomic claim: only the first request to flip status away from its
        // current value actually performs the work. A concurrent duplicate POST
        // (or a refresh-induced double-submit) sees rowCount == 0 and skips
        // side-effects like referral credit, preventing double-credit.
        $rowsChanged = 0;
        if (isset($statusTsMap[$status])) {
          ensure_order_column($db, $statusTsMap[$status]);
          $stmt = $db->prepare(
            'UPDATE orders SET status = :status, ' . $statusTsMap[$status] . ' = NOW() WHERE id = :id AND status != :status_cur'
          );
          $stmt->execute($params);
          $rowsChanged = $stmt->rowCount();
        } else {
          $stmt = $db->prepare('UPDATE orders SET status = :status WHERE id = :id AND status != :status_cur');
          $stmt->execute($params);
          $rowsChanged = $stmt->rowCount();
        }

        // Only run side-effects when this request actually changed the row.
        if ($rowsChanged > 0 && $status === 'paid' && $oldStatus !== 'paid') {
          try {
            inventory_sale_for_order($db, $id);
          } catch (Throwable $t) {
            flash_set('error', 'Inventory tables are missing. Run setup.php again.');
          }
        }

        // Trigger referral reward when order is delivered (only on actual transition)
        if ($rowsChanged > 0 && $status === 'delivered' && $oldStatus !== 'delivered') {
          try {
            referral_complete($db, $id);
          } catch (Throwable $t) {
            // referral reward is best-effort, don't block status update
          }
          // Credit the merchant's earnings wallet (already idempotent via existing-row check).
          try {
            merchant_wallet_credit_for_order($db, $id);
          } catch (Throwable $t) {
            error_log('merchant_wallet_credit_for_order failed: ' . $t->getMessage());
          }
        }

        // Ring super admin's popup on a real merchant-driven status change.
        // Gated on $rowsChanged so a refresh-induced double-submit, which the
        // atomic claim above already absorbs, doesn't ring twice.
        if ($rowsChanged > 0 && $status !== $oldStatus) {
          alert_push_status_update($db, $id, $status);
        }

        // Buyer status emails on key transitions.
        if (in_array($status, ['shipped', 'in_transit'], true) && $oldStatus !== $status) {
          try { notify_order_event($db, $id, 'shipped'); } catch (Throwable $t) { error_log('notify shipped: ' . $t->getMessage()); }
        }
        if ($status === 'delivered' && $oldStatus !== 'delivered') {
          try { notify_order_event($db, $id, 'delivered'); } catch (Throwable $t) { error_log('notify delivered: ' . $t->getMessage()); }
        }

        if ($status === 'cancelled' && $oldStatus !== 'cancelled') {
          try {
            inventory_cancel_order($db, $id);
          } catch (Throwable $t) {
            // Inventory cancellation may fail if tables not configured, but don't block the cancellation
          }
        }

        // Create tracking record
        try {
          db_exec($db, '
            INSERT INTO order_tracking (order_id, status, updated_by_type, updated_by_id, notes, updated_at) 
            VALUES (:order_id, :status, :updated_by_type, :updated_by_id, :notes, NOW())
          ', [
            'order_id' => $id,
            'status' => $status,
            'updated_by_type' => 'merchant',
            'updated_by_id' => auth_user_id(),
            'notes' => 'Status updated to ' . str_replace('_', ' ', $status),
          ]);
        } catch (Throwable $t) {
          // Tracking table might not exist yet
        }

        $success = 'Order status updated!';
        // refresh order after update
        $order = db_fetch_one($db, 'SELECT o.*, u.full_name AS buyer_name, u.phone AS buyer_phone, u.email AS buyer_email FROM orders o JOIN users u ON u.id = o.buyer_id WHERE o.id = :id LIMIT 1', ['id' => $id]);
      }
    }
    
    else if ($action === 'ready_to_pick') {
      // Mark order as packed and ready for logistics pickup with box quantity and assign vendor
      if ($oldStatus === 'paid' || $oldStatus === 'packed') {
        try {
          $boxQuantity = (int)($_POST['box_quantity'] ?? 0);
          $vendorId = isset($_POST['logistics_vendor_id']) ? (int)$_POST['logistics_vendor_id'] : 0;
          if ($boxQuantity <= 0) {
            throw new Exception('Box quantity must be greater than 0');
          }

          // validate vendor if provided
          if ($vendorId > 0) {
            $v = db_fetch_one($db, 'SELECT id, full_name FROM users WHERE id = :id AND role = :role LIMIT 1', ['id' => $vendorId, 'role' => 'logistics']);
            if (!$v) {
              throw new Exception('Selected vendor is invalid');
            }
            $vendorName = $v['full_name'];
          } else {
            $vendorName = '';
          }

          ensure_order_column($db, 'ready_to_pick_at');
          ensure_order_column($db, 'box_quantity', 'INT DEFAULT 0');
          ensure_order_column($db, 'logistics_user_id', 'INT NULL');
          db_exec($db, 'UPDATE orders SET status = :status, ready_to_pick_at = NOW(), box_quantity = :box_qty, logistics_user_id = :vid WHERE id = :id', [
            'status' => 'ready_to_pick',
            'box_qty' => $boxQuantity,
            'vid' => $vendorId > 0 ? $vendorId : null,
            'id' => $id
          ]);

          // Ring super admin's popup. This action sets status directly and
          // never goes through 'update_status' above, so it needs its own call.
          alert_push_status_update($db, $id, 'ready_to_pick');

          // Create tracking record
          $notes = 'Order packed in ' . $boxQuantity . ' box(es) and ready for logistics pickup';
          if (!empty($vendorName)) {
            $notes .= ' — assigned to ' . $vendorName;
          }
          db_exec($db, '
            INSERT INTO order_tracking (order_id, status, updated_by_type, updated_by_id, notes, updated_at) 
            VALUES (:order_id, :status, :updated_by_type, :updated_by_id, :notes, NOW())
          ', [
            'order_id' => $id,
            'status' => 'ready_to_pick',
            'updated_by_type' => 'merchant',
            'updated_by_id' => auth_user_id(),
            'notes' => $notes,
          ]);

          // Auto-create Ekart shipment if configured
          $delhiveryMsg = '';
          delhivery_ensure_schema($db);
          $delhiveryConfig = delhivery_get_config($db);
          if ($delhiveryConfig) {
              $shipResult = delhivery_create_shipment($db, $id);
              if ($shipResult['success']) {
                  $delhiveryMsg = ' 🚚 Ekart shipment created (AWB: ' . $shipResult['waybill'] . ').';
                  $pickupResult = delhivery_request_pickup($db, $id);
                  if ($pickupResult['success']) {
                      $delhiveryMsg .= ' Pickup scheduled.';
                  }
              } else {
                  $delhiveryMsg = ' ⚠️ Ekart shipment failed: ' . ($shipResult['error'] ?? 'Unknown error') . '. You can retry from order details.';
              }
          }

          flash_set('success', 'Order marked as ready for pickup in ' . $boxQuantity . ' box(es)!' . (!empty($vendorName) ? ' Assigned to ' . $vendorName . '.' : '') . $delhiveryMsg);
          redirect_to('merchant/dashboard');
        } catch (Throwable $t) {
          $error = 'Failed to update order: ' . $t->getMessage();
        }
      } else {
        $error = 'Order must be paid before marking as ready for pickup';
      }
    }
    
    elseif ($action === 'mark_payment_paid') {
        try {
            db_exec($db, 'UPDATE payments SET status = :status WHERE order_id = :order_id', ['status' => 'paid', 'order_id' => $id]);
            $success = 'Payment marked as confirmed!';
            // Refresh order to pick up the payment status change
            $order = db_fetch_one($db, 'SELECT o.*, u.full_name AS buyer_name, u.phone AS buyer_phone, u.email AS buyer_email FROM orders o JOIN users u ON u.id = o.buyer_id WHERE o.id = :id LIMIT 1', ['id' => $id]);
        } catch (Throwable $t) {
            $error = 'Failed to mark payment: ' . $t->getMessage();
        }
    }

    elseif ($action === 'delhivery_create') {
        delhivery_ensure_schema($db);
        $result = delhivery_create_shipment($db, $id);
        if ($result['success']) {
            $success = 'Ekart shipment created. AWB: ' . $result['waybill'] . '.';
            $pickupResult = delhivery_request_pickup($db, $id);
            if ($pickupResult['success']) {
                $success .= ' Pickup scheduled.';
            }
        } else {
            $error = 'Ekart shipment failed: ' . ($result['error'] ?? 'Unknown error');
        }
        $order = db_fetch_one($db, 'SELECT o.*, u.full_name AS buyer_name, u.phone AS buyer_phone, u.email AS buyer_email FROM orders o JOIN users u ON u.id = o.buyer_id WHERE o.id = :id LIMIT 1', ['id' => $id]);
    }
}

$content = function () use ($order, $address, $items, $allowedStatuses, $statusLabels, $error, $success, $viewOnly, $vendors) {
    ?>
    <div class="container py-4" style="max-width: 980px;">
      <div class="d-flex align-items-center justify-content-between mb-4 gap-2">
        <h1 class="h5 mb-0 d-flex align-items-center gap-2 flex-wrap" style="min-width: 0;">
          <i data-lucide="shopping-bag" class="mm-icon flex-shrink-0"></i> 
          <span style="word-break: break-word;">Order #<?= e((string)$order['order_no']) ?></span>
        </h1>
        <div class="d-flex gap-2 flex-nowrap flex-shrink-0">
          <a class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1 text-nowrap" href="?p=merchant/order-invoice&id=<?= (int)$order['id'] ?>" target="_blank"><i data-lucide="file-text" class="mm-icon"></i> <span class="d-none d-sm-inline">Invoice</span></a>
          <a class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1 text-nowrap" href="?p=merchant/dashboard"><i data-lucide="arrow-left" class="mm-icon"></i> <span class="d-none d-sm-inline">Back</span></a>
        </div>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger alert-sm"><?= e($error) ?></div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success alert-sm"><?= e($success) ?></div>
      <?php endif; ?>

      <div class="row g-3">
        <div class="col-12 col-lg-7">
          <div class="bg-white border rounded-4 p-3 mm-card">
            <div class="fw-semibold d-flex align-items-center gap-2"><i data-lucide="list" class="mm-icon"></i> Items</div>
            <div class="d-grid gap-2 mt-3">
              <?php foreach ($items as $it): ?>
                <div class="border rounded-4 p-2 p-sm-3">
                  <div class="d-flex gap-2 gap-sm-3">
                    <div style="width: 48px; flex-shrink: 0;">
                      <?php if (!empty($it['image_path'])): ?>
                        <img src="<?= e((string)$it['image_path']) ?>" alt="<?= e((string)$it['name']) ?>" class="rounded-3" style="width:48px;height:48px;object-fit:cover;">
                      <?php else: ?>
                        <div class="bg-light rounded-3 d-flex align-items-center justify-content-center" style="width:48px;height:48px;"><i data-lucide="image" class="mm-icon" style="width:20px;height:20px;"></i></div>
                      <?php endif; ?>
                    </div>
                    <div class="flex-grow-1 min-width-0">
                      <div class="fw-semibold small text-truncate"><?= e((string)$it['name']) ?></div>
                      <?php $iUnit = product_unit_label($it['unit'] ?? 'kg'); ?>
                      <div class="text-muted small text-truncate"><?= e((string)$it['qty_kg']) ?> <?= e($iUnit) ?> • ₹<?= e((string)$it['price_per_kg']) ?>/<?= e($iUnit) ?></div>
                    </div>
                    <div class="fw-semibold small text-nowrap">₹<?= e((string)$it['line_total']) ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-5">
          <div class="bg-white border rounded-4 p-3 mm-card">
            <div class="fw-semibold d-flex align-items-center gap-2"><i data-lucide="user" class="mm-icon"></i> Buyer</div>
            <div class="mt-2"><span class="text-muted small">Name:</span> <span class="text-truncate d-block"><?= e((string)$order['buyer_name']) ?></span></div>
            <div class="mt-1"><span class="text-muted small">Phone:</span> <span class="text-truncate d-block"><?= e(mask_phone((string)($order['buyer_phone'] ?? ''))) ?></span></div>
            <div class="mt-1"><span class="text-muted small">Email:</span> <span class="text-truncate d-block"><?= e(mask_email((string)$order['buyer_email'])) ?></span></div>
            <div class="mt-1 small text-muted">Use the order's support ticket to contact the buyer.</div>
          </div>

          <div class="bg-white border rounded-4 p-3 mm-card mt-3">
            <div class="fw-semibold d-flex align-items-center gap-2"><i data-lucide="map-pin" class="mm-icon"></i> Delivery Address</div>
            <?php if (!$address): ?>
              <div class="text-muted mt-2 small">No address</div>
            <?php else: ?>
              <div class="mt-2 fw-semibold small"><?= e((string)($address['label'] ?: 'Address')) ?></div>
              <div class="text-muted small text-truncate"><?= e((string)$address['address_line1']) ?><?= $address['address_line2'] ? ', ' . e((string)$address['address_line2']) : '' ?></div>
              <div class="text-muted small"><?= e((string)$address['city']) ?>, <?= e((string)$address['state']) ?> - <?= e((string)$address['pincode']) ?></div>
            <?php endif; ?>
          </div>

          <div class="bg-white border rounded-4 p-3 mm-card mt-3">
            <?php if (!($viewOnly ?? false)): ?>
            <div class="fw-semibold d-flex align-items-center gap-2"><i data-lucide="badge-check" class="mm-icon"></i> Status & Actions</div>
            
            <div class="mt-3">
              <form method="post" class="mb-3">
                <input type="hidden" name="action" value="update_status">
                <div class="d-flex gap-2 flex-column flex-sm-row">
                  <select class="form-select form-select-sm" name="status">
                    <?php foreach ($allowedStatuses as $st): ?>
                      <option value="<?= e($st) ?>" <?= ((string)$order['status'] === $st) ? 'selected' : '' ?>><?= e($statusLabels[$st] ?? ucwords(str_replace('_', ' ', $st))) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn btn-mm btn-sm d-inline-flex align-items-center justify-content-center gap-1 text-nowrap" type="submit"><i data-lucide="save" class="mm-icon"></i> <span class="d-none d-sm-inline">Update</span></button>
                </div>
              </form>

              <!-- Mark Payment Paid Button -->
              <?php if ((string)$order['status'] === 'new'): ?>
                <form method="post" class="mb-3">
                  <input type="hidden" name="action" value="mark_payment_paid">
                  <button class="btn btn-primary btn-sm d-flex align-items-center justify-content-center gap-2 w-100" type="submit">
                    <i data-lucide="credit-card" class="mm-icon"></i> <span class="d-none d-sm-inline">Confirm Payment Received</span><span class="d-sm-none">Confirm Payment</span>
                  </button>
                </form>
              <?php endif; ?>

              <!-- Ready to Pick Button -->
              <?php if ($order['status'] === 'paid' || $order['status'] === 'packed'): ?>
                <form method="post" class="mb-3">
                  <div class="mb-2">
                    <label class="form-label small">Number of Boxes</label>
                    <input type="number" name="box_quantity" class="form-control form-control-sm" min="1" value="1" required>
                  </div>
                  <input type="hidden" name="action" value="ready_to_pick">
                  <button class="btn btn-success btn-sm d-flex align-items-center justify-content-center gap-2 w-100" type="submit">
                    <i data-lucide="check-circle" class="mm-icon"></i> <span class="d-none d-sm-inline">Mark Packed & Ready for Pickup</span><span class="d-sm-none">Ready for Pickup</span>
                  </button>
                </form>
              <?php endif; ?>

              <!-- Delhivery Shipment -->
              <?php if (!in_array((string)$order['status'], ['new', 'cancelled'], true)): ?>
                <?php if (!empty($order['delhivery_waybill'])): ?>
                  <div class="alert alert-info py-2 px-3 small mb-3 d-flex align-items-center gap-2">
                    <i data-lucide="truck" class="mm-icon"></i>
                    <div>
                      <div class="fw-semibold">Ekart shipment created</div>
                      <div class="text-muted">AWB: <?= e((string)$order['delhivery_waybill']) ?></div>
                    </div>
                  </div>
                <?php else: ?>
                  <form method="post" class="mb-3" onsubmit="this.querySelector('button').disabled=true;">
                    <input type="hidden" name="action" value="delhivery_create">
                    <button class="btn btn-outline-primary btn-sm d-flex align-items-center justify-content-center gap-2 w-100" type="submit">
                      <i data-lucide="truck" class="mm-icon"></i> <span>Create Ekart Shipment</span>
                    </button>
                  </form>
                <?php endif; ?>
              <?php endif; ?>
            </div>
            <?php endif; ?>

            <hr>
            <?php if (!empty($order['delivered_at'])): ?>
              <div class="mb-2 small text-success">Delivered: <?= date('M d, Y H:i', strtotime((string)$order['delivered_at'])) ?></div>
            <?php endif; ?>
            <?php if (!empty($order['returned_at'])): ?>
              <div class="mb-2 small text-danger">Returned: <?= date('M d, Y H:i', strtotime((string)$order['returned_at'])) ?>
                <?php if (!empty($order['return_reason'])): ?><div class="text-muted small">Reason: "<?= e((string)$order['return_reason']) ?>"</div><?php endif; ?>
              </div>
            <?php endif; ?>
            <?php if (!empty($order['cancelled_at'])): ?>
              <div class="mb-2 small text-danger">Cancelled: <?= date('M d, Y H:i', strtotime((string)$order['cancelled_at'])) ?>
                <?php if (!empty($order['cancel_reason'])): ?><div class="text-muted small">Reason: "<?= e((string)$order['cancel_reason']) ?>"</div><?php endif; ?>
              </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center"><div class="text-muted small">Subtotal</div><div class="text-end">₹<?= e((string)$order['subtotal_amount']) ?></div></div>
            <div class="d-flex justify-content-between align-items-center mt-1"><div class="text-muted small">Shipping</div><div class="text-end">₹<?= e((string)$order['shipping_amount']) ?></div></div>
            <div class="d-flex justify-content-between align-items-center mt-2"><div class="fw-semibold">Total</div><div class="fw-semibold">₹<?= e((string)$order['total_amount']) ?></div></div>
          </div>
        </div>
      </div>
    </div>
    <?php
};

require __DIR__ . '/../../views/layout.php';
