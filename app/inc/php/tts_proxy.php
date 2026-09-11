<?php
// TTS Proxy - fetches audio from Google Translate TTS
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'kn';
$text = isset($_GET['q']) ? $_GET['q'] : '';

if (empty($text)) {
    http_response_code(400);
    exit('No text provided');
}

// Whitelist languages
$allowedLangs = ['kn', 'hi', 'en', 'ta', 'te', 'ml', 'mr'];
if (!in_array($lang, $allowedLangs)) {
    $lang = 'kn';
}

$url = 'https://translate.google.com/translate_tts?ie=UTF-8&client=tw-ob&tl=' . urlencode($lang) . '&q=' . urlencode($text);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$audio = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $audio) {
    header('Content-Type: audio/mpeg');
    header('Cache-Control: public, max-age=86400');
    echo $audio;
} else {
    http_response_code(502);
    echo 'TTS fetch failed';
}
