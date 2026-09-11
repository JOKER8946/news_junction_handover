<?php

declare(strict_types=1);

auth_require_role('buyer');

$buyerId = auth_user_id();

// Edit mode: load existing record
$editId = isset($_GET['edit']) && is_numeric($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editing = null;
if ($editId > 0) {
    $editing = db_fetch_one($db, 'SELECT * FROM buyer_addresses WHERE id = :id AND buyer_id = :bid LIMIT 1', ['id' => $editId, 'bid' => $buyerId]);
    if (!$editing) {
        $editId = 0;
    }
}

$title = $editing ? 'Edit Address' : 'Add a new address';

// Set default via query (legacy)
$setDefaultId = (int)($_GET['set_default'] ?? 0);
if ($setDefaultId > 0) {
    db_exec($db, 'UPDATE buyer_addresses SET is_default = 0 WHERE buyer_id = :buyer_id', ['buyer_id' => $buyerId]);
    db_exec($db, 'UPDATE buyer_addresses SET is_default = 1 WHERE buyer_id = :buyer_id AND id = :id', ['buyer_id' => $buyerId, 'id' => $setDefaultId]);
    redirect_to('buyer/addresses');
}

if (request_method() === 'POST') {
    $label = post_string('label');
    $fullName = post_string('full_name');
    $phone = post_string('phone');
    $line1 = post_string('address_line1');
    $line2 = post_string('address_line2');
    $city = post_string('city');
    $state = post_string('state');
    $pincode = post_string('pincode');
    $isDefault = (int)($_POST['is_default'] ?? 0) === 1 ? 1 : 0;
    $editingId = (int)($_POST['edit_id'] ?? 0);

    if ($line1 === '' || $city === '' || $state === '' || $pincode === '') {
        flash_set('error', 'Please fill all required fields');
        if ($editingId > 0) {
            redirect_to('buyer/address-add&edit=' . $editingId);
        } else {
            redirect_to('buyer/address-add');
        }
    }

    if ($isDefault === 1) {
        db_exec($db, 'UPDATE buyer_addresses SET is_default = 0 WHERE buyer_id = :buyer_id', ['buyer_id' => $buyerId]);
    }

    if ($editingId > 0) {
        $existing = db_fetch_one($db, 'SELECT id FROM buyer_addresses WHERE id = :id AND buyer_id = :bid LIMIT 1', ['id' => $editingId, 'bid' => $buyerId]);
        if ($existing) {
            db_exec($db, 'UPDATE buyer_addresses SET label = :label, full_name = :full_name, phone = :phone, address_line1 = :line1, address_line2 = :line2, city = :city, state = :state, pincode = :pincode, is_default = :is_default WHERE id = :id', [
                'label' => $label, 'full_name' => $fullName, 'phone' => $phone,
                'line1' => $line1, 'line2' => $line2, 'city' => $city, 'state' => $state, 'pincode' => $pincode,
                'is_default' => $isDefault, 'id' => $editingId,
            ]);
            flash_set('success', 'Address updated.');
            redirect_to('buyer/addresses');
        }
    }

    db_exec($db, 'INSERT INTO buyer_addresses (buyer_id, label, full_name, phone, address_line1, address_line2, city, state, pincode, is_default, created_at) VALUES (:buyer_id, :label, :full_name, :phone, :line1, :line2, :city, :state, :pincode, :is_default, NOW())', [
        'buyer_id' => $buyerId,
        'label' => $label,
        'full_name' => $fullName,
        'phone' => $phone,
        'line1' => $line1,
        'line2' => $line2,
        'city' => $city,
        'state' => $state,
        'pincode' => $pincode,
        'is_default' => $isDefault,
    ]);

    flash_set('success', 'Address saved.');
    redirect_to('buyer/addresses');
}

$content = function () use ($editing, $editId) {
    $error = flash_get('error');

    $val = function (string $key, string $fallback = '') use ($editing): string {
        return $editing ? (string)($editing[$key] ?? $fallback) : $fallback;
    };
    $isDefaultChecked = $editing && (int)($editing['is_default'] ?? 0) === 1;

    $states = [
        'Andhra Pradesh','Arunachal Pradesh','Assam','Bihar','Chhattisgarh','Goa','Gujarat','Haryana',
        'Himachal Pradesh','Jharkhand','Karnataka','Kerala','Madhya Pradesh','Maharashtra','Manipur',
        'Meghalaya','Mizoram','Nagaland','Odisha','Punjab','Rajasthan','Sikkim','Tamil Nadu','Telangana',
        'Tripura','Uttar Pradesh','Uttarakhand','West Bengal',
        'Andaman and Nicobar Islands','Chandigarh','Dadra and Nagar Haveli and Daman and Diu','Delhi',
        'Jammu and Kashmir','Ladakh','Lakshadweep','Puducherry',
    ];
?>
  <style>
    .amz-form {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      color: #0F1111;
      max-width: 600px;
      margin: 0 auto;
      padding: 24px 16px;
    }
    .amz-form-breadcrumb {
      font-size: .85rem;
      color: #565959;
      margin-bottom: 8px;
    }
    .amz-form-breadcrumb a { color: #007185; text-decoration: none; }
    .amz-form-breadcrumb a:hover { color: #C7511F; text-decoration: underline; }
    .amz-form h1 {
      font-size: 1.6rem;
      font-weight: 500;
      color: #0F1111;
      margin: 0 0 22px;
    }
    .amz-form-group { margin-bottom: 16px; }
    .amz-form-label {
      display: block;
      font-size: .92rem;
      font-weight: 700;
      color: #0F1111;
      margin-bottom: 4px;
    }
    .amz-form-input,
    .amz-form-select {
      width: 100%;
      padding: 8px 10px;
      border: 1px solid #888C8C;
      border-radius: 4px;
      font-size: .95rem;
      background: linear-gradient(180deg, #F7F8F8 0%, #FFFFFF 100%);
      color: #0F1111;
      outline: none;
      transition: border-color .15s, box-shadow .15s;
      font-family: inherit;
    }
    .amz-form-input:focus,
    .amz-form-select:focus {
      border-color: #E77600;
      box-shadow: 0 0 3px 2px rgba(228, 121, 17, 0.5);
    }
    .amz-form-help {
      color: #565959;
      font-size: .82rem;
      margin-top: 4px;
    }
    .amz-form-help.error { color: #C40000; }
    .amz-form-help.ok { color: #007600; }

    .amz-form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }
    @media (max-width: 575px) { .amz-form-row { grid-template-columns: 1fr; } }

    .amz-form-check {
      display: flex;
      align-items: center;
      gap: 8px;
      margin: 18px 0;
      font-size: .92rem;
      color: #0F1111;
    }
    .amz-form-check input { accent-color: #E77600; }

    .amz-btn-primary {
      display: inline-block;
      padding: 9px 22px;
      background: linear-gradient(180deg, #FFD814 0%, #F7CA00 100%);
      border: 1px solid #FCD200;
      border-radius: 8px;
      color: #0F1111;
      font-size: .92rem;
      font-weight: 500;
      text-decoration: none;
      cursor: pointer;
      transition: background .15s;
    }
    .amz-btn-primary:hover {
      background: linear-gradient(180deg, #F7CA00 0%, #F2C200 100%);
      color: #0F1111;
    }
    .amz-btn-cancel {
      display: inline-block;
      margin-left: 12px;
      color: #007185;
      font-size: .9rem;
      text-decoration: none;
    }
    .amz-btn-cancel:hover { color: #C7511F; text-decoration: underline; }

    .amz-flash {
      padding: 12px 16px;
      border-radius: 4px;
      margin-bottom: 16px;
      font-size: .9rem;
    }
    .amz-flash-danger { background: #FBE7E7; border: 1px solid #D5959B; color: #C40000; }
  </style>

  <div class="amz-form">
    <div class="amz-form-breadcrumb">
      <a href="?p=buyer/addresses">Your Addresses</a> ›
      <?= $editing ? 'Edit address' : 'Add a new address' ?>
    </div>
    <h1><?= $editing ? 'Edit address' : 'Add a new address' ?></h1>

    <?php if ($error): ?>
      <div class="amz-flash amz-flash-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="on">
      <?php if ($editId > 0): ?>
        <input type="hidden" name="edit_id" value="<?= (int)$editId ?>">
      <?php endif; ?>

      <div class="amz-form-group">
        <label class="amz-form-label" for="amz-country">Country/Region</label>
        <select id="amz-country" class="amz-form-select" disabled>
          <option>India</option>
        </select>
      </div>

      <div class="amz-form-group">
        <label class="amz-form-label" for="amz-name">Full name (First and Last name)</label>
        <input class="amz-form-input" id="amz-name" type="text" name="full_name" value="<?= e($val('full_name')) ?>" autocomplete="name">
      </div>

      <div class="amz-form-group">
        <label class="amz-form-label" for="amz-phone">Mobile number</label>
        <input class="amz-form-input" id="amz-phone" type="tel" name="phone" value="<?= e($val('phone')) ?>" autocomplete="tel">
        <div class="amz-form-help">May be used to assist delivery</div>
      </div>

      <div class="amz-form-group">
        <label class="amz-form-label" for="amz-pin">Pincode</label>
        <input class="amz-form-input" id="amz-pin" type="text" name="pincode" placeholder="6 digits [0-9] PIN code" inputmode="numeric" maxlength="6" pattern="\d{6}" value="<?= e($val('pincode')) ?>" required>
        <div class="amz-form-help" id="amz-pin-hint" style="min-height: 1.1em;"></div>
      </div>

      <div class="amz-form-group">
        <label class="amz-form-label" for="amz-line1">Flat, House no., Building, Company, Apartment</label>
        <input class="amz-form-input" id="amz-line1" type="text" name="address_line1" value="<?= e($val('address_line1')) ?>" required autocomplete="address-line1">
      </div>

      <div class="amz-form-group">
        <label class="amz-form-label" for="amz-line2">Area, Street, Sector, Village</label>
        <input class="amz-form-input" id="amz-line2" type="text" name="address_line2" value="<?= e($val('address_line2')) ?>" autocomplete="address-line2">
      </div>

      <div class="amz-form-group">
        <label class="amz-form-label" for="amz-label">Landmark</label>
        <input class="amz-form-input" id="amz-label" type="text" name="label" placeholder="E.g. near apollo hospital" value="<?= e($val('label')) ?>">
      </div>

      <div class="amz-form-row">
        <div class="amz-form-group">
          <label class="amz-form-label" for="amz-city">Town/City</label>
          <input class="amz-form-input" id="amz-city" type="text" name="city" value="<?= e($val('city')) ?>" required autocomplete="address-level2">
        </div>
        <div class="amz-form-group">
          <label class="amz-form-label" for="amz-state">State</label>
          <select class="amz-form-select" id="amz-state" name="state" required>
            <option value="">Choose a state</option>
            <?php $currentState = $val('state'); foreach ($states as $st): ?>
              <option value="<?= e($st) ?>" <?= ($currentState === $st) ? 'selected' : '' ?>><?= e($st) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="amz-form-check">
        <input type="checkbox" id="amz-default" name="is_default" value="1" <?= $isDefaultChecked ? 'checked' : '' ?>>
        <label for="amz-default">Make this my default address</label>
      </div>

      <div>
        <button class="amz-btn-primary" type="submit"><?= $editing ? 'Save changes' : 'Add address' ?></button>
        <a class="amz-btn-cancel" href="?p=buyer/addresses">Cancel</a>
      </div>
    </form>

    <script>
      (function () {
        var pin = document.getElementById('amz-pin');
        var city = document.getElementById('amz-city');
        var stateSel = document.getElementById('amz-state');
        var hint = document.getElementById('amz-pin-hint');
        if (!pin) return;

        var inflight = null;

        function lookup() {
          var v = (pin.value || '').replace(/\D/g, '');
          if (v.length !== 6) {
            hint.textContent = '';
            hint.classList.remove('error', 'ok');
            return;
          }
          if (inflight) inflight.abort();
          inflight = new AbortController();
          hint.textContent = 'Looking up...';
          hint.classList.remove('error', 'ok');
          fetch('?p=api/pincode&pin=' + v, { signal: inflight.signal, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              if (!data.ok) {
                hint.textContent = data.error || 'Pincode not found';
                hint.classList.add('error');
                return;
              }
              if (!city.value && data.city) city.value = data.city;
              if (stateSel && !stateSel.value && data.state) {
                for (var i = 0; i < stateSel.options.length; i++) {
                  if (stateSel.options[i].value === data.state) { stateSel.selectedIndex = i; break; }
                }
              }
              if (data.serviceable === false) {
                hint.textContent = 'Sorry, delivery not available to this pincode.';
                hint.classList.add('error');
              } else {
                hint.textContent = data.city + ', ' + data.state + (data.serviceable === true ? ' — deliverable' : '');
                hint.classList.add('ok');
              }
            })
            .catch(function () { /* silent */ });
        }

        pin.addEventListener('input', function () {
          pin.value = (pin.value || '').replace(/\D/g, '').slice(0, 6);
          if (pin.value.length === 6) lookup();
        });
        pin.addEventListener('blur', lookup);
      })();
    </script>
  </div>
<?php
};

require __DIR__ . '/../../views/layout.php';
