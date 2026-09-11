<?php

declare(strict_types=1);

if (request_method() !== 'POST') {
    redirect_to('cart');
}

$id = (int)($_POST['id'] ?? 0);
$qty = (float)($_POST['qty'] ?? 0);

if ($id > 0) {
    cart_update($id, $qty);
}

redirect_to('cart');
