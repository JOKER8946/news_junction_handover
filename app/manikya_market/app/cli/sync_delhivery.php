<?php

declare(strict_types=1);

/**
 * CLI: refresh Delhivery tracking for all open shipments.
 *
 * Run via cron, e.g.:
 *   * /30 * * * * /usr/bin/php /path/to/your/site/manikya_market/app/cli/sync_delhivery.php >> /var/log/delhivery-sync.log 2>&1
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

date_default_timezone_set('Asia/Kolkata');

$configPath = __DIR__ . '/../config.php';
if (!is_file($configPath)) {
    fwrite(STDERR, "config.php missing\n");
    exit(1);
}
$config = require $configPath;
$GLOBALS['config'] = $config;

require __DIR__ . '/../lib/db.php';
require __DIR__ . '/../lib/helpers.php';
require __DIR__ . '/../lib/delhivery.php';
require __DIR__ . '/../lib/google_oauth.php'; // provides smtp_get_config()
require __DIR__ . '/../lib/notify.php';

$db = db_connect($config);

$ts = date('Y-m-d H:i:s');
echo "[$ts] sync_delhivery: starting\n";

$rows = db_fetch_all($db, "
    SELECT id, order_no, delhivery_waybill, status, delhivery_status
    FROM orders
    WHERE delhivery_waybill IS NOT NULL
      AND delhivery_waybill <> ''
      AND status NOT IN ('delivered', 'cancelled', 'returned')
    ORDER BY id ASC
");

$total = count($rows);
$ok = 0;
$err = 0;

foreach ($rows as $r) {
    $oid = (int)$r['id'];
    $wb = (string)$r['delhivery_waybill'];
    try {
        $res = delhivery_track_shipment($db, $oid);
        if (!empty($res['success'])) {
            $ok++;
            $status = (string)($res['status'] ?? '');
            $edd = (string)($res['expected_date'] ?? '');
            echo "  ok  oid=$oid wb=$wb status=$status edd=$edd\n";
        } else {
            $err++;
            echo "  err oid=$oid wb=$wb error=" . (string)($res['error'] ?? '?') . "\n";
        }
    } catch (Throwable $t) {
        $err++;
        echo "  exc oid=$oid wb=$wb msg=" . $t->getMessage() . "\n";
    }
    // Be polite: tracking has rate limits.
    usleep(300000); // 0.3s
}

$ts = date('Y-m-d H:i:s');
echo "[$ts] sync_delhivery: done total=$total ok=$ok err=$err\n";
