<?
include '../inc/php/db_config.php';
header('Content-Type: application/json');

function update_deletion($request, $userId)
{
    global $creamdb;
    try {
        if ($request == "cancel") {
            $sql = "DELETE FROM acc_deletion WHERE userId = ?";
            $stmt = $creamdb->prepare($sql);

            if ($stmt === false) {
                throw new mysqli_sql_exception("Error preparing the SQL statement: " . $creamdb->error);
            }
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                return ['status' => 'success', 'message' => 'Account Deletion is reverted successfully'];
            } else {
                throw new mysqli_sql_exception("No data found for the given credentials or deletion failed");
            }
        } else if ($request == "confirm") {
            $sql = "UPDATE acc_deletion SET status='confirmed' WHERE userId = ?";
            $stmt = $creamdb->prepare($sql);
            if ($stmt === false) {
                throw new mysqli_sql_exception("Error preparing the SQL statement: " . $creamdb->error);
            }
            $stmt->bind_param("i", $userId);

            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                return ['status' => 'success', 'message' => 'Account Deleted Successfully'];
            } else {
                throw new mysqli_sql_exception("No data found for the given credentials or deletion failed");
            }
        }
    } catch (mysqli_sql_exception $e) {
        return ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => 'Unexpected Error: ' . $e->getMessage()];
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
    }
}

if (isset($_POST['request'], $_POST['userId'])) {
    echo json_encode(update_deletion($_POST['request'], $_POST['userId']));
}
