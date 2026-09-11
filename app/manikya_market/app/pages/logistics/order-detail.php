<?php

declare(strict_types=1);

auth_require_role('logistics');

$title = 'Order Details';

$orderId = (int)($_GET['id'] ?? 0);

// Get order details
// ensure invoice_file column exists to avoid SELECT errors on older DBs
ensure_order_column($db, 'invoice_file', "VARCHAR(255) NULL");

// ensure invoice_generated_at exists
ensure_order_column($db, 'invoice_generated_at');

// Delhivery schema
delhivery_ensure_schema($db);

$order = db_fetch_one($db, '
    SELECT 
        o.id, o.order_no, o.status, o.created_at, o.buyer_id,
        o.awb_number, o.shipping_tracking_no, o.expected_delivery_date, o.picked_at,
      o.invoice_file, o.invoice_generated_at,
        o.logistics_user_id,
        o.delhivery_waybill, o.delhivery_status, o.delhivery_status_synced_at, o.delhivery_pickup_token,
        u.full_name AS buyer_name, u.phone AS buyer_phone, u.email AS buyer_email,
        ba.address_line1, ba.address_line2, ba.city, ba.state, ba.pincode,
        o.subtotal_amount, o.shipping_amount, o.total_amount
    FROM orders o
    JOIN users u ON u.id = o.buyer_id
    LEFT JOIN buyer_addresses ba ON ba.id = o.delivery_address_id
    WHERE o.id = :id
    LIMIT 1
', ['id' => $orderId]);

if (!$order) {
    redirect_to('logistics/dashboard');
}

// Restrict access: if order is assigned to a logistics user, only that user may view it
if (!empty($order['logistics_user_id']) && (int)$order['logistics_user_id'] !== auth_user_id()) {
  flash_set('error', 'You are not authorized to view this order.');
  redirect_to('logistics/dashboard');
}

// Get order items
$items = db_fetch_all($db, '
    SELECT oi.id, oi.product_id, oi.qty_kg, oi.price_per_kg, oi.line_total,
           p.name AS product_name, p.image_path, p.unit AS product_unit
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = :order_id
', ['order_id' => (int)$order['id']]);

$error = '';
$success = '';

if (request_method() === 'POST') {
    $action = post_string('action');
    
    if ($action === 'pick_order') {
        // Mark as picked
        try {
            db_exec($db, 'UPDATE orders SET status = :status, picked_at = NOW(), logistics_user_id = :user_id WHERE id = :id', [
                'status' => 'shipped',
                'user_id' => auth_user_id(),
                'id' => $orderId,
            ]);
            
            // Create tracking record
            db_exec($db, '
                INSERT INTO order_tracking (order_id, status, updated_by_type, updated_by_id, notes, updated_at) 
                VALUES (:order_id, :status, :updated_by_type, :updated_by_id, :notes, NOW())
            ', [
                'order_id' => $orderId,
                'status' => 'shipped',
                'updated_by_type' => 'logistics',
                'updated_by_id' => auth_user_id(),
                'notes' => 'Order picked by logistics department',
            ]);
            
            $success = 'Order marked as shipped!';
            header('Refresh: 2; url=?p=logistics/dashboard');
        } catch (Throwable $t) {
            $error = 'Failed to update order: ' . $t->getMessage();
        }
    }

    // Allow logistics to update status after shipped (in_transit, delivered)
    $action2 = post_string('status_action');
    if ($action2 === 'set_status') {
        $newStatus = post_string('new_status');
        $allowed = ['in_transit', 'delivered'];
        if (in_array($newStatus, $allowed, true)) {
            try {
                $statusTsMap = [
                    'in_transit' => 'in_transit_at',
                    'delivered' => 'delivered_at',
                ];
          if (isset($statusTsMap[$newStatus])) {
            ensure_order_column($db, $statusTsMap[$newStatus]);
            // ensure logistics_user_id column exists so we can record who completed delivery
            ensure_order_column($db, 'logistics_user_id', 'INT NULL');
            if ($newStatus === 'delivered') {
              db_exec($db, 'UPDATE orders SET status = :status, ' . $statusTsMap[$newStatus] . ' = NOW(), logistics_user_id = :user_id WHERE id = :id', ['status' => $newStatus, 'id' => $orderId, 'user_id' => auth_user_id()]);
            } else {
              db_exec($db, 'UPDATE orders SET status = :status, ' . $statusTsMap[$newStatus] . ' = NOW() WHERE id = :id', ['status' => $newStatus, 'id' => $orderId]);
            }
          } else {
            db_exec($db, 'UPDATE orders SET status = :status WHERE id = :id', ['status' => $newStatus, 'id' => $orderId]);
          }

          // Deduct stock when order is delivered (if not already deducted when marked as paid)
          if ($newStatus === 'delivered') {
            try {
              inventory_sale_for_order($db, $orderId);
            } catch (Throwable $t) {
              // Inventory may not be configured, but don't block delivery marking
            }
            // Credit the merchant's earnings wallet (idempotent).
            try {
              merchant_wallet_credit_for_order($db, $orderId);
            } catch (Throwable $t) {
              error_log('merchant_wallet_credit_for_order failed: ' . $t->getMessage());
            }
            // Buyer "your order has arrived" email.
            try { notify_order_event($db, $orderId, 'delivered'); } catch (Throwable $t) { error_log('notify delivered: ' . $t->getMessage()); }
          }

          // Buyer "your order is on its way" email when logistics user marks shipped/in_transit.
          if (in_array($newStatus, ['shipped','in_transit'], true)) {
            try { notify_order_event($db, $orderId, 'shipped'); } catch (Throwable $t) { error_log('notify shipped: ' . $t->getMessage()); }
          }

                db_exec($db, '
                    INSERT INTO order_tracking (order_id, status, updated_by_type, updated_by_id, notes, updated_at)
                    VALUES (:order_id, :status, :updated_by_type, :updated_by_id, :notes, NOW())
                ', [
                    'order_id' => $orderId,
                    'status' => $newStatus,
                    'updated_by_type' => 'logistics',
                    'updated_by_id' => auth_user_id(),
                    'notes' => 'Status set to ' . $newStatus,
                ]);
                $success = 'Status updated to ' . $newStatus;
                // refresh order
                $order = db_fetch_one($db, 'SELECT o.*, u.full_name AS buyer_name, u.phone AS buyer_phone, u.email AS buyer_email FROM orders o JOIN users u ON u.id = o.buyer_id WHERE o.id = :id LIMIT 1', ['id' => $orderId]);
            } catch (Throwable $t) {
                $error = 'Failed to update status: ' . $t->getMessage();
            }
        } else {
            $error = 'Invalid status';
        }
    }

    if ($action === 'delhivery_create') {
        $result = delhivery_create_shipment($db, $orderId);
        if ($result['success']) {
            $success = '🚚 Ekart shipment created! AWB: ' . $result['waybill'];
            $pickupResult = delhivery_request_pickup($db, $orderId);
            if ($pickupResult['success']) $success .= ' Pickup scheduled.';
        } else {
            $error = 'Ekart shipment failed: ' . ($result['error'] ?? 'Unknown error');
        }
        $order = db_fetch_one($db, 'SELECT o.*, u.full_name AS buyer_name, u.phone AS buyer_phone, u.email AS buyer_email FROM orders o JOIN users u ON u.id = o.buyer_id WHERE o.id = :id LIMIT 1', ['id' => $orderId]);
    }

    if ($action === 'delhivery_track') {
        $result = delhivery_track_shipment($db, $orderId);
        if ($result['success']) {
            $success = '✅ Tracking refreshed! Status: ' . $result['status'];
        } else {
            $error = 'Tracking refresh failed: ' . ($result['error'] ?? 'Unknown error');
        }
        $order = db_fetch_one($db, 'SELECT o.*, u.full_name AS buyer_name, u.phone AS buyer_phone, u.email AS buyer_email FROM orders o JOIN users u ON u.id = o.buyer_id WHERE o.id = :id LIMIT 1', ['id' => $orderId]);
    }

    if ($action === 'delhivery_cancel') {
        $result = delhivery_cancel_shipment($db, $orderId);
        if ($result['success']) {
            $success = 'Ekart shipment cancelled.';
        } else {
            $error = 'Cancellation failed: ' . ($result['error'] ?? 'Unknown error');
        }
        $order = db_fetch_one($db, 'SELECT o.*, u.full_name AS buyer_name, u.phone AS buyer_phone, u.email AS buyer_email FROM orders o JOIN users u ON u.id = o.buyer_id WHERE o.id = :id LIMIT 1', ['id' => $orderId]);
    }

    if ($action === 'update_tracking') {
        $awbNumber = post_string('awb_number');
        $trackingNo = post_string('shipping_tracking_no');
        $expectedDelivery = post_string('expected_delivery_date');
        $notes = post_string('notes');
        $isEdit = post_string('is_edit') === '1';
        
        if (empty($trackingNo)) {
            $error = 'Tracking number is required';
        } else {
            try {
                db_exec($db, '
                    UPDATE orders 
                    SET awb_number = :awb_number, 
                        shipping_tracking_no = :shipping_tracking_no,
                        expected_delivery_date = :expected_delivery_date,
                        status = :status
                    WHERE id = :id
                ', [
                    'awb_number' => $awbNumber,
                    'shipping_tracking_no' => $trackingNo,
                    'expected_delivery_date' => !empty($expectedDelivery) ? $expectedDelivery : null,
                    'status' => 'shipped',
                    'id' => $orderId,
                ]);
                
                // Create or update tracking record
                if ($isEdit) {
                    $notes_msg = "Updated tracking: $trackingNo. $notes";
                } else {
                    $notes_msg = "New tracking: $trackingNo. $notes";
                }
                
                db_exec($db, '
                    INSERT INTO order_tracking (order_id, status, updated_by_type, updated_by_id, notes, updated_at) 
                    VALUES (:order_id, :status, :updated_by_type, :updated_by_id, :notes, NOW())
                ', [
                    'order_id' => $orderId,
                    'status' => 'shipped',
                    'updated_by_type' => 'logistics',
                    'updated_by_id' => auth_user_id(),
                    'notes' => $notes_msg,
                ]);
                
                $success = $isEdit ? 'Tracking information updated!' : 'Tracking information added!';
                // Refresh order
                $order = db_fetch_one($db, '
                    SELECT 
                        o.id, o.order_no, o.status, o.created_at, o.buyer_id,
                        o.awb_number, o.shipping_tracking_no, o.expected_delivery_date, o.picked_at,
                        u.full_name AS buyer_name, u.phone AS buyer_phone, u.email AS buyer_email,
                        ba.address_line1, ba.address_line2, ba.city, ba.state, ba.pincode,
                        o.subtotal_amount, o.shipping_amount, o.total_amount
                    FROM orders o
                    JOIN users u ON u.id = o.buyer_id
                    LEFT JOIN buyer_addresses ba ON ba.id = o.delivery_address_id
                    WHERE o.id = :id
                    LIMIT 1
                ', ['id' => $orderId]);
            } catch (Throwable $t) {
                $error = 'Failed to update tracking: ' . $t->getMessage();
            }
        }
    }
}

$delhiveryConfig = delhivery_get_config($db);
$hasDelhivery = !empty($order['delhivery_waybill']);
$delhiveryEvents = [];
if ($hasDelhivery) {
    $delhiveryEvents = db_fetch_all($db, 'SELECT * FROM delhivery_tracking_events WHERE order_id = :oid ORDER BY event_time DESC', ['oid' => (int)$order['id']]);
}

$content = function () use ($order, $items, $error, $success, $delhiveryConfig, $hasDelhivery, $delhiveryEvents) {
    ?>
    <div class="container py-4">
      <div class="d-flex align-items-center gap-2 mb-3">
        <a class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" href="?p=logistics/dashboard"><i data-lucide="arrow-left" class="mm-icon"></i> Back</a>
        <h1 class="h5 mb-0">Order: <?= e((string)$order['order_no']) ?></h1>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
      <?php endif; ?>

      <div class="row g-3">
        <!-- Left: Order & Shipping Info -->
        <div class="col-lg-8">
          <!-- Buyer Details -->
          <div class="bg-white border rounded-4 p-4 mm-card mb-3">
            <h3 class="h6 mb-3">👤 Buyer Information</h3>
            <div class="row g-3">
              <div class="col-md-6">
                <div>
                  <div class="text-muted small">Name</div>
                  <div class="fw-semibold"><?= e((string)$order['buyer_name']) ?></div>
                </div>
              </div>
              <div class="col-md-6">
                <div>
                  <div class="text-muted small">Phone</div>
                  <div class="fw-semibold">
                    <a href="tel:<?= urlencode((string)$order['buyer_phone']) ?>"><?= e((string)$order['buyer_phone']) ?></a>
                  </div>
                </div>
              </div>
              <div class="col-12">
                <div>
                  <div class="text-muted small">Email</div>
                  <div class="fw-semibold">
                    <a href="mailto:<?= urlencode((string)$order['buyer_email']) ?>"><?= e((string)$order['buyer_email']) ?></a>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Delivery Address -->
          <div class="bg-white border rounded-4 p-4 mm-card mb-3">
            <h3 class="h6 mb-3">📍 Delivery Address</h3>
            <address class="mb-0">
              <?= e((string)($order['address_line1'] ?? '')) ?><br>
              <?php if (!empty($order['address_line2'])): ?>
                <?= e((string)$order['address_line2']) ?><br>
              <?php endif; ?>
              <?= e((string)($order['city'] ?? '')) ?>, <?= e((string)($order['state'] ?? '')) ?> <?= e((string)($order['pincode'] ?? '')) ?>
            </address>
          </div>

          <!-- Order Items -->
          <div class="bg-white border rounded-4 p-4 mm-card mb-3">
            <h3 class="h6 mb-3">📦 Items</h3>
            <div class="table-responsive">
              <table class="table align-middle mb-0 small">
                <thead>
                  <tr>
                    <th>Product</th>
                    <th class="text-end">Quantity</th>
                    <th class="text-end">Price</th>
                    <th class="text-end">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($items as $item): ?>
                    <tr>
                      <td><?= e((string)$item['product_name']) ?></td>
                      <?php $lUnit = product_unit_label($item['product_unit'] ?? 'kg'); ?>
                      <td class="text-end"><?= e((string)$item['qty_kg']) ?> <?= e($lUnit) ?></td>
                      <td class="text-end">₹<?= number_format((float)$item['price_per_kg'], 0) ?>/<?= e($lUnit) ?></td>
                      <td class="text-end fw-semibold">₹<?= number_format((float)$item['line_total'], 0) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Tracking Form -->
          <?php if ($order['status'] === 'packed' || $order['status'] === 'ready_to_pick'): ?>
            <div class="bg-white border rounded-4 p-4 mm-card">
              <h3 class="h6 mb-3">🎯 Pick & Ship Order</h3>
              <form method="POST">
                <input type="hidden" name="action" value="pick_order">
                <button type="submit" class="btn btn-lg btn-success d-flex align-items-center justify-content-center gap-2 w-100">
                  <i data-lucide="check-circle" class="mm-icon"></i> Mark as Picked & Shipped
                </button>
              </form>
            </div>
          <?php endif; ?>

          <!-- Delhivery Integration -->
          <?php if ($delhiveryConfig && $hasDelhivery): ?>
            <div class="bg-white border rounded-4 p-4 mm-card mt-3">
              <h3 class="h6 mb-3">🚚 Delhivery Tracking</h3>
              <div class="bg-light border rounded-3 p-3 mb-3">
                <div class="row g-3">
                  <div class="col-md-6">
                    <div class="text-muted small">AWB / Waybill</div>
                    <div class="fw-semibold fw-monospace"><?= e((string)$order['delhivery_waybill']) ?></div>
                  </div>
                  <div class="col-md-6">
                    <div class="text-muted small">Ekart Status</div>
                    <div class="fw-semibold"><?= e((string)($order['delhivery_status'] ?? 'Pending')) ?></div>
                  </div>
                  <?php if (!empty($order['delhivery_status_synced_at'])): ?>
                    <div class="col-md-6">
                      <div class="text-muted small">Last Synced</div>
                      <div class="small"><?= date('M d, Y H:i', strtotime((string)$order['delhivery_status_synced_at'])) ?></div>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
              <div class="d-flex gap-2 mb-3 flex-wrap">
                <form method="POST" class="d-inline">
                  <input type="hidden" name="action" value="delhivery_track">
                  <button type="submit" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                    <i data-lucide="refresh-cw" class="mm-icon"></i> Refresh Tracking
                  </button>
                </form>
                <?php if (!in_array($order['status'], ['delivered', 'cancelled', 'returned'], true)): ?>
                  <form method="POST" class="d-inline" onsubmit="return confirm('Cancel this Ekart shipment?')">
                    <input type="hidden" name="action" value="delhivery_cancel">
                    <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1">
                      <i data-lucide="x-circle" class="mm-icon"></i> Cancel Shipment
                    </button>
                  </form>
                <?php endif; ?>
              </div>
              <?php if ($delhiveryEvents): ?>
                <div class="fw-semibold small mb-2">Tracking Events</div>
                <div class="table-responsive">
                  <table class="table table-sm small mb-0">
                    <thead><tr><th>Time</th><th>Status</th><th>Location</th></tr></thead>
                    <tbody>
                      <?php foreach ($delhiveryEvents as $evt): ?>
                        <tr>
                          <td class="text-nowrap"><?= $evt['event_time'] ? date('M d H:i', strtotime((string)$evt['event_time'])) : '-' ?></td>
                          <td><?= e((string)$evt['scan_status']) ?></td>
                          <td><?= e((string)$evt['location']) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
          <?php elseif ($delhiveryConfig && !$hasDelhivery): ?>
            <div class="bg-white border rounded-4 p-4 mm-card mt-3">
              <h3 class="h6 mb-3">🚚 Delhivery</h3>
              <p class="text-muted small mb-3">No Ekart shipment created for this order yet.</p>
              <form method="POST">
                <input type="hidden" name="action" value="delhivery_create">
                <button type="submit" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
                  <i data-lucide="truck" class="mm-icon"></i> Create Ekart Shipment
                </button>
              </form>
            </div>
          <?php endif; ?>

          <!-- Update Tracking Info -->
          <div class="bg-white border rounded-4 p-4 mm-card mt-3">
            <h3 class="h6 mb-3">📊 Manual Shipment Tracking</h3>
            
            <?php if (!empty($order['shipping_tracking_no'])): ?>
              <!-- Tracking Already Set - Show View/Edit Mode -->
              <div class="alert alert-success mb-3">
                <i data-lucide="check-circle" class="mm-icon"></i>
                Tracking information has been set. You can update it if needed.
              </div>
              
              <div class="bg-light border rounded-3 p-3 mb-3">
                <div class="row g-3">
                  <?php if (!empty($order['awb_number'])): ?>
                    <div class="col-md-6">
                      <div class="text-muted small">AWB Number</div>
                      <div class="fw-semibold"><?= e((string)$order['awb_number']) ?></div>
                    </div>
                  <?php endif; ?>
                  <div class="col-md-6">
                    <div class="text-muted small">Tracking Number</div>
                    <div class="fw-semibold"><?= e((string)$order['shipping_tracking_no']) ?></div>
                  </div>
                  <?php if (!empty($order['expected_delivery_date'])): ?>
                    <div class="col-md-6">
                      <div class="text-muted small">Expected Delivery Date</div>
                      <div class="fw-semibold"><?= date('M d, Y', strtotime((string)$order['expected_delivery_date'])) ?></div>
                    </div>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Edit Form (Collapsible) -->
              <button type="button" class="btn btn-sm btn-outline-primary mb-3" data-bs-toggle="collapse" data-bs-target="#editTrackingForm">
                <i data-lucide="edit-2" class="mm-icon"></i> Edit Tracking
              </button>

              <div class="collapse" id="editTrackingForm">
                <form method="POST" class="mt-3 pt-3 border-top">
                  <input type="hidden" name="action" value="update_tracking">
                  <input type="hidden" name="is_edit" value="1">
                  
                  <div class="mb-3">
                    <label class="form-label small fw-semibold">AWB Number (Optional)</label>
                    <input type="text" class="form-control form-control-sm" name="awb_number" value="<?= e((string)($order['awb_number'] ?? '')) ?>" placeholder="e.g., AWB123456">
                  </div>

                  <div class="mb-3">
                    <label class="form-label small fw-semibold">Tracking Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" name="shipping_tracking_no" value="<?= e((string)($order['shipping_tracking_no'] ?? '')) ?>" placeholder="e.g., TRK123456789" required>
                  </div>

                  <div class="mb-3">
                    <label class="form-label small fw-semibold">Expected Delivery Date</label>
                    <input type="date" class="form-control form-control-sm" name="expected_delivery_date" value="<?= !empty($order['expected_delivery_date']) ? date('Y-m-d', strtotime((string)$order['expected_delivery_date'])) : '' ?>">
                  </div>

                  <div class="mb-3">
                    <label class="form-label small fw-semibold">Notes</label>
                    <textarea class="form-control form-control-sm" name="notes" rows="3" placeholder="Add any additional information about changes made..."></textarea>
                  </div>

                  <button type="submit" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
                    <i data-lucide="save" class="mm-icon"></i> Save Changes
                  </button>
                </form>
              </div>
            <?php else: ?>
              <!-- Tracking Not Set - Show Entry Form -->
              <div class="alert alert-warning mb-3">
                <i data-lucide="alert-circle" class="mm-icon"></i>
                Please enter shipment tracking information to complete the shipment.
              </div>
              
              <form method="POST">
                <input type="hidden" name="action" value="update_tracking">
                <input type="hidden" name="is_edit" value="0">
                
                <div class="mb-3">
                  <label class="form-label small fw-semibold">AWB Number (Optional)</label>
                  <input type="text" class="form-control form-control-sm" name="awb_number" placeholder="e.g., AWB123456">
                </div>

                <div class="mb-3">
                  <label class="form-label small fw-semibold">Tracking Number <span class="text-danger">*</span></label>
                  <input type="text" class="form-control form-control-sm" name="shipping_tracking_no" placeholder="e.g., TRK123456789" required>
                </div>

                <div class="mb-3">
                  <label class="form-label small fw-semibold">Expected Delivery Date</label>
                  <input type="date" class="form-control form-control-sm" name="expected_delivery_date">
                </div>

                <div class="mb-3">
                  <label class="form-label small fw-semibold">Notes</label>
                  <textarea class="form-control form-control-sm" name="notes" rows="3" placeholder="Add any additional information about the shipment..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
                  <i data-lucide="save" class="mm-icon"></i> Add Tracking
                </button>
              </form>
            <?php endif; ?>
          </div>
        </div>

        <!-- Right: Summary & Status -->
        <div class="col-lg-4">
          <!-- Order Summary -->
          <div class="bg-white border rounded-4 p-4 mm-card mb-3 sticky-top" style="top: 20px;">
            <h3 class="h6 mb-3">📋 Order Summary</h3>
            
            <div class="mb-3 pb-3 border-bottom">
              <div class="d-flex justify-content-between mb-2 small">
                <span class="text-muted">Order Date:</span>
                <strong><?= date('M d, Y', strtotime((string)$order['created_at'])) ?></strong>
              </div>
              <div class="d-flex justify-content-between mb-2 small">
                <span class="text-muted">Status:</span>
                <span class="badge text-bg-<?= $order['status'] === 'shipped' ? 'success' : 'warning' ?>">
                  <?= str_replace('_', ' ', e((string)$order['status'])) ?>
                </span>
              </div>
              <?php if (!empty($order['picked_at'])): ?>
                <div class="d-flex justify-content-between small">
                  <span class="text-muted">Picked at:</span>
                  <strong><?= date('M d, Y H:i', strtotime((string)$order['picked_at'])) ?></strong>
                </div>
              <?php endif; ?>
            </div>

            <?php if (!empty($order['invoice_generated_at'])): ?>
              <div class="mb-3">
                <a class="btn btn-sm btn-primary w-100" href="?p=logistics/order-invoice&id=<?= (int)$order['id'] ?>"><i data-lucide="file-text" class="mm-icon"></i> View Invoice</a>
              </div>
            <?php endif; ?>

            <div class="mb-3 pb-3 border-bottom">
              <div class="d-flex justify-content-between mb-2 small">
                <span class="text-muted">Subtotal:</span>
                <strong>₹<?= number_format((float)$order['subtotal_amount'], 0) ?></strong>
              </div>
              <div class="d-flex justify-content-between mb-2 small">
                <span class="text-muted">Shipping:</span>
                <strong>₹<?= number_format((float)$order['shipping_amount'], 0) ?></strong>
              </div>
              <div class="d-flex justify-content-between small">
                <span class="fw-semibold">Total:</span>
                <span class="h6 mb-0">₹<?= number_format((float)$order['total_amount'], 0) ?></span>
              </div>
            </div>

            <!-- Tracking Info Display -->
            <?php if (!empty($order['shipping_tracking_no'])): ?>
              <div class="p-3 bg-light rounded-3 mb-3">
                <div class="small text-muted mb-1">📦 Tracking Number</div>
                <div class="fw-monospace small fw-bold"><?= e((string)$order['shipping_tracking_no']) ?></div>
                <?php if (!empty($order['awb_number'])): ?>
                  <div class="small text-muted mt-2 mb-1">📄 AWB Number</div>
                  <div class="fw-monospace small fw-bold"><?= e((string)$order['awb_number']) ?></div>
                <?php endif; ?>
                <?php if (!empty($order['expected_delivery_date'])): ?>
                  <div class="small text-muted mt-2 mb-1">📅 Expected Delivery</div>
                  <div class="fw-semibold small"><?= date('M d, Y', strtotime((string)$order['expected_delivery_date'])) ?></div>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            
            <!-- Post-Shipment Status Updates -->
            <?php if ($order['status'] === 'shipped' || $order['status'] === 'in_transit'): ?>
              <div class="bg-white border rounded-3 p-3 mt-3">
                <div class="fw-semibold small mb-2">Update Delivery Status</div>
                <form method="POST" class="row g-2">
                  <input type="hidden" name="status_action" value="set_status">
                  <div class="col-md-6">
                    <select name="new_status" class="form-select form-select-sm">
                      <?php if ($order['status'] === 'shipped'): ?>
                        <option value="in_transit">Mark In Transit</option>
                        <option value="delivered">Mark Delivered</option>
                      <?php elseif ($order['status'] === 'in_transit'): ?>
                        <option value="delivered">Mark Delivered</option>
                      <?php endif; ?>
                    </select>
                  </div>
                  <div class="col-md-6 d-grid">
                    <button class="btn btn-sm btn-primary" type="submit">Update Status</button>
                  </div>
                </form>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <?php
};

require __DIR__ . '/../../views/layout.php';
?>
