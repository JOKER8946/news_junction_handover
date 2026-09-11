<?php
header("Content-Type: application/json");

include 'php/db_config.php';
include 'function.php';

// Get the JSON data
$jsonData = file_get_contents("php://input");

// Decode the JSON data
$data = json_decode($jsonData, true);

// Initialize the response array
$response = [
    "status" => "error",
    "message" => "Invalid input data"
];

// Check if data is valid
if (isset($data['request'], $data['feedId'])) {
    $request = $data['request'];
    $feedId = $data['feedId'];

    if ($request === 'loadLike') {
        if (isset($data['userId'])) {
            $userId = $data['userId'];
            try {
                $flag = checkLike($readerdb, $userId, $feedId);

                $response = [
                    "status" => "success",
                    "response" => $flag
                ];
            } catch (Exception $e) {
                // Log the error message
                error_log($e->getMessage());

                $response = [
                    "status" => "error",
                    "message" => "An error occurred while checking like status."
                ];
            }
        }
    } elseif ($request === 'likeCount') {
        if (isset($data['userId'])) {
            $userId = $data['userId'];
            try {
                $count = likeCount($readerdb, $feedId);

                $response = [
                    "status" => "success",
                    "count" => $count
                ];
            } catch (Exception $e) {
                // Log the error message
                error_log($e->getMessage());

                $response = [
                    "status" => "error",
                    "message" => "An error occurred while checking like status."
                ];
            }
        }
    } elseif ($request === 'like') {
        if (isset($data['userId'])) {
            $userId = $data['userId'];
            try {
                // Insert like into the database
                $sql = "INSERT INTO reader_thumbs_up (userId, articleId) VALUES (?, ?)";
                $stmt = $readerdb->prepare($sql);
                $stmt->bind_param("ii", $userId, $feedId);
                $stmt->execute();

                $response = [
                    "status" => "success"
                ];
            } catch (Exception $e) {
                error_log($e->getMessage());
                $response = [
                    "status" => "error",
                    "message" => "An error occurred while adding a like."
                ];
            }
        }
    } elseif ($request === 'unlike') {
        if (isset($data['userId'])) {
            $userId = $data['userId'];
            try {
                // Remove like from the database
                $sql = "DELETE FROM reader_thumbs_up WHERE userId = ? AND articleId = ?";
                $stmt = $readerdb->prepare($sql);
                $stmt->bind_param("ii", $userId, $feedId);
                $stmt->execute();

                $response = [
                    "status" => "success"
                ];
            } catch (Exception $e) {
                error_log($e->getMessage());
                $response = [
                    "status" => "error",
                    "message" => "An error occurred while removing a like."
                ];
            }
        }
    } elseif ($request === 'checkColl'){
        if (isset($data['userId'])) {
            $userId = $data['userId'];
            try {
                // Prepare SQL statement to check likes
                $sql = "SELECT COUNT(*) AS count FROM reader_collection WHERE user_id = ? AND feed_id = ?";
                $stmt = $readerdb->prepare($sql);
                $stmt->bind_param("ii", $userId, $feedId);
                $stmt->execute();
                
                // Fetch the result
                $result = $stmt->get_result(); // Use get_result to fetch results
                $row = $result->fetch_assoc();
                $count = isset($row['count']) ? (int)$row['count'] : 0;
        
                $response = [
                    "status" => "success",
                    'count' => $count
                ];
            } catch (Exception $e) {
                error_log($e->getMessage());
                $response = [
                    "status" => "error",
                    "message" => "An error occurred while checking the like."
                ];
            }
        }
    } elseif ($request === 'addColl') {
        if (isset($data['userId'])) {
            $userId = $data['userId'];
            try {
                // Insert like into the database
                $sql = "INSERT INTO reader_collection (user_id, feed_id) VALUES (?, ?)";
                $stmt = $readerdb->prepare($sql);
                $stmt->bind_param("ii", $userId, $feedId);
                $stmt->execute();
                $response = [
                    "status" => "success"
                ];
            } catch (Exception $e) {
                error_log($e->getMessage());
                $response = [
                    "status" => "error",
                    "message" => "An error occurred while adding a like."
                ];
            }
        }
    }elseif ($request === 'removeColl') {
        if (isset($data['userId'])) {
            $userId = $data['userId'];
            try {
                // Insert like into the database
                $sql = "DELETE FROM reader_collection WHERE user_id = ? AND feed_id = ?";
                $stmt = $readerdb->prepare($sql);
                $stmt->bind_param("ii", $userId, $feedId);
                $stmt->execute();
                $response = [
                    "status" => "success"
                ];
            } catch (Exception $e) {
                error_log($e->getMessage());
                $response = [
                    "status" => "error",
                    "message" => "An error occurred while removing a like."
                ];
            }
        }
    }
}

// Send the JSON response
echo json_encode($response);