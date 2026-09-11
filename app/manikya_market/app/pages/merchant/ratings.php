<?php

declare(strict_types=1);

auth_require_role('merchant');

$title = 'Customer Ratings & Feedback';
$merchantId = (int)auth_user_id();

// Ensure ratings table exists
try {
    db_exec($db, '
        CREATE TABLE IF NOT EXISTS ratings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            buyer_id INT NOT NULL,
            rating INT NOT NULL,
            feedback TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id),
            FOREIGN KEY (buyer_id) REFERENCES users(id),
            UNIQUE KEY unique_order_rating (order_id)
        )
    ');
} catch (Throwable $e) {
    // table may already exist
}

// Get ratings on this merchant's orders only
$allRatings = db_fetch_all($db, '
    SELECT r.id, r.order_id, r.buyer_id, r.rating, r.feedback, r.created_at,
           o.order_no, o.total_amount, u.full_name as buyer_name, u.email as buyer_email
    FROM ratings r
    JOIN orders o ON o.id = r.order_id
    JOIN users u ON u.id = r.buyer_id
    WHERE o.merchant_id = :mid
    ORDER BY r.created_at DESC
', ['mid' => $merchantId]) ?: [];

// Calculate average rating
$avgRating = 0;
if (!empty($allRatings)) {
    $sum = array_sum(array_map(fn($r) => $r['rating'], $allRatings));
    $avgRating = round($sum / count($allRatings), 1);
}

$content = function() use ($allRatings, $avgRating) {
    ?>
    <div class="container py-4">
        <div class="d-flex align-items-center justify-content-between mb-4 gap-2 flex-wrap">
            <h1 class="h5 mb-0"><span class="d-none d-sm-inline">Customer Ratings & Feedback</span><span class="d-sm-none">Ratings</span></h1>
            <a class="btn btn-outline-secondary btn-sm" href="?p=merchant/dashboard">Back</a>
        </div>

        <!-- Summary Card -->
        <div class="row g-3 mb-3">
            <div class="col-12 col-sm-6 col-md-4">
                <div class="bg-white border rounded-4 p-3 p-sm-4 mm-card">
                    <div class="text-center">
                        <div class="h3 mb-1"><?= $avgRating ?></div>
                        <div class="text-muted small">Average Rating</div>
                        <div class="mt-2">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <i data-lucide="star" class="mm-icon" style="width:16px;height:16px;<?= $i < floor($avgRating) ? 'fill:gold;stroke:gold;' : 'stroke:currentColor;' ?>"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <div class="bg-white border rounded-4 p-3 p-sm-4 mm-card">
                    <div class="text-center">
                        <div class="h3 mb-1"><?= count($allRatings) ?></div>
                        <div class="text-muted small">Total Ratings</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <div class="bg-white border rounded-4 p-3 p-sm-4 mm-card">
                    <div class="text-center">
                        <div class="text-muted small mb-2">Rating Distribution</div>
                        <?php 
                            $distribution = array_fill(1, 5, 0);
                            foreach ($allRatings as $r) {
                                $distribution[$r['rating']]++;
                            }
                        ?>
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <div class="small d-flex align-items-center gap-1">
                                <?= $i ?> <i data-lucide="star" style="width:12px;height:12px;fill:gold;stroke:gold;"></i>
                                <div class="progress flex-grow-1" style="height:4px;">
                                    <div class="progress-bar bg-warning" style="width: <?= count($allRatings) > 0 ? ($distribution[$i] / count($allRatings) * 100) : 0 ?>%"></div>
                                </div>
                                <small><?= $distribution[$i] ?></small>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ratings List -->
        <div class="bg-white border rounded-4 p-3 p-sm-4 mm-card">
            <div class="fw-semibold mb-3 small">All Ratings</div>
            <?php if (empty($allRatings)): ?>
                <div class="text-muted small">No ratings yet.</div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($allRatings as $r): ?>
                        <div class="border rounded-3 p-3" style="border-left: 4px solid #ffc107;">
                            <div class="d-flex flex-wrap justify-content-between align-items-start mb-2 gap-2">
                                <div class="flex-grow-1 min-width-0">
                                    <div class="fw-semibold small text-truncate">Order #<?= e((string)$r['order_no']) ?></div>
                                    <div class="text-muted small text-truncate"><?= e((string)$r['buyer_name']) ?> · <?= date('M d, Y', strtotime((string)$r['created_at'])) ?></div>
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <div class="fw-semibold mb-1 d-flex justify-content-end gap-1">
                                        <?php for ($i = 0; $i < 5; $i++): ?>
                                            <i data-lucide="star" class="mm-icon" style="width:12px;height:12px;<?= $i < $r['rating'] ? 'fill:gold;stroke:gold;' : 'stroke:currentColor;' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="text-muted small">₹<?= e((string)$r['total_amount']) ?></div>
                                </div>
                            </div>
                            <?php if (!empty($r['feedback'])): ?>
                                <div class="text-muted small mb-2">
                                    <em>"<?= nl2br(e((string)$r['feedback'])) ?>"</em>
                                </div>
                            <?php endif; ?>
                            <div class="text-muted small d-flex flex-wrap gap-2">
                                <a href="?p=merchant/order&id=<?= (int)$r['order_id'] ?>" class="btn btn-xs btn-outline-secondary text-nowrap" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">View Order</a>
                                <a href="mailto:<?= e((string)$r['buyer_email']) ?>" class="btn btn-xs btn-outline-secondary text-nowrap" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">Contact Buyer</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .space-y-3 > * + * { margin-top: 1rem; }
        .min-width-0 { min-width: 0; }
        .btn-xs { padding: 0.25rem 0.5rem !important; font-size: 0.75rem !important; }
        @media (max-width: 576px) {
            .p-sm-4 { padding: 1.5rem !important; }
        }
    </style>
    <?php
};

require __DIR__ . '/../../views/layout.php';

?>
