<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$page = (string)($_GET['p'] ?? 'home');

// === CSRF gate ===
// Every POST request must carry a valid _csrf token (auto-injected by the
// JS snippet in layout.php). Routes that legitimately receive POSTs from
// outside our pages — Google OAuth callbacks, Razorpay webhooks, the buyer
// google-login first-step which navigates away — are exempted.
$csrfExempt = [
    'buyer/google-callback',
    'buyer/google-login',
    'buyer/razorpay-create',   // accessed via fetch() from our own checkout page; uses session-bound buyer auth
    'buyer/razorpay-verify',   // Razorpay's callback — signature verifies authenticity
    'webhook/razorpay',
];
if (request_method() === 'POST' && !in_array($page, $csrfExempt, true)) {
    if (!csrf_verify()) {
        // Don't leak that this is a CSRF rejection to bots scraping for errors —
        // log it then send the user back to the page they came from with a clear
        // re-try message.
        error_log('CSRF reject page=' . $page . ' ip=' . ($_SERVER['REMOTE_ADDR'] ?? '?'));
        flash_set('error', 'Security token expired. Please try again.');
        $back = (string)($_SERVER['HTTP_REFERER'] ?? '');
        if ($back !== '') { header('Location: ' . $back); exit; }
        redirect_to($page);
    }
}

// === Coming Soon gate — REMOVED ===
// The marketplace is now public. The gate block that used to sit here
// (staff/NJ-admin whitelist + coming-soon.php fallback) was removed so any
// visitor can browse. If you ever need to re-hide the storefront, restore
// this block from git — the coming-soon.php page is still on disk.

$routes = [
    'home' => __DIR__ . '/app/pages/home.php',
    'links' => __DIR__ . '/app/pages/links.php',

    'cart' => __DIR__ . '/app/pages/cart/view.php',
    'cart/add' => __DIR__ . '/app/pages/cart/add.php',
    'cart/update' => __DIR__ . '/app/pages/cart/update.php',
    'cart/remove' => __DIR__ . '/app/pages/cart/remove.php',
    'cart/clear' => __DIR__ . '/app/pages/cart/clear.php',

    'buyer/login' => __DIR__ . '/app/pages/buyer/login.php',
    'buyer/signup' => __DIR__ . '/app/pages/buyer/signup.php',
    'buyer/google-login' => __DIR__ . '/app/pages/buyer/google-login.php',
    'buyer/google-callback' => __DIR__ . '/app/pages/buyer/google-callback.php',
    'buyer/razorpay-create' => __DIR__ . '/app/pages/buyer/razorpay-create.php',
    'buyer/razorpay-verify' => __DIR__ . '/app/pages/buyer/razorpay-verify.php',
    'buyer/addresses' => __DIR__ . '/app/pages/buyer/addresses.php',
    'buyer/address-add' => __DIR__ . '/app/pages/buyer/address-add.php',
    'buyer/checkout' => __DIR__ . '/app/pages/buyer/checkout.php',
    'buyer/checkout-totals' => __DIR__ . '/app/pages/buyer/checkout-totals.php',
    'buyer/orders' => __DIR__ . '/app/pages/buyer/orders.php',
    'buyer/order-invoice' => __DIR__ . '/app/pages/buyer/order-invoice.php',
    'buyer/referral' => __DIR__ . '/app/pages/buyer/referral.php',
    'buyer/wallet' => __DIR__ . '/app/pages/buyer/wallet.php',
    'buyer/wishlist' => __DIR__ . '/app/pages/buyer/wishlist.php',
    'buyer/return-request' => __DIR__ . '/app/pages/buyer/return-request.php',
    'buyer/rate-order' => __DIR__ . '/app/pages/buyer/rate-order.php',
    'rating-helpful' => __DIR__ . '/app/pages/rating-helpful.php',
    'merchant/returns' => __DIR__ . '/app/pages/merchant/returns.php',

    'merchant/login' => __DIR__ . '/app/pages/merchant/login.php',
    'merchant/signup' => __DIR__ . '/app/pages/merchant/signup.php',
    'merchant/dashboard' => __DIR__ . '/app/pages/merchant/dashboard.php',
    'merchant/products' => __DIR__ . '/app/pages/merchant/products.php',
    'merchant/product-add' => __DIR__ . '/app/pages/merchant/product-add.php',
    'merchant/product-edit' => __DIR__ . '/app/pages/merchant/product-edit.php',
    'merchant/inventory' => __DIR__ . '/app/pages/merchant/inventory.php',
    'merchant/order' => __DIR__ . '/app/pages/merchant/order.php',
    'merchant/order-invoice' => __DIR__ . '/app/pages/merchant/order-invoice.php',
    'merchant/orders' => __DIR__ . '/app/pages/merchant/orders.php',
    'merchant/settings' => __DIR__ . '/app/pages/merchant/settings.php',
    'merchant/promotions' => __DIR__ . '/app/pages/merchant/promotions.php',
    'merchant/referrals' => __DIR__ . '/app/pages/merchant/referrals.php',
    'merchant/integrations' => __DIR__ . '/app/pages/merchant/integrations.php',
    'merchant/payments-ledger' => __DIR__ . '/app/pages/merchant/payments-ledger.php',
    'merchant/wallet' => __DIR__ . '/app/pages/merchant/wallet.php',
    'merchant/logistics-onboard' => __DIR__ . '/app/pages/merchant/logistics-onboard.php',
    'merchant/logistics-vendors' => __DIR__ . '/app/pages/merchant/logistics-vendors.php',
    'merchant/logistics-vendor-edit' => __DIR__ . '/app/pages/merchant/logistics-vendor-edit.php',

     // → Added this line to fix the 404 error
    'merchant/completed-orders' => __DIR__ . '/app/pages/merchant/completed-orders.php',
    'merchant/grn' => __DIR__ . '/app/pages/merchant/grn.php',
    'merchant/gate-entry' => __DIR__ . '/app/pages/merchant/gate-entry.php',
    'merchant/grn-create' => __DIR__ . '/app/pages/merchant/grn-create.php',
    'merchant/grn-detail' => __DIR__ . '/app/pages/merchant/grn-detail.php',
    'products' => __DIR__ . '/app/pages/products.php',
    'product'  => __DIR__ . '/app/pages/product.php',

    'api/check-pincode' => __DIR__ . '/app/pages/api/check-pincode.php',
    'api/alerts' => __DIR__ . '/app/pages/api/alerts.php',

    'webhook/razorpay'  => __DIR__ . '/app/pages/webhook/razorpay.php',

    'super-admin/login' => __DIR__ . '/app/pages/super-admin/login.php',
    'super-admin/dashboard' => __DIR__ . '/app/pages/super-admin/dashboard.php',
    'super-admin/merchants' => __DIR__ . '/app/pages/super-admin/merchants.php',
    'super-admin/buyers' => __DIR__ . '/app/pages/super-admin/buyers.php',
    'super-admin/categories' => __DIR__ . '/app/pages/super-admin/categories.php',
    'super-admin/orders' => __DIR__ . '/app/pages/super-admin/orders.php',
    'super-admin/merchant-wallets' => __DIR__ . '/app/pages/super-admin/merchant-wallets.php',
    'super-admin/merchant-wallet-detail' => __DIR__ . '/app/pages/super-admin/merchant-wallet-detail.php',
    'super-admin/platform-settings' => __DIR__ . '/app/pages/super-admin/platform-settings.php',
    'super-admin/payouts' => __DIR__ . '/app/pages/super-admin/payouts.php',
    'super-admin/platform-ledger' => __DIR__ . '/app/pages/super-admin/platform-ledger.php',
    'super-admin/tickets' => __DIR__ . '/app/pages/super-admin/tickets.php',
    'super-admin/ticket-detail' => __DIR__ . '/app/pages/super-admin/ticket-detail.php',
    'super-admin/shipments' => __DIR__ . '/app/pages/super-admin/shipments.php',
    'super-admin/coupons' => __DIR__ . '/app/pages/super-admin/coupons.php',
    'super-admin/inventory' => __DIR__ . '/app/pages/super-admin/inventory.php',
    'super-admin/commissions' => __DIR__ . '/app/pages/super-admin/commissions.php',

    'logistics/login' => __DIR__ . '/app/pages/logistics/login.php',
    'logistics/dashboard' => __DIR__ . '/app/pages/logistics/dashboard.php',
    'logistics/order-detail' => __DIR__ . '/app/pages/logistics/order-detail.php',
    'logistics/completed-orders' => __DIR__ . '/app/pages/logistics/completed-orders.php',
    'logistics/reports' => __DIR__ . '/app/pages/logistics/reports.php',

    'logout' => __DIR__ . '/app/pages/logout.php',
];

if (isset($routes[$page])) {
    require $routes[$page];
    exit;
}

// Fallback: attempt to load a page file directly from app/pages/<page>.php
$candidate = __DIR__ . '/app/pages/' . str_replace(['..', "\\"], ['', '/'], $page) . '.php';
if (file_exists($candidate)) {
    require $candidate;
    exit;
}

http_response_code(404);
$view = function () {
    $title = 'Not Found';
    require __DIR__ . '/app/views/layout.php';
};
$content = function () {
    echo '<div class="container py-4"><h1 class="h4">Page not found</h1></div>';
};
$view();
exit;
