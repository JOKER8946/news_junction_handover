<?php
session_start();
$_SESSION['.generated-text'] = "";
unset($_SESSION['prompt_message']);
?>

<script src="inc/genai_func.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>


<style>
    .loader {
        border: 6px solid rgba(0, 0, 0, 0.1);
        border-left-color: #f58020;
        border-radius: 50%;
        width: 35px;
        height: 35px;
        animation: spin 1s linear infinite;
        margin: 0 auto;

    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }
</style>

<div id="GenAI-Container">
    <div class="container my-4" style="padding-bottom: 4rem;">
        <ol class="breadcrumb my-3">
            <li class="breadcrumb-item">
                <h4 class="m-0">GenAI Creative</h4>
            </li>
        </ol>
        <div class="button-bar">
            <div class="button-container">
                <button id="reportButton1" class="reportButton" value="#todo" title="Convert the text that follows as a todo item.">
                    <i class="fas fa-check dropdown-icon"></i> Todo
                </button>
                <button id="reportButton2" class="reportButton" value="#joke" title="use text as a context or theme to write a joke.">
                    <i class="fas fa-laugh dropdown-icon"></i> Joke
                </button>
                <button id="reportButton3" class="reportButton" value="#bored" title="Respond with a motivational anecdote especially from among Robin Williams, Emerson, Mark Twain, Jim Rohn, Dale Carnegie, Simon Sinek">
                    <i class="fas fa-grin-squint-tears dropdown-icon"></i> Bored
                </button>
                <button id="reportButton4" class="reportButton" value="#advise" title="Take the following text to give me advise with anecdotes on how it was done by someone else.">
                    <i class="fas fa-comment-alt dropdown-icon"></i> Advise
                </button>
                <button id="reportButton5" class="reportButton" value="#done" title="Congratulate me and make me feel on top of the world.">
                    <i class="as fa-check-circle dropdown-icon"></i> Done
                </button>
                <button id="reportButton6" class="reportButton" value="#working" title="help me keep my focus on the current work.">
                    <i class="fas fa-tools dropdown-icon"></i> Working
                </button>
                <button id="reportButton7" class="reportButton" value="#ben" title="Respond like you are Ben Franklin, picking from your writings and thoughts and motivating and guiding.">
                    <i class="fas fa-user dropdown-icon"></i> Ben
                </button>                
            </div>


            <div class="right">
                <!-- <a id="whatsapp-share-link" href="javascript:void(0);">
                    <i class="fab fa-2x fa-whatsapp-square" style="position:relative;top:5px;color:#f58020;"></i>
                </a> -->

                <!-- Button to redirect to the try creative genai -->
                <a href="javascript:np()" onclick="goSection('genai', this)">
                    <button class="btn btn-primary gen3">Back to GenAI Creator</button>
                </a>
            </div>
        </div>
        <div class="" style="margin-top: 1rem; margin-bottom: 1rem">
            <textarea name="working_headline" placeholder="Enter your working headline..." id="working_headline" class="form-control" rows="2"></textarea>
        </div>

        <div class="" style="display: flex; justify-content:space-between">
            <div class="">
                <button id="submit-btn" class="btn btn-primary">Generate</button>
                <button id="clear-btn" name="clear" class="btn btn-secondary"><i class="fas fa-eraser"></i> Clear</button>
                <a id="whatsapp-share-link" href="javascript:void(0);">
                    <i class="fab fa-2x fa-whatsapp-square" style="margin-left: 5px;position:relative;top:5px;color:#f58020;"></i>
                </a>
            </div>

            <div class="" style="display: flex; justify-content:end; gap:10px">
                <div class="loadingIndicator" style="display: none;">
                    <div class="loader"></div>
                </div>
                <button id="reset-btn" name="reset" class="float-right btn btn-secondary  classReset" style="min-width: max-content; max-height:34px"><i class="fas fa-trash-alt"></i> Reset</i></button>
            </div>
        </div>





        <div class="generated-content border bg-white p-3 mt-3" style="display: none;">
            <div class="content-show">
                <div class="" style="display: flex; justify-content:space-between">
                    <h5>Generated Content:</h5>
                </div>




                <div class="generated-text">

                </div>

            </div>


            <div class="" style="display: flex; justify-content:right">
                <div class="button-content" style="display: flex; justify-content:right">
                    <button onclick="copyText()" class=" btn btn-warning " style="border: none; background:transparent">
                        <span>
                            <i class="far fa-copy fa-lg fa-fw"></i>
                        </span>
                    </button>
                </div>
                <input class="float-right btn btn-primary" id="saveButton" type="button" value="Save">
            </div>

        </div>

    </div>

</div>



<script>
    // Add click event listeners to all buttons
    $('.reportButton').click(function() {
        var value = $(this).val();
        var title = $(this).attr('title');

        setActiveButton(this.id);
    });
    // Initialize by showing content for the first button
    var initialValue = $('#reportButton1').val();
    var initialTitle = $('#reportButton1').attr('title');

    $('#reportButton1').addClass('active-tab'); // Set first button as active initially
    // Function to generate the content
    var avatar = '';




    $(document).ready(function() {

        // Check if the div with class 'code-language' is empty
        if ($.trim($('.code-language').text()) === '') {
            $('.code-language').hide(); // Hide the div if it's empty
        }

        $('#saveButton').click(function() {
            var generatedText = $('.generated-text').html();
            var workingHeadline = $('#working_headline').val();
            generatedText = replaceBrWithNewline(generatedText);
            if (!workingHeadline) {
                alert("Working Headline cannot be empty..");
                return;
            }

            console.log(generatedText);
            console.log(workingHeadline);

            // Call genai_save function here
            genai_save(generatedText, workingHeadline);
        });


        // Function to share through whatsapp
        $('#whatsapp-share-link').click(function(event) {
            event.preventDefault(); // Prevent default link behavior

            // Call the share_whatsapp function when the link is clicked
            share_whatsapp();
        });

        $('#submit-btn').click(function() {
            $('.loadingIndicator').show(); // Show loading indicator on button click

            console.log("Trying to call the function");

            var headline = $('#working_headline').val(); // Get the value of the textarea

            // AJAX call using jQuery
            $.ajax({
                url: '/genai/process_genai.php', // PHP file to handle the request
                type: 'POST', // Method type
                data: {
                    avatar: avatar, // Assuming avatar is defined elsewhere
                    working_headline: headline
                }, // Data to send
                beforeSend: function() {
                    // This function executes before the AJAX request is sent
                    console.log('AJAX request sending...');
                },
                success: function(response) {
                    console.log('AJAX request successful');
                    console.log(response);

                    // On success, update the content of the div
                    try {
                        var decodedData = decodeURIComponent(response);
                    } catch {
                        decodedData = response;
                    }
                    decodedData = convert_text(decodedData);

                    // Append new content to existing .generated-text div
                    $('.generated-text').append(decodedData);
                    $('.generated-text').append('<br><br>');


                    $(".generated-content").show();

                    // Hide loading indicator after successful response
                    $('.loadingIndicator').hide();
                },
                error: function() {
                    console.error('AJAX request failed');
                    // On error, show an alert or handle the error gracefully
                    alert('Error: Unable to fetch data.');
                    // Hide loading indicator on error
                    $('.loadingIndicator').hide();
                }
            });
        });

        // Click handler for '.reportButton' elements
        $('.reportButton').click(function() {
            avatar = $(this).attr('value');
            console.log(avatar); // Optional: Log the avatar value
        });

        // Function for Clear button
        $('#clear-btn').click(function() {
            $('#working_headline').val(''); // Clear textarea
        });

        // Function for Reset button
        $('#reset-btn').click(function() {
            $.ajax({
                url: '/genai/process_genai.php',
                type: 'POST',
                data: {
                    action: "reset"
                },
                success: function(response) {
                    if (response == "Ok") {
                        $('#working_headline').val(''); // Clear textarea
                        $('.generated-text').html('');
                        $('.generated-content').hide();
                    }
                },
                error: function() {
                    console.error('Error while sending the request');
                    $('.loadingIndicator').hide();
                }
            });
        });
    });
</script>