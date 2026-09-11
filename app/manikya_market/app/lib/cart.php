<?php

declare(strict_types=1);

function cart_get(): array
{
    $cart = $_SESSION['cart'] ?? [];
    if (!is_array($cart)) {
        return [];
    }

    $clean = [];
    foreach ($cart as $productId => $qtyKg) {
        $pid = (int)$productId;
        $qty = (float)$qtyKg;
        if ($pid <= 0 || $qty <= 0) {
            continue;
        }
        $clean[(string)$pid] = round($qty, 2);
    }

    $_SESSION['cart'] = $clean;

    return $clean;
}

function cart_count(): int
{
    return count(cart_get());
}

function cart_add(int $productId, float $qtyKg): void
{
    if ($productId <= 0 || $qtyKg <= 0) {
        return;
    }

    $cart = cart_get();
    $key = (string)$productId;
    $existing = (float)($cart[$key] ?? 0);
    $cart[$key] = round($existing + $qtyKg, 2);
    $_SESSION['cart'] = $cart;
}

function cart_update(int $productId, float $qtyKg): void
{
    if ($productId <= 0) {
        return;
    }

    $cart = cart_get();
    $key = (string)$productId;

    if ($qtyKg <= 0) {
        unset($cart[$key]);
    } else {
        $cart[$key] = round($qtyKg, 2);
    }

    $_SESSION['cart'] = $cart;
}

function cart_clear(): void
{
    unset($_SESSION['cart']);
}

/**
 * Return the currently-live discount for a given category row.
 * A discount is "live" when discount_pct > 0 AND (discount_ends_at is NULL or in the future).
 * Returns:
 *   ['pct' => float, 'label' => string, 'ends_at' => string|null]  when active
 *   ['pct' => 0.0,   'label' => '',     'ends_at' => null]           when not
 *
 * $cat is expected to have keys: discount_pct, discount_label, discount_ends_at.
 * Safe to pass any array — missing keys are treated as "no discount".
 */
function category_active_discount(array $cat): array
{
    $pct   = (float)($cat['discount_pct']  ?? 0);
    $label = trim((string)($cat['discount_label'] ?? ''));
    $ends  = (string)($cat['discount_ends_at'] ?? '');
    if ($pct <= 0) {
        return ['pct' => 0.0, 'label' => '', 'ends_at' => null];
    }
    if ($ends !== '' && strtotime($ends) <= time()) {
        // Sale expired.
        return ['pct' => 0.0, 'label' => '', 'ends_at' => null];
    }
    if ($pct > 100) $pct = 100;
    return [
        'pct'     => round($pct, 2),
        'label'   => $label,
        'ends_at' => $ends !== '' ? $ends : null,
    ];
}

/**
 * Compute a product's live displayed discount, combining:
 *   1. Auto-apply product coupon (if one is attached and eligible)  — WINS
 *   2. Category discount (falls back if no auto coupon)
 *
 * When an auto coupon is active, treat it as a per-unit rebate so quantities
 * multiply cleanly (₹50 off a ₹450 unit → save ₹50 × qty). This mirrors
 * Amazon's "Deal price" behavior.
 *
 * Returns:
 *   pct         → equivalent percent off (for the -XX% badge)
 *   per_unit    → discount ₹ per unit
 *   final_unit  → discounted per-unit price
 *   label       → sale label ("Deal" for coupon; category label for category)
 *   ends_at     → when the sale ends (null = no end)
 *   source      → 'coupon' | 'category' | ''
 *   coupon_id   → id of the auto coupon (for used_count tracking at commit)
 */
function product_auto_discount(array $product, ?array $autoCoupon = null): array
{
    $orig = (float)($product['price_per_kg'] ?? 0);

    if ($autoCoupon && $orig > 0) {
        if (($autoCoupon['discount_type'] ?? 'percent') === 'percent') {
            $perUnit = round($orig * ((float)$autoCoupon['discount_value'] / 100), 2);
            $max = $autoCoupon['max_discount'] ?? null;
            if ($max !== null && (float)$max > 0 && $perUnit > (float)$max) {
                $perUnit = (float)$max;
            }
        } else {
            $perUnit = min($orig, (float)$autoCoupon['discount_value']);
        }
        if ($perUnit > 0) {
            return [
                'pct'        => round(($perUnit / $orig) * 100, 2),
                'per_unit'   => $perUnit,
                'final_unit' => round($orig - $perUnit, 2),
                'label'      => (string)($autoCoupon['description'] ?? '') !== ''
                                ? (string)$autoCoupon['description']
                                : 'Deal',
                'ends_at'    => $autoCoupon['expires_at'] ?? null,
                'source'     => 'coupon',
                'coupon_id'  => (int)$autoCoupon['id'],
            ];
        }
    }

    // No auto coupon → fall through to category discount.
    $catDisc = category_active_discount([
        'discount_pct'     => $product['cat_discount_pct']     ?? 0,
        'discount_label'   => $product['cat_discount_label']   ?? '',
        'discount_ends_at' => $product['cat_discount_ends_at'] ?? '',
    ]);
    if ($catDisc['pct'] > 0 && $orig > 0) {
        $perUnit = round($orig * ($catDisc['pct'] / 100), 2);
        return [
            'pct'        => $catDisc['pct'],
            'per_unit'   => $perUnit,
            'final_unit' => round($orig - $perUnit, 2),
            'label'      => $catDisc['label'],
            'ends_at'    => $catDisc['ends_at'],
            'source'     => 'category',
            'coupon_id'  => 0,
        ];
    }

    return [
        'pct' => 0.0, 'per_unit' => 0.0, 'final_unit' => $orig,
        'label' => '', 'ends_at' => null, 'source' => '', 'coupon_id' => 0,
    ];
}

/**
 * Product-level discount, taking the super-admin's per-product override into account.
 *
 *   discount_override_exclude = 1 → this product NEVER discounts (opt-out).
 *   discount_override IS NOT NULL → use the product's own %/label/ends_at.
 *   else                          → inherit the category discount.
 *
 * $p should carry the product's override columns (discount_override,
 * discount_override_label, discount_override_ends_at, discount_override_exclude)
 * plus the category discount columns aliased as cat_discount_pct,
 * cat_discount_label, cat_discount_ends_at. Missing keys are safe.
 */
function product_active_discount(array $p): array
{
    if ((int)($p['discount_override_exclude'] ?? 0) === 1) {
        return ['pct' => 0.0, 'label' => '', 'ends_at' => null];
    }

    $override = $p['discount_override'] ?? null;
    if ($override !== null && (float)$override > 0) {
        return category_active_discount([
            'discount_pct'     => (float)$override,
            'discount_label'   => (string)($p['discount_override_label'] ?? ''),
            'discount_ends_at' => (string)($p['discount_override_ends_at'] ?? ''),
        ]);
    }

    return category_active_discount([
        'discount_pct'     => $p['cat_discount_pct'] ?? 0,
        'discount_label'   => $p['cat_discount_label'] ?? '',
        'discount_ends_at' => $p['cat_discount_ends_at'] ?? '',
    ]);
}

/**
 * Total shipping weight in grams for one cart line.
 *
 *   packet products → qty (packets) × pack_size_grams
 *   bulk products, unit=kg → qty (kg) × 1000
 *   bulk products, unit=gm → qty (grams) — already grams
 *   bulk products, other units → qty × 1000 (legacy behavior; approximated)
 */
function line_weight_grams(array $product, float $qty): int
{
    $soldAs = (string)($product['sold_as'] ?? 'bulk');
    if ($soldAs === 'packet') {
        $packG = (float)($product['pack_size_grams'] ?? 0);
        return (int)round($qty * $packG);
    }
    $unit = (string)($product['unit'] ?? 'kg');
    if ($unit === 'gm') {
        return (int)round($qty);
    }
    return (int)round($qty * 1000);
}

function cart_items_with_products(PDO $db): array
{
    $cart = cart_get();
    if (!$cart) {
        return [];
    }

    $ids = array_map('intval', array_keys($cart));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // Pull category discount fields alongside product data so cart pricing can
    // apply platform-wide category discounts without a second round-trip.
    // Also pull sold_as / pack_size_grams so weight math is packet-aware.
    $rows = db_fetch_all($db,
        "SELECT p.id, p.name, p.image_path, p.price_per_kg, p.stock_kg, p.unit, p.shipping_type, p.shipping_rate,
                p.merchant_id, p.category_id,
                p.sold_as, p.pack_size_grams,
                mp.business_name AS merchant_business, mp.business_pincode AS merchant_pincode,
                u.full_name AS merchant_full_name,
                c.discount_pct AS cat_discount_pct,
                c.discount_label AS cat_discount_label,
                c.discount_ends_at AS cat_discount_ends_at
         FROM products p
         LEFT JOIN merchant_profile mp ON mp.merchant_user_id = p.merchant_id
         LEFT JOIN users u ON u.id = p.merchant_id
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.is_active = 1 AND p.id IN ($placeholders)",
        $ids
    );

    $byId = [];
    foreach ($rows as $r) {
        $byId[(int)$r['id']] = $r;
    }

    // Batch-look-up any auto-apply product coupons attached to the cart's
    // products. These take precedence over category discounts.
    $autoByProduct = function_exists('coupon_auto_apply_for_products')
        ? coupon_auto_apply_for_products($db, array_keys($byId))
        : [];

    $items = [];
    foreach ($cart as $pidStr => $qty) {
        $pid = (int)$pidStr;
        if (!isset($byId[$pid])) {
            continue;
        }

        $p = $byId[$pid];
        $qtyKg = (float)$qty;                        // "qty in visible units": kg / grams / packets
        $pricePerKg = (float)$p['price_per_kg'];     // for packet products this is price per packet
        $lineOriginal = round($qtyKg * $pricePerKg, 2);

        // Auto discount: coupon wins over category. See product_auto_discount().
        $discInfo = product_auto_discount($p, $autoByProduct[$pid] ?? null);
        $discPct      = (float)$discInfo['pct'];
        $lineDiscount = $discInfo['per_unit'] > 0
            ? round($qtyKg * (float)$discInfo['per_unit'], 2)
            : 0.0;
        $lineSubtotal = round($lineOriginal - $lineDiscount, 2);
        if ($lineSubtotal < 0) $lineSubtotal = 0.0;

        $shippingType = (string)$p['shipping_type'];
        $shippingRate = (float)$p['shipping_rate'];
        $lineShipping = 0.0;
        if ($shippingType === 'per_kg') {
            // For "per_kg" shipping on packet products, bill on the pack's kg
            // weight rather than treating packet count as kg.
            $shipQty = ((string)($p['sold_as'] ?? 'bulk')) === 'packet'
                ? ($qtyKg * (float)($p['pack_size_grams'] ?? 0) / 1000.0)
                : $qtyKg;
            $lineShipping = round($shipQty * $shippingRate, 2);
        } else {
            $lineShipping = $qtyKg > 0 ? round($shippingRate, 2) : 0.0;
        }

        $items[] = [
            'product' => $p,
            'qty_kg' => $qtyKg,
            'merchant_id' => (int)($p['merchant_id'] ?? 0),
            'merchant_business' => (string)($p['merchant_business'] ?? ($p['merchant_full_name'] ?? 'Seller')),
            'line_original' => $lineOriginal,
            'line_discount' => $lineDiscount,
            'discount_pct'  => $discPct,
            'discount_label'=> (string)$discInfo['label'],
            'discount_ends_at' => $discInfo['ends_at'],
            'discount_source'  => (string)$discInfo['source'],   // '' | 'coupon' | 'category'
            'auto_coupon_id'   => (int)$discInfo['coupon_id'],   // > 0 → record usage at commit
            'line_subtotal' => $lineSubtotal,
            'line_shipping' => $lineShipping,
            'line_total' => round($lineSubtotal + $lineShipping, 2),
        ];
    }

    return $items;
}

/**
 * Group cart items by merchant.
 * Returns: [merchant_id => ['merchant_id' => int, 'merchant_business' => string,
 *                          'items' => [...], 'subtotal' => float, 'shipping' => float, 'total' => float]]
 */
function cart_groups_by_merchant(array $items): array
{
    $groups = [];
    foreach ($items as $it) {
        $mid = (int)($it['merchant_id'] ?? 0);
        if (!isset($groups[$mid])) {
            $groups[$mid] = [
                'merchant_id' => $mid,
                'merchant_business' => (string)($it['merchant_business'] ?? 'Seller'),
                'items' => [],
                'subtotal' => 0.0,
                'shipping' => 0.0,
                'total' => 0.0,
            ];
        }
        $groups[$mid]['items'][] = $it;
        $groups[$mid]['subtotal'] += (float)$it['line_subtotal'];
        $groups[$mid]['shipping'] += (float)$it['line_shipping'];
    }
    foreach ($groups as &$g) {
        $g['subtotal'] = round($g['subtotal'], 2);
        $g['shipping'] = round($g['shipping'], 2);
        $g['total']    = round($g['subtotal'] + $g['shipping'], 2);
    }
    unset($g);
    return $groups;
}

function cart_totals(PDO $db): array
{
    $items = cart_items_with_products($db);

    $subtotal = 0.0;
    $shipping = 0.0;

    foreach ($items as $i) {
        $subtotal += (float)$i['line_subtotal'];
        $shipping += (float)$i['line_shipping'];
    }

    $subtotal = round($subtotal, 2);
    $shipping = round($shipping, 2);

    return [
        'items' => $items,
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'total' => round($subtotal + $shipping, 2),
    ];
}

function cart_total_weight_grams(PDO $db): int
{
    $items = cart_items_with_products($db);
    $grams = 0;
    foreach ($items as $i) {
        $grams += line_weight_grams($i['product'], (float)$i['qty_kg']);
    }
    return $grams;
}

/**
 * Cart totals with shipping replaced by Ekart's live quote PER MERCHANT.
 * For a multi-seller cart we ask Ekart for one quote per seller's warehouse
 * pincode -> destination pincode (with that seller's portion of the cart
 * weight). The merchant subtotals + per-merchant shipping are summed for the
 * total. Falls back to per-product shipping if the API fails or any seller is
 * missing a warehouse pincode.
 *
 * Returns extra keys:
 *   'shipping_source' => 'ekart' | 'fallback'
 *   'serviceable'     => bool   (true only if EVERY merchant's pincode is serviceable)
 *   'per_merchant_shipping' => [merchantId => float]   (for the checkout UI)
 */
function cart_totals_with_delhivery(PDO $db, string $destPincode): array
{
    $totals = cart_totals($db);
    $totals['shipping_source'] = 'fallback';
    $totals['serviceable'] = true;
    $totals['per_merchant_shipping'] = [];

    if (!function_exists('delhivery_get_shipping_charge')) {
        return $totals;
    }
    if ($destPincode === '') {
        return $totals;
    }

    // Group cart items by merchant so we can quote each pickup separately.
    $items  = cart_items_with_products($db);
    $groups = cart_groups_by_merchant($items);
    if (empty($groups)) return $totals;

    $totalShipping  = 0.0;
    $allServiceable = true;
    $allQuoted      = true;  // becomes false if any merchant can't be quoted live

    foreach ($groups as $merchantId => $group) {
        // Per-merchant weight (sum each line's true grams — respects packets/gm units)
        $weight = 0;
        foreach ($group['items'] as $it) {
            $weight += line_weight_grams($it['product'], (float)$it['qty_kg']);
        }
        if ($weight <= 0) {
            $totals['per_merchant_shipping'][$merchantId] = (float)$group['shipping'];
            $totalShipping += (float)$group['shipping'];
            continue;
        }

        // Ekart picks up the merchant's pincode internally from their alias /
        // warehouse on file. delhivery_get_shipping_charge accepts a 4th param
        // for the source merchant id; if that merchant has no warehouse on
        // file, the helper returns shipping_charge=null so we fall back.
        $quote = delhivery_get_shipping_charge($db, $destPincode, $weight, (int)$merchantId);

        if (!($quote['serviceable'] ?? true)) {
            $allServiceable = false;
        }

        if ($quote['shipping_charge'] !== null) {
            $perMerchant = round((float)$quote['shipping_charge'], 2);
            $totals['per_merchant_shipping'][$merchantId] = $perMerchant;
            $totalShipping += $perMerchant;
        } else {
            // No live quote — use this merchant's fallback shipping for the total
            // but mark the overall source as fallback so the UI can surface it.
            $totals['per_merchant_shipping'][$merchantId] = (float)$group['shipping'];
            $totalShipping += (float)$group['shipping'];
            $allQuoted = false;
        }
    }

    $totals['serviceable'] = $allServiceable;
    if ($allQuoted) {
        $totals['shipping']        = round($totalShipping, 2);
        $totals['total']           = round((float)$totals['subtotal'] + $totals['shipping'], 2);
        $totals['shipping_source'] = 'ekart';
    } else {
        // Mixed result — at least one merchant fell back. Keep the per-product
        // shipping in the total (already computed by cart_totals) and label it.
        $totals['shipping_source'] = 'fallback';
    }

    return $totals;
}
