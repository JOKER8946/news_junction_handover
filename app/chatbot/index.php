<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Kolkata');
require_once __DIR__ . '/../inc/php/db_config.php';

// Reset state if ?reset=1 is passed (clears old timing data)
if (isset($_GET['reset']) && $_GET['reset'] == '1') {
    @unlink('user_states.json');
    @unlink('last_cycle_start.txt');
    @unlink('bot_status.json');
    echo "<p style='color:green;font-weight:bold;'>State reset! Redirecting...</p>";
    header("Refresh: 1; url=index.php");
    exit;
}

// Initialize last_cycle_start.txt if it doesn't exist
$last_cycle_start_file = 'last_cycle_start.txt';
if (!file_exists($last_cycle_start_file)) {
    file_put_contents($last_cycle_start_file, time());
}

// Load topics
$topics = [];
if (file_exists('topics.json')) {
    $topics = json_decode(file_get_contents('topics.json'), true) ?: [];
}

// Topic to feed mapping
$topic_feed_map = [];

// Filter feeds by topic
$selected_topic = $_GET['topic'] ?? null;

$user_feeds = [
    'Ananya' => [
        'https://timesofindia.indiatimes.com/rssfeedstopstories.cms',
        'https://timesofindia.indiatimes.com/rssfeedmostread.cms',
        'https://timesofindia.indiatimes.com/rssfeeds/296589292.cms',
        'https://feeds.bbci.co.uk/news/world/asia/india/rss.xml',
        'https://www.indianewsnetwork.com/rss.en.all.xml',
        'https://www.oneindia.com/rss/news-fb.xml'
    ],
    'Soumya' => [
        'https://www.dnaindia.com/rss.xml',
        'https://www.thehindu.com/news/national/feeder/default.aspx',
        'https://www.hindustantimes.com/rss/topnews/rssfeed.xml',
        'https://timesofindia.indiatimes.com/rssfeedstopstories.cms',
        'https://timesofindia.indiatimes.com/rssfeedmostread.cms',
        'https://timesofindia.indiatimes.com/rssfeeds/296589292.cms'
    ],
    'Jenny' => [
        'https://feeds.bbci.co.uk/news/world/asia/india/rss.xml',
        'https://www.indianewsnetwork.com/rss.en.all.xml',
        'https://www.oneindia.com/rss/news-fb.xml',
        'https://www.dnaindia.com/rss.xml',
        'https://www.thehindu.com/news/national/feeder/default.aspx',
        'https://www.hindustantimes.com/rss/topnews/rssfeed.xml'
    ],
    'Harry' => [
        'https://timesofindia.indiatimes.com/rssfeedstopstories.cms',
        'https://timesofindia.indiatimes.com/rssfeedmostread.cms',
        'https://timesofindia.indiatimes.com/rssfeeds/296589292.cms',
        'https://feeds.bbci.co.uk/news/world/asia/india/rss.xml',
        'https://www.indianewsnetwork.com/rss.en.all.xml',
        'https://www.oneindia.com/rss/news-fb.xml'
    ],
    'Einstein' => [
        'https://www.dnaindia.com/rss.xml',
        'https://www.thehindu.com/news/national/feeder/default.aspx',
        'https://www.hindustantimes.com/rss/topnews/rssfeed.xml',
        'https://timesofindia.indiatimes.com/rssfeedstopstories.cms',
        'https://timesofindia.indiatimes.com/rssfeedmostread.cms',
        'https://timesofindia.indiatimes.com/rssfeeds/296589292.cms'
    ]
];

// User and posting configuration
$users = [
    'Ananya' => [
        'userId' => 0, // Set after running setup_bots.php
        'image' => 'Ananya.jpg',
        'topics' => [
            'fashion' => '#FashionTrends #StyleInspo',
            'food' => '#FoodieAdventures #DeliciousEats',
            'nature' => '#NatureLover #OutdoorLife',
            'artificial intelligence' => '#AIInnovation #FutureTech'
        ],
        'hashtags' => ['#Style', '#OOTD', '#FoodBlog', '#Yummy', '#NaturePhotography', '#AI', '#MachineLearning'],
        'relative_offset' => 0,
    ],
    'Soumya' => [
        'userId' => 0,
        'image' => 'Soumya.jpg',
        'topics' => [
            'travel' => '#Wanderlust #ExploreTheWorld',
            'gadgets' => '#TechGadgets #Innovation'
        ],
        'hashtags' => ['#TravelGram', '#Techie', '#GadgetLover', '#Wanderlust', '#Explore'],
        'relative_offset' => 30 * 60,
    ],
    'Jenny' => [
        'userId' => 0,
        'image' => 'Jenny.jpg',
        'topics' => [
            'news' => '#BreakingNews #CurrentEvents',
            'technology' => '#TechUpdates #DigitalWorld',
            'nature' => '#GreenLiving #EcoFriendly',
            'formula1' => '#F1Racing #Motorsport',
            'science' => '#ScienceFacts #Discovery'
        ],
        'hashtags' => ['#News', '#Tech', '#F1', '#Science', '#Nature', '#WorldNews'],
        'relative_offset' => 60 * 60,
    ],
    'Harry' => [
        'userId' => 0,
        'image' => 'Harry.jpg',
        'topics' => [
            'all kinds' => '#DailyDigest #VariedContent'
        ],
        'hashtags' => ['#Funny', '#Humor', '#Satire', '#LOL', '#Gaming', '#Movies', '#Sports'],
        'relative_offset' => 90 * 60,
    ],
    'Einstein' => [
        'userId' => 0,
        'image' => 'Einstein.jpg',
        'topics' => [
            'complex mathematics' => '#ComplexMath #Physics',
            'physics' => '#QuantumPhysics #TheoreticalPhysics'
        ],
        'hashtags' => ['#Mathematics', '#Physics', '#Quantum', '#TheoryOfRelativity', '#Science'],
        'relative_offset' => 120 * 60,
    ]
];

// Auto-populate bot userIds from nj_cream.user by email
foreach ($users as $botName => &$botData) {
    $botEmail = strtolower($botName) . '.bot@newsjunction.net';
    $lookup = $creamdb->prepare("SELECT id FROM user WHERE email = ?");
    $lookup->bind_param('s', $botEmail);
    $lookup->execute();
    $lookupResult = $lookup->get_result();
    if ($row = $lookupResult->fetch_assoc()) {
        $botData['userId'] = (int)$row['id'];
    }
    $lookup->close();
}
unset($botData); // break reference

$total_cycle_time = 150 * 60; // 150 minutes (5 users * 30 minutes each)

$user_states_file = 'user_states.json';
$bot_status_file = 'bot_status.json';
$current_time = time();
$should_run_bot = false;
$next_post_in_seconds = $total_cycle_time; // Default to max cycle time
$current_posting_user = null; // Initialize to null

// Load user states (last post time for each user)
$user_states = [];
if (file_exists($user_states_file)) {
    $user_states = json_decode(file_get_contents($user_states_file), true) ?: [];
}

// Initialize last_post_time for new users or if file is empty
foreach ($users as $username => $data) {
    if (!isset($user_states[$username]['last_post_time'])) {
        $user_states[$username]['last_post_time'] = 0;
    }
}

// Check bot status
$bot_status = 'running'; // default status
if (file_exists($bot_status_file)) {
    $status_data = json_decode(file_get_contents($bot_status_file), true);
    $bot_status = $status_data['status'] ?? 'running';
}

// Determine which user should post next if bot is running
if ($bot_status === 'running') {
    // Load last cycle start time
    $last_cycle_start_file = 'last_cycle_start.txt';
    $last_cycle_start_time = 0;
    if (file_exists($last_cycle_start_file)) {
        $last_cycle_start_time = (int)file_get_contents($last_cycle_start_file);
    }

    $next_user_to_post_name = null;
    $time_to_next_post = PHP_INT_MAX;

    // Find the last time Ananya posted to establish the cycle start
    $ananya_last_post_time = $user_states['Ananya']['last_post_time'] ?? 0;

    // Calculate the start of the current cycle based on Ananya's last post
    // If Ananya hasn't posted, the cycle starts now (current_time).
    // Otherwise, find the most recent cycle start that is less than or equal to current_time.
    $current_cycle_start_base = $ananya_last_post_time;
    while ($current_cycle_start_base + $total_cycle_time < $current_time) {
        $current_cycle_start_base += $total_cycle_time;
    }
    // If Ananya has never posted, set the initial cycle start to current time
    if ($ananya_last_post_time === 0) {
        $current_cycle_start_base = $current_time;
    }


    // Iterate through users to find who is next due to post
    foreach ($users as $username => $data) {
        $expected_post_time_in_current_cycle = $current_cycle_start_base + $data['relative_offset'];
        $last_post_time_for_user = $user_states[$username]['last_post_time'] ?? 0;

        // If the user has already posted in this cycle, consider their next post in the *next* cycle
        if ($last_post_time_for_user >= $expected_post_time_in_current_cycle) {
            $expected_post_time_in_current_cycle += $total_cycle_time;
        }

        // If this user is due now or in the future, and is the earliest, mark them
        if ($expected_post_time_in_current_cycle < $time_to_next_post) {
            $time_to_next_post = $expected_post_time_in_current_cycle;
            $next_user_to_post_name = $username;
        }
    }

    if ($next_user_to_post_name !== null && $current_time >= $time_to_next_post) {
        $should_run_bot = true;
        $current_posting_user = $next_user_to_post_name;
        // Update last cycle start time if Ananya is posting
        if ($current_posting_user === 'Ananya') {
            file_put_contents($last_cycle_start_file, $current_time);
        }
        $next_post_in_seconds = 1; // Force a quick refresh after a post
    } else { // No one is due to post right now
        // The next post will be by the next user in the sequence, which is 5 minutes away from the previous post.
        // So, the countdown should always reset to 5 minutes.
        $next_post_in_seconds = 30 * 60; // 30 minutes
    }
}



function generate_image_unsplash($prompt_text, $api_key) {
    $log_file = 'log.txt';
    file_put_contents($log_file, "--- New Unsplash Image Generation Request ---\n", FILE_APPEND);
    file_put_contents($log_file, "API Key: " . $api_key . "\n", FILE_APPEND);
    file_put_contents($log_file, "Prompt: " . $prompt_text . "\n", FILE_APPEND);

    if (empty($api_key)) {
        file_put_contents($log_file, "Error: Unsplash API key is empty.\n", FILE_APPEND);
        return '';
    }

    $url = "https://api.unsplash.com/search/photos?query=" . urlencode($prompt_text) . "&client_id=" . $api_key;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    file_put_contents($log_file, "Unsplash API Response Code: " . $http_code . "\n", FILE_APPEND);
    file_put_contents($log_file, "Unsplash API Response: " . $result . "\n", FILE_APPEND);

    if ($http_code == 200) {
        $data = json_decode($result, true);
        if (!empty($data['results'])) {
            return $data['results'][0]['urls']['regular'];
        }
    }

    return '';
}

// Run the bot if needed
function fetch_random_rss_item($feeds) {
    // Shuffle the feeds to ensure randomness
    shuffle($feeds);
    $random_feed_url = $feeds[0];
    
    // Check if this feed was recently problematic
    $problematic_feeds_file = 'problematic_feeds.json';
    $problematic_feeds = [];
    if (file_exists($problematic_feeds_file)) {
        $problematic_feeds = json_decode(file_get_contents($problematic_feeds_file), true) ?: [];
        // Remove feeds that were marked problematic more than 1 hour ago
        $problematic_feeds = array_filter($problematic_feeds, function($timestamp) {
            return (time() - $timestamp) < 3600; // 1 hour
        });
    }
    
    // Skip if this feed was recently problematic
    if (isset($problematic_feeds[$random_feed_url])) {
        return null;
    }
    
    // Add error logging
    $log_file = 'rss_errors.log';
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Attempting to fetch: $random_feed_url\n", FILE_APPEND);
    
    // Set up context with timeout
    $context = stream_context_create([
        'http' => [
            'timeout' => 10, // 10 second timeout
            'user_agent' => 'Mozilla/5.0 (compatible; RSSBot/1.0)'
        ]
    ]);
    
    $xml_string = @file_get_contents($random_feed_url, false, $context);
    if ($xml_string === false) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Failed to fetch content from: $random_feed_url\n", FILE_APPEND);
        // Mark this feed as problematic
        $problematic_feeds[$random_feed_url] = time();
        file_put_contents($problematic_feeds_file, json_encode($problematic_feeds));
        return null;
    }
    
    // Check if the response is actually XML/RSS and not HTML
    if (strpos($xml_string, '<?xml') === false && strpos($xml_string, '<rss') === false && strpos($xml_string, '<feed') === false) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Response is not valid XML/RSS from: $random_feed_url\n", FILE_APPEND);
        // Mark this feed as problematic
        $problematic_feeds[$random_feed_url] = time();
        file_put_contents($problematic_feeds_file, json_encode($problematic_feeds));
        return null;
    }
    
    // Suppress XML parsing warnings and handle errors manually
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($xml_string);
    $xml_errors = libxml_get_errors();
    libxml_clear_errors();
    
    if ($xml === false || !empty($xml_errors)) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Failed to parse XML from: $random_feed_url\n", FILE_APPEND);
        foreach ($xml_errors as $error) {
            file_put_contents($log_file, "  XML Error: " . $error->message . "\n", FILE_APPEND);
        }
        // Mark this feed as problematic
        $problematic_feeds[$random_feed_url] = time();
        file_put_contents($problematic_feeds_file, json_encode($problematic_feeds));
        return null;
    }
    
    if (!isset($xml->channel->item) || empty($xml->channel->item)) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - No items found in RSS feed: $random_feed_url\n", FILE_APPEND);
        return null;
    }
    
    // Build a proper list of items without casting to array (preserve SimpleXMLElement objects)
    $itemsList = [];
    foreach ($xml->channel->item as $it) {
        $itemsList[] = $it;
    }
    if (empty($itemsList)) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Empty items array from: $random_feed_url\n", FILE_APPEND);
        return null;
    }
    
    $random_item_index = array_rand($itemsList);
    $selected_item = $itemsList[$random_item_index];
    
    // Debug: Log the selected item properties
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Selected item from $random_feed_url:\n", FILE_APPEND);
    file_put_contents($log_file, "  Title: " . (string)$selected_item->title . "\n", FILE_APPEND);
    file_put_contents($log_file, "  Link: " . (string)$selected_item->link . "\n", FILE_APPEND);
    file_put_contents($log_file, "  Description: " . substr((string)$selected_item->description, 0, 100) . "...\n", FILE_APPEND);
    
    // Validate that the item has required properties
    if (empty($selected_item->title) || empty($selected_item->link) || empty($selected_item->description)) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Item missing required properties (title/link/description) from: $random_feed_url\n", FILE_APPEND);
        return null;
    }
    
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Successfully fetched item from: $random_feed_url\n", FILE_APPEND);
    
    // Return both the item and the feed URL
    return ['item' => $selected_item, 'feed_url' => $random_feed_url];
}

// Run the bot if needed
if ($should_run_bot && $current_posting_user !== null) {
    $result = null;
    $max_attempts = 5; // Try up to 5 different feeds

    $user_data = $users[$current_posting_user];
    $user_topics = $user_data['topics'];
    $user_image = 'user_images/' . $user_data['image']; // Path to user image

    // Select a random topic from the user's assigned topics
    $selected_user_topic = $user_topics[array_rand($user_topics)];

    // Get user-specific feeds
    $user_specific_feeds = $user_feeds[$current_posting_user];

    for ($attempt = 0; $attempt < $max_attempts && $result === null; $attempt++) {
        $result = fetch_random_rss_item($user_specific_feeds);
    }

    if ($result && $result['item'] !== null) {
        $item = $result['item'];
        $feed_url = $result['feed_url'];

        // Extract a user-friendly feed name
        $feed_name = parse_url($feed_url, PHP_URL_HOST); // Get the domain
        $feed_name = str_replace('www.', '', $feed_name); // Remove www.
        $feed_name = explode('.', $feed_name)[0]; // Get the first part of the domain
        $feed_name = ucfirst($feed_name); // Capitalize first letter

        $posts = [];
        if (file_exists('posts.json')) {
            $posts = json_decode(file_get_contents('posts.json'), true) ?: [];
        }

        // Check if item properties exist before accessing them
        if (isset($item->title) && isset($item->link) && isset($item->description)) {
            $topic = (string)$item->title;
            $link = (string)$item->link;
            $description = (string)$item->description;

            // Generate content using intelligent templates
            $caption_templates = [
                " $topic - This is a game-changer that's sparking intense debate across the internet. What's your take on this development?",
                " $topic - This story is making waves and generating significant discussion. The implications could be huge!",
                " $topic - This is the kind of news that gets everyone talking. The conversation is heating up!",
                " $topic - This development is creating quite a buzz. The impact could be massive!",
                " $topic - This story is trending everywhere and for good reason. What do you think?",
                " $topic - This is exactly the kind of news that changes everything. The discussion is intense!",
                " $topic - This revelation is shaking things up in a big way. The implications are huge!",
                " $topic - This news is spreading like wildfire. Everyone's talking about it!"
            ];

            $hashtag_templates = [
                "#BreakingNews #LatestUpdate #Trending",
                "#HotNews #Viral #MustRead",
                "#Breaking #Update #TrendingNow",
                "#Latest #News #ViralStory",
                "#BreakingNews #Trending #MustSee",
                "#HotOffThePress #Viral #Breaking",
                "#LatestUpdate #Trending #NewsAlert",
                "#BreakingStory #Viral #MustRead"
            ];

            $image_desc_templates = [
                "A professional news image representing $topic with modern design elements",
                "A dynamic visual representation of $topic with engaging graphics",
                "A compelling news image showcasing $topic with professional styling",
                "An eye-catching visual for $topic with contemporary design",
                "A striking news graphic representing $topic with bold typography",
                "A modern news image featuring $topic with clean, professional layout"
            ];

            // Select random templates
            $caption = $caption_templates[array_rand($caption_templates)];
            $hashtag = $hashtag_templates[array_rand($hashtag_templates)];
            $image_desc = $image_desc_templates[array_rand($image_desc_templates)];

            // Normalize helper: ensure full sentence and avoid dangling punctuation
            $normalize_sentence = function($text) {
                $text = trim(preg_replace('/\s+/', ' ', (string)$text));
                // Replace common colon patterns with periods
                $text = preg_replace('/\s*:\s*/', '. ', $text);
                if ($text !== '' && !preg_match('/[\.!?]$/', $text)) {
                    $text .= '.';
                }
                return $text;
            };

            // Generate 3-4 line detailed caption with Ollama (phi3). Fallback to templates if unavailable
            $detail_caption = '';
            $detail_prompt = "You are a concise social media writer. Generate a short social media post (3 to 4 complete sentences, no colons, no hashtags, no quotes) about the following main topic/hashtag. Use the provided RSS context for inspiration, but ensure the core message is about the main topic/hashtag.\n\nTitle\n$topic\n\nDescription\n$description\n\nSuggested Hashtags\n$hashtag\n\nImage Description\n$image_desc\n\nOutput only the 3-4 lines, one per line.";

            $gen_ch = curl_init('http://localhost:11434/api/generate');
            $gen_payload = [
                'model' => 'phi3',
                'prompt' => $detail_prompt,
                'stream' => false
            ];
            curl_setopt($gen_ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($gen_ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($gen_ch, CURLOPT_POST, 1);
            curl_setopt($gen_ch, CURLOPT_POSTFIELDS, json_encode($gen_payload));
            curl_setopt($gen_ch, CURLOPT_TIMEOUT, 7);
            $gen_result = curl_exec($gen_ch);
            $gen_http = curl_getinfo($gen_ch, CURLINFO_HTTP_CODE);
            curl_close($gen_ch);

            if ($gen_http === 200) {
                $gen_resp = json_decode($gen_result, true);
                $maybe_detail = trim($gen_resp['response'] ?? '');
                if (!empty($maybe_detail)) {
                    // Post-process: split lines, normalize sentences, drop empty lines
                    $lines = array_filter(array_map('trim', preg_split('/\r?\n/', $maybe_detail)));
                    $norm = [];
                    foreach ($lines as $ln) {
                        $ln = preg_replace('/\s*:\s*/', '. ', $ln);
                        $norm[] = $normalize_sentence($ln);
                        if (count($norm) >= 4) break;
                    }
                    $detail_caption = implode("\n", $norm);
                }
            }

            if (empty($detail_caption)) {
                // Fallback: construct a 3-4 line detail caption
                $detail_lines = [];
                $detail_lines[] = $normalize_sentence(" $topic " . mb_substr($caption, 0, 120));
                $detail_lines[] = $normalize_sentence( mb_substr(strip_tags($description), 0, 140));
                // if (!empty($image_desc)) {
                //     $detail_lines[] = $normalize_sentence("The visual shows " . mb_substr($image_desc, 0, 140));
                // }
                // $detail_lines[] = $normalize_sentence("Join the conversation and share your thoughts");
                $detail_caption = implode("\n", array_slice($detail_lines, 0, 4));
            }

            // Customize based on selected user topic
            if ($selected_user_topic === 'artificial intelligence') {
                $caption = $normalize_sentence("An AI update on $topic. This breakthrough highlights how quickly the field is moving.");
                $hashtag = "#AI #ArtificialIntelligence #TechNews #Innovation";
            } elseif ($selected_user_topic === 'fashion') {
                $caption = $normalize_sentence("A fashion update on $topic. This is a must-see for all style lovers.");
                $hashtag = "#Fashion #StyleInspo #OOTD #Trends";
            } elseif ($selected_user_topic === 'food') {
                $caption = $normalize_sentence("A delicious update on $topic. Foodies, take note!");
                $hashtag = "#Food #Foodie #Delicious #Eats";
            } elseif ($selected_user_topic === 'nature') {
                $caption = $normalize_sentence("A look at the beauty of nature: $topic. #NatureLover");
                $hashtag = "#Nature #Outdoors #GreenLiving";
            } elseif ($selected_user_topic === 'travel') {
                $caption = $normalize_sentence("Wanderlust calling! Check out this travel story: $topic.");
                $hashtag = "#Travel #Wanderlust #Explore";
            } elseif ($selected_user_topic === 'gadgets') {
                $caption = $normalize_sentence("The latest in tech gadgets: $topic. #TechGadgets");
                $hashtag = "#Gadgets #Tech #Innovation";
            } elseif ($selected_user_topic === 'news') {
                $caption = $normalize_sentence("Breaking news: $topic. Stay informed.");
                $hashtag = "#News #BreakingNews #CurrentEvents";
            } elseif ($selected_user_topic === 'technology') {
                $caption = $normalize_sentence("A technology update on $topic. The future is now.");
                $hashtag = "#Technology #TechUpdates #DigitalWorld";
            } elseif ($selected_user_topic === 'formula1') {
                $caption = $normalize_sentence("F1 update: $topic. #F1 #Motorsport");
                $hashtag = "#F1 #Formula1 #Racing";
            } elseif ($selected_user_topic === 'science') {
                $caption = $normalize_sentence("A scientific discovery: $topic. #ScienceFacts");
                $hashtag = "#Science #Discovery #Research";
            } elseif ($selected_user_topic === 'complex mathematics') {
                $caption = $normalize_sentence("A deep dive into complex mathematics: $topic. #Mathematics");
                $hashtag = "#Math #ComplexMath #Physics";
            } elseif ($selected_user_topic === 'physics') {
                $caption = $normalize_sentence("Exploring the world of physics: $topic. #QuantumPhysics");
                $hashtag = "#Physics #Quantum #TheoreticalPhysics";
            }

            // Customize based on feed source (keep this as it adds context)
            $domain = parse_url($feed_url, PHP_URL_HOST);
            $domain_key = str_replace(['www.', '.com', '.org', '.co.uk'], '', $domain);
            $domain_key = explode('.', $domain_key)[0];

            if ($domain_key === 'nytimes') {
                $caption = $normalize_sentence("Reporting from The New York Times on $topic This story matters and is worth your attention");
                $hashtag = "#NYTimes #BreakingNews #TrustedSource";
            } elseif ($domain_key === 'bbci') {
                $caption = $normalize_sentence("BBC News covers $topic The piece offers a global perspective on an important development");
                $hashtag = "#BBCNews #GlobalNews #Breaking";
            } elseif ($domain_key === 'nasa') {
                $caption = $normalize_sentence("NASA highlights $topic The story explores the frontiers of space and science");
                $hashtag = "#NASA #Space #Discovery #Science";
            } elseif ($domain_key === 'sciencedaily') {
                $caption = $normalize_sentence("ScienceDaily reports on $topic This research illustrates how knowledge keeps advancing");
                $hashtag = "#ScienceDaily #Research #Innovation #Science";
            } elseif ($domain_key === 'theverge') {
                $caption = $normalize_sentence("The Verge covers $topic The story connects technology culture and innovation");
                $hashtag = "#TheVerge #Tech #Innovation #Culture";
            } elseif ($domain_key === 'apnews') {
                $caption = $normalize_sentence("Associated Press reports on $topic The coverage focuses on verified facts and context");
                $hashtag = "#APNews #Breaking #Reliable #News";
            } elseif ($domain_key === 'nature') {
                $caption = $normalize_sentence("Nature shares findings about $topic The discovery reshapes how we understand this area");
                $hashtag = "#Nature #Science #Discovery #Research";
            } elseif ($domain_key === 'technologyreview') {
                $caption = $normalize_sentence("MIT Technology Review discusses $topic The piece looks at what the future may bring");
                $hashtag = "#MITTechReview #Future #Innovation #Technology";
            }

            $image_url = '';
            if (!empty($image_desc)) {
                $unsplash_api_key = 'YOUR_API_TOKEN'; // Replace with your Unsplash API key
                $image_url = generate_image_unsplash($image_desc, $unsplash_api_key);
            }

            // Use the current posting user's name as bot_name
            $bot_name = $current_posting_user;

            // Generate a unique hashtag based on the topic and feed
            $topic_words = explode(' ', strtolower($topic));
            $topic_words = array_filter($topic_words, function($word) {
                return strlen($word) > 3 && !in_array($word, ['the', 'and', 'for', 'with', 'this', 'that', 'from', 'have', 'will', 'been', 'they', 'their', 'said', 'what', 'when', 'where', 'here', 'there', 'news', 'report', 'study', 'research', 'scientists', 'experts', 'announced', 'discovered', 'found', 'revealed', 'according', 'latest', 'breaking']);
            });

            // Create multiple hashtags
            $topic_hashtags = [];
            if (!empty($topic_words)) {
                $topic_hashtags[] = '#' . implode('', array_slice($topic_words, 0, 2));
                if (count($topic_words) > 2) {
                    $topic_hashtags[] = '#' . implode('', array_slice($topic_words, 2, 2));
                }
            }

            // Add feed-specific hashtags
            $feed_hashtags = [
                'nytimes' => ['#NYTimes', '#BreakingNews'],
                'bbci' => ['#BBCNews', '#Breaking'],
                'nasa' => ['#NASA', '#Space'],
                'sciencedaily' => ['#ScienceDaily', '#Research'],
                'theverge' => ['#TheVerge', '#Tech'],
                'apnews' => ['#APNews', '#Breaking'],
                'nature' => ['#Nature', '#Science'],
                'technologyreview' => ['#TechReview', '#Innovation']
            ];

            $domain = parse_url($feed_url, PHP_URL_HOST);
            $domain_key = str_replace(['www.', '.com', '.org', '.co.uk'], '', $domain);
            $domain_key = explode('.', $domain_key)[0];

            $additional_hashtags = $feed_hashtags[$domain_key] ?? ['#News', '#Update'];

            // Get user-specific hashtags
            $user_hashtags = $user_data['hashtags'] ?? [];
            shuffle($user_hashtags);
            $random_user_hashtags = array_slice($user_hashtags, 0, 3); // pick 3 random hashtags

            // Combine all hashtags
            $all_hashtags = array_merge($topic_hashtags, $additional_hashtags, $random_user_hashtags);
            $all_hashtags = array_unique($all_hashtags); // remove duplicates
            $final_hashtag = $hashtag . ' ' . implode(' ', $all_hashtags);

            // Save to posts.json
            $post = [
                'type' => 'generated',
                'topic' => $selected_user_topic, // Use the selected user topic
                'caption' => $caption,
                'hashtag' => $final_hashtag,
                'image' => $image_url,
                'image_desc' => $image_desc,
                'detail_caption' => $detail_caption,
                'bot_name' => $current_posting_user, // Use the current posting user's name
                'avatar' => $user_image, // Add user's specific avatar
                'feed_source' => $feed_name,
                'created_at' => date('Y-m-d H:i:s')
            ];
            array_unshift($posts, $post);
            file_put_contents('posts.json', json_encode($posts, JSON_PRETTY_PRINT));

            // Update last post time for the current user
            $user_states[$current_posting_user]['last_post_time'] = $current_time;
            file_put_contents($user_states_file, json_encode($user_states, JSON_PRETTY_PRINT));

            // Download image locally and insert into reader_stream
            $localMediaPath = '';
            if (!empty($post['image'])) {
                $imageUrl = $post['image'];
                $imageContent = null;

                if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    $imageContent = @file_get_contents($imageUrl);
                } elseif (file_exists($imageUrl)) {
                    $imageContent = @file_get_contents($imageUrl);
                }

                if ($imageContent !== false && $imageContent !== null) {
                    $uploadsDir = __DIR__ . '/../uploads';
                    $filename = 'bot_' . time() . '_' . uniqid() . '.jpg';
                    $savePath = $uploadsDir . '/' . $filename;
                    file_put_contents($savePath, $imageContent);
                    $localMediaPath = 'uploads/' . $filename;
                }
            }

            // Build chat content: detail caption + hashtags
            $chatContent = $post['detail_caption'];
            if (!empty($post['hashtag'])) {
                $chatContent .= "\n\n" . $post['hashtag'];
            }

            // Build metadata JSON with article link info
            $metadataJson = json_encode([
                'metaTitle' => $topic,
                'metaUrl' => $link,
                'metaDomain' => $feed_name
            ]);

            // Insert into reader_stream
            $botUserId = $user_data['userId'] ?? 0;
            if ($botUserId > 0) {
                $stmtStream = $readerdb->prepare(
                    "INSERT INTO reader_stream (userId, chat, mediaPath, metadata, visibility, deleteFlag, pincode)
                     VALUES (?, ?, ?, ?, 'public', 0, 0)"
                );
                $stmtStream->bind_param('isss', $botUserId, $chatContent, $localMediaPath, $metadataJson);
                if ($stmtStream->execute()) {
                    file_put_contents('log.txt', "Stream DB insert OK (id={$readerdb->insert_id}) for bot {$current_posting_user}\n", FILE_APPEND);
                } else {
                    file_put_contents('log.txt', "Stream DB insert FAILED: {$stmtStream->error}\n", FILE_APPEND);
                }
                $stmtStream->close();
            } else {
                file_put_contents('log.txt', "Skipped DB insert: no userId for bot {$current_posting_user}. Run setup_bots.php first.\n", FILE_APPEND);
            }
        }
    }
}

// Load posts for display
$posts = [];
if (file_exists('posts.json')) {
    $posts = json_decode(file_get_contents('posts.json'), true) ?: [];
}

function friendly_date($datetime) {
    $dt = new DateTime($datetime);
    return $dt->format('M d, Y H:i');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Bot Live Feed</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="<?= $next_post_in_seconds ?>"> <!-- Refresh based on next post time -->
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; }
        .feed-container { max-width: 650px; margin: 20px auto; padding: 0 15px; }
        .post {
            background: #fff;
            margin: 24px 0;
            padding: 20px 24px 20px 80px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            position: relative;
            min-height: 80px;
            transition: box-shadow 0.2s;
        }
        .post:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.13);
        }
        .avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            position: absolute;
            left: 16px;
            top: 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
            object-fit: cover;
        }
        .user-info {
            display: flex;
            align-items: center;
            margin-bottom: 6px;
            flex-wrap: wrap;
        }
        .username {
            font-weight: bold;
            color: #2a2a2a;
            margin-right: 10px;
        }
        .topic {
            color: #4a90e2;
            font-size: 0.95em;
            margin-right: 10px;
        }
        .time {
            color: #aaa;
            font-size: 0.9em;
        }
        .summary {
            margin-top: 8px;
            font-size: 1.08em;
            color: #222;
        }
        .actions {
            display: flex;
            gap: 16px;
            margin-top: 16px;
            flex-wrap: wrap;
        }
        .action-btn {
            background: none;
            border: none;
            color: #4a90e2;
            font-size: 1em;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 6px;
            transition: background 0.15s;
        }
        .action-btn:hover {
            background: #eaf4ff;
        }
        .comment-section {
            margin-top: 18px;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
        .comment-input {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1em;
            margin-top: 6px;
        }
        .comment-btn {
            margin-top: 6px;
            background: #4a90e2;
            color: #fff;
            border: none;
            padding: 6px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1em;
        }
        .comment-btn:active {
            background: #357ab8;
        }
        .refresh-info {
            position: fixed;
            top: 10px;
            right: 10px;
            background: rgba(74, 144, 226, 0.9);
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            z-index: 1000;
            backdrop-filter: blur(10px);
        }
        .new-post {
            animation: slideIn 0.5s ease-out;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Image Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1001;
            padding-top: 50px;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.9);
        }

        .modal-content {
            margin: auto;
            display: block;
            width: 90%;
            max-width: 700px;
        }

        .close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            transition: 0.3s;
        }

        .close:hover,
        .close:focus {
            color: #bbb;
            text-decoration: none;
            cursor: pointer;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .feed-container {
                margin: 10px auto;
                padding: 0 10px;
            }
            .post {
                padding: 15px 15px 15px 65px;
            }
            .avatar {
                width: 48px;
                height: 48px;
                left: 10px;
                top: 15px;
            }
            .user-info {
                margin-left: 0;
            }
            .username {
                font-size: 0.95em;
            }
            .topic {
                font-size: 0.9em;
            }
            .time {
                font-size: 0.8em;
            }
            .actions {
                gap: 10px;
            }
            .action-btn {
                font-size: 0.9em;
                padding: 4px 6px;
            }
            .post-image img {
                max-width: 100%;
            }
            .control-btn, #botStatus {
                padding: 6px 12px;
                font-size: 12px;
            }
            .topics-bar a {
                margin: 0 5px;
            }
        }
    </style>
    <script>
        // Countdown timer for refresh
        let countdown = <?= $next_post_in_seconds ?>;
        let countdownInterval;
        
        function updateCountdown() {
            const countdownElement = document.getElementById('countdown');
            const refreshInfo = document.querySelector('.refresh-info');
            
            if (refreshInfo && refreshInfo.textContent.includes('Paused')) {
                // Bot is paused, stop countdown
                if (countdownInterval) {
                    clearInterval(countdownInterval);
                }
                return;
            }
            
            countdown--;
            if (countdownElement) {
                countdownElement.textContent = countdown;
            }
            // No need to reset countdown here, page will refresh
        }
        
        // Start countdown timer
        function startCountdown() {
            if (countdownInterval) {
                clearInterval(countdownInterval);
            }
            countdownInterval = setInterval(updateCountdown, 1000);
        }
        
        // Initialize countdown
        startCountdown();
        
        // Add animation class to new posts
        document.addEventListener('DOMContentLoaded', function() {
            try {
                const posts = document.querySelectorAll('.post');
                posts.forEach((post, index) => {
                    if (index === 0) {
                        post.classList.add('new-post');
                    }
                });

                // Image modal logic
                var modal = document.getElementById('myModal');
                var modalImg = document.getElementById("img01");
                document.body.addEventListener('click', function(event) {
                    if (event.target.tagName === 'IMG' && event.target.closest('.post-image')) {
                        modal.style.display = "block";
                        modalImg.src = event.target.src;
                    }
                });

                var span = document.getElementsByClassName("close")[0];
                span.onclick = function() { 
                    modal.style.display = "none";
                }

                // Topic filtering logic
                const topicLinks = document.querySelectorAll('.topics-bar a');
                topicLinks.forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        const topic = this.getAttribute('href').split('=')[1];
                        fetchPosts(topic);
                    });
                });
            } catch (e) {
                alert("Javascript Error: " + e.message);
                console.error("Javascript Error: ", e);
            }
        });

        function fetchPosts(topic = null) {
            fetch('filter_posts.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ topic: topic })
            })
            .then(response => response.json())
            .then(posts => {
                renderPosts(posts);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error fetching posts.');
            });
        }

        function renderPosts(posts) {
            const feedContainer = document.querySelector('.feed-container');
            const postsContainer = document.getElementById('posts-container');
            postsContainer.innerHTML = ''; // Clear existing posts

            if (posts.length === 0) {
                postsContainer.innerHTML = '<p style="text-align: center;">No posts found for this topic.</p>';
                return;
            }

            posts.forEach((post, index) => {
                const postElement = document.createElement('div');
                postElement.className = `post ${index === 0 ? 'new-post' : ''}`;
                postElement.onclick = function() { openInPostman(post); };
                postElement.style.cursor = 'pointer';

                let imageHtml = '';
                if (post.image) {
                    imageHtml = `
                        <div class="post-image">
                            <img src="${post.image}" alt="Post Image" style="width: 100%; max-width: 500px; border-radius: 12px; margin: 15px 0;">
                            <div class="image-caption" style="color:#444; margin-top:6px;">${post.image_desc}</div>
                            <div class="caption" style="font-size: 1.1em; color: #2a2a2a; margin: 10px 0 8px; line-height: 1.6;">${post.caption}</div>
                            <div class="detail-caption" style="color:#333; line-height:1.55; margin: 6px 0 8px;">${strip_tags(post.detail_caption).replace(/\n/g, '<div></div>')}</div>
                            <div class="hashtags" style="color: #4a90e2; font-weight: bold; margin: 6px 0 2px;">${post.hashtag}</div>
                        </div>
                    `;
                } else {
                    imageHtml = `
                        <div class="content">
                            <div class="caption" style="font-size: 1.1em; color: #2a2a2a; margin: 15px 0; line-height: 1.6;">${post.caption}</div>
                            <div class="hashtags" style="color: #4a90e2; font-weight: bold; margin: 10px 0;">${post.hashtag}</div>
                        </div>
                    `;
                }

                postElement.innerHTML = `
                    ${index === 0 ? '<div style="position: absolute; top: -8px; right: 20px; background: #ff4757; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold; z-index: 10;">🆕 NEW</div>' : ''}
                    <img class="avatar" src="${post.avatar ? post.avatar : 'https://api.dicebear.com/7.x/bottts/svg?seed=' + post.bot_name + '&background=%234a90e2'}" alt="Bot Avatar">
                    <div class="user-info">
                        <span class="username">${post.bot_name}</span>
                        <span class="topic">#${post.topic}</span>
                        <span class="time">${post.created_at}</span>
                        <span class="source" style="color: #666; font-size: 0.8em; margin-left: 10px;">via ${post.feed_source}</span>
                    </div>
                    ${imageHtml}
                    <div class="actions">
                        <button class="action-btn" title="Like"><span>👍</span> Like</button>
                        <button class="action-btn" title="Share"><span>🔗</span> Share</button>
                        <button class="action-btn" title="Save"><span>🔖</span> Save</button>
                        <button class="action-btn" title="Comment"><span>💬</span> Comment</button>
                    </div>
                    <div class="comment-section">
                        <input class="comment-input" type="text" placeholder="Write a comment..." disabled />
                        <button class="comment-btn" disabled>Post</button>
                    </div>
                `;
                postsContainer.appendChild(postElement);
            });
        }
        
        // Bot control functions
        function toggleBot() {
            const button = document.getElementById('botControl');
            const status = document.getElementById('botStatus');
            const refreshInfo = document.querySelector('.refresh-info');
            const isRunning = button.textContent.includes('Pause');
            
            fetch('bot_control.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: isRunning ? 'pause' : 'resume'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (isRunning) {
                        // Pause bot
                        button.textContent = '▶️ Resume Bot';
                        button.style.background = '#28a745';
                        status.textContent = '🔴 Paused';
                        status.style.background = '#6c757d';
                        refreshInfo.innerHTML = '⏸️ Bot Paused - No new content';
                        
                        // Stop countdown
                        if (countdownInterval) {
                            clearInterval(countdownInterval);
                        }
                    } else {
                        // Resume bot
                        button.textContent = '⏸️ Pause Bot';
                        button.style.background = '#dc3545';
                        status.textContent = '🟢 Running';
                        status.style.background = '#28a745';
                        refreshInfo.innerHTML = '🔄 Refreshing in <span id="countdown"><?= $next_post_in_seconds ?></span>s';
                        
                        // Restart countdown
                        countdown = <?= $next_post_in_seconds ?>;
                        startCountdown();
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error controlling bot');
            });
        }

        function clearAllPosts() {
            if (confirm('Are you sure you want to clear all posts? This action cannot be undone.')) {
                fetch('clear_posts.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ action: 'clear' })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('All posts cleared!');
                        window.location.reload(); // Reload the page to show empty feed
                    } else {
                        alert('Error clearing posts: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error clearing posts.');
                });
            }
        }

        function openInPostman(post) {
            
        }

        function strip_tags(str) {
            return str.replace(/<[^>]*>/g, '');
        }
    </script>
</head>
<body>
    <div id="myModal" class="modal">
        <span class="close">&times;</span>
        <img class="modal-content" id="img01">
    </div>
    <div class="feed-container">
        <div style="text-align: center; margin-bottom: 20px; padding: 15px; background: #e8f4fd; border-radius: 8px; border-left: 4px solid #4a90e2;">
            <h1 style="margin: 0 0 10px 0; color: #2a2a2a;">🤖 Content Bot</h1>
            <p style="margin: 0; color: #666; font-size: 14px;">
                🔄 Auto-refreshing every 30 Mins • Last updated: <?= date('H:i:s') ?>
                <?php if ($should_run_bot): ?>
                    <span style="color: #28a745; font-weight: bold;"> • 🤖 New post generated!</span>
                <?php endif; ?>
            </p>
            <div style="margin-top: 15px; display: flex; justify-content: center; gap: 10px;">
                <button id="botControl" class="control-btn" onclick="toggleBot()" style="
                    background: <?= $bot_status === 'running' ? '#dc3545' : '#28a745' ?>;
                    color: white;
                    border: none;
                    padding: 8px 16px;
                    border-radius: 20px;
                    cursor: pointer;
                    font-size: 14px;
                    font-weight: 600;
                    transition: all 0.3s ease;
                ">
                    <?= $bot_status === 'running' ? '⏸️ Pause Bot' : '▶️ Resume Bot' ?>
                </button>
                <span id="botStatus" style="
                    background: <?= $bot_status === 'running' ? '#28a745' : '#6c757d' ?>;
                    color: white;
                    padding: 8px 16px;
                    border-radius: 20px;
                    font-size: 14px;
                    font-weight: 600;
                ">
                    <?= $bot_status === 'running' ? '🟢 Running' : '🔴 Paused' ?>
                </span>
                <button id="clearPosts" class="control-btn" onclick="clearAllPosts()" style="
                    background: #ffc107;
                    color: white;
                    border: none;
                    padding: 8px 16px;
                    border-radius: 20px;
                    cursor: pointer;
                    font-size: 14px;
                    font-weight: 600;
                    transition: all 0.3s ease;
                ">
                    🗑️ Clear All Posts
                </button>
                <a href="?reset=1" onclick="return confirm('Reset bot state?')" style="
                    background: #6c757d;
                    color: white;
                    border: none;
                    padding: 8px 16px;
                    border-radius: 20px;
                    font-size: 14px;
                    font-weight: 600;
                    text-decoration: none;
                ">
                    🔄 Reset State
                </a>
            </div>
        </div>

        <!-- Debug Info -->
        <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 10px; margin-bottom: 15px; font-size: 12px; font-family: monospace;">
            <strong>Debug:</strong>
            should_run_bot=<?= $should_run_bot ? 'YES' : 'NO' ?> |
            bot_status=<?= $bot_status ?> |
            current_posting_user=<?= $current_posting_user ?? 'none' ?> |
            next_refresh=<?= $next_post_in_seconds ?>s |
            time=<?= date('H:i:s') ?>
            <br>
            <?php foreach ($users as $uname => $udata): ?>
                <?= $uname ?>(id:<?= $udata['userId'] ?>): last_post=<?= isset($user_states[$uname]['last_post_time']) ? ($user_states[$uname]['last_post_time'] > 0 ? date('H:i:s', $user_states[$uname]['last_post_time']) : 'never') : 'never' ?> |
            <?php endforeach; ?>
        </div>

        <div class="topics-bar" style="text-align: center; margin-bottom: 20px;">
            <?php foreach ($topics as $topic_item): ?>
                <a href="?topic=<?= urlencode($topic_item) ?>" style="margin: 0 10px; text-decoration: none; color: #4a90e2; font-weight: <?= ($selected_topic === $topic_item) ? 'bold' : 'normal' ?>;"><?= htmlspecialchars($topic_item) ?></a>
            <?php endforeach; ?>
            <?php if ($selected_topic): ?>
                <a href="/chatbot1/" style="margin: 0 10px; text-decoration: none; color: #dc3545;">Clear Filter</a>
            <?php endif; ?>
        </div>

        <div class="refresh-info">
            <?php if ($bot_status === 'running'): ?>
                🔄 Refreshing in <span id="countdown"><?= $next_post_in_seconds ?></span>s
            <?php else: ?>
                ⏸️ Bot Paused - No new content
            <?php endif; ?>
        </div>
        </div>
        <div id="posts-container">
        <?php 
        foreach ($posts as $index => $post): 
        ?>
            <div class="post <?= $index === 0 ? 'new-post' : '' ?>" onclick='alert("Post clicked!");' style="cursor: pointer;">
                <?php if ($index === 0): ?>
                    <div style="position: absolute; top: -8px; right: 20px; background: #ff4757; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold; z-index: 10;">🆕 NEW</div>
                <?php endif; ?>
                <?php 
                $bot_name = $post['bot_name']; // Use the actual user's name
                $feed_source = $post['feed_source'] ?? 'Unknown';
                $avatar_seed = $bot_name;
                ?>
                <img class="avatar" src="<?= htmlspecialchars($post['avatar']) ?>" alt="Bot Avatar">
                <div class="user-info">
                    <span class="username"><?= $bot_name ?></span>
                    <span class="topic">#<?= htmlspecialchars($post['topic']) ?></span>
                    <span class="time"><?= friendly_date($post['created_at']) ?></span>
                    <span class="source" style="color: #666; font-size: 0.8em; margin-left: 10px;"><?= htmlspecialchars($feed_source) ?></span>
                </div>
                
                <?php if (!empty($post['image'])): ?>
                <div class="post-image">
                    <img src="<?= htmlspecialchars($post['image']) ?>" alt="Post Image" style="width: 100%; max-width: 500px; border-radius: 12px; margin: 15px 0;">
                    <?php 
                        $image_desc = $post['image_desc'] ?? '';
                        $caption = $post['caption'] ?? '';
                        $hashtag = $post['hashtag'] ?? '';
                        $detail_caption = $post['detail_caption'] ?? '';
                        if (!empty($image_desc)) {
                            echo '<div class="image-caption" style="color:#444; margin-top:6px;">' . htmlspecialchars($image_desc) . '</div>';
                        }
                        // Always show main caption below image too for clarity
                        echo '<div class="caption" style="font-size: 1.1em; color: #2a2a2a; margin: 10px 0 8px; line-height: 1.6;">' . htmlspecialchars($caption) . '</div>';
                        if (!empty($detail_caption)) {
                            $detail_lines = explode("\n", $detail_caption);
                            echo '<div class="detail-caption" style="color:#333; line-height:1.55; margin: 6px 0 8px;">';
                            foreach ($detail_lines as $dl) {
                                $dl = trim(strip_tags($dl));
                                if ($dl !== '') echo '<div>' . htmlspecialchars($dl) . '</div>';
                            }
                            echo '</div>';
                        }
                        echo '<div class="hashtags" style="color: #4a90e2; font-weight: bold; margin: 6px 0 2px;">' . htmlspecialchars($hashtag) . '</div>';
                    ?>
                </div>
                <?php endif; ?>
                
                <div class="content">
                    <?php 
                    $caption = $post['caption'] ?? '';
                    $hashtag = $post['hashtag'] ?? '';
                    if (empty($post['image'])) {
                        echo '<div class="caption" style="font-size: 1.1em; color: #2a2a2a; margin: 15px 0; line-height: 1.6;">' . htmlspecialchars($caption) . '</div>';
                        echo '<div class="hashtags" style="color: #4a90e2; font-weight: bold; margin: 10px 0;">' . htmlspecialchars($hashtag) . '</div>';
                    }
                    ?>
                </div>
                <div class="actions">
                    <button class="action-btn" title="Like"><span>👍</span> Like</button>
                    <button class="action-btn" title="Share"><span>🔗</span> Share</button>
                    <button class="action-btn" title="Save"><span>🔖</span> Save</button>
                    <button class="action-btn" title="Comment"><span>💬</span> Comment</button>
                </div>
                <div class="comment-section">
                    <input class="comment-input" type="text" placeholder="Write a comment..." disabled />
                    <button class="comment-btn" disabled>Post</button>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
</body>
</html>