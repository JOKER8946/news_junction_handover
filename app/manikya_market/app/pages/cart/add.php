<?php

declare(strict_types=1);

$id = (int)($_GET['id'] ?? 0);
$qty = (float)($_GET['qty'] ?? 1);

if ($qty <= 0) {
    $qty = 1;
}

if ($id > 0) {
    cart_add($id, $qty);
    flash_set('success', 'Added to cart');
}

redirect_to('home');
