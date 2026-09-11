<?
include '../inc/validate.logged.php';
include 'function.php';
include 'db_connect.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modal Example</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
        .modal-header {
            background-color: #007bff;
            color: white;
        }
    </style>
</head>

<body>

    <div class="container mt-5">
        <h2>PHP Modal Example</h2>
        <button type="button" class="btn btn-primary" id="openModal">Open Modal</button>

        <!-- Modal -->
        <div class="modal fade" id="fbShare" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Share Through Facebook Pages</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group">
                            <label for="articleTitle">Enter the text to be shared:</label>
                            <textarea id="articleTitle" class="form-control" rows="3" placeholder="Title of the article will be sent">Title of the article will be sent</textarea>
                        </div>

                        <input type="hidden" id="articleLink" class="form-control" value="https://www.example.com/article-link">

                        <?php $pages_data = facebook_fetch_user_pages($db, $gUserId); ?>
                        <h5>Select Facebook Pages:</h5>
                        <div class="list-group">
                            <?php foreach ($pages_data as $page_data) { ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="<?= $page_data['pages_id'] ?>" id="page-<?= $page_data['pages_id'] ?>">
                                    <label class="form-check-label" for="page-<?= $page_data['pages_id'] ?>">
                                        <?= $page_data['pages_name'] ?>
                                    </label>
                                </div>
                            <?php } ?>
                        </div>
                        <div id="scheduleDateTimeDiv" class="form-group" style="display: none;">
                            <label for="scheduleDateTime">Schedule Date and Time:</label>
                            <input type="datetime-local" class="form-control" id="scheduleDateTime">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="postNow">Post Now</button>
                        <button type="button" class="btn btn-info" id="setSchedule">Schedule Post</button>
                        <button type="button" class="btn btn-info" id="schedulePost" style="display: none;">Schedule Now</button>
                    </div>
                </div>
            </div>
        </div>


    </div>

    <script>
        $(document).ready(function() {
            $('#openModal').click(function() {
                $('#fbShare').modal('show');
            });

            // When the modal is hidden, reset the scheduling elements
            $('#fbShare').on('hidden.bs.modal', function() {
                $('#scheduleDateTimeDiv').hide(); // Hide the date/time div
                $('#schedulePost').hide(); // Hide the schedule post button
                $('#setSchedule').show(); // Show the set schedule button
                $('#scheduleDateTime').val(''); // Reset the schedule date/time input
                $('.form-check-input').prop('checked', false); // Uncheck all checkboxes
            });

            $('#setSchedule').click(function() {
                $('#scheduleDateTimeDiv').show();
                $('#schedulePost').show();
                $('#setSchedule').hide();
            });

            // Post Now button click
            $('#postNow').click(function() {
                var selectedPages = [];
                $('.form-check-input:checked').each(function() {
                    selectedPages.push({
                        id: $(this).val(),
                        name: $(this).next('label').text().trim() // Trim whitespace
                    });
                });
                // Get the article title and link
                var articleTitle = $('#articleTitle').val();
                var articleLink = $('#articleLink').val();

                console.log(selectedPages);
                console.log(articleTitle);
                console.log(articleLink);

                if (selectedPages.length > 0) {
                    // Prepare data for AJAX
                    var postData = {
                        action: 'postNow',
                        pages: selectedPages,
                        title: articleTitle,
                        link: articleLink
                    };

                    // AJAX call to the post_now PHP function
                    $.ajax({
                        url: 'fb_post_handler.php', // Update this path to your PHP file
                        type: 'POST',
                        data: JSON.stringify(postData),
                        contentType: 'application/json',
                        success: function(response) {
                            // Handle success response
                            alert('Post successful: ' + response.message);
                            $('#fbShare').modal('hide');
                        },
                        error: function(xhr, status, error) {
                            // Handle error response
                            alert('An error occurred: ' + error);
                        }
                    });
                } else {
                    alert('Please select at least one page to post.');
                }
            });

            // Schedule Post button click
            $('#schedulePost').click(function() {
                var selectedPages = [];
                $('.form-check-input:checked').each(function() {
                    selectedPages.push({
                        id: $(this).val(),
                        name: $(this).next('label').text().trim() // Trim whitespace
                    });
                });

                var scheduleDateTime = $('#scheduleDateTime').val();
                var articleTitle = $('#articleTitle').val();
                var articleLink = $('#articleLink').val();

                if (selectedPages.length > 0) {
                    if (scheduleDateTime) {
                        // Prepare data for AJAX
                        var postData = {
                            action: 'schedulePost',
                            pages: selectedPages,
                            title: articleTitle,
                            link: articleLink,
                            schedule: scheduleDateTime
                        };

                        // AJAX call to the schedule_post PHP function
                        $.ajax({
                            url: 'fb_post_handler.php', // Update this path to your PHP file
                            type: 'POST',
                            data: JSON.stringify(postData),
                            contentType: 'application/json',
                            success: function(response) {
                                // Handle success response
                                alert('Scheduled successfully: ' + response.message);
                                $('#fbShare').modal('hide');
                            },
                            error: function(xhr, status, error) {
                                // Handle error response
                                alert('An error occurred: ' + error);
                            }
                        });
                    } else {
                        alert('Please select a date and time to schedule the post.');
                    }
                } else {
                    alert('Please select at least one page to schedule.');
                }
            });
        });
    </script>

</body>

</html>