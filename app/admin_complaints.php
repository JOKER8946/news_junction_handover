<?php
include './inc/php/db_config.php';
include './inc/php/validate.logged.php';
include './inc/php/function.php';

if (empty($gIsAdmin)) {
    header('Location: my_complaints.php');
    exit;
}

// Get total complaint count
$totalSql = "SELECT COUNT(*) as total FROM complaints";
$totalResult = $creamdb->query($totalSql);
$totalRow = $totalResult->fetch_assoc();
$totalComplaints = $totalRow['total'];

// Get complaints by pincode
$pincodesSql = "SELECT pincode, COUNT(*) as count 
                FROM complaints 
                GROUP BY pincode 
                ORDER BY count DESC, pincode ASC";
$pincodesResult = $creamdb->query($pincodesSql);
$pincodeData = $pincodesResult->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaints Dashboard - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="icon" type="image/x-icon" href="../grfx/img/logo.ico">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <link rel="stylesheet" href="inc/css/social.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://js.zohostatic.com/books/zfwidgets/assets/js/zf-widget.js"></script>
    <script src="inc/js/common.js"></script>
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .admin-header h2 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 28px;
            font-weight: 700;
            color: #333;
        }

        .total-count {
            font-size: 18px;
            font-weight: 700;
            color: #667eea;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #667eea;
            transition: all 0.3s;
        }

        .stat-card:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .stat-card h3 {
            font-size: 14px;
            font-weight: 600;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 10px 0;
        }

        .stat-card .count {
            font-size: 32px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 10px;
        }

        .stat-card .pincode {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            background: #f0f0f0;
            padding: 8px 12px;
            border-radius: 6px;
            display: inline-block;
        }

        .stat-card.total {
            border-left-color: #27ae60;
            background: linear-gradient(135deg, rgba(39, 174, 96, 0.05) 0%, rgba(39, 174, 96, 0.1) 100%);
        }

        .stat-card.total .count {
            color: #27ae60;
            font-size: 48px;
        }

        .stat-card.total h3 {
            color: #27ae60;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
        }

        .chart-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-top: 30px;
        }

        .chart-title {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
        }

        .complaint-row {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .complaint-row:last-child {
            border-bottom: none;
        }

        .complaint-pincode {
            font-weight: 600;
            color: #667eea;
            min-width: 100px;
            font-size: 16px;
        }

        .complaint-bar {
            flex: 1;
            height: 30px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            border-radius: 6px;
            margin: 0 20px;
            position: relative;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
        }

        .complaint-count {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-weight: 700;
            font-size: 12px;
        }

        .complaint-percentage {
            min-width: 50px;
            text-align: right;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .admin-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .complaint-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .complaint-bar {
                width: 100%;
                margin: 0;
            }

            .complaint-percentage {
                width: 100%;
                text-align: left;
            }
        }
    </style>
</head>

<body>
    <div class="containers">
        <?php include 'inc/php/social_navbar.php'; ?>
        <?php include 'inc/php/social_sidebar.php'; ?>

        <div class="search-main-content main-content">
            <a href="manage_complaint_emails.php" class="back-link" style="margin:10px; padding :4px; border: 1px solid black; background:white;color: black; border-radius:12px;">
                Complaint emails management
            </a>
                <div class="admin-container">
                <div class="admin-header">
                    <h2>
                        <i class="fas fa-chart-bar"></i>
                        Complaints Overview
                    </h2>
                    <div class="total-count">
                        Total: <span style="color: #27ae60;"><?php echo $totalComplaints; ?></span>
                    </div>
                </div>

                <?php if ($totalComplaints == 0): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3>No Complaints</h3>
                        <p>No complaints have been submitted yet.</p>
                    </div>
                <?php else: ?>
                    <!-- Total Card -->
                    <div class="stats-grid">
                        <div class="stat-card total">
                            <h3>Total Complaints</h3>
                            <div class="count"><?php echo $totalComplaints; ?></div>
                            <p style="color: #666; margin: 0; font-size: 13px;">Across all pincodes</p>
                        </div>
                    </div>

                    <!-- Complaints by Pincode -->
                    <div class="chart-container">
                        <div class="chart-title">
                            <i class="fas fa-map-pin" style="color: #667eea; margin-right: 10px;"></i>
                            Complaints by Pincode
                        </div>

                        <?php
                        $maxCount = max(array_column($pincodeData, 'count'));
                        foreach ($pincodeData as $item):
                            $percentage = round(($item['count'] / $totalComplaints) * 100);
                            $barWidth = round(($item['count'] / $maxCount) * 100);
                        ?>
                            <div class="complaint-row">
                                <div class="complaint-pincode">
                                    <i class="fas fa-map-pin"></i> <?php echo htmlspecialchars($item['pincode']); ?>
                                </div>
                                <div class="complaint-bar" style="width: calc(100% - 250px);">
                                    <div style="width: <?php echo $barWidth; ?>%; height: 100%; background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); border-radius: 6px; position: relative;">
                                        <span class="complaint-count"><?php echo $item['count']; ?></span>
                                    </div>
                                </div>
                                <div class="complaint-percentage"><?php echo $percentage; ?>%</div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Detailed Stats Grid -->
                    <div class="stats-grid" style="margin-top: 40px;">
                        <?php foreach ($pincodeData as $item): ?>
                            <div class="stat-card">
                                <h3>Pincode <?php echo htmlspecialchars($item['pincode']); ?></h3>
                                <div class="count"><?php echo $item['count']; ?></div>
                                <span class="pincode">
                                    <?php echo round(($item['count'] / $totalComplaints) * 100); ?>% of total
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>


            <?php include 'inc/php/footer.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>