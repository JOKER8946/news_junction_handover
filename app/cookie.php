<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <a href="stream.php"><button>home page</button></a>
    <?php
    // Check if there are any cookies set
    if (count($_COOKIE) > 0) {
        echo "<h2>Cookies associated with this session:</h2>";
        echo "<ul>";

        // Loop through each cookie and display its name and value
        foreach ($_COOKIE as $name => $value) {
            echo "<li><strong>$name</strong>: $value</li>";
        }

        echo "</ul>";
    } else {
        echo "<p>No cookies are set for this session.</p>";
    }
    ?>
</body>

</html>