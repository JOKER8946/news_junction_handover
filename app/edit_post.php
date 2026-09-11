
<?php
include './inc/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($_POST['action'] == 'edit') {
        // edit_post.php
        if (isset($_POST['post_id']) && isset($_POST['content'])) {
            $post_id = $_POST['post_id'];
            $new_content = trim($_POST['content']);

            // Get the current timestamp for 'editedOn'
            $editedOn = date('Y-m-d H:i:s');  // Get current time

            // Prepare the SQL statement to update the post content and editedOn timestamp
            $stmt = $conn->prepare("UPDATE reader_stream SET chat = ?, editedOn = ? WHERE id = ?");
            $stmt->bind_param("ssi", $new_content, $editedOn, $post_id);  // "ssi" means string, string, integer

            if ($stmt->execute()) {
                $response = [
                    'status' => 'success',
                    'message' => 'Post updated successfully.'
                ];
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Error: ' . $stmt->error
                ];
            }

            $stmt->close();
            echo json_encode($response);
        }
    }

    if ($_POST['action'] == 'delete') {
        // delete_post.php
        if (isset($_POST['post_id'])) {
            $post_id = $_POST['post_id'];
            $deletedOn = date('Y-m-d H:i:s');  // Get current time for deletedOn

            // Prepare the SQL statement to set the deleteFlag and the deletedOn timestamp
            $stmt = $conn->prepare("UPDATE reader_stream SET deleteFlag = 1, deletedOn = ? WHERE id = ?");
            $stmt->bind_param("si", $deletedOn, $post_id);  // "si" means string, integer

            if ($stmt->execute()) {
                $response = [
                    'status' => 'success',
                    'message' => 'Post deleted successfully.'
                ];
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Error: ' . $stmt->error
                ];
            }

            $stmt->close();
            echo json_encode($response);
        }
    }
}
?>