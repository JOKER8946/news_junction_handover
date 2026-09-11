<?php

$company = $company ?? [];
$logo = (string)($company['business_logo_path'] ?? '');
$name = (string)($company['business_name'] ?? '');
$address = (string)($company['business_address'] ?? '');
$gstin = (string)($company['business_gstin'] ?? '');
$pan = (string)($company['business_pan'] ?? '');
$state = (string)($company['business_state'] ?? '');
$pincode = (string)($company['business_pincode'] ?? '');
$bankName = (string)($company['business_bank_name'] ?? '');
$bankAcc = (string)($company['business_bank_account'] ?? '');
$bankIfsc = (string)($company['business_bank_ifsc'] ?? '');
?>
<div class="d-flex gap-3 align-items-start">
  <div style="width: 80px;">
    <?php if ($logo !== ''): ?>
      <img src="<?= e($logo) ?>" alt="Logo" class="rounded-3 border" style="width:80px;height:80px;object-fit:contain;background:#fff;">
    <?php else: ?>
      <div class="bg-light rounded-3 border d-flex align-items-center justify-content-center" style="width:80px;height:80px;"><i data-lucide="store" class="mm-icon"></i></div>
    <?php endif; ?>
  </div>
  <div class="flex-grow-1">
    <div class="fw-semibold" style="font-size: 1.05rem; line-height: 1.2;">
      <?= e($name !== '' ? $name : 'Seller') ?>
    </div>
    <?php if ($address !== ''): ?>
      <div class="text-muted small" style="white-space: pre-line;"><?= e($address) ?></div>
    <?php endif; ?>
    <div class="text-muted small mt-1">
      <?php if ($gstin !== ''): ?>GSTIN: <?= e($gstin) ?><?php endif; ?>
      <?php if ($pan !== ''): ?><?= $gstin !== '' ? ' • ' : '' ?>PAN: <?= e($pan) ?><?php endif; ?>
      <?php if ($state !== '' || $pincode !== ''): ?><?= ($gstin !== '' || $pan !== '') ? ' • ' : '' ?><?= e(trim($state . ' ' . $pincode)) ?><?php endif; ?>
    </div>
    <?php if ($bankName !== '' || $bankAcc !== '' || $bankIfsc !== ''): ?>
      <div class="text-muted small mt-1">
        Bank: <?= e($bankName) ?><?= $bankAcc !== '' ? ' • A/C: ' . e($bankAcc) : '' ?><?= $bankIfsc !== '' ? ' • IFSC: ' . e($bankIfsc) : '' ?>
      </div>
    <?php endif; ?>
  </div>
</div>
