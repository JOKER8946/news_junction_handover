<?php
session_start();

if (isset($_SESSION['article_attempts'])) {
    echo "Total Attempts: " . $_SESSION['article_attempts'];
} else {
    echo "No attempted";  // Default value if the session variable is not set
}


require_once './assets/php/db_connect.php';
require_once './assets/php/config.php';
require_once './assets/php/db_config.php';
// require_once 'genai/genai_function.php';
require_once './genai/genai_function.php';



require_once 'assets/php/validate.logged.php';
require_once 'assets/php/function.php';


if (!isset($_SESSION['article_attempts'])) {
    $_SESSION['article_attempts'] = 0;  // Set default attempts to 0
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $model = "gpt-4o";
    $type = 'request_article';

    $system_prompt = array(
        array("role" => "system", "content" => "Try to complete the sentence within the max_tokens."),
        array("role" => "system", "content" => "Try to complete the sentence even if the max_tokens is reached."),
        array("role" => "system", "content" => "Always use MySQL-compatible SVG embeds to show icons or images."),
        array("role" => "system", "content" => "You are a report generator that can generate an article."),
        array("role" => "system", "content" => "Please respond only with the result and do not add extra messages.")
    );

    $articleHeadline = $_POST['articleHeadline'] ?? '';
    $articleObjective = $_POST['articleObjective'] ?? '';
    $articleTargetGroup = $_POST['articleTargetGroup'] ?? '';
    $articleKeywords = $_POST['articleKeywords'] ?? '';
    $articleNumWords = $_POST['articleNumWords'] ?? '';
    $articleOutline = $_POST['articleOutline'] ?? '';
    $articlePrimarySource = $_POST['articlePrimarySource'] ?? '';
    $articleSecondarySource = $_POST['articleSecondarySource'] ?? '';
    $articleImageLink = $_POST['imageLink'] ?? '';

    $user_prompt = array(
        array("role" => "user", "content" => 'I want to create an amazing article.'),
        array("role" => "user", "content" => 'Title: "' . $articleHeadline . '".'),
        array("role" => "user", "content" => 'Objective: ' . $articleObjective . '.'),
        array("role" => "user", "content" => 'Target audience: "' . $articleTargetGroup . '"'),
        array("role" => "user", "content" => 'Keywords: "' . $articleKeywords . '".'),
        array("role" => "user", "content" => 'Limit the article to ' . $articleNumWords . ' words.')
    );

    if ($articleOutline) {
        $user_prompt[] = array("role" => "user", "content" => 'Outline: ' . $articleOutline);
    }
    if ($articlePrimarySource) {
        $user_prompt[] = array("role" => "user", "content" => 'Primary source: "' . $articlePrimarySource . '"');
    }
    if ($articleSecondarySource) {
        $user_prompt[] = array("role" => "user", "content" => 'Secondary source: "' . $articleSecondarySource . '"');
    }
    if ($articleImageLink) {
        $user_prompt[] = array("role" => "user", "content" => 'Use this image: "' . $articleImageLink . '".');
    }

    $response = processPrompt($type, $system_prompt, $user_prompt, $model);

    // Store response in session so it can be accessed after redirection
    $_SESSION['generated_article'] = $response;

    // Redirect to the same page to display content
    header("Location: generated_article.php?article_ready=1");
    exit;
}

function extractTitleDescription($text)
{
    // Trim whitespace and remove newline characters
    $text = trim(str_replace("\n", ' ', $text));

    // Use regex to capture the first sentence (title)
    if (preg_match('/^(.*?[.!?])(\s+|$)/s', $text, $matches)) {
        $title = trim($matches[1]); // First sentence as title
        $description = trim(substr($text, strlen($matches[0]))); // Remaining text as description

        // Limit title to 150 characters (truncate if needed)
        if (strlen($title) > 150) {
            $title = substr($title, 0, 150) . '...'; // Adding ellipsis for clarity
        }

        return [
            'title' => json_encode($title, JSON_UNESCAPED_UNICODE),
            'description' => json_encode($description, JSON_UNESCAPED_UNICODE)
        ];
    }

    return null; // No valid format found
}



// Display article if available
$article_content = $_SESSION['generated_article'] ?? 'No article generated yet.';
$articleData = extractTitleDescription($article_content);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generated Article</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">


    <script src="assets/js/genai_func.js"></script>
    <script src="assets/js/stream.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/marked/4.0.2/marked.min.js"></script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Get the Markdown content from the hidden div
            var markdownContent = $('#generated_response').text();

            // Convert Markdown to HTML using marked.js
            var htmlContent = convert_text(markdownContent);
            console.log(htmlContent)
            // Display the converted HTML
            $('#generated_response').html(htmlContent);
        });
    </script>
    <script>
        function article_save(title, description) {

            console.log('Title:', title);
            console.log('Description:', description);

            title = convert_text(title);
            description = convert_text(description);

            $.ajax({
                type: 'POST',
                url: 'genai/genai_article_save.php',
                data: {
                    title: title,
                    description: description
                },
                success: function(response) {
                    console.log('Success Response:', response);
                    alert(response);
                },
                error: function(xhr, status, error) {
                    console.error('Error saving data:', error);
                    console.error('XHR:', xhr);
                    console.error('Status:', status);
                }
            });
        }
    </script>
</head>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    :root {
        --primary-bg: #0f172a;
        --secondary-bg: #1e293b;
        --accent-color: #38bdf8;
        --text-primary: #f8fafc;
        --text-secondary: #94a3b8;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        background: var(--primary-bg);
        color: var(--text-primary);
        font-family: 'Inter', sans-serif;
        line-height: 1.6;
        min-height: 100vh;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
        margin-top: 120px;
    }

    .header {
        text-align: center;
        margin-bottom: 2rem;
        opacity: 0;
        transform: translateY(20px);
        animation: fadeInUp 0.6s ease forwards;
    }

    .header h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
        background: linear-gradient(45deg, var(--accent-color), #818cf8);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .header p {
        color: var(--text-secondary);
        font-size: 1.1rem;
    }

    #generated_response {
        background: var(--secondary-bg);
        padding: 2rem;
        border-radius: 1rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        opacity: 0;
        transform: translateY(20px);
        animation: fadeInUp 0.6s ease forwards 0.3s;
        position: relative;
        overflow: hidden;
    }

    #generated_response::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, var(--accent-color), #818cf8);
    }

    .actions {
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
        margin-top: 2rem;
        opacity: 0;
        transform: translateY(20px);
        animation: fadeInUp 0.6s ease forwards 0.6s;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 0.5rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-primary {
        background: var(--accent-color);
        color: var(--primary-bg);
    }

    .btn-secondary {
        background: var(--secondary-bg);
        color: var(--text-primary);
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }


    #generated_response {
        user-select: none;
        pointer-events: none;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 768px) {
        .container {
            padding: 1rem;
        }

        .header h1 {
            font-size: 2rem;
        }

        #generated_response {
            padding: 1.5rem;
        }
    }
</style>






<body>
    <?php include 'navbar.php' ?>

    <div class="container">
        <header class="header">
            <h1>Generated Article</h1>

        </header>
        <div id="generated_response"><?= $article_content ?></div>
        <!-- <div id="generatedArticleContent"></div> -->

        <div style="margin-top: 10px;">
            <? if ($gUserPlan) { ?>
                <button class='btn btn-primary' onclick="article_save(<?= htmlspecialchars($articleData['title'], ENT_QUOTES, 'UTF-8') ?>,<?= htmlspecialchars($articleData['description'], ENT_QUOTES, 'UTF-8') ?>)">Save to Collections</button>
            <? } else { ?>
                <button class='btn btn-primary' onclick="window.location.href='test_premium.php'" style="color:red">Upgrade to pro to save the article</button>
            <? } ?>
        </div>


    </div>



</body>




<script>
    document.addEventListener('DOMContentLoaded', function() {
        const element = document.getElementById('generated_response');

        // Disable right-click
        element.addEventListener('contextmenu', function(event) {
            event.preventDefault();
        });

        // Disable keyboard shortcuts for copying
        document.addEventListener('keydown', function(event) {
            if ((event.ctrlKey && event.key === 'c') ||
                (event.ctrlKey && event.key === 'u') ||
                (event.ctrlKey && event.key === 's') ||
                (event.ctrlKey && event.key === 'p')) {
                event.preventDefault();
            }
        });
    });
</script>

<script>
        // Assuming the backend sets the attempts count in session or localStorage
        let userAttempts = <?php echo $_SESSION['article_attempts'] ?? 0; ?>;
        const maxAttempts = 3;

        function chkGenerateArticle(action) {
            if (userAttempts < maxAttempts) {
                // Proceed with article generation
                userAttempts++;
                <?php $_SESSION['article_attempts'] = $userAttempts; ?>

                // Call the function for article generation here
                // For example: generateArticle(action);
            } else {
                alert("You have reached the maximum attempts.");
            }
        }
    </script>


</html>