<?php

declare(strict_types=1);

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    cart_update($id, 0);
}

redirect_to('cart');
