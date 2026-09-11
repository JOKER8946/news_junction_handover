<?php
require_once './inc/php/db_config.php';
require_once './inc/php/validate.logged.php';
require_once './inc/php/function.php';

global $gUserId, $readerdb;

// Get district from URL
$district = isset($_GET['district']) ? trim($_GET['district']) : '';

if (empty($district)) {
    header('Location: dashboard.php');
    exit;
}

// Fetch newspapers for this district uploaded by the current user
$stmt = $readerdb->prepare("SELECT * FROM newspapers WHERE uploaded_by = ? AND district = ? ORDER BY created_at DESC");
$stmt->bind_param("is", $gUserId, $district);
$stmt->execute();
$result = $stmt->get_result();

$newspapers = [];
while ($row = $result->fetch_assoc()) {
    $newspapers[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($district) ?> - Newspapers | News Junction</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="grfx/img/logo.ico">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="inc/css/social.css">

    <style>
        .back-bar {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .back-bar a {
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 5px;
            background: var(--bg-card);
            transition: all 0.3s;
        }

        .back-bar a:hover {
            opacity: 0.85;
            transform: translateX(-3px);
        }

        .district-title {
            font-size: 1.75rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
        }

        .district-title i {
            color: var(--primary);
            margin-right: 8px;
        }

        .np-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }

        .np-card {
            background: var(--reader-card-color);
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }

        .np-card:hover {
            transform: scale(1.03);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
        }

        .np-card-img {
            width: 100%;
            height: 160px;
            object-fit: cover;
        }

        .np-card-placeholder {
            width: 100%;
            height: 160px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .np-card-placeholder i {
            font-size: 50px;
            color: #fff;
        }

        .np-card-body {
            padding: 12px 15px;
        }

        .np-card-title {
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
            margin-bottom: 6px;
            text-align: center;
        }

        .np-card-date {
            font-size: 11px;
            color: #aaa;
            text-align: center;
            margin-bottom: 10px;
        }

        .np-card .btn-view {
            display: block;
            margin: 0 auto;
            width: 70%;
            text-align: center;
            padding: 6px 12px;
            font-size: 13px;
            border-radius: 4px;
            background: var(--primary);
            color: #fff;
            text-decoration: none;
            transition: background 0.3s;
        }

        .np-card .btn-view:hover {
            opacity: 0.9;
        }

        .empty-msg {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        .empty-msg i {
            font-size: 50px;
            margin-bottom: 15px;
            opacity: 0.4;
        }

        .empty-msg h3 {
            margin-bottom: 8px;
            color: var(--text-primary);
        }

        @media (max-width: 576px) {
            .np-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 12px;
            }

            .np-card-img,
            .np-card-placeholder {
                height: 120px;
            }

            .district-title {
                font-size: 1.25rem;
            }
        }

        @media (min-width: 768px) {
            .col-md-2 {
                padding: 0px !important;
            }
        }

        @media (max-width: 768px) {
            .col-md-2 {
                display: none !important;
            }
        }

        .sideMaincontent {
            padding: 30px 20px;
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>

    <? include 'inc/php/social_navbar.php' ?>
    <div class="reader-main-content main-content">
        <? include 'inc/php/social_sidebar.php' ?>
        <div class="container-fluid col-sm-12 col-md-12 sideMaincontent" style="padding-bottom: 70px;">

            <div class="back-bar">
                <a href="dashboard.php">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>

            <h2 class="district-title">
                <i class="fas fa-map-marker-alt"></i><?= htmlspecialchars($district) ?>
            </h2>

            <?php if (!empty($newspapers)) { ?>
                <div class="np-grid">
                    <?php foreach ($newspapers as $np) { ?>
                        <div class="np-card">
                            <?php if (!empty($np['cover_image'])): ?>
                                <img src="<?= htmlspecialchars($np['cover_image']) ?>" class="np-card-img" alt="<?= htmlspecialchars($np['title']) ?>">
                            <?php else: ?>
                                <div class="np-card-placeholder">
                                    <i class="fas fa-file-pdf"></i>
                                </div>
                            <?php endif; ?>
                            <div class="np-card-body">
                                <div class="np-card-title"><?= htmlspecialchars($np['title']) ?></div>
                                <div class="np-card-date"><?= date('d M Y', strtotime($np['created_at'])) ?></div>
                                <a href="<?= htmlspecialchars($np['pdf_url']) ?>" target="_blank" class="btn-view">View PDF</a>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <div class="empty-msg">
                    <i class="fas fa-newspaper"></i>
                    <h3>No Newspapers Found</h3>
                    <p>No newspapers have been uploaded for <?= htmlspecialchars($district) ?> yet.</p>
                </div>
            <?php } ?>

        </div>
    </div>
    <? include "inc/php/footer.php"; ?>

</body>

</html>
