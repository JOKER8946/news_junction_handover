<?php

$access_token = "EAAUOqGZCvgXABO89X72FOqDC6UCJ7NEG0JFZBwdZCu21dfQy8r2JvKdifIZCkuCAI8VVBMEkExHpHSxyQhxpYStH254CmhjIwM1f4iNaVM7yhL9d4bZArGmvZCJ3vx5uyjrrPtzB1VNNue0XLGi10uyLysBejVd9dFBYP6LkhhAtNRd7LMPZATP7h4MIifOHkgchgZDZD";
$waba_id = "545323565262040";
$graph_url = "https://graph.facebook.com/v21.0";

// For creating new template
if (isset($_POST['create_template'])) {

    $template_name = strtolower(str_replace(" ", "_", $_POST['template_name']));
    $template_body = $_POST['template_body'];
    $language = $_POST['language'];

    // Build payload
    $payload = [
        "name" => $template_name,
        "category" => "UTILITY",
        "language" => $language,
        "components" => [
            [
                "type" => "BODY",
                "text" => $template_body
            ]
        ]
    ];

    $ch = curl_init("$graph_url/$waba_id/message_templates");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $access_token",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $create_response = curl_exec($ch);
    curl_close($ch);
}

// For deleting template
if (isset($_POST['delete_template'])) {
    $template_name = $_POST['delete_template'];

    $ch = curl_init("$graph_url/$waba_id/message_templates?name=$template_name");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $access_token"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $delete_response = curl_exec($ch);
    curl_close($ch);
}

// Get existing templates
$ch = curl_init("$graph_url/$waba_id/message_templates");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $access_token"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$list_response = curl_exec($ch);
curl_close($ch);

$templates = json_decode($list_response, true);
?>

<!DOCTYPE html>
<html>
<head>
<title>WhatsApp Template Manager</title>
<style>
body { font-family: Arial; margin: 30px; }
textarea { width: 400px; height: 120px; }
table { border-collapse: collapse; width: 100%; margin-top: 20px; }
th, td { border: 1px solid #ddd; padding: 8px; }
th { background-color: #f2f2f2; }
</style>
</head>

<body>

<h2>WhatsApp Template Manager</h2>

<!-- Create Template Form -->
<h3>Create New Template</h3>

<form method="post">
    <label>Template Name:</label><br>
    <input type="text" name="template_name" required><br><br>

    <label>Language Code (example: en_US):</label><br>
    <input type="text" name="language" value="en_US" required><br><br>

    <label>Body Text (use {{1}}, {{2}} for placeholders):</label><br>
    <textarea name="template_body" required></textarea><br><br>

    <input type="submit" name="create_template" value="Create Template">
</form>

<?php 
if (isset($create_response)) {
    echo "<h4>Template Creation Response:</h4>";
    echo "<pre>$create_response</pre>";
}
?>

<hr>

<!-- Existing Templates -->
<h3>Existing Templates</h3>

<table>
    <tr>
        <th>Name</th>
        <th>Status</th>
        <th>Category</th>
        <th>Language</th>
        <th>Action</th>
    </tr>

    <?php if (!empty($templates['data'])): ?>
        <?php foreach ($templates['data'] as $t): ?>
            <tr>
                <td><?= htmlspecialchars($t['name']) ?></td>
                <td><?= htmlspecialchars($t['status']) ?></td>
                <td><?= htmlspecialchars($t['category']) ?></td>
                <td><?= htmlspecialchars($t['language']) ?></td>
                <td>
                    <form method="post" style="display:inline;">
                        <button type="submit" name="delete_template" value="<?= $t['name'] ?>">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>

</table>

<?php 
if (isset($delete_response)) {
    echo "<h4>Delete Response:</h4>";
    echo "<pre>$delete_response</pre>";
}
?>

</body>
</html>
