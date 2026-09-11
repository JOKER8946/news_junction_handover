<?php
// Handle the AJAX request for metadata fetching
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    if (isset($_POST['ytUrl'])) {
        // Function to extract the YouTube Video ID
        function getYouTubeVideoId($url)
        {
            if (preg_match('/(?:v=|youtu\.be\/|embed\/)([a-zA-Z0-9_-]+)/', $url, $matches)) {
                return $matches[1]; // Return the video ID
            }
            return null; // Return null if no match is found
        }

        // Function to fetch video title using YouTube oEmbed API
        function fetchYouTubeTitle($videoId)
        {
            $oEmbedUrl = "https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=$videoId&format=json";
            $response = @file_get_contents($oEmbedUrl);
            if ($response) {
                $data = json_decode($response, true);
                return $data['title'] ?? 'No Title Found'; // Return title or default message
            }
            return 'No Title Found'; // Return default message if API fails
        }

        // Check if the form is submitted
        $iframeHtml = ''; // Initialize the iframe HTML
        $title = ''; // Initialize the title variable

        // Extract the video ID
        $videoId = getYouTubeVideoId($_POST['ytUrl']);

        if ($videoId) {
            // Fetch the video title
            $title = fetchYouTubeTitle($videoId);

            // Generate the iframe HTML
            $iframeHtml = '
                <iframe width="853" height="480" src="https://www.youtube.com/embed/' . $videoId . '" 
                    title="' . $title . '" frameborder="0" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                    referrerpolicy="strict-origin-when-cross-origin" allowfullscreen>
                </iframe>
            ';

            echo json_encode(
                [
                    'title' => $title,
                    'iframe' => $iframeHtml
                ],
                JSON_UNESCAPED_UNICODE
            );
            exit;
        }
        echo json_encode(['error' => 'Invalid Youtube URL'], JSON_UNESCAPED_UNICODE);
        exit;
    } else if (isset($_POST['url'])) {
        $url = $_POST['url'] ?? '';

        // Function to fetch Open Graph metadata from a URL
        function getMetadata($url)
        {
            $metadata = [
                'title' => null,
                'description' => null,
                'image' => null,
                'url' => $url,
                'domain' => getDomainName($url)
            ];

            // Fetch HTML content and parse metadata
            $html = @file_get_contents($url);
            if ($html) {
                // Using DOMDocument to load HTML and extract Open Graph data
                $doc = new DOMDocument();
                // @$doc->loadHTML($html);
                // Set encoding to UTF-8
                @$doc->loadHTML('<?xml encoding="UTF-8">' . $html);
                $metas = $doc->getElementsByTagName('meta');

                // Try to extract Open Graph metadata
                foreach ($metas as $meta) {
                    if ($meta->getAttribute('property') == 'og:title') {
                        $metadata['title'] = $meta->getAttribute('content');
                    }
                    if ($meta->getAttribute('property') == 'og:description') {
                        $metadata['description'] = $meta->getAttribute('content');
                    }
                    if ($meta->getAttribute('property') == 'og:image') {
                        $metadata['image'] = $meta->getAttribute('content');
                    }
                }

                // Fallback if Open Graph metadata is not found
                if (empty($metadata['title']) || empty($metadata['description'])) {
                    // Get general meta tags as a fallback
                    $meta_tags = get_meta_tags($url);
                    if (empty($metadata['title'])) {
                        $metadata['title'] = $meta_tags['title'] ?? 'No Title Found';
                    }
                    if (empty($metadata['description'])) {
                        $metadata['description'] = $meta_tags['description'] ?? 'No Description Found';
                    }
                }
            }

            return $metadata;
        }

        function filter_url($url)
        {
            // Remove the trailing period if it exists using regex
            $url = preg_replace('/\.$/', '', $url);
            return $url;
        }

        function getDomainName($url)
        {
            // Parse the URL
            $parsedUrl = parse_url($url);

            // Extract the host part (the domain)
            if (isset($parsedUrl['host'])) {
                return $parsedUrl['host'];
            } else {
                return false; // Return false if the URL is invalid or doesn't contain a host
            }
        }

        // Fetch metadata for the provided URL
        if (!empty($url)) {
            $metadata = getMetadata(filter_url($url));
        } else {
            $metadata = [
                'title' => null,
                'description' => null,
                'image' => null,
                'url' => null,
                'domain' => null
            ];
        }

        // Return the metadata as a JSON response
        echo json_encode($metadata, JSON_UNESCAPED_UNICODE);
        exit;  // End the script after the response

    }
}
?>