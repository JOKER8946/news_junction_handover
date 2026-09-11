<?php
// ini_set('display_startup_errors', 1);

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *"); // Allow from all domains (for testing)
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

// ==== Configuration ====
$googleApiKey = "YOUR_GOOGLE_API_KEY_2"; // Use your actual key
$searchEngineId = "301095593922644eb";
$geminiApiKey = "YOUR_GOOGLE_API_KEY";
$defaultResultCount = 5;

// ==== Receive & Parse JSON Input ====
$input = json_decode(file_get_contents("php://input"), true);

$keywords = trim($input['keywords'] ?? '');
$resultCount = (int) ($input['resultCount'] ?? $defaultResultCount);

if (empty($keywords)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing 'keywords' in the request."]);
    exit;
}

try {
    // Step 1: Get URLs from Google Search
    $searchResults = fetchUrlsFromGoogleSearch($googleApiKey, $searchEngineId, $keywords, $resultCount);

    // Step 2: Fetch content from each URL
    $fetchedContents = [];
    foreach ($searchResults as $result) {
        $content = fetchUrlContent($result['url']);
        if ($content) {
            $fetchedContents[] = [
                'title' => $result['title'],
                'url' => $result['url'],
                'content' => $content
            ];
        }
    }

    // Step 3: Generate Gemini Summary
    $summary = generateGeminiSummary($fetchedContents, $keywords, $geminiApiKey);

    // Step 4: Respond with JSON
    echo json_encode([
        "keywords" => $keywords,
        "summary" => $summary,
        "sources" => array_map(function ($r) {
            return [
                "title" => $r['title'],
                "url" => $r['url']
            ];
        }, $fetchedContents)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}

// ==== Include your function definitions below ====
/* (Reuse your existing `fetchUrlsFromGoogleSearch`, `fetchUrlContent`, and `generateGeminiSummary` functions here) */
function fetchUrlsFromGoogleSearch($apiKey, $searchEngineId, $keywords, $resultCount)
{
    // URL encode the query
    $encodedQuery = urlencode($keywords);

    // Build Google Custom Search API URL
    $endpoint = "https://www.googleapis.com/customsearch/v1?key={$apiKey}&cx={$searchEngineId}&q={$encodedQuery}&num={$resultCount}";

    // Initialize cURL
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);

    // Set timeout to avoid hanging
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Check for cURL errors
    if (curl_errno($ch)) {
        curl_close($ch);
        throw new Exception("Search API connection error: " . curl_error($ch));
    }

    curl_close($ch);

    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        throw new Exception("API Error: " . ($errorData['error']['message'] ?? 'Failed to fetch search results'));
    }

    $data = json_decode($response, true);

    try {
        // Check if response contains items
        if (!isset($data['items']) || empty($data['items'])) {
            return [];
        }

        // Format results
        $formattedResults = [];
        foreach ($data['items'] as $item) {
            $formattedResults[] = [
                'title' => $item['title'],
                'url' => $item['link'],
                'description' => $item['snippet'] ?? 'No description available'
            ];
        }

        return $formattedResults;
    } catch (Exception $e) {
        throw new Exception('Failed to parse search results. Please try again.');
    }
}

/**
 * Fetch and clean content from a URL
 *
 * @param string $url - The URL to fetch content from
 * @return string|false - The cleaned content or false on failure
 */
function fetchUrlContent($url)
{
    // Validate URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }

    // Initialize cURL
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Timeout after 15 seconds
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // Connection timeout
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For SSL sites

    // Execute request
    $content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

    // Check for cURL errors
    if (curl_errno($ch)) {
        curl_close($ch);
        return false;
    }

    curl_close($ch);

    if ($httpCode !== 200 || !$content) {
        return false;
    }

    // Skip non-HTML content
    if ($contentType && !preg_match('/text\/html|application\/xhtml\+xml/', $contentType)) {
        return false;
    }

    // Use DOMDocument for better HTML parsing
    $dom = new DOMDocument();
    @$dom->loadHTML($content);

    // Remove script, style, header, nav, and footer elements
    $elementsToRemove = [
        'script',
        'style',
        'header',
        'nav',
        'footer',
        'iframe',
        'noscript',
        'form',
        'aside'
    ];

    $remove = [];
    foreach ($elementsToRemove as $tag) {
        $elements = $dom->getElementsByTagName($tag);
        for ($i = 0; $i < $elements->length; $i++) {
            $remove[] = $elements->item($i);
        }
    }

    foreach ($remove as $node) {
        if ($node && $node->parentNode) {
            $node->parentNode->removeChild($node);
        }
    }

    // Get main content if possible
    $mainContent = "";
    $contentTags = ['article', 'main', 'section', 'div[role="main"]', '.content', '.main', '#content', '#main'];

    foreach ($contentTags as $tag) {
        $xpath = new DOMXPath($dom);
        $elements = $xpath->query('//' . preg_replace('/\[.*\]/', '', $tag));

        if ($elements && $elements->length > 0) {
            $mainContent = $elements->item(0)->textContent;
            break;
        }
    }

    // If no main content found, use the body content
    if (empty($mainContent)) {
        $body = $dom->getElementsByTagName('body');
        if ($body->length > 0) {
            $mainContent = $body->item(0)->textContent;
        } else {
            $mainContent = $dom->textContent;
        }
    }

    // Clean up extra whitespace
    $cleanText = preg_replace('/\s+/', ' ', $mainContent);
    $cleanText = trim($cleanText);

    // Truncate to reasonable length for display and processing
    $maxLength = 10000; // Adjust as needed for better context
    if (strlen($cleanText) > $maxLength) {
        $cleanText = substr($cleanText, 0, $maxLength) . "...";
    }

    return $cleanText;
}

/**
 * Generate a summary using Gemini API
 *
 * @param array $fetchedContents - Array of content from all sources
 * @param string $keywords - Search keywords
 * @param string $apiKey - Gemini API key
 * @return string - Generated summary
 */
function generateGeminiSummary($fetchedContents, $keywords, $apiKey)
{
    // Check if API key is provided
    if (empty($apiKey) || $apiKey === "YOUR_GEMINI_API_KEY") {
        return "Please configure your Gemini API key to enable AI-powered summaries.";
    }

    // Combine the content from all sources (but limit size to avoid hitting API limits)
    $allContent = "";
    foreach ($fetchedContents as $item) {
        // Add metadata about the source
        $allContent .= "SOURCE: " . $item['title'] . "\n";
        $allContent .= "URL: " . $item['url'] . "\n";

        // Extract the most relevant parts of the content (first part often contains key info)
        $contentExcerpt = substr($item['content'], 0, 3000); // Get first 3000 chars

        // Add the content with clear separation
        $allContent .= "CONTENT: " . $contentExcerpt . "\n\n";
        $allContent .= "---\n\n"; // Clear separator between sources

        // If we're exceeding a reasonable size for the API request, break
        if (strlen($allContent) > 20000) {
            $allContent .= "[Additional content truncated due to size constraints]\n";
            break;
        }
    }

    // Prepare the prompt for Gemini with more specific instructions
    $prompt = "I've gathered detailed information about '{$keywords}' from " . count($fetchedContents) . " different web sources.\n\n" .
        "Without including a title or heading, please write a well-organized summary of this content in 3 to 4 clear paragraphs. Each paragraph should focus on a different key aspect of '{$keywords}'. Begin directly with the summary.\n\n" .
        wordwrap($allContent, 200, "\n");





    // Prepare the request to Gemini API
    $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $apiKey;

    $data = [
        "contents" => [
            [
                "parts" => [
                    [
                        "text" => $prompt
                    ]
                ]
            ]
        ],
        "generationConfig" => [
            "temperature" => 0.3, // Lower temperature for more factual/consistent output
            "topK" => 40,
            "topP" => 0.95,
            "maxOutputTokens" => 1024,
            "stopSequences" => []
        ],
        "safetySettings" => [
            [
                "category" => "HARM_CATEGORY_HARASSMENT",
                "threshold" => "BLOCK_MEDIUM_AND_ABOVE"
            ],
            [
                "category" => "HARM_CATEGORY_HATE_SPEECH",
                "threshold" => "BLOCK_MEDIUM_AND_ABOVE"
            ],
            [
                "category" => "HARM_CATEGORY_SEXUALLY_EXPLICIT",
                "threshold" => "BLOCK_MEDIUM_AND_ABOVE"
            ],
            [
                "category" => "HARM_CATEGORY_DANGEROUS_CONTENT",
                "threshold" => "BLOCK_MEDIUM_AND_ABOVE"
            ]
        ]
    ];

    // Initialize cURL session for Gemini API
    $ch = curl_init($endpoint);

    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Allow up to 60 seconds for API response

    // Execute the request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Check for cURL errors
    if (curl_errno($ch)) {
        curl_close($ch);
        return "Error connecting to Gemini API: " . curl_error($ch);
    }

    curl_close($ch);

    // Check for errors in the API response
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMessage = isset($errorData['error']['message']) ? $errorData['error']['message'] : "API returned status code $httpCode";
        return "Failed to generate summary: $errorMessage";
    }

    // Process the API response
    $responseData = json_decode($response, true);

    try {
        // Extract the generated text from the response
        if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            return $responseData['candidates'][0]['content']['parts'][0]['text'];
        } else {
            return "Summary could not be generated. The API response was not in the expected format.";
        }
    } catch (Exception $e) {
        return "Error processing Gemini API response: " . $e->getMessage();
    }
}
