<?php
// process.php

// Enable error reporting for debugging

// Set header to return JSON response
header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get form data
$mainPrompt = $_POST['mainPrompt'] ?? '';
$headline = $_POST['articleHeadline'] ?? '';
$objective = $_POST['articleObjective'] ?? '';
$targetGroup = $_POST['articleTargetGroup'] ?? '';
$keywords = $_POST['articleKeywords'] ?? '';
$numWords = $_POST['articleNumWords'] ?? '';

// Validate required fields
$requiredFields = [
    'articleHeadline' => 'Headline',
    'articleObjective' => 'Objective',
    'articleTargetGroup' => 'Target Group',
    'articleKeywords' => 'Keywords',
    'articleNumWords' => 'Number of words'
];

$errors = [];

foreach ($requiredFields as $field => $label) {
    if (empty($_POST[$field])) {
        $errors[] = $label . ' is required';
    }
}

if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please fix the following errors: ' . implode(', ', $errors)
    ]);
    exit;
}

// Process the form data
// Add your article generation logic here
// For now, we'll just return a success message

echo json_encode([
    'success' => true,
    'message' => 'Article generation request submitted successfully!'
]);
?>