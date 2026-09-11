<?
include './inc/php/db_config.php';
include './inc/php/validate.logged.php';
include './inc/php/function.php';
include 'inc/function.php';


// Cream: Create
$act = '';
if (!empty($_POST)) $act = isset($_POST["act"]) ? $_POST["act"] : '';

// Create Post
if ($act == 'createPost') {
    $postTitle = isset($_POST['postTitle']) ? $_POST['postTitle'] : '';
    $postBody = isset($_POST['postBody']) ? $_POST['postBody'] : '';
    $isArchive = isset($_POST['isArchive']) ? trim($_POST['isArchive']) : '';
    $isReadMore = isset($_POST['isReadMore']) ? trim($_POST['isReadMore']) : '';
    $readMoreTxt = isset($_POST['readMoreTxt']) ? trim($_POST['readMoreTxt']) : '';
    $readMoreResponse = isset($_POST['readMoreResponse']) ? trim($_POST['readMoreResponse']) : '';
    $readMoreEmail = isset($_POST['readMoreEmail']) ? trim($_POST['readMoreEmail']) : '';
    $isMandatoryCompany = (isset($_POST['isMandatoryCompany']) && $_POST['isMandatoryCompany'] !== 'undefined')
        ? (int)$_POST['isMandatoryCompany']
        : 'NULL';

    $isMandatoryEmail = (isset($_POST['isMandatoryEmail']) && $_POST['isMandatoryEmail'] !== 'undefined')
        ? (int)$_POST['isMandatoryEmail']
        : 'NULL';

    $isMandatoryMobile = (isset($_POST['isMandatoryMobile']) && $_POST['isMandatoryMobile'] !== 'undefined')
        ? (int)$_POST['isMandatoryMobile']
        : 'NULL';

    $isArchive = ($isArchive === '1') ? 1 : 'NULL';
    $isReadMore = ($isReadMore === '1') ? 1 : 'NULL';

    if ($isMandatoryCompany == '' || $isMandatoryCompany == 'undefined') $isMandatoryCompany = 'NULL';
    if ($isMandatoryEmail == '' || $isMandatoryEmail == 'undefined') $isMandatoryEmail = 'NULL';
    if ($isMandatoryMobile == '' || $isMandatoryMobile == 'undefined') $isMandatoryMobile = 'NULL';
    if ($isReadMore == '') {
        $isMandatoryCompany = 'NULL';
        $isMandatoryEmail = 'NULL';
        $isMandatoryMobile = 'NULL';
    }
    if ($postTitle != '' && $postBody != '') {
        $postTitle = mysqli_real_escape_string($creamdb, $postTitle);
        $postBody = mysqli_real_escape_string($creamdb, $postBody);
        $sql = "INSERT INTO user_collection(user_id,title,description,is_archive, is_read_more,read_more_txt,read_more_response,read_more_email,is_mandatory_company,is_mandatory_email,is_mandatory_mobile,date_added) VALUES($gUserId,'$postTitle','$postBody',$isArchive, $isReadMore,'$readMoreTxt','$readMoreResponse','$readMoreEmail',$isMandatoryCompany,$isMandatoryEmail,$isMandatoryMobile,Now())";
        //  echo $sql."<br>";
        mysqli_query($creamdb, $sql);
        $postId = mysqli_insert_id($creamdb);

        // For Business Gyan
        if ($gUserId == 287) {
            $datePublished = isset($_POST['datePublished']) ? $_POST['datePublished'] : '';
            if ($datePublished <> '') {
                $sql = "UPDATE user_collection SET date_published='$datePublished' WHERE id=$postId AND user_id=$gUserId";
                mysqli_query($creamdb, $sql);
            }
            $pageViewStart = isset($_POST['pageViewStart']) ? $_POST['pageViewStart'] : '';
            if ($pageViewStart <> '') {
                $sql = "UPDATE user_collection SET page_view_start=$pageViewStart WHERE id=$postId AND user_id=$gUserId";
                mysqli_query($creamdb, $sql);
            }
            $author = isset($_POST['author']) ? $_POST['author'] : '';
            if ($author <> '') {
                $author = mysqli_real_escape_string($creamdb, $author);
                $sql = "UPDATE user_collection SET author='$author' WHERE id=$postId AND user_id=$gUserId";
                mysqli_query($creamdb, $sql);
            }
            $articleTag = isset($_POST['articleTag']) ? $_POST['articleTag'] : '';
            if ($articleTag <> '') {
                $arrArticleTags = explode(',', $articleTag);
                foreach ($arrArticleTags as $value) {
                    $sql = "INSERT INTO user_collection_tag(articleId,articleTag) VALUES($postId,'$value')";
                    mysqli_query($creamdb, $sql);
                }
            }
        }

        if (isset($_FILES['uploadCover'])) {
            $temp = $_FILES['uploadCover'];
            if (is_uploaded_file($temp['tmp_name'])) {

                $fileExt = strtolower(pathinfo($temp['name'], PATHINFO_EXTENSION));
                $fileUpload = $postId . '-' . time() . '.' . $fileExt;
                move_uploaded_file($temp['tmp_name'], 'data/covers/' . $fileUpload);
                $sql = "UPDATE user_collection SET cover_img='$fileUpload' WHERE id=$postId AND user_id=$gUserId";
                mysqli_query($creamdb, $sql);
            }
        }
        echo "OK";
    }
}


// Default
if ($act == '') {
    $location = json_decode(find_ipgeo_location(), true);

    $res = trim($location['City'] . ($location['City'] && $location['Country'] ? ", " : "") . $location['Country']);
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>

        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Create | News Junction</title>

        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

        <!-- Magnific Popup -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/jquery.magnific-popup.min.js"></script>

        <!-- Bootstrap, Font Awesome, etc. -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
        <link rel="icon" type="image/x-icon" href="grfx/img/logo.ico">

        <!-- Custom CSS -->

        <link rel="stylesheet" href="inc/css/social.css">

        <!-- Scripts -->
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
        <!-- <script src="https://cdn.tiny.cloud/1/kz1jcdrlicpzilnm0x80vemrxz252921vwmb10kytce5n9ez/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script> -->

        <!-- EXPIRED API -->
        <!-- <script src="https://cdn.tiny.cloud/1/gg63dftxs904yq8t5rs5qyu8xo1wnzpfo1rflntk3u6ic37t/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script> -->

        <!-- <script src="https://cdn.tiny.cloud/1/5yjnpbss8885tihd6pg7yaxy3q4hgkbi3mjxqk1ydk8wd45x/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script> -->

        <!-- <script src="https://cdn.tiny.cloud/1/z48eqoog13tw2bmewb1b9k4z4g8h512evj14dpo12v3tjt7z/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script> -->

        <script src="https://cdn.tiny.cloud/1/720y3p95h0psi2n78gt09nbiyiqixqixlrezcaaelxhh34sd/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
        <!-- <script src="assets/tinymce/js/tinymce/tinymce.min.js" referrerpolicy="origin"></script> -->

        <script src=".inc/js/common.js"></script>


        <style>
            .newsroll-dropdown {
                border: none;
                outline: none;
                position: relative;
                display: inline-block;
                /* display: flex; */
                align-items: center;
                padding-left: 8px;
                /* border: none; */
                padding-bottom: 8px;
            }

            .newsroll-dropbtn {
                border: none;
                color: rgba(255, 255, 255, 0.5);
                background-color: #212529;
            }

            .newsroll-dropbtn:focus {
                border: none;
                outline: none;

            }

            .form-control {
                color: black;
                background-color: #fff;
                border: 1px solid var(--border-sec-color);
            }

            .newsroll-dropdown-content {
                display: none;
                outline: none;
                position: relative;
                background-color: #212529;
                min-width: 160px;
            }

            .newsroll-dropdown-content a {
                color: white;
                padding: 12px 16px;
                text-decoration: none;
                display: block;
            }

            .show {
                display: block;
            }

            .btn-primary {
                color: #fff;
                background-color: #db5919;
                border-color: #db5919;
            }

            .footer {
                text-align: center;
                padding: 20px;
                background-color: var(--footer-bg-dark) !important;
                color: var(--text-primary);

                position: fixed;
                bottom: 0;
                width: 100%;
            }

            .breadcrumb {
                display: -ms-flexbox;
                display: flex;
                -ms-flex-wrap: wrap;
                flex-wrap: wrap;
                padding: .75rem 1rem;
                margin-bottom: 1rem;
                list-style: none;
                background-color: transparent !important;
                border-radius: .25rem;
            }

            #frmPost {
                margin-bottom: 60px;
                /* Adjust as needed based on the footer's height */
            }




            tbody th {
                color: var(--text-primary);
                text-decoration: none;
                background-color: transparent;
            }


            thead th {
                color: var(--text-primary);
                text-decoration: none;
                background-color: transparent;
            }


            a {
                color: var(--text-primary);
                text-decoration: none;
                background-color: transparent;
            }

            .table td,
            .table th {
                padding: .75rem;
                vertical-align: top;
                border-top: 1px solid white;
            }


            .table {
                width: 100%;
                margin-bottom: 1rem;
                color: var(--text-primary);

            }

            @media screen and (min-width:768px) {

                .container-fluid,
                .container-lg,
                .container-md,
                .container-sm,
                .container-xl {
                    width: 90% !important;
                    padding-right: 15px;
                    padding-left: 15px;
                    margin-right: auto;
                    margin-left: auto;
                    margin-top: 10px;
                }

            }

            .dropdown-menu {
                position: absolute;
                top: 100%;
                left: -100px;
                z-index: 1000;
                display: none;
                float: left;
                min-width: 10rem;
                padding: .5rem 0;
                margin: .125rem 0 0;
                font-size: 1rem;
                color: var(--text-primary);

                text-align: left;
                list-style: none;
                background-color: #fff;
                background-clip: padding-box;
                border: 1px solid rgba(0, 0, 0, .15);
                border-radius: .25rem;
            }


            .go-back-bar {
                margin-top: 20px !important;
            }

            .tox-promotion {
                display: none !important;
            }
        </style>

    </head>

    <body>


        <div>
            <? include 'inc/php/social_navbar.php' ?>
            <? include 'inc/php/social_sidebar.php' ?>
            <main class="search-main-content main-content">

                <div class="container-fluid  col-sm-12 col-md-12 sideMaincontent">
                    <ol class="breadcrumb my-3">
                        <li class="breadcrumb-item">
                            <h4 class="m-0">Create</h4>
                        </li>
                    </ol>
                    <div class="row mb-4">
                        <div class="col">
                            <ul class="nav nav-tabs mb-4">
                                <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#rss" role="tab" onclick="$('#panelStatus').html('')">Create your Post</a></li>
                                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#newsletter" role="tab" onclick="$('#panelStatus').html('')">Add from My Collections</a></li>
                            </ul>
                            <div class="tab-content m-3">
                                <div class="tab-pane fade show active" id="rss" role="tabpanel">
                                    <form id="frmPost">
                                        <div class="form-row">
                                            <div class="form-group col">
                                                <label for="postTitle">Title</label>
                                                <input type="text" class="form-control" id="postTitle" name="postTitle" maxlength="500" />
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col">
                                                <textarea id="postBody" name="postBody"></textarea>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-4">
                                                <label for="uploadCover">Cover Image</label>
                                            </div>
                                            <div class="form-group col-md-8">
                                                <div class="form-check form-check-inline ml-0 ml-sm-2 w-100">
                                                    <input type="file" class="form-control-file" id="uploadCover" name="uploadCover" accept="image/*" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-4">
                                                <label for="isArchive">Archive Item</label>
                                            </div>
                                            <div class="form-group col-md-8">
                                                <div class="form-check form-check-inline ml-0 ml-sm-2 w-50">
                                                    <input class="form-check-input" type="radio" id="isArchive" name="isArchive" value="1">
                                                    <label class="form-check-label" for="isArchive" style="padding: 5px 10px;">Yes</label>
                                                </div>
                                                <div class="form-check form-check-inline ml-0 ml-sm-2 w-50">
                                                    <input class="form-check-input" type="radio" id="isArchiveNo" name="isArchive" value="0" checked>
                                                    <label class="form-check-label" for="isArchiveNo" style="padding: 5px 10px;">No</label>
                                                </div>
                                            </div>
                                        </div>
                                        <?
                                        if ($gUserPlan == 0) {
                                            echo '<div class="alert alert-success" role="alert">Lead Capture feature is only available in <b>Pro</b> plan! Go to My Account to upgrade.</div>';
                                        } else {
                                        ?>

                                            <!-- <div class="form-row">
                                                <div class="form-group col-md-4">
                                                    <label for="isReadMore">Show Call To Action Button</label>
                                                </div>
                                                <div class="form-group col-md-8">
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" id="isReadMoreYes" name="isReadMore" value="1">
                                                        <label class="form-check-label" for="isReadMoreYes">Yes</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" id="isReadMoreNo" name="isReadMore" value="0" checked>
                                                        <label class="form-check-label" for="isReadMoreNo">No</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-4">
                                                    <label for="readMoreTxt">Call To Action Button Text</label>
                                                </div>
                                                <div class="form-group col-md-8">
                                                    <input type="text" class="form-control" id="readMoreTxt" name="readMoreTxt" maxlength="50" />
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-4 pt-2">
                                                    <label for="readMoreResponse">Call To Action Response</label>
                                                </div>
                                                <div class="form-group col-md-8">
                                                    <input type="text" class="form-control" id="readMoreResponse" name="readMoreResponse" maxlength="300" />
                                                    <small class="form-text text-muted">Enter a URL (including http or https) or if left blank, will show a default Thank you page</small>
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-4 pt-2">
                                                    <label for="readMoreResponse">Call To Action Email</label>
                                                </div>
                                                <div class="form-group col-md-8">
                                                    <input type="text" class="form-control" id="readMoreEmail" name="readMoreEmail" maxlength="300" />
                                                    <small class="form-text text-muted">Enter a valid email addresses seperated by commas where you want the lead details to be emailed</small>
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-4">
                                                    <label for="isMandatoryCompany">Call To Action Mandatory Fields</label>
                                                </div>
                                                <div class="form-group col-md-8">
                                                    <div class="row">
                                                        <div class="col-12 col-lg-4"><label class="form-control border-0"><input type="checkbox" id="isMandatoryCompany" name="isMandatoryCompany" value="1"> Company/Institution</label></div>
                                                        <div class="col-12 col-lg-4"><label class="form-control border-0"><input type="checkbox" id="isMandatoryEmail" name="isMandatoryEmail" value="1"> Email</label></div>
                                                        <div class="col-12 col-lg-4"><label class="form-control border-0"><input type="checkbox" id="isMandatoryMobile" name="isMandatoryMobile" value="1"> Mobile</label></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="hidden" id="act" name="act" value="createPost" /> -->
                                            <? } ?>
                                            <button class="btn btn-primary" onclick="return chkCreatePost()">Save Post</button>
                                    </form>
                                </div>
                                <div class="tab-pane fade" id="newsletter" role="tabpanel">
                                    <?
                                    $numFeed = 1;
                                    $sql = "SELECT * FROM user_collection WHERE user_id=$gUserId AND is_archive IS NULL ORDER BY id DESC limit 10";
                                    $result = mysqli_query($creamdb, $sql);
                                    $numRows = mysqli_num_rows($result);
                                    if ($numRows > 0) {
                                    ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">#</th>
                                                        <th scope="col">Post</th>
                                                        <th scope="col">Publisher</th>
                                                        <th scope="col">Copy</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        $collectionId = $row['id'];
                                                        $collectionTitle = $row['title'];
                                                        $collectionURL = $row['url'];
                                                        $collectionDesc = $row['description'];

                                                        // Clean up newlines in description
                                                        $collectionDesc = str_replace(array("\r", "\n"), '', $collectionDesc);

                                                        // Check if $collectionURL is not empty or null
                                                        if (!empty($collectionURL)) {
                                                            // Extract the publisher domain from the URL
                                                            $collectionPublisher = substr($collectionURL, strpos($collectionURL, ".") + 1);
                                                            $collectionPublisher = ucfirst(strtok($collectionPublisher, '.'));
                                                        } else {
                                                            // Fallback if the URL is empty or null
                                                            $collectionPublisher = 'Cream';
                                                        }

                                                        // Generate the collection link
                                                        $collectionLink = '/view/' . $collectionId . '/' . createArticleURL($collectionTitle);

                                                        // Check if user subdomain is set, and generate full URL
                                                        if ($gUserSubdomain <> '') {
                                                            $collectionLinkFull = 'https://newsjunction.net' . $collectionLink;
                                                        } else {
                                                            $collectionLinkFull = 'https://newsjunction.net' . $collectionLink;
                                                        }

                                                        // Prepare the text for copying to clipboard (sanitize for HTML and escape quotes)
                                                        $copyClipboard = htmlspecialchars(str_replace("'", "\'", $collectionTitle)) . '\n' .
                                                            htmlspecialchars(str_replace("'", "\'", $collectionDesc)) . '\n' .
                                                            htmlspecialchars($collectionLinkFull);
                                                    ?>
                                                        <tr>
                                                            <th scope="row"><?= $numFeed ?>.</th>
                                                            <td><a href="javascript:np()" onclick="openWin('<?= $collectionLink ?>')"><?= $collectionTitle ?></a></td>
                                                            <th><?= $collectionPublisher ?></th>
                                                            <th align="right"><a href="javascript:np()" onclick="copyToClipboard('<?= $copyClipboard ?>')" title="Copy to Clipboard"><i class="far fa-clipboard fa-lg text-muted pr-2"></i></a></th>
                                                        </tr>
                                                    <?
                                                        $numFeed += 1;
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        <?
                                    } else {
                                        ?>
                                            You do not have any items in your collection!
                                        <?
                                    }
                                        ?>
                                        </div>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </main>
            <?php include 'inc/php/footer.php' ?>

        </div>



    </body>
    <script>
        tinymce.init({
            init_instance_callback: 'insert_contents',
            selector: 'textarea',
            height: 400,
            statusbar: false,
            forced_root_block: '',
            force_br_newlines: true,
            force_p_newlines: false,
            file_picker_types: 'image',
            automatic_uploads: true,
            paste_data_images: true,
            paste_as_text: true, // Force plain text paste
            invalid_elements: 'span',
            extended_valid_elements: 'script[src|async|defer|type|charset]',
            images_upload_url: 'process/upload.php',
            file_picker_callback: function(callback, value, meta) {
                var input = document.createElement('input');
                input.setAttribute('type', 'file');
                input.setAttribute('accept', 'image/*');
                input.onchange = function() {
                    var file = this.files[0];
                    var reader = new FileReader();
                    reader.onload = function() {
                        var id = 'blob' + (new Date()).getTime();
                        var blobCache = tinymce.activeEditor.editorUpload.blobCache;
                        var base64 = reader.result.split(',')[1];
                        var blobInfo = blobCache.create(id, file, base64);
                        blobCache.add(blobInfo);
                        callback(blobInfo.blobUri(), {
                            title: file.name
                        });
                    };
                    reader.readAsDataURL(file);
                };
                input.click();
            },
            menubar: 'edit format',
            menubar: true, // Enable menubar for mobile
            toolbar: 'undo redo | bold italic underline strikethrough | fontselect fontsizeselect formatselect | alignleft aligncenter alignright alignjustify | outdent indent | numlist bullist checklist | forecolor backcolor removeformat | link unlink anchor image media | table insertdatetime charmap hr pagebreak | code fullscreen preview | searchreplace visualblocks visualchars emoticons template',
            plugins: [
                'advlist', 'anchor', 'autolink', 'autosave', 'charmap', 'code', 'codesample',
                'emoticons', 'fullscreen', 'help', 'image', 'insertdatetime', 'link', 'lists',
                'media', 'pagebreak', 'preview', 'searchreplace', 'table', 'template', 'visualblocks',
                'visualchars', 'wordcount', 'imagetools', 'nonbreaking', 'paste', 'quickbars'
            ],
            contextmenu: false, // Use the native context menu for better paste support
            mobile: {
                toolbar: 'undo redo | bold italic underline strikethrough | fontselect fontsizeselect formatselect | alignleft aligncenter alignright alignjustify | outdent indent | numlist bullist checklist | forecolor backcolor removeformat | link unlink anchor image media | table insertdatetime charmap hr pagebreak | code fullscreen preview | searchreplace visualblocks visualchars emoticons template',
                plugins: [
                    'advlist', 'anchor', 'autolink', 'autosave', 'charmap', 'code', 'codesample',
                    'emoticons', 'fullscreen', 'help', 'image', 'insertdatetime', 'link', 'lists',
                    'media', 'pagebreak', 'preview', 'searchreplace', 'table', 'template', 'visualblocks',
                    'visualchars', 'wordcount', 'imagetools', 'nonbreaking', 'paste', 'quickbars'
                ],
                menubar: true
            },
            setup: function(editor) {
                editor.on('paste', function(e) {
                    console.log('Pasting content:', e.clipboardData.getData('text/plain'));
                    // Additional handling if needed
                });
            }
        });

        function insert_contents(inst) {
            inst.setContent('');
        }
    </script>
    <script>
        function newsrollToggleDropdown() {
            var dropdownContent = document.getElementById("newsrollDropdown");
            dropdownContent.classList.toggle("show");
        }

        // Close the dropdown if the user clicks outside of it
        window.onclick = function(event) {
            if (!event.target.matches('.newsroll-dropbtn')) {
                var dropdowns = document.getElementsByClassName("newsroll-dropdown-content");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }
    </script>

    <!-- <script>
        function chkCreatePost() {
            $('#panelStatus').html('');
            var postTitle = $('#postTitle').val();
            var postBody = tinymce.get('postBody').getContent();
            if (postTitle == '') {
                alert('Error: Title not entered!');
                return false;
            }
            if (postBody == '') {
                alert('Error: Post is empty!');
                return false;
            }
            tinyMCE.triggerSave();
            tinymce.activeEditor.uploadImages(function(success) {
                var articleTag = $('input[name=articleTag]:checked').map(function() {
                    return this.value;
                }).get();
                var formData = new FormData();
                formData.append('act', 'createPost');
                formData.append('postTitle', postTitle);
                formData.append('postBody', postBody);
                formData.append('articleTag', articleTag);
                formData.append('datePublished', $('#datePublished').val());
                formData.append('pageViewStart', $('#pageViewStart').val());
                formData.append('author', $('#author').val());
                formData.append('isArchive', $('input[name=isArchive]:checked').val());
                formData.append('isReadMore', $('input[name=isReadMore]:checked').val());
                formData.append('readMoreTxt', $('#readMoreTxt').val());
                formData.append('readMoreResponse', $('#readMoreResponse').val());
                formData.append('readMoreEmail', $('#readMoreEmail').val());
                formData.append('isMandatoryCompany', $('input[name=isMandatoryCompany]:checked').val());
                formData.append('isMandatoryEmail', $('input[name=isMandatoryEmail]:checked').val());
                formData.append('isMandatoryMobile', $('input[name=isMandatoryMobile]:checked').val());
                formData.append('uploadCover', $('#uploadCover')[0].files[0]);


                $.ajax({
                        method: "POST",
                        url: 'create.php',
                        data: formData,
                        processData: false,
                        contentType: false,
                        enctype: 'multipart/form-data'
                    })
                    .done(function(msg) {
                        if (msg == 'OK') {
                            tinymce.get('postBody').setContent('');
                            console.log("Ajax Message: " + msg)
                            $('#rss').html('Post has been created!<br><br><button class="btn btn-primary" onclick="location.reload()">Add a New Post</button>');
                        }
                    });
            });
            return false;
        }
    </script> -->

    <script>
        // Function to handle the Save Post button click
        function chkCreatePost() {
            // Clear any previous status
            $('#panelStatus').html('');

            // Collect form data
            var postTitle = $('#postTitle').val();
            var postBody = tinymce.get('postBody').getContent();

            // Check if title and body are filled in
            if (postTitle == '') {
                alert('Error: Title not entered!');
                return false;
            }
            if (postBody == '') {
                alert('Error: Post is empty!');
                return false;
            }

            // Trigger the save for TinyMCE content
            tinyMCE.triggerSave();

            // Collect other form data (e.g., isArchive, isReadMore, etc.)
            var articleTag = $('input[name=articleTag]:checked').map(function() {
                return this.value;
            }).get();

            var formData = new FormData();
            formData.append('act', 'createPost');
            formData.append('postTitle', postTitle);
            formData.append('postBody', postBody);
            formData.append('articleTag', articleTag);
            formData.append('isArchive', $('input[name=isArchive]:checked').val());
            formData.append('isReadMore', $('input[name=isReadMore]:checked').val());
            formData.append('readMoreTxt', $('#readMoreTxt').val());
            formData.append('readMoreResponse', $('#readMoreResponse').val());
            formData.append('readMoreEmail', $('#readMoreEmail').val());
            formData.append('uploadCover', $('#uploadCover')[0].files[0]);

            // Submit form via AJAX
            $.ajax({
                method: "POST",
                url: 'create.php', // Adjust to your server-side processing script
                data: formData,
                processData: false,
                contentType: false,
                enctype: 'multipart/form-data',
                success: function(response) {
                    console.log("Response from server: ", response);
                    if (response == 'OK') {
                        tinymce.get('postBody').setContent('');
                        $('#rss').html(
                            'Post has been created!<br><br>' +
                            '<button class="btn btn-primary" onclick="location.reload()">Add a New Post</button>' +
                            '<button class="btn btn-primary" onclick="location.href=\'my_collection.php\'">Go to My Collections</button>'
                        );

                    } else {
                        $('#rss').html('Error: Could not create post.');
                    }
                },
                error: function(xhr, status, error) {
                    $('#rss').html('An error occurred while processing the request.');
                    console.log('Error:', error);
                }
            });

            return false; // Prevent default form submission
        }
    </script>


    <script>
        function copyToClipboard(note) {
            // Append the custom text to the note
            var textToCopy = note;

            // Try using the Clipboard API first
            if (navigator.clipboard) {
                navigator.clipboard.writeText(textToCopy).then(function() {
                    alert('Note copied to clipboard: ' + textToCopy);
                }).catch(function(error) {
                    console.error('Clipboard API error: ', error);
                    fallbackCopy(textToCopy);
                });
            } else {
                console.error('Clipboard API is not available');
                fallbackCopy(textToCopy);
            }

            // Fallback method using a temporary textarea element
            function fallbackCopy(textToCopy) {
                // Create a temporary textarea element using jQuery
                var $tempTextArea = $('<textarea>');

                // Set the value of the textarea to the text we want to copy
                $tempTextArea.val(textToCopy).appendTo('body');

                // Focus the textarea and select the content using jQuery
                $tempTextArea.focus().select();
                $tempTextArea[0].setSelectionRange(0, textToCopy.length); // For mobile devices

                // Try executing the copy command
                try {
                    var successful = document.execCommand('copy');
                    if (successful) {
                        alert('Note copied to clipboard: ' + textToCopy);
                    } else {
                        alert('Failed to copy note.');
                    }
                } catch (err) {
                    console.error('Error copying text: ', err);
                    alert('Failed to copy note.');
                } finally {
                    // Remove the temporary textarea from the document
                    $tempTextArea.remove();
                }
            }

        }
    </script>

    </html>

<? } ?>