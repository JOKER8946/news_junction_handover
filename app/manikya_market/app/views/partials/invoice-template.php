<?php
/**
 * Shared Amazon-style tax invoice template.
 *
 * Expects these variables to be in scope before being required:
 *   $order      - row from orders + joined buyer fields (buyer_name/phone/email)
 *   $address    - delivery_address row (or null)
 *   $items      - order_items joined with products
 *   $company    - merchant_profile row for the SELLER (use order.merchant_id)
 *   $payment    - latest payments row for the order (or [])
 *   $gstRate    - float, e.g. 18.0 (sourced from platform_settings)
 *   $fullBase   - absolute base URL (used for back link)
 *   $id         - (int) order id
 *   $backHref   - href for the "Back" button (role-specific)
 */

declare(strict_types=1);

// Indian state → State/UT code lookup.
$inv_stateCodes = [
    'andhra pradesh' => '37', 'arunachal pradesh' => '12', 'assam' => '18',
    'bihar' => '10', 'chhattisgarh' => '22', 'goa' => '30', 'gujarat' => '24',
    'haryana' => '06', 'himachal pradesh' => '02', 'jharkhand' => '20',
    'karnataka' => '29', 'kerala' => '32', 'madhya pradesh' => '23',
    'maharashtra' => '27', 'manipur' => '14', 'meghalaya' => '17',
    'mizoram' => '15', 'nagaland' => '13', 'odisha' => '21', 'punjab' => '03',
    'rajasthan' => '08', 'sikkim' => '11', 'tamil nadu' => '33',
    'telangana' => '36', 'tripura' => '16', 'uttar pradesh' => '09',
    'uttarakhand' => '05', 'west bengal' => '19',
    'delhi' => '07', 'jammu and kashmir' => '01', 'ladakh' => '38',
    'puducherry' => '34', 'chandigarh' => '04', 'andaman and nicobar islands' => '35',
    'dadra and nagar haveli and daman and diu' => '26', 'lakshadweep' => '31',
];
$inv_normState = fn($s) => strtolower(trim((string)$s));
$inv_sellerStateCode = $inv_stateCodes[$inv_normState($company['business_state'] ?? '')] ?? '';
$inv_buyerStateCode  = is_array($address) ? ($inv_stateCodes[$inv_normState($address['state'] ?? '')] ?? '') : '';
$inv_sameState = ($inv_sellerStateCode !== '' && $inv_buyerStateCode !== '' && $inv_sellerStateCode === $inv_buyerStateCode);

$inv_gstFactor = 1 + ($gstRate / 100);

if (!function_exists('numberToIndianWords')) {
    function numberToIndianWords(float $amount): string {
        $rupees = (int)floor($amount);
        $paise  = (int)round(($amount - $rupees) * 100);
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
                 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        $twoDigits = function (int $n) use ($ones, $tens) {
            if ($n < 20) return $ones[$n];
            return trim($tens[(int)($n / 10)] . ' ' . $ones[$n % 10]);
        };
        $threeDigits = function (int $n) use ($ones, $twoDigits) {
            $r = '';
            if ($n >= 100) { $r .= $ones[(int)($n / 100)] . ' Hundred'; $n %= 100; if ($n) $r .= ' '; }
            if ($n) $r .= $twoDigits($n);
            return $r;
        };
        if ($rupees === 0 && $paise === 0) return 'Zero only';
        $words = '';
        if ($rupees >= 10000000) { $words .= $threeDigits((int)($rupees / 10000000)) . ' Crore '; $rupees %= 10000000; }
        if ($rupees >= 100000)   { $words .= $threeDigits((int)($rupees / 100000))   . ' Lakh ';  $rupees %= 100000; }
        if ($rupees >= 1000)     { $words .= $threeDigits((int)($rupees / 1000))     . ' Thousand '; $rupees %= 1000; }
        if ($rupees > 0)         { $words .= $threeDigits($rupees); }
        $words = trim($words);
        if ($paise > 0) {
            $words .= ($words === '' ? '' : ' Rupees and ') . $twoDigits($paise) . ' Paise';
        }
        return $words . ' only';
    }
}
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(($title ?? ('Invoice ' . (string)($order['order_no'] ?? '')))) ?></title>
  <style>
    *, *::before, *::after { box-sizing: border-box; }
    body {
      font-family: Arial, Helvetica, "Liberation Sans", sans-serif;
      color: #0F1111; background: #FFFFFF;
      margin: 0; padding: 0;
      font-size: 12px;
    }
    .inv-wrap { max-width: 920px; margin: 0 auto; padding: 24px 28px; background: #FFFFFF; }
    .inv-head {
      display: flex; align-items: flex-start; justify-content: space-between;
      gap: 16px; margin-bottom: 20px;
    }
    .inv-brand { display: flex; align-items: center; gap: 10px; }
    .inv-brand img { height: 42px; width: auto; object-fit: contain; }
    .inv-brand-name { font-weight: 700; font-size: 1rem; }
    .inv-title { text-align: right; }
    .inv-title h1 { font-size: 18px; font-weight: 700; margin: 0; }
    .inv-title-sub { font-size: 12px; color: #565959; margin-top: 2px; }
    .inv-row { display: flex; gap: 24px; margin-bottom: 12px; }
    .inv-col { flex: 1 1 0; min-width: 0; }
    .inv-col-r { text-align: right; }
    .inv-block-label { font-weight: 700; margin-bottom: 4px; font-size: 12px; }
    .inv-block { font-size: 12px; line-height: 1.5; color: #0F1111; }
    .inv-block .muted { color: #565959; }
    .inv-meta-line { margin: 1px 0; }
    .inv-meta-line strong { font-weight: 700; }
    .inv-orderline { display: flex; justify-content: space-between; gap: 24px; margin: 14px 0 8px; font-size: 12px; }
    .inv-orderline .key { font-weight: 700; }
    table.inv-items { width: 100%; border-collapse: collapse; margin-top: 14px; font-size: 11.5px; }
    table.inv-items th, table.inv-items td {
      border: 1px solid #5C5C5C; padding: 6px 8px; vertical-align: top;
    }
    table.inv-items thead th { background: #F0F2F2; font-weight: 700; text-align: left; }
    table.inv-items .num { text-align: right; white-space: nowrap; }
    table.inv-items tfoot td { font-weight: 700; }
    .inv-amount-words {
      border-left: 1px solid #5C5C5C; border-right: 1px solid #5C5C5C;
      border-bottom: 1px solid #5C5C5C;
      padding: 8px; font-size: 12px;
    }
    .inv-amount-words .label { font-weight: 700; margin-bottom: 2px; }
    .inv-sig {
      border-left: 1px solid #5C5C5C; border-right: 1px solid #5C5C5C;
      border-bottom: 1px solid #5C5C5C;
      padding: 12px 8px; text-align: right; font-size: 12px;
    }
    .inv-sig .for { font-weight: 700; margin-bottom: 36px; }
    .inv-sig .auth { font-weight: 700; }
    .inv-note { font-size: 10.5px; color: #565959; margin: 10px 0; }
    table.inv-payment { width: 100%; border-collapse: collapse; margin-top: 14px; font-size: 11.5px; }
    table.inv-payment th, table.inv-payment td {
      border: 1px solid #5C5C5C; padding: 6px 8px; vertical-align: top;
    }
    table.inv-payment th { background: #F0F2F2; font-weight: 700; text-align: left; }
    .inv-footer { margin-top: 28px; text-align: center; font-size: 9.5px; color: #6B7178; line-height: 1.5; }
    .actions {
      display: flex; justify-content: space-between; align-items: center;
      max-width: 920px; margin: 0 auto; padding: 14px 28px 0;
    }
    .actions a, .actions button {
      background: #F0F2F2; color: #0F1111; border: 1px solid #888C8C;
      border-radius: 6px; padding: 6px 14px; font-size: 12px;
      text-decoration: none; cursor: pointer;
    }
    .actions a:hover, .actions button:hover { background: #E3E6E6; }
    .actions .primary { background: #FFD814; border-color: #FCD200; }
    .actions .primary:hover { background: #F7CA00; }
    @media print {
      .actions { display: none !important; }
      body { background: #FFF !important; }
      .inv-wrap { padding: 0; max-width: 100%; }
    }
  </style>
</head>
<body>

<div class="actions no-print">
  <a href="<?= e($backHref ?? ($fullBase . '/?p=home')) ?>">← Back</a>
  <button class="primary" onclick="window.print()">Print / Save as PDF</button>
</div>

<div class="inv-wrap">

  <div class="inv-head">
    <div class="inv-brand">
      <?php if (!empty($company['business_logo_path'])): ?>
        <img src="<?= e((string)$company['business_logo_path']) ?>" alt="Logo">
      <?php endif; ?>
      <div class="inv-brand-name"><?= e((string)($company['business_name'] ?? 'Manikya Market')) ?></div>
    </div>
    <div class="inv-title">
      <h1>Tax Invoice/Bill of Supply/Cash Memo</h1>
      <div class="inv-title-sub">(Original for Recipient)</div>
    </div>
  </div>

  <div class="inv-row">
    <div class="inv-col">
      <div class="inv-block-label">Sold By :</div>
      <div class="inv-block">
        <div><?= e((string)($company['business_name'] ?? '')) ?></div>
        <div class="muted"><?= nl2br(e((string)($company['business_address'] ?? ''))) ?></div>
        <div class="muted">
          <?= e((string)($company['business_state'] ?? '')) ?>
          <?= !empty($company['business_pincode']) ? ' – ' . e((string)$company['business_pincode']) : '' ?>
        </div>
      </div>
    </div>
    <div class="inv-col inv-col-r">
      <div class="inv-block-label">Billing Address :</div>
      <div class="inv-block">
        <div><?= e((string)$order['buyer_name']) ?></div>
        <?php if (is_array($address)): ?>
          <div class="muted">
            <?= e((string)($address['address_line1'] ?? '')) ?><?= !empty($address['address_line2']) ? ', ' . e((string)$address['address_line2']) : '' ?>
          </div>
          <div class="muted">
            <?= e(strtoupper((string)($address['city'] ?? ''))) ?>,
            <?= e(strtoupper((string)($address['state'] ?? ''))) ?>,
            <?= e((string)($address['pincode'] ?? '')) ?>
          </div>
          <div class="muted">IN</div>
          <?php if ($inv_buyerStateCode !== ''): ?>
            <div class="inv-meta-line"><strong>State/UT Code:</strong> <?= e($inv_buyerStateCode) ?></div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="inv-row">
    <div class="inv-col">
      <div class="inv-block">
        <?php if (!empty($company['business_pan'])): ?>
          <div class="inv-meta-line"><strong>PAN No:</strong> <?= e((string)$company['business_pan']) ?></div>
        <?php endif; ?>
        <?php if (!empty($company['business_gstin'])): ?>
          <div class="inv-meta-line"><strong>GST Registration No:</strong> <?= e((string)$company['business_gstin']) ?></div>
        <?php endif; ?>
        <?php if (!empty($company['business_cin'])): ?>
          <div class="inv-meta-line"><strong>CIN No:</strong> <?= e((string)$company['business_cin']) ?></div>
        <?php endif; ?>
      </div>
    </div>
    <div class="inv-col inv-col-r">
      <div class="inv-block-label">Shipping Address :</div>
      <div class="inv-block">
        <div><?= e((string)$order['buyer_name']) ?></div>
        <?php if (is_array($address)): ?>
          <div class="muted">
            <?= e((string)($address['address_line1'] ?? '')) ?><?= !empty($address['address_line2']) ? ', ' . e((string)$address['address_line2']) : '' ?>
          </div>
          <div class="muted">
            <?= e(strtoupper((string)($address['city'] ?? ''))) ?>,
            <?= e(strtoupper((string)($address['state'] ?? ''))) ?>,
            <?= e((string)($address['pincode'] ?? '')) ?>
          </div>
          <div class="muted">IN</div>
          <?php if ($inv_buyerStateCode !== ''): ?>
            <div class="inv-meta-line"><strong>State/UT Code:</strong> <?= e($inv_buyerStateCode) ?></div>
          <?php endif; ?>
          <div class="inv-meta-line"><strong>Place of supply:</strong> <?= e(strtoupper((string)($address['state'] ?? ''))) ?></div>
          <div class="inv-meta-line"><strong>Place of delivery:</strong> <?= e(strtoupper((string)($address['state'] ?? ''))) ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="inv-orderline">
    <div>
      <div><span class="key">Order Number:</span> <?= e((string)$order['order_no']) ?></div>
      <div><span class="key">Order Date:</span> <?= e(date('d.m.Y', strtotime((string)$order['created_at']))) ?></div>
    </div>
    <div class="inv-col-r">
      <div><span class="key">Invoice Number :</span> <?= e('INV-' . str_pad((string)(int)$id, 6, '0', STR_PAD_LEFT)) ?></div>
      <div><span class="key">Invoice Date :</span> <?= e(date('d.m.Y', strtotime((string)($order['invoice_generated_at'] ?? $order['created_at'])))) ?></div>
    </div>
  </div>

  <table class="inv-items">
    <thead>
      <tr>
        <th style="width: 28px;">Sl. No</th>
        <th>Description</th>
        <th class="num" style="width: 72px;">Unit Price</th>
        <th class="num" style="width: 36px;">Qty</th>
        <th class="num" style="width: 84px;">Net Amount</th>
        <th class="num" style="width: 56px;">Tax Rate</th>
        <th style="width: 56px;">Tax Type</th>
        <th class="num" style="width: 70px;">Tax Amount</th>
        <th class="num" style="width: 84px;">Total Amount</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $inv_totalTax = 0;
      $inv_totalAmount = 0;
      $inv_sl = 0;
      foreach ($items as $it):
          if (!is_array($it)) continue;
          $inv_sl++;
          $qty = (float)$it['qty_kg'];
          $lineGross = (float)$it['line_total'];
          $lineNet   = round($lineGross / $inv_gstFactor, 2);
          $unitNet   = $qty > 0 ? round($lineNet / $qty, 2) : 0;
          $taxAmt    = round($lineGross - $lineNet, 2);
          $inv_totalTax    += $taxAmt;
          $inv_totalAmount += $lineGross;
          $unitLabel = product_unit_label($it['unit'] ?? 'kg');
          $rowSpan   = $inv_sameState ? 2 : 1;
      ?>
        <tr>
          <td rowspan="<?= $rowSpan ?>"><?= $inv_sl ?></td>
          <td rowspan="<?= $rowSpan ?>">
            <?= e((string)$it['name']) ?>
            <?php if (!empty($it['part_code'])): ?> | <?= e((string)$it['part_code']) ?><?php endif; ?>
            <?php if (!empty($it['product_type'])): ?><br><span class="muted"><?= e((string)$it['product_type']) ?></span><?php endif; ?>
            <br><span class="muted">Unit: <?= e($unitLabel) ?></span>
          </td>
          <td class="num" rowspan="<?= $rowSpan ?>">₹<?= number_format($unitNet, 2) ?></td>
          <td class="num" rowspan="<?= $rowSpan ?>"><?= rtrim(rtrim(number_format($qty, 2), '0'), '.') ?></td>
          <td class="num" rowspan="<?= $rowSpan ?>">₹<?= number_format($lineNet, 2) ?></td>
          <?php if ($inv_sameState): ?>
            <td class="num"><?= e(rtrim(rtrim(number_format($gstRate / 2, 2), '0'), '.')) ?>%</td>
            <td>CGST</td>
            <td class="num">₹<?= number_format($taxAmt / 2, 2) ?></td>
            <td class="num" rowspan="<?= $rowSpan ?>">₹<?= number_format($lineGross, 2) ?></td>
          <?php else: ?>
            <td class="num"><?= e(rtrim(rtrim(number_format($gstRate, 2), '0'), '.')) ?>%</td>
            <td>IGST</td>
            <td class="num">₹<?= number_format($taxAmt, 2) ?></td>
            <td class="num">₹<?= number_format($lineGross, 2) ?></td>
          <?php endif; ?>
        </tr>
        <?php if ($inv_sameState): ?>
          <tr>
            <td class="num"><?= e(rtrim(rtrim(number_format($gstRate / 2, 2), '0'), '.')) ?>%</td>
            <td>SGST</td>
            <td class="num">₹<?= number_format($taxAmt / 2, 2) ?></td>
          </tr>
        <?php endif; ?>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="7">TOTAL:</td>
        <td class="num">₹<?= number_format($inv_totalTax, 2) ?></td>
        <td class="num">₹<?= number_format($inv_totalAmount, 2) ?></td>
      </tr>
    </tfoot>
  </table>

  <div class="inv-amount-words">
    <div class="label">Amount in Words:</div>
    <div><?= e(numberToIndianWords((float)$inv_totalAmount)) ?></div>
  </div>

  <div class="inv-sig">
    <div class="for">For <?= e((string)($company['business_name'] ?? '')) ?>:</div>
    <div class="auth">Authorized Signatory</div>
  </div>

  <div class="inv-note">Whether tax is payable under reverse charge - No</div>

  <table class="inv-payment">
    <thead>
      <tr>
        <th>Payment Transaction ID:</th>
        <th>Date &amp; Time:</th>
        <th>Invoice Value:</th>
        <th>Mode of Payment:</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><?= e((string)($payment['razorpay_payment_id'] ?? (($payment['provider'] ?? '') === 'cod' ? 'COD' : '—'))) ?></td>
        <td><?= e(!empty($payment['created_at']) ? date('d/m/Y, H:i:s', strtotime((string)$payment['created_at'])) . ' hrs' : '—') ?></td>
        <td><?= number_format((float)$inv_totalAmount, 2) ?></td>
        <td><?= e(($payment['provider'] ?? '') === 'cod' ? 'Cash on Delivery (COD)' : (($payment['provider'] ?? '') === 'razorpay' ? 'UPI / Card / NetBanking (Razorpay)' : '—')) ?></td>
      </tr>
    </tbody>
  </table>

  <div class="inv-footer">
    Please note that this invoice is not a demand for payment<br>
    Customers desirous of availing input GST credit are requested to provide their GSTIN at the time of order.<br>
    <br>
    Page 1 of 1
  </div>
</div>

</body>
</html>
