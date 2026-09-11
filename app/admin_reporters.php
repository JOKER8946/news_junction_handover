<?php
include './inc/php/db_config.php';
include './inc/php/validate.logged.php';
include './inc/php/function.php';

// Only admins can access (role-based check; set by validate.logged.php)
if (empty($gIsAdmin)) {
    header('Location: stream.php');
    exit;
}

// Handle filter
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query
$sql = "SELECT r.*, u.full_name, u.email, u.phone_no, u.country_code, u.pincode, u.date_created as user_created, u.is_activated
        FROM reporters r
        INNER JOIN user u ON r.user_id = u.id
        WHERE u.is_deleted IS NULL";

if ($statusFilter !== 'all') {
    $sql .= " AND r.status = '" . $creamdb->real_escape_string($statusFilter) . "'";
}
$sql .= " ORDER BY r.date_created DESC";

$result = $creamdb->query($sql);
$reporters = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Get counts
$countSql = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) as verified,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM reporters";
$countResult = $creamdb->query($countSql);
$counts = $countResult ? $countResult->fetch_assoc() : ['total' => 0, 'pending' => 0, 'verified' => 0, 'rejected' => 0];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporter Management - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="../grfx/img/nj_logo.png">
    <link rel="stylesheet" href="inc/css/social.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        :root {
            --cream: #f8f4e3;
            --orange: #db5919;
            --white: #ffffff;
            --dark-cream: #e6e0cc;
            --dark-orange: #b44815;
            --text-dark: #333333;
        }

        body {
            background-color: var(--cream);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .admin-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .admin-header h2 {
            color: var(--dark-orange);
            margin: 0;
            font-size: 24px;
        }

        .stat-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            cursor: pointer;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-card.active {
            border: 2px solid var(--orange);
        }

        .stat-card .count {
            font-size: 28px;
            font-weight: bold;
            color: var(--dark-orange);
        }

        .stat-card .label {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }

        .reporter-card {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .reporter-card .reporter-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .reporter-card .reporter-name {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .reporter-card .reporter-email {
            font-size: 13px;
            color: #666;
        }

        .reporter-card .reporter-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }

        .reporter-card .detail-item {
            font-size: 13px;
        }

        .detail-item .detail-label {
            font-weight: 600;
            color: #888;
            display: block;
            margin-bottom: 2px;
        }

        .badge-pending { background-color: #ffc107; color: #333; }
        .badge-verified { background-color: #28a745; }
        .badge-rejected { background-color: #dc3545; }

        .btn-verify {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 6px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
        }

        .btn-verify:hover { background-color: #218838; color: white; }

        .btn-reject {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 6px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
        }

        .btn-reject:hover { background-color: #c82333; color: white; }

        .btn-back {
            background-color: var(--orange);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-back:hover { background-color: var(--dark-orange); color: white; }

        .no-reporters {
            text-align: center;
            padding: 60px 20px;
            color: #888;
            font-size: 16px;
        }

        .admin-notes-input {
            width: 100%;
            border: 1px solid var(--dark-cream);
            border-radius: 5px;
            padding: 8px;
            font-size: 13px;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .stat-cards { grid-template-columns: repeat(2, 1fr); }
            .reporter-card .reporter-details { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <div class="admin-header">
            <div>
                <h2><i class="fas fa-user-shield"></i> Reporter Management</h2>
                <small style="color:#666;">Manage and verify reporter registrations</small>
            </div>
            <a href="stream.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <!-- Stats -->
        <div class="stat-cards">
            <a href="admin_reporters.php" class="stat-card <?= $statusFilter === 'all' ? 'active' : '' ?>" style="text-decoration:none;">
                <div class="count"><?= $counts['total'] ?></div>
                <div class="label">Total Reporters</div>
            </a>
            <a href="admin_reporters.php?status=pending" class="stat-card <?= $statusFilter === 'pending' ? 'active' : '' ?>" style="text-decoration:none;">
                <div class="count" style="color:#ffc107;"><?= $counts['pending'] ?></div>
                <div class="label">Pending</div>
            </a>
            <a href="admin_reporters.php?status=verified" class="stat-card <?= $statusFilter === 'verified' ? 'active' : '' ?>" style="text-decoration:none;">
                <div class="count" style="color:#28a745;"><?= $counts['verified'] ?></div>
                <div class="label">Verified</div>
            </a>
            <a href="admin_reporters.php?status=rejected" class="stat-card <?= $statusFilter === 'rejected' ? 'active' : '' ?>" style="text-decoration:none;">
                <div class="count" style="color:#dc3545;"><?= $counts['rejected'] ?></div>
                <div class="label">Rejected</div>
            </a>
        </div>

        <!-- Copy Reporter Registration Link -->
        <div style="background: var(--white); border-radius: 10px; padding: 15px 20px; margin-bottom: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
            <div style="font-weight: 600; color: var(--text-dark); white-space: nowrap;"><i class="fas fa-link"></i> Reporter Registration Link:</div>
            <input type="text" id="reporterLink" value="https://newsjunction.net/sign-in.php?ref=reporter" readonly
                style="flex: 1; min-width: 250px; border: 1px solid var(--dark-cream); border-radius: 5px; padding: 8px 12px; font-size: 13px; color: var(--text-dark); background: var(--cream);">
            <button onclick="copyReporterLink()" id="btnCopyLink"
                style="background-color: var(--orange); color: white; border: none; padding: 8px 20px; border-radius: 5px; cursor: pointer; font-size: 13px; white-space: nowrap;">
                <i class="fas fa-copy"></i> Copy Link
            </button>
        </div>

        <!-- Reporter List -->
        <?php if (count($reporters) === 0): ?>
            <div class="no-reporters">
                <i class="fas fa-inbox" style="font-size:40px; margin-bottom:15px;"></i>
                <br>No reporter registrations found.
            </div>
        <?php else: ?>
            <?php foreach ($reporters as $reporter): ?>
                <div class="reporter-card" id="reporter-<?= $reporter['user_id'] ?>">
                    <div class="reporter-header">
                        <div>
                            <div class="reporter-name"><?= htmlspecialchars($reporter['full_name']) ?></div>
                            <div class="reporter-email"><?= htmlspecialchars($reporter['email']) ?></div>
                        </div>
                        <div>
                            <?php if ($reporter['status'] === 'pending'): ?>
                                <span class="badge badge-pending px-3 py-2">Pending</span>
                            <?php elseif ($reporter['status'] === 'verified'): ?>
                                <span class="badge badge-verified px-3 py-2">Verified</span>
                            <?php else: ?>
                                <span class="badge badge-rejected px-3 py-2">Rejected</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="reporter-details">
                        <div class="detail-item">
                            <span class="detail-label">Phone</span>
                            <?= htmlspecialchars($reporter['country_code'] . ' ' . $reporter['phone_no']) ?>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Pincode</span>
                            <?= htmlspecialchars($reporter['pincode']) ?>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Experience</span>
                            <?= htmlspecialchars($reporter['experience'] ?: 'Not specified') ?>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Areas of Coverage</span>
                            <?= htmlspecialchars($reporter['areas_of_coverage'] ?: 'Not specified') ?>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Portfolio</span>
                            <?php if ($reporter['portfolio_url']): ?>
                                <a href="<?= htmlspecialchars($reporter['portfolio_url']) ?>" target="_blank"><?= htmlspecialchars($reporter['portfolio_url']) ?></a>
                            <?php else: ?>
                                Not provided
                            <?php endif; ?>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Registered On</span>
                            <?= date('M d, Y h:i A', strtotime($reporter['date_created'])) ?>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Email Activated</span>
                            <?= $reporter['is_activated'] ? '<span style="color:#28a745">Yes</span>' : '<span style="color:#dc3545">No</span>' ?>
                        </div>
                        <?php if ($reporter['date_verified']): ?>
                        <div class="detail-item">
                            <span class="detail-label">Verified On</span>
                            <?= date('M d, Y h:i A', strtotime($reporter['date_verified'])) ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($reporter['bio']): ?>
                    <div class="detail-item mb-3">
                        <span class="detail-label">Bio</span>
                        <?= htmlspecialchars($reporter['bio']) ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($reporter['admin_notes']): ?>
                    <div class="detail-item mb-3">
                        <span class="detail-label">Admin Notes</span>
                        <?= htmlspecialchars($reporter['admin_notes']) ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($reporter['status'] === 'pending'): ?>
                    <div style="border-top: 1px solid var(--dark-cream); padding-top: 15px;">
                        <input type="text" class="admin-notes-input" id="notes-<?= $reporter['user_id'] ?>" placeholder="Admin notes (optional)">
                        <div style="margin-top: 10px; display: flex; gap: 10px;">
                            <button class="btn-verify" onclick="verifyReporter(<?= $reporter['user_id'] ?>)">
                                <i class="fas fa-check"></i> Verify Reporter
                            </button>
                            <button class="btn-reject" onclick="rejectReporter(<?= $reporter['user_id'] ?>)">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function copyReporterLink() {
            var linkInput = document.getElementById('reporterLink');
            linkInput.select();
            linkInput.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(linkInput.value).then(function() {
                var btn = document.getElementById('btnCopyLink');
                btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                btn.style.backgroundColor = '#28a745';
                setTimeout(function() {
                    btn.innerHTML = '<i class="fas fa-copy"></i> Copy Link';
                    btn.style.backgroundColor = '#db5919';
                }, 2000);
            });
        }

        function verifyReporter(userId) {
            if (!confirm('Are you sure you want to verify this reporter?')) return;
            var notes = $('#notes-' + userId).val();
            $.ajax({
                url: 'process/admin_reporter_process.php',
                method: 'POST',
                data: { act: 'verify', userId: userId, notes: notes },
                dataType: 'json'
            }).done(function(res) {
                if (res.status === 'OK') {
                    location.reload();
                } else {
                    alert('Error: ' + (res.message || 'Could not verify reporter.'));
                }
            });
        }

        function rejectReporter(userId) {
            if (!confirm('Are you sure you want to reject this reporter?')) return;
            var notes = $('#notes-' + userId).val();
            $.ajax({
                url: 'process/admin_reporter_process.php',
                method: 'POST',
                data: { act: 'reject', userId: userId, notes: notes },
                dataType: 'json'
            }).done(function(res) {
                if (res.status === 'OK') {
                    location.reload();
                } else {
                    alert('Error: ' + (res.message || 'Could not reject reporter.'));
                }
            });
        }
    </script>
</body>

</html>
