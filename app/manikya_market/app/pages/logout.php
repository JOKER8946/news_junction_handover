<?php

declare(strict_types=1);

// Remember which role was active BEFORE clearing the session so we can
// send the user to the appropriate sign-in page on the way out.
$priorRole = auth_role();

auth_logout();

switch ($priorRole) {
    case 'super_admin':
        redirect_to('super-admin/login');
        break;
    case 'merchant':
        redirect_to('merchant/login');
        break;
    case 'logistics':
        redirect_to('logistics/login');
        break;
    default:
        redirect_to('home');
}
