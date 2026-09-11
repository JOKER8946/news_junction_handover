<?php

declare(strict_types=1);

auth_require_role('logistics');

$title = 'Logistics Reports';

$logisticsUserId = auth_user_id();

// Ensure relevant columns exist
ensure_order_column($db, 'picked_at');
ensure_order_column($db, 'in_transit_at');
ensure_order_column($db, 'delivered_at');
ensure_order_column($db, 'logistics_user_id', 'INT NULL');

$baseUrl = rtrim(app_base_path($config), '/');

$reportsDir = __DIR__ . '/../../../uploads/reports';
if (!is_dir($reportsDir)) @mkdir($reportsDir, 0755, true);

$errors = [];
$success = '';
$generatedFile = '';

if (request_method() === 'POST') {
    $type = post_string('report_type');
    $allowed = ['picked', 'in_transit', 'delivered'];
    if (!in_array($type, $allowed, true)) {
        $errors[] = 'Invalid report type';
    } else {
        // Build query depending on type
        if ($type === 'picked') {
            $where = "(o.picked_at IS NOT NULL OR LOWER(TRIM(o.status)) IN ('shipped','packed','ready_to_pick')) AND o.logistics_user_id = :logistics_user_id";
            $tsCol = 'picked_at';
        } elseif ($type === 'in_transit') {
            $where = "(o.in_transit_at IS NOT NULL OR LOWER(TRIM(o.status)) = 'in_transit') AND o.logistics_user_id = :logistics_user_id";
            $tsCol = 'in_transit_at';
        } else {
            $where = "(o.delivered_at IS NOT NULL OR LOWER(TRIM(o.status)) = 'delivered') AND o.logistics_user_id = :logistics_user_id";
            $tsCol = 'delivered_at';
        }

        $rows = db_fetch_all($db, "
            SELECT o.id, o.order_no, o.status, o.picked_at, o.in_transit_at, o.delivered_at, o.shipping_tracking_no, o.awb_number, o.total_amount, u.full_name AS buyer_name, l.full_name AS logistics_name
            FROM orders o
            JOIN users u ON u.id = o.buyer_id
            LEFT JOIN users l ON l.id = o.logistics_user_id
            WHERE " . $where . "
            ORDER BY COALESCE(o." . $tsCol . ", o.created_at) DESC
        ", ['logistics_user_id' => $logisticsUserId]) ?: [];

        if (empty($rows)) {
            $errors[] = 'No orders match the selected report criteria.';
        } else {
            $now = new DateTimeImmutable();
            $fileName = sprintf('report-%s-%s.csv', $type, $now->format('Ymd-His'));
            $filePath = $reportsDir . DIRECTORY_SEPARATOR . $fileName;

            $fh = fopen($filePath, 'w');
            if ($fh === false) {
                $errors[] = 'Failed to create report file.';
            } else {
                // header
                fputcsv($fh, ['Order ID', 'Order No', 'Buyer', 'Status', 'Picked At', 'In Transit At', 'Delivered At', 'Tracking No', 'AWB No', 'Total', 'Logistics User']);
                foreach ($rows as $r) {
                    fputcsv($fh, [
                        $r['id'],
                        $r['order_no'],
                        $r['buyer_name'],
                        $r['status'],
                        !empty($r['picked_at']) ? date('Y-m-d H:i:s', strtotime((string)$r['picked_at'])) : '',
                        !empty($r['in_transit_at']) ? date('Y-m-d H:i:s', strtotime((string)$r['in_transit_at'])) : '',
                        !empty($r['delivered_at']) ? date('Y-m-d H:i:s', strtotime((string)$r['delivered_at'])) : '',
                        $r['shipping_tracking_no'] ?? '',
                        $r['awb_number'] ?? '',
                        $r['total_amount'] ?? '',
                        $r['logistics_name'] ?? '',
                    ]);
                }
                fclose($fh);
                $generatedFile = $fileName;
                $success = 'Report generated and saved.';
            }
        }
    }
}

// list recent reports
$files = [];
$dirFiles = glob($reportsDir . DIRECTORY_SEPARATOR . '*.csv');
if ($dirFiles) {
    usort($dirFiles, function($a,$b){ return filemtime($b) - filemtime($a); });
    foreach ($dirFiles as $f) {
        $files[] = [
            'name' => basename($f),
            'path' => $baseUrl . '/uploads/reports/' . basename($f),
            'mtime' => date('Y-m-d H:i:s', filemtime($f)),
            'size' => filesize($f),
        ];
    }
}

$content = function() use ($errors, $success, $generatedFile, $files) {
    ?>
    <div class="container py-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h5 mb-0">Logistics Reports</h1>
            <a class="btn btn-sm btn-outline-secondary" href="?p=logistics/dashboard">Back</a>
        </div>

        <?php if ($errors): ?>
            <div class="alert alert-danger"><?php echo e(implode('<br>', $errors)); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo e($success); ?>
                <?php if ($generatedFile): ?>
                    <div class="mt-2"><a href="<?php echo e(rtrim(app_base_path($config), '/')); ?>/uploads/reports/<?php echo e($generatedFile); ?>" class="btn btn-sm btn-primary" target="_blank">Download <?php echo e($generatedFile); ?></a></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white border rounded-4 p-3 mm-card mb-3">
            <form method="POST" class="row g-2 align-items-center">
                <div class="col-auto">
                    <label class="form-label small mb-0">Generate report:</label>
                </div>
                <div class="col-auto">
                    <select name="report_type" class="form-select form-select-sm">
                        <option value="picked">Order Pickup (picked)</option>
                        <option value="in_transit">In Transit</option>
                        <option value="delivered">Delivery Completed</option>
                    </select>
                </div>
                <div class="col-auto d-grid">
                    <button class="btn btn-sm btn-primary" type="submit">Generate & Save CSV</button>
                </div>
            </form>
        </div>

        <div class="bg-white border rounded-4 p-3 mm-card">
            <h6>Recent Reports</h6>
            <?php if (empty($files)): ?>
                <div class="text-muted small">No reports yet.</div>
            <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($files as $f): ?>
                        <li class="mb-2">
                            <a href="<?= e($f['path']) ?>" target="_blank"><?= e($f['name']) ?></a>
                            <div class="small text-muted"><?= e($f['mtime']) ?> — <?= round($f['size']/1024,2) ?> KB</div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    <?php
};

require __DIR__ . '/../../views/layout.php';
