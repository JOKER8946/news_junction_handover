<?php

declare(strict_types=1);

auth_require_role('buyer');

$title = 'Ticket';
$buyerId = auth_user_id();
$ticketId = (int)($_GET['id'] ?? 0);

$ticket = db_fetch_one($db, '
    SELECT t.*, o.order_no
    FROM tickets t
    LEFT JOIN orders o ON o.id = t.order_id
    WHERE t.id = :id AND t.buyer_id = :buyer_id
    LIMIT 1
', ['id' => $ticketId, 'buyer_id' => $buyerId]);

if (!$ticket) {
    redirect_to('buyer/tickets');
}

if (request_method() === 'POST' && isset($_POST['action']) && $_POST['action'] === 'message') {
    if (strtolower((string)$ticket['status']) === 'closed') {
        flash_set('error', 'This ticket is closed. Please open a new ticket.');
        redirect_to('buyer/ticket-detail?id=' . $ticketId);
    }

    $message = post_string('message');
    $imagePath = null;

    if (!empty($_FILES['attachment']['name']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        $mime = mime_content_type($_FILES['attachment']['tmp_name']) ?: '';
        if (isset($allowed[$mime]) && $_FILES['attachment']['size'] <= 5 * 1024 * 1024) {
            $ext = $allowed[$mime];
            $uploadDir = __DIR__ . '/../../uploads/tickets';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0775, true);
            }
            $fname = 'ticket-' . $ticketId . '-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = $uploadDir . '/' . $fname;
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $dest)) {
                $imagePath = 'uploads/tickets/' . $fname;
            }
        } else {
            flash_set('error', 'Attachment must be JPG, PNG, WEBP or GIF, max 5 MB.');
            redirect_to('buyer/ticket-detail?id=' . $ticketId);
        }
    }

    if ($message !== '' || $imagePath !== null) {
        if ($message === '' && $imagePath !== null) {
            $message = '(image attached)';
        }
        db_exec($db, '
            INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, message, image_path, created_at)
            VALUES (:ticket_id, :sender_type, :sender_id, :message, :image_path, NOW())
        ', [
            'ticket_id' => $ticketId,
            'sender_type' => 'buyer',
            'sender_id' => $buyerId,
            'message' => $message,
            'image_path' => $imagePath,
        ]);
        db_exec($db, 'UPDATE tickets SET updated_at = NOW(), status = :status WHERE id = :id', [
            'status' => 'open', 'id' => $ticketId,
        ]);
        flash_set('success', 'Message sent');
    }
    redirect_to('buyer/ticket-detail?id=' . $ticketId);
}

if (request_method() === 'POST' && isset($_POST['action']) && $_POST['action'] === 'close_ticket') {
    db_exec($db, 'UPDATE tickets SET status = :status, updated_at = NOW() WHERE id = :id AND buyer_id = :buyer_id', [
        'status' => 'closed',
        'id' => $ticketId,
        'buyer_id' => $buyerId,
    ]);
    flash_set('success', 'Ticket closed.');
    redirect_to('buyer/ticket-detail?id=' . $ticketId);
}

if (request_method() === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reopen_ticket') {
    db_exec($db, 'UPDATE tickets SET status = :status, updated_at = NOW() WHERE id = :id AND buyer_id = :buyer_id', [
        'status' => 'open',
        'id' => $ticketId,
        'buyer_id' => $buyerId,
    ]);
    flash_set('success', 'Ticket reopened.');
    redirect_to('buyer/ticket-detail?id=' . $ticketId);
}

$ticket = db_fetch_one($db, '
    SELECT t.*, o.order_no
    FROM tickets t
    LEFT JOIN orders o ON o.id = t.order_id
    WHERE t.id = :id AND t.buyer_id = :buyer_id
    LIMIT 1
', ['id' => $ticketId, 'buyer_id' => $buyerId]);

$messages = db_fetch_all($db, '
    SELECT tm.*, u.full_name AS user_name
    FROM ticket_messages tm
    LEFT JOIN users u ON u.id = tm.sender_id
    WHERE tm.ticket_id = :ticket_id
    ORDER BY tm.created_at ASC
', ['ticket_id' => $ticketId]);

$td_status_meta = function (string $status): array {
    $s = strtolower($status);
    if ($s === 'open') return ['label' => 'Open', 'class' => 'tk-st-open', 'icon' => 'circle-dot'];
    if ($s === 'in_progress' || $s === 'pending') return ['label' => 'In Progress', 'class' => 'tk-st-progress', 'icon' => 'clock'];
    if ($s === 'closed' || $s === 'resolved') return ['label' => 'Closed', 'class' => 'tk-st-closed', 'icon' => 'check-circle-2'];
    return ['label' => ucwords(str_replace('_', ' ', $status)), 'class' => 'tk-st-open', 'icon' => 'circle-dot'];
};

$td_time_ago = function (string $datetime): string {
    $time = strtotime($datetime);
    if ($time === false) return $datetime;
    $diff = time() - $time;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) { $m = (int)floor($diff/60); return $m . ' min' . ($m === 1 ? '' : 's') . ' ago'; }
    if ($diff < 86400) { $h = (int)floor($diff/3600); return $h . ' hour' . ($h === 1 ? '' : 's') . ' ago'; }
    if ($diff < 604800) { $d = (int)floor($diff/86400); return $d . ' day' . ($d === 1 ? '' : 's') . ' ago'; }
    return date('M d, Y', $time);
};

$content = function () use ($ticket, $messages, $td_status_meta, $td_time_ago) {
    $success = flash_get('success');
    $error = flash_get('error');
    $statusRaw = strtolower((string)$ticket['status']);
    $meta = $td_status_meta($statusRaw);
    $isClosed = ($statusRaw === 'closed' || $statusRaw === 'resolved');
    ?>
    <style>
        .km-tdetail { max-width: 1100px; }
        .td-header-card {
            background: linear-gradient(135deg, #FF8C00 0%, #FFD700 100%);
            color: #fff;
            border-radius: 16px;
            padding: 1.4rem 1.6rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 6px 20px rgba(255,140,0,.18);
        }
        .td-header-card .ticket-id {
            display: inline-block; background: rgba(255,255,255,.22);
            padding: .15rem .55rem; border-radius: 999px;
            font-size: .78rem; font-weight: 700; letter-spacing: .03em;
        }
        .td-header-card h1 { color: #fff; font-weight: 700; margin: .35rem 0 .25rem; }
        .td-header-card .meta-line { color: rgba(255,255,255,.92); font-size: .85rem; }
        .td-header-card .btn-light {
            color: #C25E00; font-weight: 600; border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,.08);
        }
        .td-header-card .btn-light:hover { background: #fff8ef; color: #B05500; }
        .td-header-card .btn-outline-light { border-color: rgba(255,255,255,.6); color: #fff; font-weight: 600; border-radius: 10px; }
        .td-header-card .btn-outline-light:hover { background: rgba(255,255,255,.18); color: #fff; }

        .tk-badge {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: .72rem; font-weight: 600; padding: .2rem .55rem;
            border-radius: 999px; line-height: 1;
        }
        .tk-badge i { width: 12px; height: 12px; }
        .tk-st-open     { background: #DCFCE7; color: #15803D; }
        .tk-st-progress { background: #FEF3C7; color: #B45309; }
        .tk-st-closed   { background: #E5E7EB; color: #4B5563; }

        .td-info-card {
            background: #fff; border: 1px solid #F0EAE0; border-radius: 14px;
            padding: 1rem 1.1rem;
        }
        .td-info-card .label {
            font-size: .72rem; text-transform: uppercase; letter-spacing: .04em;
            color: #6b7280; font-weight: 600;
        }
        .td-info-card .value { color: #1f2937; font-weight: 500; }
        .td-info-card a { color: #C25E00; text-decoration: none; font-weight: 600; }
        .td-info-card a:hover { text-decoration: underline; }

        .td-conv {
            background: #fff; border: 1px solid #F0EAE0; border-radius: 14px;
            padding: 1.25rem 1.1rem;
            display: flex; flex-direction: column; gap: 1rem;
            min-height: 240px;
            background-image:
                radial-gradient(rgba(255,140,0,.05) 1px, transparent 1px);
            background-size: 16px 16px;
        }
        .td-msg-row { display: flex; align-items: flex-end; gap: .55rem; }
        .td-msg-row.from-you { justify-content: flex-end; }
        .td-avatar {
            width: 34px; height: 34px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: .85rem; color: #fff; flex-shrink: 0;
            box-shadow: 0 2px 4px rgba(0,0,0,.08);
        }
        .td-avatar.you { background: linear-gradient(135deg, #FF8C00, #FFD700); color:#fff; order: 2; }
        .td-avatar.support { background: linear-gradient(135deg, #16A34A, #4ADE80); color:#fff; }
        .td-avatar.merchant { background: linear-gradient(135deg, #2563EB, #60A5FA); color:#fff; }
        .td-avatar.system  { background: #6B7280; color:#fff; }

        .td-bubble-wrap { max-width: 70%; }
        .td-msg-row.from-you .td-bubble-wrap { align-items: flex-end; display: flex; flex-direction: column; }
        .td-msg-row.from-them .td-bubble-wrap { align-items: flex-start; display: flex; flex-direction: column; }

        .td-bubble {
            padding: .65rem .9rem; border-radius: 14px; line-height: 1.45;
            font-size: .94rem; color: #1f2937; position: relative;
            word-wrap: break-word; overflow-wrap: anywhere;
            box-shadow: 0 1px 2px rgba(0,0,0,.04);
        }
        .td-bubble.you {
            background: linear-gradient(135deg, #FFE9C8, #FFD7A2);
            border-bottom-right-radius: 4px;
        }
        .td-bubble.them {
            background: #F3F4F6; border-bottom-left-radius: 4px;
        }
        .td-bubble.support { background: #DCFCE7; }
        .td-bubble.merchant { background: #DBEAFE; }
        .td-bubble.system { background: #F3F4F6; color:#6b7280; font-style: italic; text-align: center; }

        .td-bubble img.attach {
            display: block; max-width: 100%; max-height: 280px; border-radius: 10px;
            margin-top: .4rem; border: 1px solid rgba(0,0,0,.06); cursor: zoom-in;
        }

        .td-msg-meta {
            font-size: .72rem; color: #9ca3af; margin-top: 4px;
            display: flex; gap: .4rem; align-items: center;
        }
        .td-msg-meta .name { font-weight: 600; color: #6b7280; }

        .td-empty-conv {
            text-align: center; color: #9ca3af; padding: 2rem 1rem;
        }
        .td-empty-conv i { width: 28px; height: 28px; margin-bottom: .5rem; }

        .td-reply-card {
            background: #fff; border: 1px solid #F0EAE0; border-radius: 14px;
            padding: 1rem; margin-top: 1rem;
        }
        .td-reply-card textarea { resize: vertical; min-height: 90px; }
        .td-reply-card textarea:focus { border-color: #FFB066; box-shadow: 0 0 0 .2rem rgba(255,140,0,.15); }
        .td-attach-row {
            display: flex; flex-wrap: wrap; align-items: center; gap: .6rem; margin-top: .55rem;
        }
        .td-attach-btn {
            display: inline-flex; align-items: center; gap: .35rem;
            background: #FFF1DD; color: #C25E00; padding: .4rem .7rem;
            border-radius: 999px; font-size: .82rem; font-weight: 600;
            cursor: pointer; border: 1px dashed #FFCB8E; transition: all .15s ease;
        }
        .td-attach-btn:hover { background: #FFE3B7; border-color: #FFB066; }
        .td-attach-btn i { width: 14px; height: 14px; }
        .td-attach-name { font-size: .8rem; color: #6b7280; }
        .td-attach-clear {
            background: none; border: none; color: #ef4444;
            font-size: .8rem; cursor: pointer; padding: 0;
        }

        .td-closed-banner {
            background: #FEF3C7; border: 1px solid #FCD34D; color: #92400E;
            border-radius: 12px; padding: .85rem 1rem; margin-top: 1rem;
            display: flex; align-items: center; gap: .55rem;
        }
        .td-closed-banner i { width: 18px; height: 18px; }

        .td-img-modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,.85);
            display: none; align-items: center; justify-content: center;
            z-index: 2000; padding: 24px; cursor: zoom-out;
        }
        .td-img-modal-overlay.show { display: flex; }
        .td-img-modal-overlay img { max-width: 100%; max-height: 100%; border-radius: 8px; }
        .td-img-modal-close {
            position: absolute; top: 16px; right: 20px; background: rgba(255,255,255,.15);
            border: none; color: #fff; width: 36px; height: 36px; border-radius: 50%;
            font-size: 1.4rem; cursor: pointer;
        }

        @media (max-width: 576px) {
            .td-bubble-wrap { max-width: 85%; }
        }
    </style>

    <div class="container py-4 km-tdetail">

        <div class="td-header-card">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div style="min-width:0; flex:1;">
                    <span class="ticket-id">#<?= (int)$ticket['id'] ?></span>
                    <span class="tk-badge <?= e($meta['class']) ?> ms-2">
                        <i data-lucide="<?= e($meta['icon']) ?>"></i><?= e($meta['label']) ?>
                    </span>
                    <h1 class="h4 text-truncate" style="max-width:100%"><?= e((string)$ticket['subject']) ?></h1>
                    <div class="meta-line d-flex flex-wrap gap-3">
                        <span><i data-lucide="calendar" style="width:14px;height:14px"></i> Opened <?= e(date('M d, Y · H:i', strtotime((string)$ticket['created_at']))) ?></span>
                        <span><i data-lucide="refresh-cw" style="width:14px;height:14px"></i> Updated <?= e($td_time_ago((string)$ticket['updated_at'])) ?></span>
                        <?php if (!empty($ticket['order_no'])): ?>
                            <span><i data-lucide="package" style="width:14px;height:14px"></i> Order #<?= e((string)$ticket['order_no']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="d-flex gap-2 flex-shrink-0">
                    <a class="btn btn-outline-light btn-sm" href="?p=buyer/tickets">
                        <i data-lucide="arrow-left" style="width:14px;height:14px"></i> All Tickets
                    </a>
                    <?php if (!$isClosed): ?>
                        <form method="post" class="d-inline" onsubmit="return confirm('Close this ticket? You can reopen it later.');">
                            <input type="hidden" name="action" value="close_ticket">
                            <button class="btn btn-light btn-sm" type="submit">
                                <i data-lucide="check" style="width:14px;height:14px"></i> Close
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="action" value="reopen_ticket">
                            <button class="btn btn-light btn-sm" type="submit">
                                <i data-lucide="rotate-ccw" style="width:14px;height:14px"></i> Reopen
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2">
                <i data-lucide="check-circle" style="width:18px;height:18px"></i>
                <div><?= e($success) ?></div>
                <button class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2">
                <i data-lucide="alert-circle" style="width:18px;height:18px"></i>
                <div><?= e($error) ?></div>
                <button class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div id="tdConversation" class="td-conv">
            <?php if (empty($messages)): ?>
                <div class="td-empty-conv">
                    <i data-lucide="message-square-dashed"></i>
                    <div>No messages yet — start the conversation below.</div>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $m):
                    $sender   = strtolower((string)$m['sender_type']);
                    $isYou    = ($sender === 'buyer');
                    $rowClass = $isYou ? 'from-you' : 'from-them';
                    $name     = $isYou ? 'You' : (!empty($m['user_name']) ? (string)$m['user_name'] : ucfirst($sender));
                    $bubbleClass = $isYou ? 'you' : ($sender === 'merchant' ? 'merchant' : ($sender === 'support' ? 'support' : 'them'));
                    $avatarLetter = strtoupper(mb_substr($name, 0, 1));
                    $avatarClass = $isYou ? 'you' : ($sender === 'merchant' ? 'merchant' : ($sender === 'support' ? 'support' : 'system'));
                ?>
                <div class="td-msg-row <?= $rowClass ?>">
                    <div class="td-avatar <?= $avatarClass ?>"><?= e($avatarLetter) ?></div>
                    <div class="td-bubble-wrap">
                        <div class="td-bubble <?= $bubbleClass ?>">
                            <?= nl2br(e((string)$m['message'])) ?>
                            <?php if (!empty($m['image_path'])): ?>
                                <img class="attach td-zoomable"
                                     src="/<?= e(ltrim((string)$m['image_path'], '/')) ?>"
                                     alt="attachment">
                            <?php endif; ?>
                        </div>
                        <div class="td-msg-meta">
                            <span class="name"><?= e($name) ?></span>
                            <span>·</span>
                            <span title="<?= e(date('M d, Y H:i', strtotime((string)$m['created_at']))) ?>">
                                <?= e($td_time_ago((string)$m['created_at'])) ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($isClosed): ?>
            <div class="td-closed-banner">
                <i data-lucide="lock"></i>
                <div>This ticket is <strong>closed</strong>. Reopen it to send a new reply.</div>
            </div>
        <?php else: ?>
            <div class="td-reply-card">
                <form method="post" enctype="multipart/form-data" id="tdReplyForm">
                    <input type="hidden" name="action" value="message">
                    <textarea class="form-control" name="message" rows="3" maxlength="4000"
                              placeholder="Type your reply..."></textarea>
                    <div class="td-attach-row">
                        <label class="td-attach-btn" for="tdAttachInput">
                            <i data-lucide="paperclip"></i>
                            <span>Attach image</span>
                        </label>
                        <input type="file" id="tdAttachInput" name="attachment" accept="image/*" hidden>
                        <span class="td-attach-name" id="tdAttachName"></span>
                        <button type="button" class="td-attach-clear d-none" id="tdAttachClear">remove</button>
                        <span class="ms-auto form-text" id="tdCharCount">0 / 4000</span>
                        <button class="btn btn-mm btn-sm" type="submit" id="tdSendBtn">
                            <i data-lucide="send" style="width:14px;height:14px"></i> Send
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <div class="td-img-modal-overlay" id="tdImgModal">
        <button class="td-img-modal-close" type="button" aria-label="Close">×</button>
        <img id="tdImgModalImg" alt="">
    </div>

    <script>
    (function () {
        var conv = document.getElementById('tdConversation');
        if (conv) {
            try { conv.scrollTop = conv.scrollHeight; } catch (e) {}
            try { window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' }); } catch (e) {}
        }

        var msgEl = document.querySelector('#tdReplyForm textarea[name="message"]');
        var counter = document.getElementById('tdCharCount');
        if (msgEl && counter) {
            msgEl.addEventListener('input', function () { counter.textContent = msgEl.value.length + ' / 4000'; });
        }
        if (msgEl) {
            msgEl.addEventListener('keydown', function (e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                    var f = document.getElementById('tdReplyForm');
                    if (f) f.requestSubmit();
                }
            });
        }

        var attachInput = document.getElementById('tdAttachInput');
        var attachName  = document.getElementById('tdAttachName');
        var attachClear = document.getElementById('tdAttachClear');
        if (attachInput) {
            attachInput.addEventListener('change', function () {
                if (attachInput.files && attachInput.files[0]) {
                    var f = attachInput.files[0];
                    attachName.textContent = f.name + ' (' + (f.size / 1024).toFixed(0) + ' KB)';
                    attachClear.classList.remove('d-none');
                    if (f.size > 5 * 1024 * 1024) {
                        attachName.textContent += ' — too large (max 5 MB)';
                        attachName.style.color = '#dc2626';
                    } else {
                        attachName.style.color = '';
                    }
                } else {
                    attachName.textContent = '';
                    attachClear.classList.add('d-none');
                }
            });
        }
        if (attachClear) {
            attachClear.addEventListener('click', function () {
                if (attachInput) attachInput.value = '';
                attachName.textContent = '';
                attachClear.classList.add('d-none');
            });
        }

        var replyForm = document.getElementById('tdReplyForm');
        var sendBtn = document.getElementById('tdSendBtn');
        if (replyForm && sendBtn) {
            replyForm.addEventListener('submit', function (e) {
                var hasMsg = msgEl && msgEl.value.trim().length > 0;
                var hasFile = attachInput && attachInput.files && attachInput.files.length > 0;
                if (!hasMsg && !hasFile) {
                    e.preventDefault();
                    if (msgEl) msgEl.focus();
                    return;
                }
                sendBtn.disabled = true;
                sendBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Sending...';
            });
        }

        var modal = document.getElementById('tdImgModal');
        var modalImg = document.getElementById('tdImgModalImg');
        var modalClose = modal ? modal.querySelector('.td-img-modal-close') : null;
        document.querySelectorAll('.td-zoomable').forEach(function (img) {
            img.addEventListener('click', function () {
                if (!modal) return;
                modalImg.src = img.src;
                modal.classList.add('show');
            });
        });
        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === modal || e.target === modalClose) {
                    modal.classList.remove('show');
                    modalImg.src = '';
                }
            });
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal && modal.classList.contains('show')) {
                modal.classList.remove('show');
                modalImg.src = '';
            }
        });

        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    })();
    </script>
    <?php
};

require __DIR__ . '/../../views/layout.php';

?>
