


<?php
// Include the SimplePie library
require_once('../backup/inc/simplepie/autoloader.php');

$servername = "localhost";
$dbname = "reader";
$username = "YOUR_DB_USER";
$password = "YOUR_DB_PASSWORD";

// Create a MySQLi connection
$mysqli = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

// Check if the RSS URL is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rss_url']) && !empty($_POST['rss_url'])) {
    $rss_url = $_POST['rss_url'];

    // Create a new SimplePie object
    $feed = new SimplePie\SimplePie();
    $feed->set_feed_url($rss_url);
    $feed->init();

    // Check for errors
    if ($feed->error()) {
        echo 'Error: ' . $feed->error();
        exit;
    }

    // Prepare SQL statement for inserting or updating data
    $stmt = $mysqli->prepare('REPLACE INTO rss_feeds_url (rss_url, rss_title, rss_description, rss_publisher, rss_image, rss_language) VALUES ( ?, ?, ?, ?, ?, ?)');

    // Fetch feed details
    $rss_title = $feed->get_title();
    $rss_description = $feed->get_description();
    $rss_publisher = $feed->get_title(); // Assuming publisher is the feed title
    $rss_image = $feed->get_image_url();
    $rss_language = $feed->get_language();



    // Bind parameters and execute
    $stmt->bind_param('ssssss', $rss_url, $rss_title, $rss_description, $rss_publisher, $rss_image, $rss_language);
    $stmt->execute();



    echo 'Feed metadata has been processed and stored in the database.';
}

// Fetch all RSS feeds from the database
$result = $mysqli->query('SELECT * FROM rss_feeds_url');

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>RSS Feed Processor</title>
</head>

<body>
    <h1>RSS Feed Processor</h1>

    <!-- Form for entering RSS Feed URL -->
    <form action="" method="post">
        <label for="rss_url">Enter RSS Feed URL:</label>
        <input type="text" id="rss_url" name="rss_url" required>
        <input type="submit" value="Process Feed">
    </form>

    <!-- Display stored RSS feeds -->
    <h2>Stored RSS Feeds</h2>
    <?php if ($result->num_rows > 0): ?>
        <table border="1">
            <thead>
                <tr>
                    <th>RSS URL</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Publisher</th>
                    <th>Image</th>
                    <th>Category</th>
                    <th>Language</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['rss_url']); ?></td>
                        <td><?php echo htmlspecialchars($row['rss_title']); ?></td>
                        <td><?php echo htmlspecialchars($row['rss_description']); ?></td>
                        <td><?php echo htmlspecialchars($row['rss_publisher']); ?></td>
                        <td><?php if (!empty($row['rss_image'])): ?><img src="<?php echo htmlspecialchars($row['rss_image']); ?>" alt="Feed Image" style="max-width:100px;"><?php endif; ?></td>
                        <td><?php echo htmlspecialchars($row['rss_category']); ?></td>
                        <td><?php echo htmlspecialchars($row['rss_language']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No RSS feeds found in the database.</p>
    <?php endif; ?>

    <?php
    // Free the result set
    $result->free();

    // Close the MySQLi connection
    $mysqli->close();
    ?>
</body>

</html>


