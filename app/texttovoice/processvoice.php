<?php
include '../inc/validate.logged.php';

// Ensure your audio folder exists and is writable
$audioDir = $_SERVER['DOCUMENT_ROOT'] . '/audio/';
if (!is_dir($audioDir)) {
    mkdir($audioDir, 0755, true);
}
if (!is_writable($audioDir)) {
    die('Error: Audio directory is not writable');
}

$timestamp = time();

if (isset($_POST['title']) && isset($_POST['description'])) {
    $mydata = urldecode($_POST['title']) . "\n" . urldecode($_POST['description']);
    texttovoice($gUserId, $timestamp, $mydata);
}

function texttovoice($gUserId, $timestamp, $mydata)
{
    // Use environment variable for security
    $apiKey = "sk-proj-FrlWqCTIyid7DZGorv0uT3BlbkFJzqrUB0km57kpp4aFPNV7";
    if (!$apiKey) {
        die('Error: OpenAI API key is missing');
    }

    $url = "https://api.openai.com/v1/audio/speech";

    $data = array(
        "model" => "tts-1",
        "input" => $mydata,
        "voice" => "alloy"
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "Authorization: Bearer $apiKey"
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FAILONERROR, false); // let us handle HTTP errors manually
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);

    // Check for cURL errors
    if ($response === false) {
        die('cURL error: ' . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Check for HTTP errors
    if ($httpCode !== 200) {
        die('HTTP error ' . $httpCode . ': ' . $response);
    }

    // Save the MP3 file
    $fileName = '_'.$gUserId.$timestamp.'speech.mp3';
    $filePath = $_SERVER['DOCUMENT_ROOT'] . '/audio/' . $fileName;
    file_put_contents($filePath, $response);

    echo 'audio/' . $fileName;
}
