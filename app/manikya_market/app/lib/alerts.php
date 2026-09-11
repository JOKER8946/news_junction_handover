<?php

declare(strict_types=1);

/**
 * In-app popup alerts for merchant + super admin.
 *
 * These are browser popups (polled by the JS block at the foot of
 * views/layout.php), NOT the sms/whatsapp/email trail in `notification_logs`.
 * They are deliberately separate: notification_logs is a delivery log for an
 * external provider and its `recipient_role` enum has no 'super_admin'.
 *
 * Rules for anything in this file:
 *  - An alert is a side-effect of a business event, never a precondition of it.
 *    Every writer swallows its own errors: a failed alert must never break a
 *    checkout or a status update. Callers do not need their own try/catch.
 *  - Alerts are only ever created going forward. Nothing backfills existing
 *    orders, so switching this on cannot spam a merchant with historic orders.
 */

function alerts_ensure_schema(PDO $db): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $db->exec(
        "CREATE TABLE IF NOT EXISTS order_alerts (
            id                INT AUTO_INCREMENT PRIMARY KEY,
            order_id          INT NOT NULL,
            order_no          VARCHAR(40) NOT NULL,
            recipient_role    ENUM('merchant','super_admin') NOT NULL,
            recipient_user_id INT NULL,
            type              VARCHAR(32) NOT NULL,
            title             VARCHAR(120) NOT NULL,
            body              VARCHAR(255) NOT NULL,
            link              VARCHAR(190) NULL,
            created_at        DATETIME NOT NULL,
            seen_at           DATETIME NULL,
            KEY idx_poll (recipient_role, seen_at, id),
            KEY idx_user (recipient_user_id, seen_at),
            KEY idx_order (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    $done = true;
}

/**
 * Insert one alert. Never throws — see the file header.
 */
function alert_push(PDO $db, array $f): void
{
    try {
        alerts_ensure_schema($db);
        $stmt = $db->prepare(
            'INSERT INTO order_alerts
                (order_id, order_no, recipient_role, recipient_user_id, type, title, body, link, created_at)
             VALUES
                (:oid, :ono, :role, :uid, :type, :title, :body, :link, NOW())'
        );
        $stmt->execute([
            'oid'   => (int)$f['order_id'],
            'ono'   => (string)$f['order_no'],
            'role'  => (string)$f['recipient_role'],
            'uid'   => isset($f['recipient_user_id']) ? (int)$f['recipient_user_id'] : null,
            'type'  => (string)$f['type'],
            'title' => mb_substr((string)$f['title'], 0, 120),
            'body'  => mb_substr((string)$f['body'], 0, 255),
            'link'  => isset($f['link']) ? mb_substr((string)$f['link'], 0, 190) : null,
        ]);
    } catch (Throwable $t) {
        error_log('alert_push failed for order ' . ($f['order_id'] ?? '?') . ': ' . $t->getMessage());
    }
}

/**
 * Merchant popup: a buyer placed an order this merchant must act on.
 *
 * Call this at the point the order becomes actionable, NOT at the point the
 * row is created — otherwise Razorpay drafts that never get paid would ring
 * the merchant's bell. In practice that means: COD from checkout.php (which
 * opens COD at 'paid'), and Razorpay from razorpay-verify.php once the
 * signature is confirmed. Those two paths are mutually exclusive per order,
 * so one order rings exactly once.
 */
function alert_push_new_order(PDO $db, int $orderId): void
{
    try {
        $o = db_fetch_one(
            $db,
            'SELECT o.id, o.order_no, o.merchant_id, o.total_amount, u.full_name AS buyer_name
               FROM orders o
               JOIN users u ON u.id = o.buyer_id
              WHERE o.id = :id
              LIMIT 1',
            ['id' => $orderId]
        );
        if (!$o || empty($o['merchant_id'])) {
            return;
        }
        alert_push($db, [
            'order_id'          => (int)$o['id'],
            'order_no'          => (string)$o['order_no'],
            'recipient_role'    => 'merchant',
            'recipient_user_id' => (int)$o['merchant_id'],
            'type'              => 'new_order',
            'title'             => 'New order received',
            'body'              => 'Order #' . $o['order_no'] . ' from ' . (string)$o['buyer_name']
                                   . ' — ₹' . number_format((float)$o['total_amount'], 2),
            'link'              => '?p=merchant/order&id=' . (int)$o['id'],
        ]);
    } catch (Throwable $t) {
        error_log('alert_push_new_order failed: ' . $t->getMessage());
    }
}

/**
 * Super-admin popup: a merchant moved an order through Status & Actions.
 *
 * Fans out one row per active super_admin, each with its own recipient_user_id,
 * so every admin gets their own copy and dismissing one does not clear anyone
 * else's. (An earlier cut used a single NULL-recipient row as a shared queue;
 * that meant the first admin to click OK silenced it for the rest.)
 *
 * The fan-out is a snapshot at push time: a super_admin created later won't
 * receive alerts raised before their account existed. That's intended.
 */
function alert_push_status_update(PDO $db, int $orderId, string $newStatus, ?string $actorName = null): void
{
    try {
        $o = db_fetch_one(
            $db,
            'SELECT o.id, o.order_no, o.merchant_id,
                    COALESCE(mp.business_name, mu.full_name, CONCAT("Merchant #", o.merchant_id)) AS merchant_name
               FROM orders o
               LEFT JOIN users mu ON mu.id = o.merchant_id
               LEFT JOIN merchant_profile mp ON mp.merchant_user_id = o.merchant_id
              WHERE o.id = :id
              LIMIT 1',
            ['id' => $orderId]
        );
        if (!$o) {
            return;
        }
        $label = ucwords(str_replace('_', ' ', $newStatus));
        $who   = $actorName !== null && $actorName !== '' ? $actorName : (string)$o['merchant_name'];

        $admins = db_fetch_all(
            $db,
            "SELECT id FROM users WHERE role = 'super_admin' AND status = 'active'"
        ) ?: [];

        foreach ($admins as $admin) {
            alert_push($db, [
                'order_id'          => (int)$o['id'],
                'order_no'          => (string)$o['order_no'],
                'recipient_role'    => 'super_admin',
                'recipient_user_id' => (int)$admin['id'],
                'type'              => 'status_update',
                'title'             => 'Order status updated: ' . $label,
                'body'              => $who . ' set order #' . $o['order_no'] . ' to ' . $label . '.',
                'link'              => '?p=super-admin/shipments',
            ]);
        }
    } catch (Throwable $t) {
        error_log('alert_push_status_update failed: ' . $t->getMessage());
    }
}

/**
 * Unseen alerts for whoever is logged in. Returns [] for any other role, and
 * [] rather than throwing if the table isn't there yet — the poller runs on
 * every page and must never surface a 500.
 *
 * Both roles are addressed per-user (see alert_push_status_update), so one
 * query serves both.
 */
function alerts_fetch_unseen(PDO $db, string $role, ?int $userId, int $limit = 5): array
{
    if (!in_array($role, ['merchant', 'super_admin'], true) || !$userId) {
        return [];
    }
    try {
        return db_fetch_all(
            $db,
            'SELECT id, order_id, order_no, type, title, body, link, created_at
               FROM order_alerts
              WHERE recipient_role = :role
                AND recipient_user_id = :uid
                AND seen_at IS NULL
              ORDER BY id ASC
              LIMIT ' . (int)$limit,
            ['role' => $role, 'uid' => (int)$userId]
        ) ?: [];
    } catch (Throwable $t) {
        return [];
    }
}

/**
 * Mark alerts seen. Scoped to the caller's own rows so one account can never
 * clear another's bell by posting arbitrary ids — including one super admin
 * clearing another super admin's copy.
 */
function alerts_mark_seen(PDO $db, array $ids, string $role, ?int $userId): int
{
    $ids = array_values(array_filter(array_map('intval', $ids), static fn($i) => $i > 0));
    if (!$ids || !in_array($role, ['merchant', 'super_admin'], true) || !$userId) {
        return 0;
    }
    try {
        $in   = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare(
            "UPDATE order_alerts SET seen_at = NOW()
              WHERE seen_at IS NULL
                AND recipient_role = ?
                AND recipient_user_id = ?
                AND id IN ($in)"
        );
        $stmt->execute(array_merge([$role, (int)$userId], $ids));
        return $stmt->rowCount();
    } catch (Throwable $t) {
        error_log('alerts_mark_seen failed: ' . $t->getMessage());
        return 0;
    }
}
