<?php

declare(strict_types=1);

/**
 * Get referral configuration from merchant_profile.
 * Returns referrer_pct, referee_pct, and max_per_user.
 */
function referral_get_config(PDO $db): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $defaults = ['referrer_pct' => 5.00, 'referee_pct' => 10.00, 'max_per_user' => 5];

    try {
        $row = db_fetch_one($db, "
            SELECT mp.referral_referrer_pct, mp.referral_referee_pct, mp.referral_max_per_user
            FROM merchant_profile mp
            JOIN users u ON u.id = mp.merchant_user_id
            WHERE u.role = 'merchant'
            ORDER BY mp.id ASC LIMIT 1
        ");
        if ($row) {
            $config = [
                'referrer_pct' => (float)($row['referral_referrer_pct'] ?? 5.00),
                'referee_pct' => (float)($row['referral_referee_pct'] ?? 10.00),
                'max_per_user' => (int)($row['referral_max_per_user'] ?? 5),
            ];
            return $config;
        }
    } catch (Throwable $t) {
        // columns may not exist yet on older installs
    }

    $config = $defaults;
    return $config;
}

/**
 * Generate a unique referral code for a user.
 * Called once during signup.
 */
function referral_generate_code(PDO $db, int $userId): string
{
    // Ensure tables exist
    referral_ensure_tables($db);

    // Check if user already has a code
    $existing = db_fetch_one($db, 'SELECT code FROM referral_codes WHERE user_id = :uid LIMIT 1', ['uid' => $userId]);
    if ($existing) {
        return (string)$existing['code'];
    }

    // Generate unique 8-char alphanumeric code
    $attempts = 0;
    do {
        $code = strtoupper(substr(bin2hex(random_bytes(5)), 0, 8));
        $dup = db_fetch_one($db, 'SELECT id FROM referral_codes WHERE code = :code LIMIT 1', ['code' => $code]);
        $attempts++;
    } while ($dup && $attempts < 10);

    db_exec($db, 'INSERT INTO referral_codes (user_id, code, created_at) VALUES (:uid, :code, NOW())', [
        'uid' => $userId,
        'code' => $code,
    ]);

    return $code;
}

/**
 * Get referral code for a user.
 */
function referral_get_code(PDO $db, int $userId): ?string
{
    referral_ensure_tables($db);
    $row = db_fetch_one($db, 'SELECT code FROM referral_codes WHERE user_id = :uid LIMIT 1', ['uid' => $userId]);
    return $row ? (string)$row['code'] : null;
}

/**
 * Apply a referral code during signup — link referee to referrer.
 * Returns true if the code was valid and applied.
 */
function referral_apply_code(PDO $db, int $refereeId, string $code): bool
{
    referral_ensure_tables($db);

    $code = strtoupper(trim($code));
    if ($code === '') {
        return false;
    }

    // Find the referral code owner
    $rc = db_fetch_one($db, 'SELECT user_id FROM referral_codes WHERE code = :code LIMIT 1', ['code' => $code]);
    if (!$rc) {
        return false;
    }

    $referrerId = (int)$rc['user_id'];

    // Cannot refer yourself
    if ($referrerId === $refereeId) {
        return false;
    }

    // Check if referee already has a referral
    $existing = db_fetch_one($db, 'SELECT id FROM referrals WHERE referee_id = :rid LIMIT 1', ['rid' => $refereeId]);
    if ($existing) {
        return false;
    }

    // Referrer can only refer up to the configured max
    $refConfig = referral_get_config($db);
    $maxPerUser = $refConfig['max_per_user'];
    $refCount = db_fetch_one($db, 'SELECT COUNT(*) AS cnt FROM referrals WHERE referrer_id = :uid', ['uid' => $referrerId]);
    if ((int)($refCount['cnt'] ?? 0) >= $maxPerUser) {
        return false;
    }

    db_exec($db, 'INSERT INTO referrals (referrer_id, referee_id, status, referee_discount_pct, referrer_reward_pct, created_at) VALUES (:referrer, :referee, :status, :disc, :reward, NOW())', [
        'referrer' => $referrerId,
        'referee' => $refereeId,
        'status' => 'pending',
        'disc' => $refConfig['referee_pct'],
        'reward' => $refConfig['referrer_pct'],
    ]);

    return true;
}

/**
 * Check if a buyer is a referee with an unused discount (pending referral, no order yet).
 * Returns the referral row or null.
 */
function referral_get_pending_discount(PDO $db, int $buyerId): ?array
{
    referral_ensure_tables($db);
    return db_fetch_one($db, 'SELECT * FROM referrals WHERE referee_id = :rid AND status = :st LIMIT 1', [
        'rid' => $buyerId,
        'st' => 'pending',
    ]);
}

/**
 * Check if buyer has any previous completed orders (to know if this is their first).
 */
function referral_is_first_order(PDO $db, int $buyerId): bool
{
    $row = db_fetch_one($db, 'SELECT COUNT(*) AS cnt FROM orders WHERE buyer_id = :bid AND status != :cancelled', [
        'bid' => $buyerId,
        'cancelled' => 'cancelled',
    ]);
    return ((int)($row['cnt'] ?? 0)) <= 0;
}

/**
 * Complete a referral when the referee's first order is delivered.
 * Credits 5% of order total to referrer's wallet.
 */
function referral_complete(PDO $db, int $orderId): void
{
    referral_ensure_tables($db);

    // Get order details
    $order = db_fetch_one($db, 'SELECT id, buyer_id, total_amount, subtotal_amount FROM orders WHERE id = :id LIMIT 1', ['id' => $orderId]);
    if (!$order) {
        return;
    }

    $buyerId = (int)$order['buyer_id'];

    // Find pending referral for this buyer
    $referral = db_fetch_one($db, 'SELECT * FROM referrals WHERE referee_id = :rid AND status = :st LIMIT 1', [
        'rid' => $buyerId,
        'st' => 'pending',
    ]);
    if (!$referral) {
        return;
    }

    $rewardPct = (float)$referral['referrer_reward_pct'];
    $orderTotal = (float)$order['total_amount'];
    $rewardAmount = round($orderTotal * ($rewardPct / 100), 2);
    $referrerId = (int)$referral['referrer_id'];
    $referralId = (int)$referral['id'];

    // Atomic claim: only ONE concurrent caller successfully flips status to
    // 'completed'. The other sees rowCount == 0 and skips the wallet credit —
    // prevents double-credit if the merchant clicks "delivered" twice in quick
    // succession.
    $stmt = $db->prepare(
        "UPDATE referrals
            SET status         = 'completed',
                referee_order_id = :oid,
                reward_amount  = :reward,
                completed_at   = NOW()
          WHERE id = :id AND status = 'pending'"
    );
    $stmt->execute([
        'oid'    => $orderId,
        'reward' => $rewardAmount,
        'id'     => $referralId,
    ]);
    if ($stmt->rowCount() === 0) {
        // Already completed by a parallel request; nothing more to do.
        return;
    }

    // Credit referrer's wallet (only the winning caller reaches here)
    if ($rewardAmount > 0) {
        wallet_credit($db, $referrerId, $rewardAmount, 'Referral reward — ' . number_format($rewardPct, 1) . '% of order #' . $orderId, 'referral', $referralId);
    }
}

/**
 * Credit amount to a user's wallet.
 */
function wallet_credit(PDO $db, int $userId, float $amount, string $description, string $refType = '', int $refId = 0): void
{
    referral_ensure_tables($db);
    db_exec($db, 'INSERT INTO wallet_transactions (user_id, type, amount, description, ref_type, ref_id, created_at) VALUES (:uid, :type, :amount, :desc, :ref_type, :ref_id, NOW())', [
        'uid' => $userId,
        'type' => 'credit',
        'amount' => round($amount, 2),
        'desc' => $description,
        'ref_type' => $refType ?: null,
        'ref_id' => $refId ?: null,
    ]);
}

/**
 * Debit amount from a user's wallet.
 */
function wallet_debit(PDO $db, int $userId, float $amount, string $description, string $refType = '', int $refId = 0): void
{
    referral_ensure_tables($db);
    db_exec($db, 'INSERT INTO wallet_transactions (user_id, type, amount, description, ref_type, ref_id, created_at) VALUES (:uid, :type, :amount, :desc, :ref_type, :ref_id, NOW())', [
        'uid' => $userId,
        'type' => 'debit',
        'amount' => round($amount, 2),
        'desc' => $description,
        'ref_type' => $refType ?: null,
        'ref_id' => $refId ?: null,
    ]);
}

/**
 * Race-safe debit. Inserts a debit row ONLY if the user's current balance is
 * sufficient. Two concurrent calls cannot both succeed for more than the
 * balance, because the WHERE clause in the INSERT...SELECT is evaluated
 * atomically (under InnoDB's read view at insert time).
 *
 * Returns true if the debit was recorded, false if balance was insufficient.
 */
function wallet_debit_if_sufficient(PDO $db, int $userId, float $amount, string $description, string $refType = '', int $refId = 0): bool
{
    if ($amount <= 0) return false;
    referral_ensure_tables($db);
    $rounded = round($amount, 2);
    // checkout.php calls this from inside its own transaction. PDO/MySQL have
    // no nested transactions: an unconditional beginTransaction() here throws
    // "There is already an active transaction", this catch swallows it and
    // returns false, and checkout then reports "Wallet balance insufficient"
    // to a buyer whose wallet is perfectly funded. Worse, the commit() below
    // would have committed the CALLER's half-built order. So only own the
    // transaction when there isn't one already; otherwise join it and let the
    // caller commit or roll back.
    $ownsTransaction = !$db->inTransaction();
    try {
        if ($ownsTransaction) {
            $db->beginTransaction();
        }
        // Take a row-level lock on existing wallet rows for this user so a
        // parallel reader sees a consistent view.
        $stmt = $db->prepare(
            "SELECT COALESCE(SUM(CASE WHEN type='credit' THEN amount ELSE -amount END), 0) AS balance
               FROM wallet_transactions
              WHERE user_id = :uid
              FOR UPDATE"
        );
        $stmt->execute(['uid' => $userId]);
        $balance = (float)($stmt->fetchColumn() ?? 0);
        if ($balance + 0.001 < $rounded) {
            // Only unwind what we started. If the caller owns the transaction,
            // rolling back here would discard their work too — just report the
            // shortfall and let them decide.
            if ($ownsTransaction) {
                $db->rollBack();
            }
            return false;
        }
        $ins = $db->prepare(
            'INSERT INTO wallet_transactions (user_id, type, amount, description, ref_type, ref_id, created_at)
             VALUES (:uid, "debit", :amount, :desc, :ref_type, :ref_id, NOW())'
        );
        $ins->execute([
            'uid' => $userId,
            'amount' => $rounded,
            'desc' => $description,
            'ref_type' => $refType ?: null,
            'ref_id' => $refId ?: null,
        ]);
        if ($ownsTransaction) {
            $db->commit();
        }
        return true;
    } catch (Throwable $t) {
        if ($ownsTransaction && $db->inTransaction()) { $db->rollBack(); }
        error_log('wallet_debit_if_sufficient failed: ' . $t->getMessage());
        return false;
    }
}

/**
 * Get wallet balance for a user.
 */
function wallet_balance(PDO $db, int $userId): float
{
    referral_ensure_tables($db);
    $row = db_fetch_one($db, "SELECT COALESCE(SUM(CASE WHEN type='credit' THEN amount ELSE -amount END), 0) AS balance FROM wallet_transactions WHERE user_id = :uid", [
        'uid' => $userId,
    ]);
    return round((float)($row['balance'] ?? 0), 2);
}

/**
 * Get wallet transaction history for a user.
 */
function wallet_transactions(PDO $db, int $userId): array
{
    referral_ensure_tables($db);
    return db_fetch_all($db, 'SELECT * FROM wallet_transactions WHERE user_id = :uid ORDER BY created_at DESC', ['uid' => $userId]);
}

/**
 * Get the singleton platform_settings row. Returns null only if migration v5
 * hasn't been run.
 */
function platform_settings_get(PDO $db): ?array
{
    try {
        $row = db_fetch_one($db, 'SELECT * FROM platform_settings WHERE id = 1 LIMIT 1');
        return $row ?: null;
    } catch (Throwable $t) {
        return null;
    }
}

/**
 * Platform-level Razorpay credentials. Returns null if super-admin hasn't
 * configured Razorpay keys yet.
 */
function platform_razorpay_get_config(PDO $db): ?array
{
    $s = platform_settings_get($db);
    if (!$s) return null;
    $keyId = trim((string)($s['razorpay_key_id'] ?? ''));
    $keySecret = trim((string)($s['razorpay_key_secret'] ?? ''));
    if ($keyId === '' || $keySecret === '') return null;
    return ['key_id' => $keyId, 'key_secret' => $keySecret];
}

/**
 * Platform-level Delhivery config.
 */
function platform_delhivery_get_config(PDO $db): ?array
{
    $s = platform_settings_get($db);
    if (!$s || empty($s['delhivery_api_token'])) return null;
    $mode = ($s['delhivery_mode'] ?? 'sandbox') === 'production' ? 'production' : 'sandbox';
    return [
        'token' => (string)$s['delhivery_api_token'],
        'mode' => $mode,
        'base_url' => function_exists('delhivery_base_url') ? delhivery_base_url($mode) : '',
        'warehouse_name'    => (string)($s['delhivery_warehouse_name'] ?? ''),
        'warehouse_address' => (string)($s['delhivery_warehouse_address'] ?? ''),
        'warehouse_city'    => (string)($s['delhivery_warehouse_city'] ?? ''),
        'warehouse_state'   => (string)($s['delhivery_warehouse_state'] ?? ''),
        'warehouse_pincode' => (string)($s['delhivery_warehouse_pincode'] ?? ''),
        'warehouse_phone'   => (string)($s['delhivery_warehouse_phone'] ?? ''),
    ];
}

/**
 * Platform-level SMTP config.
 */
function platform_smtp_get_config(PDO $db): ?array
{
    $s = platform_settings_get($db);
    if (!$s) return null;
    $username = trim((string)($s['smtp_username'] ?? ''));
    $password = smtp_normalize_password($s['smtp_password'] ?? '');
    if ($username === '' || $password === '') return null;
    return [
        'host'       => trim((string)($s['smtp_host'] ?? 'smtp.gmail.com')) ?: 'smtp.gmail.com',
        'port'       => (int)($s['smtp_port'] ?? 587) ?: 587,
        'username'   => $username,
        'password'   => $password,
        'from_email' => trim((string)($s['smtp_from_email'] ?? '')) ?: $username,
        'from_name'  => trim((string)($s['smtp_from_name'] ?? 'Manikya Market')) ?: 'Manikya Market',
    ];
}

/**
 * Current platform commission percentage. Defaults to 10% if settings missing.
 */
function platform_commission_pct(PDO $db): float
{
    $s = platform_settings_get($db);
    return $s ? round((float)$s['commission_pct'], 2) : 10.00;
}

/**
 * Effective commission % for a given product. Returns the product's own
 * commission_pct when set; otherwise falls back to the platform default.
 */
function product_commission_pct(PDO $db, int $productId): float
{
    if ($productId <= 0) return platform_commission_pct($db);
    try {
        $row = db_fetch_one($db, 'SELECT commission_pct FROM products WHERE id = :id LIMIT 1', ['id' => $productId]);
    } catch (Throwable $t) {
        $row = null;
    }
    if ($row && $row['commission_pct'] !== null) {
        return round((float)$row['commission_pct'], 2);
    }
    return platform_commission_pct($db);
}

/**
 * Compute commission + seller_payable for a single line item amount.
 * Returns [pct, commission_amount, seller_payable].
 */
function compute_line_commission(float $lineTotal, float $pct): array
{
    $pct = max(0.0, min(100.0, $pct));
    $comm = round($lineTotal * ($pct / 100.0), 2);
    $payable = round($lineTotal - $comm, 2);
    return [$pct, $comm, $payable];
}

/**
 * The super-admin user id used as the destination of platform commission
 * wallet credits. Returns 0 if no super-admin exists.
 */
function platform_admin_user_id(PDO $db): int
{
    try {
        $row = db_fetch_one($db, "SELECT id FROM users WHERE role='super_admin' ORDER BY id ASC LIMIT 1");
        return (int)($row['id'] ?? 0);
    } catch (Throwable $t) {
        return 0;
    }
}

/**
 * Credit a merchant's wallet for a confirmed-payment order, applying the
 * platform commission. Idempotent — if a credit already exists for
 * (user_id=merchant_id, ref_type='order', ref_id=$orderId) we no-op.
 *
 * Effect:
 *   1. orders.commission_pct / commission_amount / merchant_payable stamped
 *   2. merchant wallet credited with merchant_payable (= total - commission)
 *   3. platform (super-admin) wallet credited with commission_amount
 *
 * Returns true if new credits were inserted.
 */
function merchant_wallet_credit_for_order(PDO $db, int $orderId): bool
{
    referral_ensure_tables($db);

    $order = db_fetch_one($db,
        'SELECT id, order_no, merchant_id, total_amount FROM orders WHERE id = :id LIMIT 1',
        ['id' => $orderId]
    );
    if (!$order) return false;

    $merchantId = (int)($order['merchant_id'] ?? 0);
    $total      = (float)($order['total_amount'] ?? 0);
    if ($merchantId <= 0 || $total <= 0) return false;

    $existing = db_fetch_one($db,
        "SELECT id FROM wallet_transactions
         WHERE user_id = :uid AND ref_type = 'order' AND ref_id = :rid LIMIT 1",
        ['uid' => $merchantId, 'rid' => $orderId]
    );
    if ($existing) return false;

    // Sum from per-line snapshots. If an item is missing its snapshot
    // (legacy order), fall back to the platform default applied to that line.
    $items = db_fetch_all($db,
        'SELECT id, product_id, line_total, commission_pct, commission_amount, seller_payable
         FROM order_items WHERE order_id = :oid',
        ['oid' => $orderId]
    ) ?: [];

    $sumItems     = 0.0;
    $commission   = 0.0;
    $payable      = 0.0;
    $platformPct  = platform_commission_pct($db);
    foreach ($items as $it) {
        $lineTotal = (float)$it['line_total'];
        $sumItems += $lineTotal;
        $linePct  = $it['commission_pct'] !== null ? (float)$it['commission_pct'] : $platformPct;
        $lineComm = (float)$it['commission_amount'];
        $linePay  = (float)$it['seller_payable'];
        // If snapshot wasn't recorded (legacy/0), compute on the fly.
        if ($lineComm <= 0 && $linePay <= 0 && $lineTotal > 0) {
            [, $lineComm, $linePay] = compute_line_commission($lineTotal, $linePct);
        }
        $commission += $lineComm;
        $payable    += $linePay;
    }

    // Marketplace model: the seller is paid ONLY for the product they sold,
    // net of platform commission. Everything else the buyer paid stays with
    // the platform:
    //   - Shipping  → recovers the Ekart pickup bill (platform is the Ekart client)
    //   - GST       → platform collects + remits to government (marketplace TCS model)
    //   - Discounts → platform-funded; seller is honored at their listed price
    $commission = round($commission, 2);
    $payable    = round($payable, 2);   // = $sumItems - $commission, from per-line loop above
    if ($payable < 0) { $payable = 0.0; }

    // Platform's share of the buyer's payment = everything except the seller's
    // product net. Captures: commission + shipping + GST − any platform-absorbed
    // discounts. If discounts pushed total below the seller's payable (rare),
    // the platform absorbs a loss rather than reducing the seller.
    $platformShare = round($total - $payable, 2);
    if ($platformShare < 0) { $platformShare = 0.0; }

    // Informational stamps on the order row.
    $effectivePct = $total > 0 ? round(($commission / $total) * 100, 2) : 0.0;

    $adminId = platform_admin_user_id($db);

    try {
        db_exec($db,
            'UPDATE orders SET commission_pct = :pct, commission_amount = :comm, merchant_payable = :pay WHERE id = :id',
            ['pct' => $effectivePct, 'comm' => $commission, 'pay' => $payable, 'id' => $orderId]
        );
    } catch (Throwable $t) { /* commission columns may not exist on old installs */ }

    wallet_credit(
        $db,
        $merchantId,
        $payable,
        'Earnings from order #' . (string)$order['order_no'] . ' (product net of commission; shipping & GST retained by platform)',
        'order',
        $orderId
    );

    if ($adminId > 0 && $platformShare > 0) {
        wallet_credit(
            $db,
            $adminId,
            $platformShare,
            'Platform share from order #' . (string)$order['order_no']
                . ' — commission ₹' . number_format($commission, 2)
                . ' + shipping/GST/etc. ₹' . number_format(max(0.0, $platformShare - $commission), 2),
            'commission',
            $orderId
        );
    }

    return true;
}

/**
 * Reverse the wallet credits made for an order — used when an order is
 * cancelled or returned after the seller/admin were already credited.
 *
 *   1. Finds the original 'order' credit on the seller's wallet and inserts
 *      an offsetting debit for the same amount.
 *   2. Finds the original 'commission' credit on the super-admin's wallet and
 *      inserts an offsetting debit.
 *
 * Idempotent — if reversals already exist (ref_type='order_reversal' or
 * 'commission_reversal'), no-op.
 *
 * Returns true if any reversal was inserted.
 */
function reverse_merchant_wallet_credit_for_order(PDO $db, int $orderId, string $reason = 'cancelled'): bool
{
    $order = db_fetch_one($db,
        'SELECT id, order_no, merchant_id FROM orders WHERE id = :id LIMIT 1',
        ['id' => $orderId]
    );
    if (!$order) return false;

    $merchantId = (int)($order['merchant_id'] ?? 0);
    $orderNo    = (string)$order['order_no'];
    $adminId    = platform_admin_user_id($db);
    $didAnything = false;

    // ─── Seller side ─────────────────────────────────────────────────
    if ($merchantId > 0) {
        $alreadyReversed = db_fetch_one($db,
            "SELECT id FROM wallet_transactions
             WHERE user_id = :uid AND ref_type='order_reversal' AND ref_id = :rid AND type='debit' LIMIT 1",
            ['uid' => $merchantId, 'rid' => $orderId]
        );
        if (!$alreadyReversed) {
            $sellerCredit = db_fetch_one($db,
                "SELECT amount FROM wallet_transactions
                 WHERE user_id = :uid AND ref_type='order' AND ref_id = :rid AND type='credit' LIMIT 1",
                ['uid' => $merchantId, 'rid' => $orderId]
            );
            if ($sellerCredit && (float)$sellerCredit['amount'] > 0) {
                wallet_debit(
                    $db, $merchantId, (float)$sellerCredit['amount'],
                    'Reversal — order #' . $orderNo . ' (' . $reason . ')',
                    'order_reversal', $orderId
                );
                $didAnything = true;
            }
        }
    }

    // ─── Platform commission side ───────────────────────────────────
    if ($adminId > 0) {
        $alreadyReversedAdmin = db_fetch_one($db,
            "SELECT id FROM wallet_transactions
             WHERE user_id = :uid AND ref_type='commission_reversal' AND ref_id = :rid AND type='debit' LIMIT 1",
            ['uid' => $adminId, 'rid' => $orderId]
        );
        if (!$alreadyReversedAdmin) {
            $adminCredit = db_fetch_one($db,
                "SELECT amount FROM wallet_transactions
                 WHERE user_id = :uid AND ref_type='commission' AND ref_id = :rid AND type='credit' LIMIT 1",
                ['uid' => $adminId, 'rid' => $orderId]
            );
            if ($adminCredit && (float)$adminCredit['amount'] > 0) {
                wallet_debit(
                    $db, $adminId, (float)$adminCredit['amount'],
                    'Commission reversal — order #' . $orderNo . ' (' . $reason . ')',
                    'commission_reversal', $orderId
                );
                $didAnything = true;
            }
        }
    }

    return $didAnything;
}

/**
 * Record a payout from the platform to a merchant. Debits the merchant's
 * wallet and inserts a row in the payouts table. Returns the new payout id.
 */
function platform_record_payout(
    PDO $db,
    int $merchantId,
    float $amount,
    string $method,
    string $referenceNo,
    string $notes,
    int $recordedByUserId
): int {
    if ($merchantId <= 0 || $amount <= 0) return 0;

    $db->beginTransaction();
    try {
        db_exec($db,
            'INSERT INTO payouts (merchant_id, amount, status, method, reference_no, notes, created_by, created_at, paid_at)
             VALUES (:mid, :amt, :st, :method, :ref, :notes, :by, NOW(), NOW())',
            [
                'mid'    => $merchantId,
                'amt'    => round($amount, 2),
                'st'     => 'paid',
                'method' => $method ?: 'bank_transfer',
                'ref'    => $referenceNo ?: null,
                'notes'  => $notes ?: null,
                'by'     => $recordedByUserId > 0 ? $recordedByUserId : null,
            ]
        );
        $payoutId = (int)$db->lastInsertId();

        wallet_debit(
            $db,
            $merchantId,
            $amount,
            'Payout #' . $payoutId . ($referenceNo !== '' ? ' (ref: ' . $referenceNo . ')' : ''),
            'payout',
            $payoutId
        );

        $db->commit();
        return $payoutId;
    } catch (Throwable $t) {
        $db->rollBack();
        error_log('platform_record_payout failed: ' . $t->getMessage());
        return 0;
    }
}

/**
 * Get referral stats for a user (as referrer).
 */
function referral_stats(PDO $db, int $userId): array
{
    referral_ensure_tables($db);

    $total = db_fetch_one($db, 'SELECT COUNT(*) AS cnt FROM referrals WHERE referrer_id = :uid', ['uid' => $userId]);
    $completed = db_fetch_one($db, 'SELECT COUNT(*) AS cnt FROM referrals WHERE referrer_id = :uid AND status = :st', ['uid' => $userId, 'st' => 'completed']);
    $pending = db_fetch_one($db, 'SELECT COUNT(*) AS cnt FROM referrals WHERE referrer_id = :uid AND status = :st', ['uid' => $userId, 'st' => 'pending']);
    $earnings = db_fetch_one($db, 'SELECT COALESCE(SUM(reward_amount), 0) AS total FROM referrals WHERE referrer_id = :uid AND status = :st', ['uid' => $userId, 'st' => 'completed']);

    $totalCount = (int)($total['cnt'] ?? 0);
    $refConfig = referral_get_config($db);
    $maxPerUser = $refConfig['max_per_user'];

    return [
        'total' => $totalCount,
        'completed' => (int)($completed['cnt'] ?? 0),
        'pending' => (int)($pending['cnt'] ?? 0),
        'earnings' => round((float)($earnings['total'] ?? 0), 2),
        'max' => $maxPerUser,
        'remaining' => max(0, $maxPerUser - $totalCount),
        'referrer_pct' => $refConfig['referrer_pct'],
        'referee_pct' => $refConfig['referee_pct'],
    ];
}

/**
 * Get all referrals made by a user (as referrer).
 */
function referral_list(PDO $db, int $userId): array
{
    referral_ensure_tables($db);
    return db_fetch_all($db, '
        SELECT r.*, u.full_name AS referee_name, u.email AS referee_email
        FROM referrals r
        JOIN users u ON u.id = r.referee_id
        WHERE r.referrer_id = :uid
        ORDER BY r.created_at DESC
    ', ['uid' => $userId]);
}

/**
 * Ensure referral and wallet tables exist (auto-create on first use).
 */
function referral_ensure_tables(PDO $db): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        $db->exec("CREATE TABLE IF NOT EXISTS referral_codes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            code VARCHAR(10) NOT NULL UNIQUE,
            created_at DATETIME NOT NULL,
            CONSTRAINT fk_refcode_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS referrals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            referrer_id INT NOT NULL,
            referee_id INT NOT NULL UNIQUE,
            status ENUM('pending','completed','expired') NOT NULL DEFAULT 'pending',
            referee_discount_pct DECIMAL(5,2) NOT NULL DEFAULT 10.00,
            referrer_reward_pct DECIMAL(5,2) NOT NULL DEFAULT 5.00,
            referee_order_id INT NULL,
            discount_amount DECIMAL(10,2) NULL,
            reward_amount DECIMAL(10,2) NULL,
            created_at DATETIME NOT NULL,
            completed_at DATETIME NULL,
            CONSTRAINT fk_ref_referrer FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_ref_referee FOREIGN KEY (referee_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS wallet_transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type ENUM('credit','debit') NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            description VARCHAR(255) NULL,
            ref_type VARCHAR(30) NULL,
            ref_id INT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_wallet_user (user_id),
            CONSTRAINT fk_wallet_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $t) {
        // Tables may already exist or FK issues on older installs
    }
}
