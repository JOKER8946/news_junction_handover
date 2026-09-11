<?php
require_once './inc/php/validate.logged.php';
include './inc/php/function.php';
include './inc/config.php';
include './inc/php/db_config.php';


$user_id = $gUserId;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $channel_id = intval($_POST['channel_id']);

    if ($action === 'toggle_visibility') {
        $current_visibility = $_POST['current_visibility'];
        $new_visibility = ($current_visibility === 'private') ? 'public' : 'private';

        $stmt = $readerdb->prepare("UPDATE channels SET visibility = ? WHERE id = ? AND created_by = ?");
        $stmt->bind_param("sii", $new_visibility, $channel_id, $user_id);
        $stmt->execute();
    } elseif ($action === 'delete') {
        $stmt = $readerdb->prepare("DELETE FROM channels WHERE id = ? AND created_by = ?");
        $stmt->bind_param("ii", $channel_id, $user_id);
        $stmt->execute();
    }

    header("Location: dashboard.php");
    exit;
}
