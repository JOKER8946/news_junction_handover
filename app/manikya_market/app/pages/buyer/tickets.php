<?php

declare(strict_types=1);

auth_require_role('buyer');

$title = 'Support Tickets';
$buyerId = auth_user_id();

if (request_method() === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_ticket') {
    $subject = post_string('subject');
    $message = post_string('message');
    $orderId = (int)($_POST['order_id'] ?? 0) ?: null;

    if (!$subject || !$message) {
        flash_set('error', 'Subject and message are required');
        redirect_to('buyer/tickets');
    }

    $imagePath = null;
    if (!empty($_FILES['attachment']['name']) && (int)$_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        $mime = function_exists('mime_content_type') ? (mime_content_type($_FILES['attachment']['tmp_name']) ?: '') : '';
        if (!isset($allowed[$mime])) {
            flash_set('error', 'Attachment must be an image (JPG, PNG, WEBP or GIF).');
            redirect_to('buyer/tickets');
        }
        if ((int)$_FILES['attachment']['size'] > 5 * 1024 * 1024) {
            flash_set('error', 'Attachment is too large. Maximum size is 5 MB.');
            redirect_to('buyer/tickets');
        }
        $ext = $allowed[$mime];
        $uploadDir = __DIR__ . '/../../uploads/tickets';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }
        $fname = 'ticket-buyer-' . $buyerId . '-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $uploadDir . '/' . $fname;
        if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $dest)) {
            flash_set('error', 'Could not save attachment. Please try again.');
            redirect_to('buyer/tickets');
        }
        $imagePath = 'uploads/tickets/' . $fname;
    } elseif (!empty($_FILES['attachment']['name']) && (int)$_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
        flash_set('error', 'Attachment failed to upload. Please try again.');
        redirect_to('buyer/tickets');
    }

    db_exec($db, 'INSERT INTO tickets (order_id, buyer_id, subject, status, created_at, updated_at) VALUES (:order_id, :buyer_id, :subject, :status, NOW(), NOW())', [
        'order_id' => $orderId,
        'buyer_id' => $buyerId,
        'subject' => $subject,
        'status' => 'open',
    ]);

    $ticketId = (int)$db->lastInsertId();
    db_exec($db, 'INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, message, image_path, created_at) VALUES (:ticket_id, :sender_type, :sender_id, :message, :image_path, NOW())', [
        'ticket_id' => $ticketId,
        'sender_type' => 'buyer',
        'sender_id' => $buyerId,
        'message' => $message,
        'image_path' => $imagePath,
    ]);

    flash_set('success', 'Ticket created successfully');
    redirect_to('buyer/ticket-detail?id=' . $ticketId);
}

$tickets = db_fetch_all($db, '
    SELECT t.id, t.subject, t.status, t.created_at, t.updated_at, t.order_id,
           o.order_no,
           (SELECT COUNT(*) FROM ticket_messages tm WHERE tm.ticket_id = t.id) AS msg_count,
           (SELECT tm2.message FROM ticket_messages tm2 WHERE tm2.ticket_id = t.id ORDER BY tm2.created_at DESC LIMIT 1) AS last_message,
           (SELECT tm3.sender_type FROM ticket_messages tm3 WHERE tm3.ticket_id = t.id ORDER BY tm3.created_at DESC LIMIT 1) AS last_sender
    FROM tickets t
    LEFT JOIN orders o ON o.id = t.order_id
    WHERE t.buyer_id = :buyer_id
    ORDER BY t.updated_at DESC
', ['buyer_id' => $buyerId]);

$stats = ['total' => 0, 'open' => 0, 'in_progress' => 0, 'closed' => 0];
foreach ($tickets as $tk) {
    $stats['total']++;
    $st = strtolower((string)$tk['status']);
    if (isset($stats[$st])) {
        $stats[$st]++;
    }
}

$buyerOrders = db_fetch_all($db, 'SELECT id, order_no FROM orders WHERE buyer_id = :buyer_id ORDER BY created_at DESC', ['buyer_id' => $buyerId]);

$tk_status_meta = function (string $status): array {
    $s = strtolower($status);
    if ($s === 'open') {
        return ['label' => 'Open', 'class' => 'tk-st-open', 'icon' => 'circle-dot'];
    }
    if ($s === 'in_progress' || $s === 'pending') {
        return ['label' => 'In Progress', 'class' => 'tk-st-progress', 'icon' => 'clock'];
    }
    if ($s === 'closed' || $s === 'resolved') {
        return ['label' => 'Closed', 'class' => 'tk-st-closed', 'icon' => 'check-circle-2'];
    }
    return ['label' => ucwords(str_replace('_', ' ', $status)), 'class' => 'tk-st-open', 'icon' => 'circle-dot'];
};

$tk_time_ago = function (string $datetime): string {
    $time = strtotime($datetime);
    if ($time === false) {
        return $datetime;
    }
    $diff = time() - $time;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) {
        $m = (int)floor($diff / 60);
        return $m . ' min' . ($m === 1 ? '' : 's') . ' ago';
    }
    if ($diff < 86400) {
        $h = (int)floor($diff / 3600);
        return $h . ' hour' . ($h === 1 ? '' : 's') . ' ago';
    }
    if ($diff < 604800) {
        $d = (int)floor($diff / 86400);
        return $d . ' day' . ($d === 1 ? '' : 's') . ' ago';
    }
    return date('M d, Y', $time);
};

$content = function () use ($tickets, $buyerOrders, $stats, $tk_status_meta, $tk_time_ago) {
    $success = flash_get('success');
    $error = flash_get('error');
    ?>
    <style>
        /* ── Amazon-style palette: white cards, teal links, yellow CTAs ── */
        .km-tickets { max-width: 1240px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; color: #0F1111; }

        /* Page header — clean white with title + actions, no gradient */
        .tk-header-card {
            display: flex; align-items: center; justify-content: space-between;
            gap: 16px; flex-wrap: wrap;
            background: transparent;
            padding: 4px 0 14px;
            margin-bottom: 14px;
            border-bottom: 1px solid #D5D9D9;
        }
        .tk-header-card h1 {
            font-size: 1.7rem; font-weight: 500; color: #0F1111; margin: 0;
        }
        .tk-header-card .lead-sub { color: #565959; font-size: .92rem; margin-top: 2px; }
        .tk-header-card .btn-light {
            background: #F0F2F2; color: #0F1111; border: 1px solid #888C8C;
            border-radius: 8px; padding: 7px 14px; font-size: .88rem; font-weight: 500;
            box-shadow: 0 1px 0 rgba(0,0,0,.04);
        }
        .tk-header-card .btn-light:hover { background: #E3E6E6; color: #0F1111; }
        .tk-header-card .btn-light[data-bs-target] {
            background: linear-gradient(180deg, #FFD814 0%, #F7CA00 100%);
            border-color: #FCD200; color: #0F1111;
        }
        .tk-header-card .btn-light[data-bs-target]:hover {
            background: linear-gradient(180deg, #F7CA00 0%, #F2C200 100%);
            border-color: #F2C200;
        }

        /* KPI cards — flat white, light gray border, soft shadow */
        .tk-stat-card {
            background: #FFFFFF;
            border: 1px solid #D5D9D9;
            border-radius: 8px;
            padding: 14px 16px;
            display: flex; align-items: center; gap: 12px;
            transition: box-shadow .15s, border-color .15s;
            height: 100%;
            box-shadow: 0 1px 2px rgba(15,17,17,.04);
        }
        .tk-stat-card:hover { box-shadow: 0 2px 6px rgba(15,17,17,.08); border-color: #B7BABA; }
        .tk-stat-icon {
            width: 38px; height: 38px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
            background: #F0F2F2; color: #565959;
        }
        .tk-stat-icon i { width: 18px; height: 18px; }
        .tk-stat-icon.icon-total    { background: #F0F2F2; color: #232F3E; }
        .tk-stat-icon.icon-open     { background: #E6F4EA; color: #067D62; }
        .tk-stat-icon.icon-progress { background: #FFF4D6; color: #B45309; }
        .tk-stat-icon.icon-closed   { background: #EAEDED; color: #565959; }
        .tk-stat-value { font-size: 1.4rem; font-weight: 700; line-height: 1.1; color: #0F1111; }
        .tk-stat-label { font-size: .72rem; color: #565959; text-transform: uppercase; letter-spacing: .04em; font-weight: 600; }

        /* Search + filter row — Amazon-style search with dark navy button */
        .tk-toolbar {
            background: transparent;
            border: 0;
            padding: 0;
            margin-bottom: 14px;
            display: flex; gap: 16px; align-items: stretch; flex-wrap: wrap;
        }
        .tk-toolbar .tk-search-wrap {
            flex: 1 1 360px; display: flex; max-width: 540px;
            border: 1px solid #888C8C; border-radius: 8px; overflow: hidden;
            background: #FFFFFF;
        }
        .tk-toolbar .input-group-text {
            background: #F0F2F2; border: 0; border-right: 1px solid #888C8C;
            color: #565959; padding: 0 12px;
        }
        .tk-toolbar .form-control {
            border: 0; outline: 0; padding: 9px 12px; font-size: .92rem;
            background: transparent; color: #0F1111;
        }
        .tk-toolbar .form-control:focus { box-shadow: none; }
        .tk-toolbar .tk-search-wrap:focus-within {
            border-color: #007185;
            box-shadow: 0 0 3px 2px rgba(228,121,17,.4);
        }

        /* Tabs — underline-style like My Orders */
        .tk-filter-btns {
            display: inline-flex; gap: 0;
            background: transparent;
            border-bottom: 1px solid transparent;
        }
        .tk-filter-btns .btn-check { display: none !important; }
        .tk-filter-btns label.btn {
            background: transparent !important;
            border: 0 !important;
            border-bottom: 3px solid transparent !important;
            border-radius: 0 !important;
            color: #007185;
            font-size: .92rem; font-weight: 400;
            padding: 8px 14px;
            transition: color .15s, border-color .15s;
        }
        .tk-filter-btns label.btn:hover { color: #C7511F; text-decoration: underline; }
        .tk-filter-btns .btn-check:checked + label.btn {
            color: #0F1111 !important; font-weight: 700 !important;
            border-bottom-color: #C7511F !important;
            background: transparent !important;
            text-decoration: none;
        }
        .tk-filter-btns label.btn .badge {
            background: #F0F2F2 !important; color: #565959 !important;
            border: 1px solid #D5D9D9; font-weight: 500; font-size: .68rem;
        }
        .tk-filter-btns .btn-check:checked + label.btn .badge {
            background: #232F3E !important; color: #FFFFFF !important; border-color: #232F3E;
        }

        /* Ticket cards — white surface, teal subject link, gray border */
        .tk-card {
            display: block; background: #FFFFFF; border: 1px solid #D5D9D9;
            border-radius: 8px; padding: 16px 18px; color: inherit;
            transition: box-shadow .15s, border-color .15s, transform .15s;
            position: relative; overflow: hidden;
            box-shadow: 0 1px 2px rgba(15,17,17,.04);
            text-decoration: none;
        }
        .tk-card:hover {
            box-shadow: 0 4px 10px rgba(15,17,17,.08);
            border-color: #B7BABA;
            color: inherit; text-decoration: none;
            transform: translateY(-1px);
        }
        .tk-card::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px;
            background: #D5D9D9; transition: background .15s;
        }
        .tk-card[data-status="open"]::before        { background: #067D62; }
        .tk-card[data-status="in_progress"]::before { background: #B45309; }
        .tk-card[data-status="closed"]::before      { background: #B7BABA; }
        .tk-card-grid { display: grid; grid-template-columns: auto 1fr auto; gap: 14px; align-items: start; }
        .tk-id-col { min-width: 56px; text-align: center; padding-top: 2px; }
        .tk-id {
            display: inline-block; background: #F0F2F2; border: 1px solid #D5D9D9;
            color: #565959; padding: 2px 8px; border-radius: 999px;
            font-size: .72rem; font-weight: 600;
        }
        .tk-dot {
            display: inline-block; width: 8px; height: 8px; border-radius: 50%;
            background: #C7511F; margin-left: 6px; vertical-align: middle;
            animation: tkPulse 1.6s infinite ease-in-out;
        }
        @keyframes tkPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(199,81,31,.55); }
            50%      { box-shadow: 0 0 0 6px rgba(199,81,31,0); }
        }
        .tk-subject {
            font-size: 1rem; font-weight: 500;
            color: #007185; margin: 0;
            transition: color .15s;
        }
        .tk-card:hover .tk-subject { color: #C7511F; }
        .tk-badge {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: .7rem; font-weight: 600; padding: 3px 8px;
            border-radius: 999px; line-height: 1;
        }
        .tk-badge i { width: 12px; height: 12px; }
        .tk-st-open     { background: #E6F4EA; color: #067D62; }
        .tk-st-progress { background: #FFF4D6; color: #B45309; }
        .tk-st-closed   { background: #EAEDED; color: #565959; }
        .tk-pill {
            display: inline-flex; align-items: center; gap: 4px;
            background: #F0F2F2; color: #565959; border: 1px solid #D5D9D9;
            font-size: .7rem; font-weight: 500; padding: 2px 8px;
            border-radius: 999px; line-height: 1.2;
        }
        .tk-pill i { width: 12px; height: 12px; }
        .tk-preview {
            color: #565959; font-size: .88rem; margin-top: 6px;
            display: -webkit-box; -webkit-line-clamp: 1; line-clamp: 1; -webkit-box-orient: vertical;
            overflow: hidden; text-overflow: ellipsis;
        }
        .tk-preview strong { color: #0F1111; font-weight: 600; }
        .tk-preview strong.from-support { color: #067D62; }
        .tk-meta {
            display: flex; flex-wrap: wrap; gap: 14px; margin-top: 8px;
            color: #565959; font-size: .76rem;
        }
        .tk-meta span { display: inline-flex; align-items: center; gap: 4px; }
        .tk-meta i { width: 12px; height: 12px; }
        .tk-arrow { color: #007185; align-self: center; }
        .tk-arrow i { width: 20px; height: 20px; }

        /* Empty states */
        .tk-empty {
            background: #FFFFFF; border: 1px solid #D5D9D9; border-radius: 8px;
            padding: 44px 20px; text-align: center;
            box-shadow: 0 1px 2px rgba(15,17,17,.04);
        }
        .tk-empty h5 { color: #0F1111; font-weight: 500; }
        .tk-empty .empty-icon {
            width: 60px; height: 60px; border-radius: 50%;
            background: #F0F2F2; color: #565959;
            display: inline-flex; align-items: center; justify-content: center;
            margin-bottom: 12px;
        }
        .tk-empty .empty-icon i { width: 26px; height: 26px; }
        .tk-empty .btn-mm {
            background: linear-gradient(180deg, #FFD814 0%, #F7CA00 100%) !important;
            border: 1px solid #FCD200 !important; color: #0F1111 !important;
            border-radius: 100px; font-weight: 500; padding: 7px 18px;
        }

        /* Modal — clean white header */
        .modal-content { border-radius: 10px; border: 1px solid #D5D9D9; }
        .modal-header {
            background: #FFFFFF; color: #0F1111;
            border-bottom: 1px solid #D5D9D9;
            border-radius: 10px 10px 0 0;
            padding: 16px 20px;
        }
        .modal-header .modal-title {
            color: #0F1111; font-weight: 600; font-size: 1.1rem;
            display: flex; align-items: center; gap: 8px;
        }
        .modal-header .btn-close { filter: none; opacity: .55; }
        .modal-header .btn-close:hover { opacity: 1; }
        .modal-body label { color: #0F1111; font-weight: 500; }
        .modal-body .form-control, .modal-body .form-select {
            border-color: #888C8C; font-size: .92rem;
        }
        .modal-body .form-control:focus, .modal-body .form-select:focus {
            border-color: #007185; box-shadow: 0 0 3px 2px rgba(228,121,17,.4);
        }
        .modal-footer { border-top: 1px solid #D5D9D9; padding: 14px 20px; }
        .modal-footer .btn-mm {
            background: linear-gradient(180deg, #FFD814 0%, #F7CA00 100%);
            border: 1px solid #FCD200; color: #0F1111;
            border-radius: 100px; padding: 8px 22px; font-weight: 500;
        }
        .modal-footer .btn-mm:hover {
            background: linear-gradient(180deg, #F7CA00 0%, #F2C200 100%);
            border-color: #F2C200; color: #0F1111;
        }
        .modal-footer .btn-light {
            background: #F0F2F2; border: 1px solid #888C8C; color: #0F1111;
            border-radius: 8px;
        }

        /* Uploader — neutral gray dashed */
        .tk-uploader {
            border: 2px dashed #B7BABA; border-radius: 8px; background: #FAFAFA;
            padding: 14px; cursor: pointer; transition: all .15s ease;
            position: relative;
        }
        .tk-uploader:hover, .tk-uploader.dragover {
            background: #F0F2F2; border-color: #007185;
        }
        .tk-uploader.has-error { border-color: #B12704; background: #FCE9E5; }
        .tk-uploader-empty { display: flex; align-items: center; gap: 12px; pointer-events: none; }
        .tk-uploader-icon {
            width: 40px; height: 40px; border-radius: 8px;
            background: #F0F2F2; color: #565959;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .tk-uploader-icon i { width: 20px; height: 20px; }
        .tk-uploader-text { font-size: .9rem; color: #565959; }
        .tk-uploader-text strong { color: #007185; }
        .tk-uploader-preview { display: flex; align-items: center; gap: 12px; }
        .tk-uploader-preview img {
            width: 56px; height: 56px; object-fit: cover;
            border-radius: 6px; border: 1px solid #D5D9D9; flex-shrink: 0;
        }
        .tk-uploader-info { flex: 1; min-width: 0; }
        .tk-uploader-remove {
            border-radius: 6px; padding: 4px 8px;
            background: #FCE9E5; color: #B12704; border: 1px solid #F4C0B6;
        }
        .tk-uploader-remove:hover { background: #F8D0C7; color: #8C1F03; }

        /* Alerts — Amazon styling */
        .alert { border-radius: 6px; border-width: 1px; padding: 10px 14px; }
        .alert-success { background: #E6F4EA; border-color: #BCE3CC; color: #067D62; }
        .alert-danger  { background: #FCE9E5; border-color: #F4C0B6; color: #B12704; }

        @media (max-width: 767px) {
            .tk-header-card h1 { font-size: 1.4rem; }
            .tk-toolbar { gap: 10px; }
            .tk-filter-btns { width: 100%; overflow-x: auto; white-space: nowrap; }
            .tk-filter-btns label.btn { padding: 8px 10px; font-size: .85rem; }
        }
        @media (max-width: 576px) {
            .tk-card-grid { grid-template-columns: 1fr; }
            .tk-id-col { text-align: left; }
            .tk-arrow { display: none; }
            .tk-stat-card { padding: 12px; }
            .tk-stat-value { font-size: 1.2rem; }
        }
    </style>

    <div class="container py-4 km-tickets">

        <div class="tk-header-card d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h1 class="h4 mb-1 d-flex align-items-center gap-2">
                    <i data-lucide="life-buoy" style="width:24px;height:24px"></i>
                    Support Tickets
                </h1>
                <div class="lead-sub small mb-0">Get help from our team — track every conversation in one place.</div>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-light btn-sm" href="?p=home">
                    <i data-lucide="arrow-left" style="width:14px;height:14px"></i> Home
                </a>
                <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#newTicketModal">
                    <i data-lucide="plus" style="width:14px;height:14px"></i> Raise Complaint
                </button>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                <i data-lucide="check-circle" style="width:18px;height:18px"></i>
                <div><?= e($success) ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                <i data-lucide="alert-circle" style="width:18px;height:18px"></i>
                <div><?= e($error) ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="tk-stat-card">
                    <div class="tk-stat-icon icon-total"><i data-lucide="ticket"></i></div>
                    <div>
                        <div class="tk-stat-value"><?= (int)$stats['total'] ?></div>
                        <div class="tk-stat-label">Total</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="tk-stat-card">
                    <div class="tk-stat-icon icon-open"><i data-lucide="circle-dot"></i></div>
                    <div>
                        <div class="tk-stat-value"><?= (int)$stats['open'] ?></div>
                        <div class="tk-stat-label">Open</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="tk-stat-card">
                    <div class="tk-stat-icon icon-progress"><i data-lucide="clock"></i></div>
                    <div>
                        <div class="tk-stat-value"><?= (int)$stats['in_progress'] ?></div>
                        <div class="tk-stat-label">In Progress</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="tk-stat-card">
                    <div class="tk-stat-icon icon-closed"><i data-lucide="check-circle-2"></i></div>
                    <div>
                        <div class="tk-stat-value"><?= (int)$stats['closed'] ?></div>
                        <div class="tk-stat-label">Closed</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tk-toolbar">
            <div class="tk-search-wrap">
                <span class="input-group-text"><i data-lucide="search" style="width:14px;height:14px"></i></span>
                <input type="search" id="tkSearch" class="form-control" placeholder="Search by subject, order # or ticket ID...">
            </div>
            <div class="tk-filter-btns" role="group" aria-label="Status filter">
                <input type="radio" class="btn-check" name="tkFilter" id="tkFAll" value="all" checked>
                <label class="btn" for="tkFAll" data-f="all">All <span class="badge ms-1"><?= (int)$stats['total'] ?></span></label>

                <input type="radio" class="btn-check" name="tkFilter" id="tkFOpen" value="open">
                <label class="btn" for="tkFOpen" data-f="open">Open <span class="badge ms-1"><?= (int)$stats['open'] ?></span></label>

                <input type="radio" class="btn-check" name="tkFilter" id="tkFProg" value="in_progress">
                <label class="btn" for="tkFProg" data-f="in_progress">In Progress <span class="badge ms-1"><?= (int)$stats['in_progress'] ?></span></label>

                <input type="radio" class="btn-check" name="tkFilter" id="tkFClosed" value="closed">
                <label class="btn" for="tkFClosed" data-f="closed">Closed <span class="badge ms-1"><?= (int)$stats['closed'] ?></span></label>
            </div>
        </div>

        <div id="tkList" class="d-flex flex-column gap-2">
            <?php if (empty($tickets)): ?>
                <div class="tk-empty">
                    <div class="empty-icon"><i data-lucide="inbox"></i></div>
                    <h5 class="mb-1 fw-semibold">No tickets yet</h5>
                    <p class="text-muted mb-3">When you raise a support request, it will appear here.</p>
                    <button class="btn btn-mm btn-sm" data-bs-toggle="modal" data-bs-target="#newTicketModal">
                        <i data-lucide="plus" style="width:14px;height:14px"></i> Raise your first complaint
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($tickets as $t):
                    $statusRaw = strtolower((string)$t['status']);
                    $meta = $tk_status_meta($statusRaw);
                    $unread = (((string)($t['last_sender'] ?? '')) !== '' && ((string)$t['last_sender']) !== 'buyer' && $statusRaw !== 'closed');
                    $haystack = strtolower(((string)$t['subject']) . ' ' . ((string)($t['order_no'] ?? '')) . ' #' . (int)$t['id']);
                    $preview = (string)($t['last_message'] ?? '');
                    if (mb_strlen($preview) > 140) {
                        $preview = mb_substr($preview, 0, 140) . '...';
                    }
                ?>
                <a href="?p=buyer/ticket-detail&id=<?= (int)$t['id'] ?>"
                   class="tk-card"
                   data-status="<?= e($statusRaw) ?>"
                   data-search="<?= e($haystack) ?>">
                    <div class="tk-card-grid">
                        <div class="tk-id-col">
                            <span class="tk-id">#<?= (int)$t['id'] ?></span>
                            <?php if ($unread): ?><span class="tk-dot" title="New reply from support"></span><?php endif; ?>
                        </div>
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <h2 class="tk-subject"><?= e((string)$t['subject']) ?></h2>
                                <span class="tk-badge <?= e($meta['class']) ?>">
                                    <i data-lucide="<?= e($meta['icon']) ?>"></i><?= e($meta['label']) ?>
                                </span>
                                <?php if (!empty($t['order_no'])): ?>
                                    <span class="tk-pill">
                                        <i data-lucide="package"></i>Order #<?= e((string)$t['order_no']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if ($preview !== ''): ?>
                                <div class="tk-preview">
                                    <?php if (((string)($t['last_sender'] ?? '')) === 'buyer'): ?>
                                        <strong>You:</strong>
                                    <?php elseif (((string)($t['last_sender'] ?? '')) !== ''): ?>
                                        <strong class="from-support">Support:</strong>
                                    <?php endif; ?>
                                    <?= e($preview) ?>
                                </div>
                            <?php endif; ?>
                            <div class="tk-meta">
                                <span><i data-lucide="message-square"></i><?= (int)$t['msg_count'] ?> message<?= ((int)$t['msg_count'] === 1 ? '' : 's') ?></span>
                                <span><i data-lucide="refresh-cw"></i>Updated <?= e($tk_time_ago((string)$t['updated_at'])) ?></span>
                                <span class="d-none d-md-inline-flex"><i data-lucide="calendar"></i>Opened <?= e(date('M d, Y', strtotime((string)$t['created_at']))) ?></span>
                            </div>
                        </div>
                        <div class="tk-arrow">
                            <i data-lucide="chevron-right"></i>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
                <div id="tkNoMatch" class="tk-empty d-none">
                    <div class="empty-icon"><i data-lucide="search-x"></i></div>
                    <h5 class="mb-1 fw-semibold">No matching tickets</h5>
                    <p class="text-muted mb-0">Try a different search term or filter.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal fade" id="newTicketModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" id="tkNewForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create_ticket">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i data-lucide="ticket" style="width:18px;height:18px"></i>
                            Raise a Complaint / Ticket
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Related Order <span class="text-muted fw-normal">(optional)</span></label>
                            <select class="form-select" name="order_id">
                                <option value="">— Not related to a specific order —</option>
                                <?php foreach ($buyerOrders as $order): ?>
                                    <option value="<?= (int)$order['id'] ?>">Order #<?= e((string)$order['order_no']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Subject <span class="text-danger">*</span></label>
                            <input class="form-control" name="subject" required maxlength="255" placeholder="Brief summary of your issue">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Message <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="message" rows="5" required maxlength="4000" placeholder="Describe your complaint in detail. Include order numbers, product names or what went wrong."></textarea>
                            <div class="d-flex justify-content-between mt-1">
                                <span class="form-text">More detail helps us resolve your complaint faster.</span>
                                <span class="form-text" id="tkCharCount">0 / 4000</span>
                            </div>
                        </div>
                        <div class="mb-1">
                            <label class="form-label small fw-semibold">Attach a photo <span class="text-muted fw-normal">(optional)</span></label>
                            <div class="tk-uploader" id="tkUploader">
                                <input type="file" id="tkAttachInput" name="attachment" accept="image/*" hidden>
                                <div class="tk-uploader-empty" id="tkUploaderEmpty">
                                    <div class="tk-uploader-icon"><i data-lucide="image-plus"></i></div>
                                    <div class="tk-uploader-text">
                                        <strong>Click to upload</strong> or drag &amp; drop
                                        <div class="small text-muted">JPG, PNG, WEBP or GIF · max 5 MB</div>
                                    </div>
                                </div>
                                <div class="tk-uploader-preview d-none" id="tkUploaderPreview">
                                    <img id="tkPreviewImg" alt="">
                                    <div class="tk-uploader-info">
                                        <div class="fw-semibold small text-truncate" id="tkPreviewName">file.jpg</div>
                                        <div class="text-muted small" id="tkPreviewSize">0 KB</div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light tk-uploader-remove" id="tkRemoveAttach" title="Remove">
                                        <i data-lucide="x" style="width:14px;height:14px"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="form-text" id="tkAttachError" style="color:#dc2626;display:none;"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-mm" id="tkSubmitBtn">
                            <i data-lucide="send" style="width:14px;height:14px"></i> Submit Ticket
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    (function () {
        var search   = document.getElementById('tkSearch');
        var filters  = document.querySelectorAll('input[name="tkFilter"]');
        var cards    = document.querySelectorAll('.tk-card');
        var noMatch  = document.getElementById('tkNoMatch');

        function applyFilters() {
            var q = (search && search.value || '').trim().toLowerCase();
            var f = (document.querySelector('input[name="tkFilter"]:checked') || {}).value || 'all';
            var visible = 0;
            cards.forEach(function (c) {
                var matchSearch = !q || (c.dataset.search || '').indexOf(q) !== -1;
                var matchFilter = (f === 'all') || (c.dataset.status === f);
                var show = matchSearch && matchFilter;
                c.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            if (noMatch) noMatch.classList.toggle('d-none', visible !== 0 || cards.length === 0);
        }

        if (search) {
            var t = null;
            search.addEventListener('input', function () {
                clearTimeout(t);
                t = setTimeout(applyFilters, 80);
            });
        }
        filters.forEach(function (el) { el.addEventListener('change', applyFilters); });

        var modal = document.getElementById('newTicketModal');
        var msgEl = modal ? modal.querySelector('textarea[name="message"]') : null;
        var counter = document.getElementById('tkCharCount');
        if (msgEl && counter) {
            msgEl.addEventListener('input', function () {
                counter.textContent = msgEl.value.length + ' / 4000';
            });
        }
        var newForm = document.getElementById('tkNewForm');
        var submitBtn = document.getElementById('tkSubmitBtn');

        // Image uploader
        var uploader      = document.getElementById('tkUploader');
        var attachInput   = document.getElementById('tkAttachInput');
        var emptyView     = document.getElementById('tkUploaderEmpty');
        var previewView   = document.getElementById('tkUploaderPreview');
        var previewImg    = document.getElementById('tkPreviewImg');
        var previewName   = document.getElementById('tkPreviewName');
        var previewSize   = document.getElementById('tkPreviewSize');
        var removeBtn     = document.getElementById('tkRemoveAttach');
        var attachError   = document.getElementById('tkAttachError');
        var ALLOWED       = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        var MAX_BYTES     = 5 * 1024 * 1024;

        function showAttachError(msg) {
            if (!attachError) return;
            if (msg) {
                attachError.textContent = msg;
                attachError.style.display = '';
                if (uploader) uploader.classList.add('has-error');
            } else {
                attachError.textContent = '';
                attachError.style.display = 'none';
                if (uploader) uploader.classList.remove('has-error');
            }
        }
        function formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + ' KB';
            return (bytes / 1024 / 1024).toFixed(1) + ' MB';
        }
        function setFile(file) {
            showAttachError('');
            if (!file) {
                if (attachInput) attachInput.value = '';
                if (emptyView) emptyView.classList.remove('d-none');
                if (previewView) previewView.classList.add('d-none');
                if (previewImg) previewImg.src = '';
                return;
            }
            if (ALLOWED.indexOf(file.type) === -1) {
                showAttachError('Please pick an image file (JPG, PNG, WEBP or GIF).');
                if (attachInput) attachInput.value = '';
                return;
            }
            if (file.size > MAX_BYTES) {
                showAttachError('Image is too large (max 5 MB). Selected: ' + formatSize(file.size) + '.');
                if (attachInput) attachInput.value = '';
                return;
            }
            var reader = new FileReader();
            reader.onload = function (ev) {
                if (previewImg) previewImg.src = ev.target.result;
            };
            reader.readAsDataURL(file);
            if (previewName) previewName.textContent = file.name;
            if (previewSize) previewSize.textContent = formatSize(file.size);
            if (emptyView) emptyView.classList.add('d-none');
            if (previewView) previewView.classList.remove('d-none');
        }

        if (uploader && attachInput) {
            uploader.addEventListener('click', function (e) {
                if (e.target.closest('#tkRemoveAttach')) return;
                attachInput.click();
            });
            attachInput.addEventListener('change', function () {
                if (attachInput.files && attachInput.files[0]) setFile(attachInput.files[0]);
            });
            ['dragenter', 'dragover'].forEach(function (ev) {
                uploader.addEventListener(ev, function (e) {
                    e.preventDefault(); e.stopPropagation();
                    uploader.classList.add('dragover');
                });
            });
            ['dragleave', 'drop'].forEach(function (ev) {
                uploader.addEventListener(ev, function (e) {
                    e.preventDefault(); e.stopPropagation();
                    uploader.classList.remove('dragover');
                });
            });
            uploader.addEventListener('drop', function (e) {
                if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
                    var dt = new DataTransfer();
                    dt.items.add(e.dataTransfer.files[0]);
                    attachInput.files = dt.files;
                    setFile(e.dataTransfer.files[0]);
                }
            });
        }
        if (removeBtn) {
            removeBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                setFile(null);
            });
        }

        if (newForm && submitBtn) {
            newForm.addEventListener('submit', function () {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Submitting...';
            });
        }
        if (modal) {
            modal.addEventListener('shown.bs.modal', function () {
                var first = modal.querySelector('input[name="subject"]');
                if (first) first.focus();
            });
            modal.addEventListener('hidden.bs.modal', function () {
                if (newForm) newForm.reset();
                setFile(null);
                var counter = document.getElementById('tkCharCount');
                if (counter) counter.textContent = '0 / 4000';
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i data-lucide="send" style="width:14px;height:14px"></i> Submit Ticket';
                    if (window.lucide) window.lucide.createIcons();
                }
            });
        }

        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    })();
    </script>
    <?php
};

require __DIR__ . '/../../views/layout.php';

?>
