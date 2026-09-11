<?php
// Cream: Community Feeds

require_once '../inc/validate.logged.php';
require_once '../inc/config.php';
$act = '';
if (!empty($_POST)) $act = isset($_POST["act"]) ? $_POST["act"] : '';

// Function to truncate the description to a certain number of words
function truncateHtml($html, $wordLimit)
{
    // Convert HTML entities to their corresponding characters
    $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Use regex to strip HTML tags while preserving spaces
    $text = preg_replace('/<[^>]*>/', ' ', $html);

    // Normalize whitespace
    $text = preg_replace('/\s+/', ' ', trim($text));

    // Split the text content into words
    $words = explode(' ', $text);

    // Truncate to the word limit
    if (count($words) > $wordLimit) {
        $words = array_slice($words, 0, $wordLimit);
        $truncatedText = implode(' ', $words) . '...';
    } else {
        $truncatedText = implode(' ', $words);
    }

    // Prepare HTML for reconstruction
    $html = '<html><body>' . htmlspecialchars($html, ENT_HTML5 | ENT_QUOTES, 'UTF-8') . '</body></html>';

    // Reconstruct the HTML with truncated content
    $truncatedHtml = reconstructHtml($html, $truncatedText);

    return $truncatedHtml;
}
// Function to reconstruct HTML with truncated text
function reconstructHtml($html, $truncatedText)
{
    // Use regex to find the content between the body tags
    return preg_replace('/<body[^>]*>(.*?)<\/body>/is', '<body>' . htmlspecialchars($truncatedText, ENT_HTML5 | ENT_QUOTES, 'UTF-8') . '</body>', $html);
}

// Add to Collection
if ($act == 'addCollection') {
    $data = isset($_POST['data']) ? $_POST['data'] : '';
    if ($data != '') {
        $arrData = json_decode($data, true);
        foreach ($arrData as &$value) {
            $feedTitle = $value['title'];
            $feedURL = $value['url'];
            $feedDesc = $value['desc'];
            $sql = "SELECT url FROM user_collection WHERE url='$feedURL' AND user_id=$gUserId";
            $result = mysqli_query($db, $sql);
            $numRows = mysqli_num_rows($result);
            if ($numRows == 0) {
                $feedTitle = mysqli_real_escape_string($db, $feedTitle);
                $feedURL = mysqli_real_escape_string($db, $feedURL);
                $feedDesc = mysqli_real_escape_string($db, $feedDesc);
                $sql = "INSERT INTO user_collection(user_id,title,url,description,date_added) VALUES($gUserId,'$feedTitle','$feedURL','$feedDesc',Now())";
                mysqli_query($db, $sql);
            }
        }
        echo "OK";
    }
}

?>
<ol class="breadcrumb my-3">
    <li class="breadcrumb-item w-100">
        <div class="text-left w-50">
            <h4 class="mt-1">Community Feeds</h4>
        </div>
        <div class="text-right w-50"><button type="button" id="buttonAddCollection" class="hide btn btn-success" onclick="chkAddCollection()">Add selected to My Collection</button></div>
    </li>
</ol>

<?php
$sql = "
SELECT
    uc.id AS article_id,
    uc.title,
    uc.share_collection_id,
    uc.description,
    uc.likes,
    uc.date_added,
    u.full_name AS user_name
FROM
    user_collection uc
INNER JOIN
    user u ON uc.user_id = u.id
WHERE
    uc.share_collection_id IS NOT NULL
ORDER BY
    uc.date_added DESC;
";

$result = mysqli_query($db, $sql);

if ($result) {
    $numRows = mysqli_num_rows($result);

    if ($numRows > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $articleId = $row['article_id'];
            $title = $row['title'];
            $description = $row['description']; // Truncate description to 25 words
            $feedPublisher = $row['user_name']; // Updated to fetch the user's full name
            $feedDate = $row['date_added']; // Updated to fetch the date added
            $isExist = 0;
?>
            <div class="card p-0 mb-3 border-0" style="background-color:#f5ede7">
                <div class="panelFeed card-body">
                    <h4>
                        <a href="article.php?article_id=<?= $articleId ?>" style="color:#f26522" onclick="openPopup(this.href); return false;"><?= htmlentities($title) ?>
                        </a>
                    </h4>
                    <p style="color:#7d7d7d"><?= truncateHtml($description, 100) ?></p>
                    <div class="row">
                        <div class="col-12 col-md-6 text-center text-md-left">
                            <?= htmlentities($feedPublisher) ?> &bull; <?= htmlentities($feedDate) ?>
                        </div>
                        <div class="data col-12 col-md-6 text-center text-md-left mt-2 m-md-0" data-feed-title="<?= htmlentities($feedTitle) ?>" data-feed-url="#" data-feed-publisher="<?= htmlentities($feedPublisher) ?>" data-feed-desc="<?= htmlentities($feedDesc) ?>">
                            <?php if ($isExist == 0) { ?>
                                <label class="btn-secondary m-0 px-2 py-1" style="user-select:none"><input type="checkbox" name="feedData" onclick="addCollection()"> My Collection</label>
                            <?php } else { ?>
                                <label class="badge-warning m-0 px-3 py-1" style="user-select:none">My Collection</label>
                            <?php } ?>
                            <label class="buttonCreamShare btn-info m-0 px-2 py-1" style="user-select:none">Cream<i>Share</i></label>
                            <label class="btn-success m-0 px-2 py-1" id="button1" onclick="CopyToClipboard('<?= htmlentities($feedTitle) ?>')">Share</label>
                            <button id="incrementButton" data-id="<?= $row['article_id'] ?>">Like Article</button>
                            <div id="likesDisplay"></div>
                            <br /><br />
                        </div>
                    </div>
                </div>
            </div>
<?php
        }
    } else {
        echo "Nothing found here!";
    }
} else {
    echo "Error in query execution: " . mysqli_error($db);
}
?>


<!-- JavaScript to make AJAX request -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // add click event listener to the button
        $(document).on('click', '.incrementButton', function() {
            // get the id from the data attribute
            var id = $(this).data('id');

            // make an AJAX request to the server-side script
            $.ajax({
                type: 'POST',
                url: 'inc/increment.php',
                data: {
                    id: id
                },
                success: function(likes) {
                    // update the likes display on the page
                    $('#likesDisplay').text('Likes: ' + likes);
                },
                error: function() {
                    alert('Error: unable to increment likes.');
                }
            });
        });
    });
</script>

<!-- </script> -->
<script type="text/javascript">
    function CopyToClipboard(text) {
        var $temp = $("<textarea>");
        $("body").append($temp);
        $temp.val(text).select();
        document.execCommand("copy");
        $temp.remove();
        alert("Copied to clipboard!");
    }

    function openPopup(url) {
        var width = 800; // Width of the popup window
        var height = 600; // Height of the popup window
        var left = (screen.width - width) / 2;
        var top = (screen.height - height) / 2;

        var popupWindow = window.open(url, '_blank', 'toolbar=yes,scrollbars=yes,resizable=yes,top=' + top + ',left=' + left + ',width=' + width + ',height=' + height);
        popupWindow.focus();
    }
</script>