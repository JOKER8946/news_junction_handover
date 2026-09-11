<?php

declare(strict_types=1);

// Mark a review as helpful. Buyer must be logged in. Idempotent per (rating, buyer).
auth_require_role('buyer');

$buyerId  = (int)auth_user_id();
$ratingId = (int)($_POST['rating_id'] ?? $_GET['rating_id'] ?? 0);
$back     = (string)($_POST['return_to'] ?? $_GET['return_to'] ?? 'products');

if ($ratingId <= 0) {
    redirect_to($back);
}

try {
    db_exec($db, 'INSERT IGNORE INTO rating_helpful (rating_id, buyer_id) VALUES (:r, :b)', [
        'r' => $ratingId, 'b' => $buyerId,
    ]);
    if ($db->lastInsertId()) {
        db_exec($db, 'UPDATE ratings SET helpful_votes = helpful_votes + 1 WHERE id = :id', ['id' => $ratingId]);
    }
} catch (Throwable $t) { /* ignore */ }

redirect_to($back);
