<?php

declare(strict_types=1);

auth_require_role('buyer');

$title = 'Your Addresses';

$buyerId = auth_user_id();

// Handle remove
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $removeId = (int)$_GET['remove'];
    db_exec($db, 'DELETE FROM buyer_addresses WHERE id = :id AND buyer_id = :bid', ['id' => $removeId, 'bid' => $buyerId]);
    flash_set('success', 'Address removed.');
    redirect_to('buyer/addresses');
}

// Handle set default
if (isset($_GET['set_default']) && is_numeric($_GET['set_default'])) {
    $defId = (int)$_GET['set_default'];
    db_exec($db, 'UPDATE buyer_addresses SET is_default = 0 WHERE buyer_id = :bid', ['bid' => $buyerId]);
    db_exec($db, 'UPDATE buyer_addresses SET is_default = 1 WHERE id = :id AND buyer_id = :bid', ['id' => $defId, 'bid' => $buyerId]);
    flash_set('success', 'Default address updated.');
    redirect_to('buyer/addresses');
}

$addresses = db_fetch_all($db, 'SELECT * FROM buyer_addresses WHERE buyer_id = :buyer_id ORDER BY is_default DESC, created_at DESC', [
    'buyer_id' => $buyerId,
]);

$buyerInfo = db_fetch_one($db, 'SELECT full_name FROM users WHERE id = :id LIMIT 1', ['id' => $buyerId]);
$buyerName = (string)($buyerInfo['full_name'] ?? '');

$content = function () use ($addresses, $buyerName) {
    $success = flash_get('success');
    $error = flash_get('error');
?>
  <style>
    .amz-addr {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      color: #0F1111;
    }
    .amz-addr-breadcrumb {
      font-size: .85rem;
      color: #565959;
      margin-bottom: 8px;
    }
    .amz-addr-breadcrumb a { color: #007185; text-decoration: none; }
    .amz-addr-breadcrumb a:hover { color: #C7511F; text-decoration: underline; }
    .amz-addr-breadcrumb .sep { margin: 0 6px; color: #565959; }
    .amz-addr h1 { font-size: 1.6rem; font-weight: 500; margin: 0 0 24px; color: #0F1111; }

    .amz-addr-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 18px;
    }
    @media (max-width: 991px) { .amz-addr-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 575px) { .amz-addr-grid { grid-template-columns: 1fr; } }

    .amz-addr-card {
      background: #fff;
      border: 1px solid #D5D9D9;
      border-radius: 8px;
      min-height: 280px;
      padding: 20px;
      display: flex;
      flex-direction: column;
      position: relative;
    }
    .amz-addr-card.is-default {
      border: 2px solid #007185;
      padding: 19px;
    }
    .amz-addr-default-banner {
      background: #007185;
      color: #fff;
      padding: 6px 14px;
      margin: -19px -19px 14px;
      font-size: .82rem;
      font-weight: 500;
      border-radius: 6px 6px 0 0;
    }
    .amz-addr-name {
      font-weight: 700;
      font-size: 1rem;
      margin-bottom: 6px;
      color: #0F1111;
    }
    .amz-addr-line {
      color: #0F1111;
      font-size: .92rem;
      line-height: 1.45;
    }
    .amz-addr-phone {
      color: #0F1111;
      font-size: .9rem;
      margin-top: 6px;
    }
    .amz-addr-instr {
      margin-top: 12px;
    }
    .amz-addr-instr a {
      color: #007185;
      font-size: .85rem;
      text-decoration: none;
    }
    .amz-addr-instr a:hover { color: #C7511F; text-decoration: underline; }
    .amz-addr-actions {
      margin-top: auto;
      padding-top: 14px;
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
      font-size: .85rem;
    }
    .amz-addr-actions a { color: #007185; text-decoration: none; }
    .amz-addr-actions a:hover { color: #C7511F; text-decoration: underline; }
    .amz-addr-actions .sep { color: #D5D9D9; }
    .amz-addr-actions .danger { color: #C7511F; }

    .amz-addr-add {
      background: #fff;
      border: 1px dashed #B7BABA;
      border-radius: 8px;
      min-height: 280px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 14px;
      color: #565959;
      text-decoration: none;
      transition: background .15s, border-color .15s;
    }
    .amz-addr-add:hover { background: #F7FAFA; border-color: #007185; color: #565959; }
    .amz-addr-add .plus {
      font-size: 4.5rem;
      line-height: 1;
      font-weight: 300;
      color: #B7BABA;
    }
    .amz-addr-add .label {
      font-size: 1.15rem;
      font-weight: 500;
      color: #565959;
    }

    .amz-flash {
      padding: 12px 16px;
      border-radius: 4px;
      margin-bottom: 16px;
      font-size: .9rem;
    }
    .amz-flash-success { background: #E7FBE7; border: 1px solid #95D5A6; color: #007600; }
    .amz-flash-danger { background: #FBE7E7; border: 1px solid #D5959B; color: #C40000; }
  </style>

  <div class="amz-addr">
    <div class="container py-4" style="max-width: 1180px;">
      <div class="amz-addr-breadcrumb">
        <a href="?p=home">Your Account</a><span class="sep">›</span>Your Addresses
      </div>
      <h1>Your Addresses</h1>

      <?php if ($success): ?>
        <div class="amz-flash amz-flash-success"><?= e($success) ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="amz-flash amz-flash-danger"><?= e($error) ?></div>
      <?php endif; ?>

      <div class="amz-addr-grid">
        <a class="amz-addr-add" href="?p=buyer/address-add">
          <div class="plus">+</div>
          <div class="label">Add address</div>
        </a>

        <?php foreach ($addresses as $a):
          $isDefault = (int)$a['is_default'] === 1;
          $name = trim((string)($a['full_name'] ?: $a['label'] ?: $buyerName ?: 'Address'));
        ?>
          <div class="amz-addr-card <?= $isDefault ? 'is-default' : '' ?>">
            <?php if ($isDefault): ?>
              <div class="amz-addr-default-banner">Default address</div>
            <?php endif; ?>

            <div class="amz-addr-name"><?= e($name) ?></div>

            <?php if (!empty($a['address_line1'])): ?>
              <div class="amz-addr-line"><?= e((string)$a['address_line1']) ?></div>
            <?php endif; ?>
            <?php if (!empty($a['address_line2'])): ?>
              <div class="amz-addr-line"><?= e((string)$a['address_line2']) ?></div>
            <?php endif; ?>
            <div class="amz-addr-line">
              <?= e((string)$a['city']) ?>,
              <?= e(strtoupper((string)$a['state'])) ?>
              <?= e((string)$a['pincode']) ?>
            </div>
            <div class="amz-addr-line">India</div>

            <?php if (!empty($a['phone'])): ?>
              <div class="amz-addr-phone">Phone number: <?= e((string)$a['phone']) ?></div>
            <?php endif; ?>

            <div class="amz-addr-actions">
              <a href="?p=buyer/address-add&edit=<?= (int)$a['id'] ?>">Edit</a>
              <span class="sep">|</span>
              <a class="danger" href="?p=buyer/addresses&remove=<?= (int)$a['id'] ?>" onclick="return confirm('Remove this address?');">Remove</a>
              <?php if (!$isDefault): ?>
                <span class="sep">|</span>
                <a href="?p=buyer/addresses&set_default=<?= (int)$a['id'] ?>">Set as Default</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <script>if (window.lucide) window.lucide.createIcons();</script>
<?php
};

require __DIR__ . '/../../views/layout.php';
