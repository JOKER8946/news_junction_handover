<?php

declare(strict_types=1);

/**
 * Look up an active coupon by code. Returns null if missing/inactive/expired.
 */
function coupon_lookup(PDO $db, string $code): ?array
{
    $code = strtoupper(trim($code));
    if ($code === '') return null;
    $row = db_fetch_one($db, '
        SELECT * FROM coupons
        WHERE code = :c AND is_active = 1
          AND (expires_at IS NULL OR expires_at > NOW())
          AND (usage_limit IS NULL OR used_count < usage_limit)
        LIMIT 1
    ', ['c' => $code]);
    return $row ?: null;
}

/**
 * Compute the discount amount the coupon would give against an order.
 *
 * $subtotal = whole-cart subtotal (used as the base when the coupon is
 * platform-wide).
 *
 * $items    = the cart items array (as returned by cart_items_with_products).
 * When the coupon has a non-null product_id, the effective base is the sum
 * of ONLY the line_subtotals for that product; min_order_value is checked
 * against that scoped base, not the whole cart. If the buyer's cart doesn't
 * include that product at all, returns 0 (coupon doesn't apply).
 *
 * Returns 0 if the coupon can't apply (below min_order_value, product
 * missing, etc.).
 */
function coupon_compute_discount(array $coupon, float $subtotal, array $items = []): float
{
    $productId = isset($coupon['product_id']) && $coupon['product_id'] !== null
        ? (int)$coupon['product_id']
        : 0;

    // Product-scoped coupon: recompute the base from matching cart lines.
    if ($productId > 0) {
        $base = 0.0;
        foreach ($items as $it) {
            $pid = (int)($it['product']['id'] ?? 0);
            if ($pid === $productId) {
                $base += (float)($it['line_subtotal'] ?? $it['line_total'] ?? 0);
            }
        }
        if ($base <= 0) {
            // Buyer isn't buying the target product — coupon can't apply.
            return 0.0;
        }
        $subtotal = round($base, 2);
    }

    $minOrder = (float)($coupon['min_order_value'] ?? 0);
    if ($subtotal < $minOrder) return 0.0;

    if (($coupon['discount_type'] ?? 'percent') === 'fixed') {
        $disc = (float)$coupon['discount_value'];
    } else {
        $disc = round($subtotal * ((float)$coupon['discount_value'] / 100), 2);
    }
    $max = $coupon['max_discount'] ?? null;
    if ($max !== null && $max > 0 && $disc > (float)$max) {
        $disc = (float)$max;
    }

    // Never let the discount exceed the base (subscoped or full) — otherwise
    // a fixed-₹100-off coupon could give back more than the eligible spend.
    if ($disc > $subtotal) $disc = $subtotal;

    return max(0.0, round($disc, 2));
}

/**
 * Batch-fetch the best "auto-apply" coupon for each of the given product ids.
 *
 * An auto-apply coupon is one that shows on the storefront tile as a live
 * discount (Amazon-style strikethrough MRP), no code entry required. It must:
 *   - be product-scoped (product_id set)
 *   - be active, not expired, under usage_limit
 *   - have min_order_value = 0 (needs no cart threshold)
 *
 * Returns: [productId => couponRow, ...] — products without an eligible
 * coupon are absent. If multiple coupons are eligible for one product, the
 * most recently created one wins (super admin's latest sale intent).
 */
function coupon_auto_apply_for_products(PDO $db, array $productIds): array
{
    if (!$productIds) return [];
    $ids = array_values(array_unique(array_map('intval', $productIds)));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $rows = db_fetch_all($db, "
        SELECT * FROM coupons
        WHERE product_id IN ($placeholders)
          AND is_active = 1 AND min_order_value = 0
          AND (expires_at IS NULL OR expires_at > NOW())
          AND (usage_limit IS NULL OR used_count < usage_limit)
        ORDER BY created_at DESC
    ", $ids) ?: [];
    $byProduct = [];
    foreach ($rows as $r) {
        $pid = (int)$r['product_id'];
        if (!isset($byProduct[$pid])) $byProduct[$pid] = $r;   // first (newest) wins
    }
    return $byProduct;
}

/**
 * Increment usage_count after a successful order.
 */
function coupon_record_usage(PDO $db, int $couponId): void
{
    if ($couponId <= 0) return;
    db_exec($db, 'UPDATE coupons SET used_count = used_count + 1 WHERE id = :id', ['id' => $couponId]);
}

/**
 * Decrement usage_count when an order using this coupon is cancelled, so a
 * usage-limited coupon doesn't get exhausted by cancelled orders.
 */
function coupon_release_usage(PDO $db, int $couponId): void
{
    if ($couponId <= 0) return;
    db_exec($db, 'UPDATE coupons SET used_count = GREATEST(used_count - 1, 0) WHERE id = :id', ['id' => $couponId]);
}

/**
 * Current GST rate from platform_settings (defaults to 18%).
 */
function platform_gst_rate(PDO $db): float
{
    $s = function_exists('platform_settings_get') ? platform_settings_get($db) : null;
    return $s ? round((float)($s['gst_rate_pct'] ?? 18.0), 2) : 18.0;
}

/**
 * Compute GST amount on a taxable subtotal.
 */
function compute_gst(float $taxable, float $ratePct): float
{
    return round($taxable * ($ratePct / 100), 2);
}
