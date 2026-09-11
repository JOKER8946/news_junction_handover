<?php
include "assets/php/db_config.php";

// SQL query
$sql = "SELECT
    n.id,
    n.userName,
    COUNT(r.referrer_id) AS count
FROM
    referrer_info n
JOIN
    referral_info r ON n.id = r.referrer_id
GROUP BY
    n.id, n.userName
ORDER BY
    count DESC";

$result = $creamdb->query($sql);

// Prepare data arrays for the chart
$users = array();
$counts = array();
$max_count = 0;

// Display the HTML table first
echo "<h2>Referral Data Table</h2>";
if ($result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<thead><tr><th>ID</th><th>Name</th><th>Count</th></tr></thead><tbody>";
    
    // Store the result data for reuse in the chart
    $rows = array();
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
        $total_count += (int)$row["count"];
        
        // Output table row
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row["id"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["userName"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["count"]) . "</td>";
        echo "</tr>";
        
        // Store data for the chart
        $users[] = $row["userName"];
        $counts[] = (int)$row["count"];
        if ((int)$row["count"] > $max_count) {
            $max_count = (int)$row["count"];
        }
    }
    echo "</tbody></table>";
    echo "<h3>Total Referrals: " . $total_count . "</h3>";

} else {
    echo "No results found.";
}

// Now, generate the horizontal bar chart
echo "<h2>Referral Data Chart</h2>";

// Scale and dimensions
$svg_width = 800;
$svg_height = 200 + (count($users) * 40); // Adjust height based on number of users
$margin = [60, 60, 60, 150]; // top, right, bottom, left
$chart_width = $svg_width - $margin[1] - $margin[3];
$chart_height = $svg_height - $margin[0] - $margin[2];
$bar_height = 30;
$bar_spacing = 10;

// Set the scale for x-axis
$scale_factor = $chart_width / (($max_count == 0) ? 1 : $max_count);

// Create SVG header
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 <?php echo $svg_width; ?> <?php echo $svg_height; ?>">
    <style>
        .bar { fill: #4682b4; }
        .bar:hover { fill: #5f9ea0; }
        .axis { font-family: Arial, sans-serif; font-size: 12px; }
        .axis-label { font-family: Arial, sans-serif; font-size: 14px; font-weight: bold; }
        .title { font-family: Arial, sans-serif; font-size: 18px; font-weight: bold; }
        .value-label { font-family: Arial, sans-serif; font-size: 12px; fill: #333; font-weight: bold; }
        .no-data { font-family: Arial, sans-serif; font-size: 16px; fill: #666; }
    </style>
    
    <!-- Title -->
    <text x="<?php echo $svg_width / 2; ?>" y="30" class="title" text-anchor="middle">
        Referral Counts by User
    </text>
    
    <?php if (count($users) > 0): ?>
        <line 
            x1="<?php echo $margin[3]; ?>" 
            y1="<?php echo $svg_height - $margin[2]; ?>" 
            x2="<?php echo $svg_width - $margin[1]; ?>" 
            y2="<?php echo $svg_height - $margin[2]; ?>" 
            stroke="#333" 
            stroke-width="1"
        />
        
        <text 
            x="<?php echo $margin[3] + $chart_width / 2; ?>" 
            y="<?php echo $svg_height - 15; ?>" 
            class="axis-label" 
            text-anchor="middle"
        >
            Number of Referrals
        </text>
        
        <?php
        // X-axis ticks
        $tick_count = 5;
        $tick_interval = $max_count / $tick_count;
        
        for ($i = 0; $i <= $tick_count; $i++) {
            $tick_value = $i * $tick_interval;
            $tick_x = $margin[3] + ($tick_value * $scale_factor);
            ?>
            <line
                x1="<?php echo $tick_x; ?>"
                y1="<?php echo $svg_height - $margin[2]; ?>"
                x2="<?php echo $tick_x; ?>"
                y2="<?php echo $svg_height - $margin[2] + 5; ?>"
                stroke="#333"
                stroke-width="1"
            />
            <text
                x="<?php echo $tick_x; ?>"
                y="<?php echo $svg_height - $margin[2] + 20; ?>"
                class="axis"
                text-anchor="middle"
            >
                <?php echo round($tick_value); ?>
            </text>
            <?php
        }
        
        // Draw bars for each user
        for ($i = 0; $i < count($users); $i++) {
            $bar_y = $margin[0] + ($i * ($bar_height + $bar_spacing));
            $bar_width = $counts[$i] * $scale_factor;
            ?>
            <text
                x="<?php echo $margin[3] - 10; ?>"
                y="<?php echo $bar_y + ($bar_height / 2) + 5; ?>"
                class="axis"
                text-anchor="end"
                dominant-baseline="middle"
            >
                <?php echo htmlspecialchars($users[$i]); ?>
            </text>
            <rect
                x="<?php echo $margin[3]; ?>"
                y="<?php echo $bar_y; ?>"
                width="<?php echo $bar_width; ?>"
                height="<?php echo $bar_height; ?>"
                class="bar"
                data-count="<?php echo $counts[$i]; ?>"
            />
            <text
                x="<?php echo $margin[3] + $bar_width + 5; ?>"
                y="<?php echo $bar_y + ($bar_height / 2) + 5; ?>"
                class="value-label"
                dominant-baseline="middle"
            >
                <?php echo $counts[$i]; ?>
            </text>
            <?php
        }
        ?>
    <?php else: ?>
        <!-- No data message -->
        <text
            x="<?php echo $svg_width / 2; ?>"
            y="<?php echo $svg_height / 2; ?>"
            class="no-data"
            text-anchor="middle"
        >
            No referral data available
        </text>
    <?php endif; ?>
</svg>