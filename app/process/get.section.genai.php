<?php
require_once "../inc/validate.logged.php";
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
                <h4 class="m-0">GenAI</h4>
            </li>
        </ol>

        <? if ($gUserPlan == 0) {
        ?>
            <div class="alert alert-success" role="alert">The <b>GenAI</b> is only available for <b>Pro</b> users! Go to My Account to upgrade.</div>
        <?
        } else { ?>
            <div class="button-bar">
                <div class="button-container">
                    <button id="reportButton1" class="reportButton" value="#reporter" title="Convert the text that follows as a newspaper report">
                        <i class="fas fa-newspaper dropdown-icon"></i> Report
                    </button>
                    <button id="reportButton2" class="reportButton" value="#tran-ek" title="Convert the text that follows into Kannada language">
                        <i class="fas fa-exchange-alt dropdown-icon"></i> Tran-ek
                    </button>
                    <button id="reportButton3" class="reportButton" value="#tran-ke" title="Convert the text in Kannada that follows into English language">
                        <i class="fas fa-exchange-alt dropdown-icon"></i> Tran-ke
                    </button>
                    <button id="reportButton4" class="reportButton" value="#list" title="Respond with HTML entities that can be rendered directly without postprocessing. Add an HTML break entity after each item.">
                        <i class="fas fa-list dropdown-icon"></i> List
                    </button>
                    <button id="reportButton5" class="reportButton" value="#post" title="Convert the text that follows as a social post.">
                        <i class="fas fa-image"></i> Post
                    </button>
                    <button id="reportButton6" class="reportButton" value="#note" title="Convert the text that follows as a note.">
                        <i class="fas fa-sticky-note dropdown-icon"></i> Note
                    </button>
                    <button id="reportButton7" class="reportButton" value="#code" title="Respond with the amazing coding solution.">
                        <i class="fas fa-code dropdown-icon"></i> Code
                    </button>

                </div>


                <div class="right">
                    <!-- <a id="whatsapp-share-link" href="javascript:void(0);">
                    <i class="fab fa-2x fa-whatsapp-square" style="position:relative;top:5px;color:#f58020;"></i>
                </a> -->

                    <!-- Button to redirect to the try creative genai -->
                    <a href="javascript:np()" onclick="goSection('genaicreative', this)">
                        <button class="btn btn-primary gen3">Try Creative</button>
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
        <? } ?>

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
            $('.loadingIndicator').show();
            var headline = $('#working_headline').val();

            $.ajax({
                url: '/genai/process_genai.php',
                type: 'POST',
                data: {
                    avatar: avatar,
                    working_headline: headline
                },
                success: function(response) {
                    try {
                        var decodedData = decodeURIComponent(response);
                    } catch {
                        decodedData = response;
                    }
                    decodedData = convert_text(decodedData);
                    $('.generated-text').append(decodedData);
                    $('.generated-text').append('<br><br>');
                    $(".generated-content").show();
                    $('.loadingIndicator').hide();
                },
                error: function() {
                    console.error('AJAX request failed');
                    alert('Error: Unable to fetch data.');
                    $('.loadingIndicator').hide();
                }
            });
        });

        // Click handler for '.reportButton' elements
        $('.reportButton').click(function() {
            avatar = $(this).attr('value');
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