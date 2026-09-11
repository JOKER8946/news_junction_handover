
<?php

// Include SimplePie library
require_once(__DIR__ . '/../inc/simplepie/autoloader.php');

// Include database connection
include '../assets/php/db_connect.php';


function fetchHtmlContent($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.121 Safari/537.36');

    $html = curl_exec($ch);
    curl_close($ch);

    return $html;
}

function getFirstImageUrl($url)
{
    // Fetch the HTML content of the URL using cURL
    $html = fetchHtmlContent($url);
    if ($html === FALSE) {
        echo "Failed to fetch the URL content.\n";
        return null;
    }

    // Load the HTML content into a DOMDocument
    $doc = new DOMDocument();
    @$doc->loadHTML($html); // Suppress errors due to malformed HTML

    // Find the first <img> tag
    $imgTags = $doc->getElementsByTagName('img');
    if ($imgTags->length > 0) {
        $firstImg = $imgTags->item(0);
        $imgUrl = $firstImg->getAttribute('src');

        // Check if the image URL is empty or missing
        if (empty($imgUrl)) {
            echo "Image src attribute is empty or missing.\n";
            return null;
        }

        // Make the image URL absolute if it’s relative
        if (strpos($imgUrl, 'http') !== 0) {
            $parsedUrl = parse_url($url);
            $imgUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . '/' . ltrim($imgUrl, '/');
        }

        return $imgUrl;
    } else {
        echo "No image found in the HTML.\n";
        return null;
    }
}

function fetch_business_standard_image($url)
{

    // Set up cURL options
    $ch = curl_init($url);

    // Set the headers as an associative array
    $headers = [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
        'Accept-Encoding: gzip, deflate, br, zstd',
        'Accept-Language: en-US,en;q=0.9',
        'Cache-Control: no-cache',
        'Cookie: userUid=1728537242669-a5ca6796-c750-4a1e-b231-df307fea620e; _sid=MTcyODUzNzI0MjY3MC5icTc%3D; _cc_id=7d478f9881d57419250c84b6018df565; _gcl_au=1.1.1122819885.1728537245; _fbp=fb.1.1728537245555.590394025996360652; _ga=GA1.1.10526761.1728537246; WZRK_G=4a5933c8cab7473ba3140082d01d7953; _scor_uid=7f0388954ab348a4bd3d68ce53392684; panoramaId_expiry=1731320137322; panoramaId=8b90c705acaa6abbb51c0094f1ba4945a702bb8a4200481fafbc12babef0864b; panoramaIdType=panoIndiv; _sharedID=c04f607e-cbea-4db0-978d-d7867018f619; _sharedID_cst=zix7LPQsHA%3D%3D; _au_1d=AU1D-0100-001730715345-MFJ2CWIA-BRP0; _col_uuid=0b6160a5-e60c-4f03-a227-c9cd50d26aba-1sjac; __browsiSessionID=56cd45fe-045c-4f4d-9705-b864948193f0&false&DEFAULT&in&desktop-4.33.528&false; __browsiUID=b3963d2d-0474-4ea3-93f3-d47cf959bf6a; cto_bundle=16U0Xl8xNGhOQTY4azF2bXR6dlQyaGNZSE1BVHJRSnQ1bjZPWnh5TlNFWktobnc1SGxTZ3BRbWlKYzlKUGdNeGY2bWpOc3Qwc2pWUGJva01pbmZhQnNKdkp0RGZicXhQMiUyQmY0TWZrRmZZZ2JIJTJGTDJhSWs2Skg4bTg2bmVXeUp2NlFKbyUyQnI1cSUyQjIxVEwxMVhSUnZ2M0hndVlKSTklMkJUWXlTS1ZnQXBla3VCYkVKOE1JeGU5eThHYkdxRm9CbEQlMkJBYmk5UUwzTXRhZzdSSmtWVFQzZWMlMkZiRzREREpleE1vRzFqMEdYaiUyQk1NbWR5Z3QxcyUzRA; _ga_FVWZ0RM4DH=GS1.1.1730782577.3.0.1730782577.60.0.0; __gads=ID=6a23964e97fec96a:T=1728537244:RT=1730782580:S=ALNI_MZYLRNxbAx_y1QY4uc9UF0EYXaF5w; __gpi=UID=00000f3cbeee4b38:T=1728537244:RT=1730782580:S=ALNI_MYThSwlIGyOu6mp1_oesek2JBWN3Q; __eoi=ID=3ee9373ac52cb278:T=1728537244:RT=1730782580:S=AA-AfjaNuBiiRJNoYjzEjQNcHl1e; _ga_KRGL1M61LX=GS1.1.1730781216.5.1.1730782582.55.0.0',
        'Pragma: no-cache',
        'Priority: u=0, i',
        'Sec-CH-UA: "Chromium";v="130", "Google Chrome";v="130", "Not?A_Brand";v="99"',
        'Sec-CH-UA-Mobile: ?1',
        'Sec-CH-UA-Platform: "Android"',
        'Sec-Fetch-Dest: document',
        'Sec-Fetch-Mode: navigate',
        'Sec-Fetch-Site: none',
        'Sec-Fetch-User: ?1',
        'Upgrade-Insecure-Requests: 1',
        'User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36'
    ];

    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  // To return the response as a string
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);  // To follow redirects
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);  // Add the headers to the request
    curl_setopt($ch, CURLOPT_ENCODING, "gzip");  // Handle gzip encoding (if any)

    // Execute the cURL request
    $response = curl_exec($ch);

    // Check for errors
    if (curl_errno($ch)) {
        echo 'cURL Error: ' . curl_error($ch);
        curl_close($ch);
        return null;
    }

    // Check HTTP status code
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code != 200) {
        echo "Error: HTTP status code " . $http_code;
        curl_close($ch);
        return null;
    }

    // Parse the HTML to extract the og:image URL
    preg_match('/<meta property="og:image" content="([^"]+)"/', $response, $matches);

    // Check if og:image meta tag was found
    if (isset($matches[1])) {
        // Return the image URL from the content attribute
        $image_url = $matches[1];
        curl_close($ch);
        return $image_url;
    } else {
        echo "No og:image meta tag found.";
        curl_close($ch);
        return null;
    }
}

// Function to fetch RSS feeds and insert into database
function fetchAndInsertRssFeeds($conn)
{
    // Fetch RSS feed URLs from the database
    $sql = 'SELECT rss_id, rss_url FROM rss_feeds_url';
    $result = $conn->query($sql);

    // Check if the query was successful
    if (!$result) {
        die('Query Error: ' . $conn->error);
    }

    // Loop through each RSS feed URL
    while ($row = $result->fetch_assoc()) {
        $feedUrl = $row['rss_url'];
        $feedId = $row['rss_id'];

        // Initialize SimplePie
        $rss = new SimplePie();
        $rss->set_feed_url($feedUrl);
        $rss->enable_cache(false);
        $rss->init();
        $rss->handle_content_type();

        // Check for errors
        if ($rss->error()) {
            echo "Error fetching feed: " . $rss->error();
            continue;
        }

        // Loop through each item in the RSS feed
        foreach ($rss->get_items() as $item) {
            $title = $conn->real_escape_string($item->get_title());
            $url = $conn->real_escape_string($item->get_permalink());
            $description = $conn->real_escape_string($item->get_description());
            $content = $conn->real_escape_string($item->get_content());
            $author = $conn->real_escape_string($item->get_author() ? $item->get_author()->get_name() : '');
            $image = $conn->real_escape_string($item->get_enclosure() ? $item->get_enclosure()->get_link() : '');
            $date = $conn->real_escape_string($item->get_date('Y-m-d H:i:s'));

            // Process categories
            $categories = $item->get_categories();
            if ($categories && is_array($categories)) {
                $categoryNames = array_map(function ($cat) {
                    return htmlspecialchars($cat->get_label());
                }, $categories);
                $categoryString = implode(', ', $categoryNames);
            } else {
                $categoryString = ''; // Or you can set it to an empty string ''
            }

            // Prepare SQL to insert article into the database
            $sql = 'INSERT IGNORE INTO rss_feeds_articles (feed_id, url, title, description, content, author, image, category, date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('issssssis', $feedId, $url, $title, $description, $content, $author, $image, $categoryString, $date);

            // Execute the query
            if (!$stmt->execute()) {
                echo "Error inserting article: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}

// Function to update image URLs
function updateImageUrls($conn)
{
    $rssIds = [2, 3, 4, 5, 10];

    foreach ($rssIds as $rssId) {
        $query = "SELECT URL FROM rss_feeds_articles WHERE feed_id= $rssId AND (image IS NULL OR image = '')";
        // Fetch all articles that do not have an image and have feed_id = 18
        $result = $conn->query($query);

        if ($result) {
            // Check if there are rows returned
            if ($result->num_rows === 0) {
                echo "No articles found without images.\n";
                return; // Exit the function if no articles are found
            }

            // Iterate over each row
            while ($row = $result->fetch_assoc()) {
                $url = $row['URL']; // Make sure you use the correct key

                // Check if URL is valid
                if (!empty($url)) {
                    $imageUrl = fetch_business_standard_image($url);

                    if ($imageUrl) {
                        // Update the image URL in the database
                        $updateStmt = $conn->prepare("UPDATE rss_feeds_articles SET image = ? WHERE url = ?");
                        if ($updateStmt) {
                            $updateStmt->bind_param("ss", $imageUrl, $url);
                            $updateStmt->execute();
                            echo "Updated image URL for $url: $imageUrl<br>";
                            $updateStmt->close(); // Close the statement
                        } else {
                            echo "Failed to prepare update statement: " . $conn->error . "\n";
                        }
                    } else {
                        echo "No image found for $url.\n";
                    }
                } else {
                    echo "URL is empty for one of the articles.\n";
                }
            }
        } else {
            echo "Query failed: " . $conn->error . "\n";
        }
    }
}
// Call the functions
fetchAndInsertRssFeeds($conn);
updateImageUrls($conn);

// Close the database connection
$conn->close();

echo "Articles Updated Successfully at " . date('Y-m-d H:i:s') . "\n";
