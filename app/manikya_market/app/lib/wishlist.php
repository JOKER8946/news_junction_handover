<?php

declare(strict_types=1);

function wishlist_add(PDO $db, int $buyerId, int $productId): bool
{
    if ($buyerId <= 0 || $productId <= 0) return false;
    try {
        db_exec($db, 'INSERT IGNORE INTO wishlists (buyer_id, product_id, created_at) VALUES (:b, :p, NOW())', [
            'b' => $buyerId, 'p' => $productId,
        ]);
        return true;
    } catch (Throwable $t) {
        return false;
    }
}

function wishlist_remove(PDO $db, int $buyerId, int $productId): bool
{
    if ($buyerId <= 0 || $productId <= 0) return false;
    db_exec($db, 'DELETE FROM wishlists WHERE buyer_id = :b AND product_id = :p', [
        'b' => $buyerId, 'p' => $productId,
    ]);
    return true;
}

function wishlist_has(PDO $db, int $buyerId, int $productId): bool
{
    if ($buyerId <= 0 || $productId <= 0) return false;
    $row = db_fetch_one($db, 'SELECT id FROM wishlists WHERE buyer_id = :b AND product_id = :p LIMIT 1', [
        'b' => $buyerId, 'p' => $productId,
    ]);
    return $row !== null && $row !== false;
}

function wishlist_count(PDO $db, int $buyerId): int
{
    if ($buyerId <= 0) return 0;
    $row = db_fetch_one($db, 'SELECT COUNT(*) AS c FROM wishlists WHERE buyer_id = :b', ['b' => $buyerId]);
    return (int)($row['c'] ?? 0);
}

function wishlist_items(PDO $db, int $buyerId): array
{
    if ($buyerId <= 0) return [];
    return db_fetch_all($db,
        'SELECT p.id, p.name, p.image_path, p.price_per_kg, p.stock_kg, p.is_active,
                p.unit, p.product_type, p.part_code,
                p.merchant_id, mp.business_name AS merchant_business,
                w.created_at AS added_at
         FROM wishlists w
         JOIN products p ON p.id = w.product_id
         LEFT JOIN merchant_profile mp ON mp.merchant_user_id = p.merchant_id
         WHERE w.buyer_id = :b
         ORDER BY w.created_at DESC',
        ['b' => $buyerId]
    ) ?: [];
}
