<?php
header('Content-Type: application/json');

include '../../inc/php/validate.logged.php';
include '../db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);

class AttributeException extends Exception {}
class PostException extends Exception {}
class TokenException extends Exception {}

function sendResponse($response, $data)
{
    if ($response == "error") {
        echo json_encode(['status' => 'error', 'error_data' => $data]);
    } elseif ($response == "success") {
        echo json_encode(['status' => 'success', 'data' => $data]);
    } else {
        echo json_encode(['status' => 'error', 'error_data' => 'No Data Provided.']);
    }
}

function facebook_post_now_pages($db, $userId, $pages, $title, $link)
{
    $successData = [];
    $errorData = [];
    try {
        foreach ($pages as $page) {
            $response = facebook_post_to_page($db, $userId, $page['id'], $title, $link);

            // Check if the response indicates success
            if (isset($response['status']) && $response['status'] === 'success') {
                $successData[] = [
                    'status' => 'success',
                    'message' => $response['message'],
                    'page' => $page['name'],
                    'response_data' => $response['response_data']
                ];
            } else {
                $errorData[] = [
                    'page_id' => $page,
                    'status' => 'error',
                    'error_message' => $response['error'] ?? 'An unknown error occurred.',
                ];
            }
        }
        if (!empty($errorData)) {
            if (!empty($successData)) {
                throw new PostException(json_encode(["success_data" => $successData, "error_data" => $errorData]));
            } else {
                throw new PostException(json_encode($errorData));
            }
        } elseif (!empty($successData)) {
            sendResponse("success", $successData);
        } else {
            throw new PostException(json_encode("No Successful Post Done......"));
        }
    } catch (PostException $e) {
        $errorMessages = json_decode($e->getMessage());
        $error = [
            'error_message' => 'An error occurred while posting.',
            'error' => $errorMessages
        ];
        sendResponse("error", $error);
    }
}

function facebook_post_to_page($db, $userId, $pageId, $message, $link)
{
    try {
        $tokenResponse = fetch_long_live_access_token($db, $userId, $pageId);

        // Check if the token was successfully retrieved
        if ($tokenResponse['status'] != 'success') {
            throw new RuntimeException($tokenResponse['error_message']);
        }

        $pageAccessToken = $tokenResponse['token'];
        $postUrl = 'https://graph.facebook.com/v20.0/' . $pageId . '/feed';

        // Data to send
        $postData = [
            'link' => $link,
            'message' => $message,
            'access_token' => $pageAccessToken,
        ];

        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute request
        $response = curl_exec($ch);

        // Check if there was an error with cURL
        if (curl_errno($ch)) {
            throw new RuntimeException('cURL error: ' . curl_error($ch));
        }

        curl_close($ch);

        // Decode the response
        $responseData = json_decode($response, true);

        // Check if there was an error in the API response
        if (isset($responseData['error'])) {
            throw new RuntimeException('Error: ' . $responseData['error']['message']);
        }

        // Return success response as an associative array
        return [
            'status' => 'success',
            'message' => 'Message posted successfully!',
            'page_id' => $pageId,
            'response_data' => $responseData, // Include API response data if needed
        ];
    } catch (TokenException $e) {
        return [
            'status' => 'error',
            'page_id' => $pageId,
            'error_message' => 'Token Error: ' . $e->getMessage(),
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'error' => 'An unexpected error occurred: ' . $e->getMessage(),
        ];
    } catch (RuntimeException $e) {
        return [
            'status' => 'error',
            'error' => $e->getMessage(),
        ];
    }
}

function fetch_long_live_access_token($db, $userId, $pageId)
{
    try {
        // Prepare the SQL statement
        $sql = "SELECT token FROM fb_long_lived_token WHERE pageId = ? AND userId = ?";

        if ($stmt = mysqli_prepare($db, $sql)) {
            // Bind parameters
            mysqli_stmt_bind_param($stmt, "si", $pageId, $userId);

            // Execute the statement
            if (mysqli_stmt_execute($stmt)) {
                // Bind result
                mysqli_stmt_bind_result($stmt, $token);

                // Fetch the result
                if (mysqli_stmt_fetch($stmt)) {
                    return [
                        'status' => 'success',
                        'token' => $token,
                    ];
                } else {
                    throw new TokenException('No token found for userId: ' . $userId . ' and pageId: ' . $pageId);
                }
            } else {
                throw new RuntimeException('Error executing statement: ' . mysqli_stmt_error($stmt));
            }

            // Close the statement
            mysqli_stmt_close($stmt);
        } else {
            throw new Exception('Error preparing statement: ' . mysqli_error($db));
        }
    } catch (TokenException $e) {
        return [
            'status' => 'error',
            'page_id' => $pageId,
            'error_message' => 'Token Error: ' . $e->getMessage(),
        ];
    } catch (RuntimeException $e) {
        return [
            'status' => 'error',
            'page_id' => $pageId,
            'error_message' => $e->getMessage(),
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'page_id' => $pageId,
            'error_message' => 'An unexpected error occurred: ' . $e->getMessage(),
        ];
    }
}

function facebook_schedule_post_to_pages($db, $userId, $pages, $title, $link, $schedule_time)
{
    $successData = [];
    $errorData = [];
    try {
        foreach ($pages as $page) {
            $response = facebook_schedule_page($db, $userId, $page['id'], $title, $link, $schedule_time);

            // Check if the response indicates success
            if (isset($response['status']) && $response['status'] === 'success') {
                $successData[] = [
                    'status' => 'success',
                    'message' => $response['message'],
                    'page' => $page['name'],
                    'schedule_at' => $schedule_time,
                    'response_data' => $response['response_data']
                ];
            } else {
                $errorData[] = [
                    'page_id' => $page,
                    'status' => 'error',
                    'schedule_at' => $schedule_time,
                    'error_message' => $response['error'] ?? 'An unknown error occurred.',
                ];
            }
        }
        if (!empty($errorData)) {
            if (!empty($successData)) {
                throw new PostException(json_encode(["success_data" => $successData, "error_data" => $errorData]));
            } else {
                throw new PostException(json_encode($errorData));
            }
        } elseif (!empty($successData)) {
            sendResponse("success", $successData);
        } else {
            throw new PostException(json_encode("No Successful Post Done......"));
        }
    } catch (PostException $e) {
        $errorMessages = json_decode($e->getMessage());
        $error = [
            'error_message' => 'An error occurred while posting.',
            'error' => $errorMessages
        ];
        sendResponse("error", $error);
    }
}

function facebook_schedule_page($db, $userId, $pageId, $message, $link, $schedule_time)
{
    try {
        $tokenResponse = fetch_long_live_access_token($db, $userId, $pageId);

        // Check if the token was successfully retrieved
        if ($tokenResponse['status'] != 'success') {
            throw new RuntimeException($tokenResponse['error_message']);
        }

        $pageAccessToken = $tokenResponse['token'];
        $postUrl = 'https://graph.facebook.com/v20.0/' . $pageId . '/feed';

        // Data to send
        $postData = [
            'link' => $link,
            'message' => $message,
            'access_token' => $pageAccessToken,
            'published' => false, // Set to false for scheduled posts
            'scheduled_publish_time' => $schedule_time // Unix timestamp for scheduling
        ];

        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute request
        $response = curl_exec($ch);

        // Check if there was an error with cURL
        if (curl_errno($ch)) {
            throw new RuntimeException('cURL error: ' . curl_error($ch));
        }

        curl_close($ch);

        // Decode the response
        $responseData = json_decode($response, true);

        // Check if there was an error in the API response
        if (isset($responseData['error'])) {
            throw new RuntimeException('Error: ' . $responseData['error']['message']);
        }

        // Return success response as an associative array
        return [
            'status' => 'success',
            'message' => 'Message posted successfully!',
            'page_id' => $pageId,
            'scheduled_at' => $schedule_time,
            'response_data' => $responseData, // Include API response data if needed
        ];

    } catch (TokenException $e) {
        return [
            'status' => 'error',
            'page_id' => $pageId,
            'error_message' => 'Token Error: ' . $e->getMessage(),
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'error' => 'An unexpected error occurred: ' . $e->getMessage(),
        ];
    } catch (RuntimeException $e) {
        return [
            'status' => 'error',
            'error' => $e->getMessage(),
        ];
    }
}

try {
    // Check for required parameters
    if (!isset($data['action'])) {
        throw new AttributeException('Action is not set.');
    }
    if (!isset($data['pages']) || !is_array($data['pages'])) {
        throw new AttributeException('Pages are not set or not an array.');
    }
    if (!isset($data['title'])) {
        throw new AttributeException('Title is not set.');
    }
    if (!isset($data['link'])) {
        throw new AttributeException('Link is not set.');
    }

    switch ($data['action']) {
        case 'postNow':
            facebook_post_now_pages($db, $gUserId, $data['pages'], $data['title'], $data['link']);
            break;

        case 'schedulePost':
            if (!isset($data['schedule'])) {
                throw new AttributeException('Schedule time is not provided.');
            }
            facebook_schedule_post_to_pages($db, $gUserId, $data['pages'], $data['title'], $data['link'], $data['schedule']);
            break;
        default:
            throw new Exception('Invalid action provided.');
    }
} catch (AttributeException $e) {
    $errorResponse = json_encode([
        'error_message' => 'Attribute is missing.',
        'error_data' => $e->getMessage()
    ]);
    sendResponse('error', $errorResponse);
} catch (Exception $e) {
    $errorResponse = json_encode([
        'error_message' => 'An unexpected error occurred.',
        'error_data' => $e->getMessage()
    ]);
    sendResponse('error', $errorResponse);
}
