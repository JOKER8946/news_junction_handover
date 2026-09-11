<?php

declare(strict_types=1);

auth_require_role('buyer');

$buyerId = (int)auth_user_id();
$orderId = (int)($_GET['order_id'] ?? $_POST['order_id'] ?? 0);

$order = db_fetch_one($db,
    'SELECT o.id, o.order_no, o.status, o.merchant_id
     FROM orders o WHERE o.id = :id AND o.buyer_id = :b LIMIT 1',
    ['id' => $orderId, 'b' => $buyerId]
);
if (!$order || $order['status'] !== 'delivered') {
    flash_set('error', 'You can only review delivered orders.');
    redirect_to('buyer/orders');
}

// First product in the order — for product_id linkage on the rating
$firstItem = db_fetch_one($db,
    'SELECT product_id FROM order_items WHERE order_id = :o ORDER BY id ASC LIMIT 1',
    ['o' => $orderId]
);
$productId = (int)($firstItem['product_id'] ?? 0);

$existing = db_fetch_one($db, 'SELECT * FROM ratings WHERE order_id = :o LIMIT 1', ['o' => $orderId]);

if (request_method() === 'POST' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating   = (int)post_string('rating');
    $feedback = trim(post_string('feedback'));
    if ($rating < 1 || $rating > 5) {
        flash_set('error', 'Pick a rating between 1 and 5.');
        redirect_to('buyer/rate-order?order_id=' . $orderId);
    }

    // Handle optional image upload
    $imagePath = $existing['image_path'] ?? null;
    if (isset($_FILES['image']) && (int)($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $tmp  = (string)$_FILES['image']['tmp_name'];
        $orig = (string)$_FILES['image']['name'];
        $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION)) ?: 'jpg';
        $allowed = ['jpg','jpeg','png','webp','gif'];
        if (in_array($ext, $allowed, true)) {
            $uploadDir = __DIR__ . '/../../../uploads/reviews';
            if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
            $newName = 'rev_' . date('YmdHis') . '_' . random_int(1000, 9999) . '.' . $ext;
            if (@move_uploaded_file($tmp, $uploadDir . '/' . $newName)) {
                $imagePath = (rtrim(app_base_path($GLOBALS['config'] ?? []), '/') ?: '') . '/uploads/reviews/' . $newName;
            }
        }
    }

    if ($existing) {
        db_exec($db, 'UPDATE ratings SET rating=:r, feedback=:f, image_path=:img, product_id=:pid, created_at=NOW() WHERE id=:id', [
            'r' => $rating, 'f' => $feedback ?: null, 'img' => $imagePath, 'pid' => $productId ?: null, 'id' => (int)$existing['id'],
        ]);
        flash_set('success', 'Review updated.');
    } else {
        db_exec($db, 'INSERT INTO ratings (order_id, buyer_id, product_id, rating, feedback, image_path) VALUES (:o, :b, :pid, :r, :f, :img)', [
            'o' => $orderId, 'b' => $buyerId, 'pid' => $productId ?: null,
            'r' => $rating, 'f' => $feedback ?: null, 'img' => $imagePath,
        ]);
        flash_set('success', 'Thanks for your review!');
    }
    redirect_to('buyer/orders');
}

$title = 'Review your order';

$content = function () use ($order, $existing) {
    ?>
    <div class="container py-4" style="max-width: 600px;">
      <h1 class="h5 mb-3">Review order <?= e((string)$order['order_no']) ?></h1>
      <div class="bg-white border rounded-3 p-4">
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
          <div class="mb-3">
            <label class="form-label">Rating (1–5) *</label>
            <select class="form-select" name="rating" required>
              <?php for ($i = 5; $i >= 1; $i--): ?>
                <option value="<?= $i ?>" <?= ((int)($existing['rating'] ?? 0) === $i) ? 'selected' : '' ?>>
                  <?= str_repeat('★', $i) . str_repeat('☆', 5 - $i) ?> (<?= $i ?>)
                </option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Your feedback</label>
            <textarea class="form-control" name="feedback" rows="4"><?= e((string)($existing['feedback'] ?? '')) ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Photo (optional)</label>
            <input class="form-control" type="file" name="image" accept="image/*">
            <?php if (!empty($existing['image_path'])): ?>
              <div class="mt-2"><img src="<?= e((string)$existing['image_path']) ?>" style="max-height: 120px; border-radius: 8px;"></div>
            <?php endif; ?>
          </div>
          <button class="btn btn-mm" type="submit">Save review</button>
          <a class="btn btn-outline-secondary" href="?p=buyer/orders">Cancel</a>
        </form>
      </div>
    </div>
    <?php
};

require __DIR__ . '/../../views/layout.php';
