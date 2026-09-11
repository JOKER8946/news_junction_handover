<?
// Cream: My Collections

require_once 'assets/php/validate.logged.php';
require_once 'inc/config.php';
require_once 'inc/simplepie/autoloader.php';


function esamudaayshare($feedId, $gUserId)
{
    global $db;
    if (in_array($gUserId, $esamudaayUsers)) {
        if (checkesamudaay($db, $feedId)) { ?>
            <button class="btn btn-success" id="eSamudaayShare" disabled>Shared to eSamudaay</button>
        <? } else { ?>
            <button class="btn btn-primary" id="eSamudaayShare" onclick="esamudaayshare(<?= $feedId ?>)">eSamudaay Share</button>
        <? } ?>

    <? }
}

function checkesamudaay($db, $feedId)
{
    $sql = "SELECT COUNT(*) AS count FROM user_collection WHERE id = ? AND esamudaay = 1";
    $stmt = $db->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $feedId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $count = $row['count'];
        $stmt->close();
        return $count == 1;
    } else {
        echo "Error preparing statement: " . $db->error;
        return false;
    }
}

function createArticleURL($title)
{
    if ($title <> '') {
        $title = str_replace(' ', '-', $title);
        $title = str_replace('%', '', $title);
        $title = str_replace("'", "", $title);
        return $title;
    } else {
        return '';
    }
}

$act = '';
$actAfter = '';
if (!empty($_POST)) {
    $act = isset($_POST["act"]) ? $_POST["act"] : '';
    $actAfter = isset($_POST["actAfter"]) ? $_POST["actAfter"] : '';
}

// Add to Newsletter
if ($act == 'addNewsletter') {
    $data = isset($_POST['data']) ? $_POST['data'] : '';
    if ($data != '') {
        $listId = '';
        $arrData = json_decode($data, true);
        foreach ($arrData as &$value) {
            $listId .= $value['id'] . ',';
        }
        if ($gUserPlan == 0) {
            $sql = "SELECT id FROM user_newsletter WHERE user_id=$gUserId AND MONTH(date_created)=MONTH(NOW())";
            $result = mysqli_query($db, $sql);
            $numRows = mysqli_num_rows($result);
            if ($numRows > 0) {
                echo "NO";
            } else {
                $sql = "INSERT INTO user_newsletter(user_id,article_id,date_created) VALUES($gUserId,'$listId',Now())";
                mysqli_query($db, $sql);
                echo "OK";
            }
        } else {
            $sql = "INSERT INTO user_newsletter(user_id,article_id,date_created) VALUES($gUserId,'$listId',Now())";
            mysqli_query($db, $sql);
            echo "OK";
        }
    }
}


// Add to Landing
if ($act == 'addLanding') {
    $data = isset($_POST['data']) ? $_POST['data'] : '';
    if ($data != '') {
        $listId = '';
        $arrData = json_decode($data, true);
        foreach ($arrData as &$value) {
            $sql = "INSERT INTO user_landing(user_id,article_id,date_created) VALUES($gUserId," . $value['id'] . ",Now())";
            mysqli_query($db, $sql);
        }
        echo "OK";
    }
}

// Remove from Showcase
if ($act == 'removeShowcase') {
    $collectionId = isset($_POST['collectionId']) ? $_POST['collectionId'] : '';
    if ($collectionId != '') {
        $sql = "DELETE FROM user_landing WHERE user_id=$gUserId AND article_id=$collectionId";
        mysqli_query($db, $sql);
        echo "OK";
    }
}

// Add to Covid
if ($act == 'addCovid') {
    $data = isset($_POST['data']) ? $_POST['data'] : '';
    if ($data != '') {
        $listId = '';
        $arrData = json_decode($data, true);
        foreach ($arrData as &$value) {
            $sql = "INSERT INTO user_covid(user_id,article_id,date_created) VALUES($gUserId," . $value['id'] . ",Now())";
            mysqli_query($db, $sql);
        }
        echo "OK";
    }
}


// Email Collection
if ($act == 'sendPost') {
    $editId = isset($_POST['editId']) ? $_POST['editId'] : '';
    $sendName = isset($_POST['sendName']) ? $_POST['sendName'] : '';
    $sendEmail = isset($_POST['sendEmail']) ? $_POST['sendEmail'] : '';
    $sendBody = isset($_POST['sendBody']) ? trim($_POST['sendBody']) : '';
    sendEmail($sendName, $sendEmail, '', 'News Junction', $sendBody);
    echo "OK";
}


// Edit Collection
if ($act == 'edit') {
    $editId = isset($_POST['editId']) ? $_POST['editId'] : '';
    $postTitle = isset($_POST['postTitle']) ? $_POST['postTitle'] : '';
    $postBody = isset($_POST['postBody']) ? trim($_POST['postBody']) : '';
    $isArchive = isset($_POST['isArchive']) ? trim($_POST['isArchive']) : '';
    $isReadMore = isset($_POST['isReadMore']) ? trim($_POST['isReadMore']) : '';
    $readMoreTxt = isset($_POST['readMoreTxt']) ? trim($_POST['readMoreTxt']) : '';
    $readMoreResponse = isset($_POST['readMoreResponse']) ? trim($_POST['readMoreResponse']) : '';
    $readMoreEmail = isset($_POST['readMoreEmail']) ? trim($_POST['readMoreEmail']) : '';
    $isMandatoryCompany = isset($_POST['isMandatoryCompany']) ? trim($_POST['isMandatoryCompany']) : '';
    $isMandatoryEmail = isset($_POST['isMandatoryEmail']) ? trim($_POST['isMandatoryEmail']) : '';
    $isMandatoryMobile = isset($_POST['isMandatoryMobile']) ? trim($_POST['isMandatoryMobile']) : '';
    if ($isArchive == 0) $isArchive = 'NULL';
    if ($isReadMore == 0) $isReadMore = 'NULL';
    if ($isMandatoryCompany == '' || $isMandatoryCompany == 'undefined') $isMandatoryCompany = 'NULL';
    if ($isMandatoryEmail == '' || $isMandatoryEmail == 'undefined') $isMandatoryEmail = 'NULL';
    if ($isMandatoryMobile == '' || $isMandatoryMobile == 'undefined') $isMandatoryMobile = 'NULL';
    if ($isReadMore == '') {
        $isMandatoryCompany = 'NULL';
        $isMandatoryEmail = 'NULL';
        $isMandatoryMobile = 'NULL';
    }
    if ($editId != '' && $postTitle != '' && $postBody != '') {
        $postTitle = mysqli_real_escape_string($db, $postTitle);
        $postBody = mysqli_real_escape_string($db, $postBody);

        $sql = "UPDATE user_collection SET title='$postTitle',description='$postBody',is_archive=$isArchive,is_read_more=$isReadMore,read_more_txt='$readMoreTxt',read_more_response='$readMoreResponse',read_more_email='$readMoreEmail',is_mandatory_company=$isMandatoryCompany,is_mandatory_email=$isMandatoryEmail,is_mandatory_mobile=$isMandatoryMobile,date_modified=Now() WHERE id=$editId AND user_id=$gUserId";
        mysqli_query($db, $sql);
        $sql = "UPDATE user_collection SET title='$postTitle',description='$postBody',date_modified=Now() WHERE share_collection_id=$editId AND share_user_id=$gUserId";
        mysqli_query($db, $sql);

        // For Business Gyan
        if ($gUserId == 287) {
            $datePublished = isset($_POST['datePublished']) ? $_POST['datePublished'] : '';
            if ($datePublished <> '') {
                $sql = "UPDATE user_collection SET date_published='$datePublished' WHERE id=$editId AND user_id=$gUserId";
                mysqli_query($db, $sql);
            }
            $pageViewStart = isset($_POST['pageViewStart']) ? $_POST['pageViewStart'] : '';
            if ($pageViewStart <> '') {
                $sql = "UPDATE user_collection SET page_view_start=$pageViewStart WHERE id=$editId AND user_id=$gUserId";
                mysqli_query($db, $sql);
            }
            $author = isset($_POST['author']) ? $_POST['author'] : '';
            if ($author <> '') {
                $author = mysqli_real_escape_string($db, $author);
                $sql = "UPDATE user_collection SET author='$author' WHERE id=$editId AND user_id=$gUserId";
                mysqli_query($db, $sql);
            }
            $sql = "DELETE FROM user_collection_tag WHERE articleId=$editId";
            mysqli_query($db, $sql);
            $articleTag = isset($_POST['articleTag']) ? $_POST['articleTag'] : '';
            if ($articleTag <> '') {
                $arrArticleTags = explode(',', $articleTag);
                foreach ($arrArticleTags as $value) {
                    $sql = "INSERT INTO user_collection_tag(articleId,articleTag) VALUES($editId,'$value')";
                    mysqli_query($db, $sql);
                }
            }
        }

        if (isset($_FILES['uploadCover'])) {
            $temp = $_FILES['uploadCover'];
            if (is_uploaded_file($temp['tmp_name'])) {
                $fileExt = strtolower(pathinfo($temp['name'], PATHINFO_EXTENSION));
                $fileUpload = $editId . '-' . time() . '.' . $fileExt;
                move_uploaded_file($temp['tmp_name'], '../data/covers/' . $fileUpload);
                $sql = "UPDATE user_collection SET cover_img='$fileUpload' WHERE id=$editId AND user_id=$gUserId";
                mysqli_query($db, $sql);
                $sql = "UPDATE user_collection SET cover_img='$fileUpload' WHERE share_collection_id=$editId AND share_user_id=$gUserId";
                mysqli_query($db, $sql);
            }
        }
        echo "OK";
    }
}


// Restore Collection
if ($act == 'restoredCollection') {
    $collectionId = isset($_POST['delId']) ? $_POST['delId'] : '';
    if ($collectionId != '') {
        $sql = "UPDATE user_collection SET is_archive=NULL WHERE id=$collectionId AND user_id=$gUserId";
        mysqli_query($db, $sql);
        echo "OK";
    }
}


// Archive Collection
if ($act == 'archivedCollection') {
    $collectionId = isset($_POST['delId']) ? $_POST['delId'] : '';
    if ($collectionId != '') {
        $sql = "UPDATE user_collection SET is_archive=1 WHERE id=$collectionId AND user_id=$gUserId";
        mysqli_query($db, $sql);
        echo "OK";
    }
}


// Delete Collection
if ($act == 'deleteCollection') {
    $collectionId = isset($_POST['delId']) ? $_POST['delId'] : '';
    if ($collectionId != '') {
        $sql = "DELETE FROM user_collection WHERE id=$collectionId AND user_id=$gUserId";
        mysqli_query($db, $sql);
        echo "OK";
    }
}


// Show Restore Collection
if ($act == 'restoreCollection') {
    $collectionId = isset($_POST['id']) ? $_POST['id'] : '';
    ?>
    <div class="popup" style="width:420px">
        <div class="widget">
            <form id="frmDel" name="frmDel">
                <div class="card">
                    <div class="card-header bg-dark">
                        <h5 class="mb-0 text-light">Restore Collection</h5>
                    </div>
                </div>
                <div id="widget_B" style="padding:15px 25px">
                    You are about to restore this to My Collections!
                </div>
                <div id="widget_F" style="border-top:1px solid #ebedf2;padding:20px 10px;">
                    <div class="col">
                        <button type="button" class="btn btn-primary" onclick="return chkRestoreCollection()">Confirm</button>
                    </div>
                </div>
                <input type="hidden" id="delId" name="delId" value="<?= $collectionId ?>" />
                <input type="hidden" id="act" name="act" value="restoredCollection" />
            </form>
        </div>
    </div>
<?
}


// Show Archive Collection
if ($act == 'archiveCollection') {
    $collectionId = isset($_POST['id']) ? $_POST['id'] : '';
?>
    <div class="popup" style="width:420px">
        <div class="widget">
            <form id="frmDel" name="frmDel">
                <div class="card">
                    <div class="card-header bg-dark">
                        <h5 class="mb-0 text-light">Archive Collection</h5>
                    </div>
                </div>
                <div id="widget_B" style="padding:15px 25px">
                    You are about to archive this from My Collections!
                </div>
                <div id="widget_F" style="border-top:1px solid #ebedf2;padding:20px 10px;">
                    <div class="col">
                        <button type="button" class="btn btn-primary" onclick="return chkArchiveCollection()">Confirm</button>
                    </div>
                </div>
                <input type="hidden" id="delId" name="delId" value="<?= $collectionId ?>" />
                <input type="hidden" id="act" name="act" value="archivedCollection" />
            </form>
        </div>
    </div>
<?
}


// Show Delete Collection
if ($act == 'delCollection') {
    $collectionId = isset($_POST['id']) ? $_POST['id'] : '';
?>
    <div class="popup" style="width:420px">
        <div class="widget">
            <form id="frmDel" name="frmDel">
                <div class="card">
                    <div class="card-header bg-dark">
                        <h5 class="mb-0 text-light">Delete Collection</h5>
                    </div>
                </div>
                <div id="widget_B" style="padding:15px 25px">
                    You are about to delete this from My Collections!
                </div>
                <div id="widget_F" style="border-top:1px solid #ebedf2;padding:20px 10px;">
                    <div class="col">
                        <button type="button" class="btn btn-primary" onclick="return chkDelCollection()">Confirm</button>
                    </div>
                </div>
                <input type="hidden" id="delId" name="delId" value="<?= $collectionId ?>" />
                <input type="hidden" id="act" name="act" value="deleteCollection" />
            </form>
        </div>
    </div>
<?
}


// Show Edit
if ($act == 'editForm') {
    $isValid = 0;
    $postId = isset($_POST['id']) ? $_POST['id'] : '';
    $sql = "SELECT * FROM user_collection WHERE id=$postId";
    $result = mysqli_query($db, $sql);
    $numRows = mysqli_num_rows($result);
    if ($numRows > 0) {
        $row = mysqli_fetch_assoc($result);
        $postTitle = $row['title'];
        $postBody = $row['description'];
        $imgCover = $row['cover_img'];
        $author = $row['author'];
        $isArchive = $row['is_archive'];
        $isReadMore = $row['is_read_more'];
        $readMoreTxt = $row['read_more_txt'];
        $readMoreResponse = $row['read_more_response'];
        $readMoreEmail = $row['read_more_email'];
        $isMandatoryCompany = $row['is_mandatory_company'];
        $isMandatoryEmail = $row['is_mandatory_email'];
        $isMandatoryMobile = $row['is_mandatory_mobile'];
        $isValid = 1;

        // For Business Gyan
        if ($gUserId == 287) {
            $datePublished = $row['date_published'];
            $pageViewStart = $row['page_view_start'];
            $articleTag = ',';
            $sql = "SELECT articleTag FROM user_collection_tag WHERE articleId=$postId";
            $resultInner = mysqli_query($db, $sql);
            while ($rowInner = mysqli_fetch_assoc($resultInner)) {
                $itemArticleTag = $rowInner['articleTag'];
                $articleTag .= $itemArticleTag . ',';
            }
        }
    }
?>
    <div class="popup" style="max-width:800px">
        <div class="widget">
            <form id="frmEdit" name="frmEdit">
                <div class="card">
                    <div class="card-header bg-dark">
                        <h5 class="mb-0 text-light">Edit Item</h5>
                    </div>
                </div>
                <div id="widget_B" style="padding:15px 25px; max-height:calc(100vh - 200px);overflow-y:auto;">
                    <? if ($isValid == 1) { ?>
                        <div class="form-row">
                            <div class="form-group col">
                                <label for="articleTitle">Title</label>
                                <input type="text" class="form-control" id="postTitle" name="postTitle" value="<?= $postTitle ?>" />
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col">
                                <textarea style="width:100%" id="postBody" name="postBody"><?= $postBody ?></textarea>
                            </div>
                        </div>

                        <?
                        // For Business Gyan
                        if ($gUserId == 287) {
                        ?>
                            <div class="form-row mb-3">
                                <div class="form-group col-md-4">
                                    <label for="articleTag">Article Tags</label>
                                </div>
                                <div class="form-row col-md-8">

                                    <div class="col-12 col-md-4">
                                        <b>Marketing</b><br>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Branding & Advertising" <? if (strpos($articleTag, ',Branding & Advertising,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Branding & Advertising</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Customer" <? if (strpos($articleTag, ',Customer,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Customer</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Competition" <? if (strpos($articleTag, ',Competition,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Competition</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Networking" <? if (strpos($articleTag, ',Networking,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Networking</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Business Networking" <? if (strpos($articleTag, ',Business Networking,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Business Networking</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Sales" <? if (strpos($articleTag, ',Sales,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Sales</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Social Media" <? if (strpos($articleTag, ',Social Media,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Social Media</label></div>
                                    </div>

                                    <div class="col-12 mt-4 col-md-4 mt-md-0">
                                        <b>Human Resource</b><br>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="HR Management" <? if (strpos($articleTag, ',HR Management,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">HR Management</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Organization Structures" <? if (strpos($articleTag, ',Organization Structures,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Organization Structures</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Training" <? if (strpos($articleTag, ',Training,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Training</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Recruit" <? if (strpos($articleTag, ',Recruit,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Recruit</label></div>
                                    </div>

                                    <div class="col-12 mt-4 col-md-4 mt-md-0">
                                        <b>Technology</b><br>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Enterprise Software" <? if (strpos($articleTag, ',Enterprise Software,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Enterprise Software</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Personal Software" <? if (strpos($articleTag, ',Personal Software,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Personal Software</label></div>
                                    </div>

                                    <div class="col-12 col-md-4 mt-4">
                                        <b>Finance</b><br>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Funding" <? if (strpos($articleTag, ',Funding,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Funding</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Accounting" <? if (strpos($articleTag, ',Accounting,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Accounting</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Taxation" <? if (strpos($articleTag, ',Taxation,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Taxation</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Treasury" <? if (strpos($articleTag, ',Treasury,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Treasury</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Management Information Systems" <? if (strpos($articleTag, ',Management Information Systems,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Management Information Systems </label></div>
                                    </div>

                                    <div class="col-12 col-md-4 mt-4">
                                        <b>Operations</b><br>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Quality" <? if (strpos($articleTag, ',Quality,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Quality</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Facility management" <? if (strpos($articleTag, ',Facility management,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Facility management</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Infrastructure" <? if (strpos($articleTag, ',Infrastructure,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Infrastructure</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Office Automation" <? if (strpos($articleTag, ',Office Automation,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Office Automation</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Customer Service" <? if (strpos($articleTag, ',Customer Service,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Customer Service</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Automation" <? if (strpos($articleTag, ',Automation,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Automation</label></div>
                                    </div>

                                    <div class="col-12 col-md-4 mt-4">
                                        <b>Industry</b><br>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Ites & Bpo" <? if (strpos($articleTag, ',Ites & Bpo,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Ites & Bpo</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Hospitality" <? if (strpos($articleTag, ',Hospitality,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Hospitality</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="IT" <? if (strpos($articleTag, ',IT,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">IT</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Manufacturing" <? if (strpos($articleTag, ',Manufacturing,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Manufacturing</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Retail" <? if (strpos($articleTag, ',Retail,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Retail</label></div>
                                    </div>

                                    <div class="col-12 col-md-4 mt-4">
                                        <b>Leadership</b><br>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Empowerment" <? if (strpos($articleTag, ',Empowerment,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Empowerment</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Innovation" <? if (strpos($articleTag, ',Innovation,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Innovation</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Inspiration" <? if (strpos($articleTag, ',Inspiration,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Inspiration</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Srategy" <? if (strpos($articleTag, ',Srategy,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Srategy</label></div>
                                    </div>

                                    <div class="col-12 col-md-4 mt-4">
                                        <b>Work Life</b><br>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Personality Development" <? if (strpos($articleTag, ',Personality Development,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Personality Development</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Success" <? if (strpos($articleTag, ',Success,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Success</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Health" <? if (strpos($articleTag, ',Health,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Health</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Personal Finance" <? if (strpos($articleTag, ',Personal Finance,') !== false) { ?> CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">Personal Finance</label></div>
                                    </div>

                                    </tr>
                                    </table>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="author">Author(s)</label>
                                </div>
                                <div class="form-group col-md-8">
                                    <input type="text" class="form-control" id="author" name="author" maxlength="200" value="<?= $author ?>" />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="pageViewStart">Published Date</label>
                                </div>
                                <div class="form-group col-md-8">
                                    <input type="date" class="form-control" id="datePublished" name="datePublished" style="width:145px" value="<?= $datePublished ?>" />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="pageViewStart">Initial Page View</label>
                                </div>
                                <div class="form-group col-md-8">
                                    <input type="text" class="form-control" id="pageViewStart" name="pageViewStart" style="width:100px" onkeyPress="return numCheck(event)" maxlength="8" value="<?= $pageViewStart ?>" />
                                </div>
                            </div>
                        <? } ?>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="uploadCover">Cover Image</label>
                            </div>
                            <div class="form-group col-md-8">
                                <div class="form-check form-check-inline ml-0 ml-sm-2 w-50">
                                    <input type="file" class="form-control-file" id="uploadCover" name="uploadCover" accept="image/*" />
                                </div>
                                <? if ($imgCover <> '') { ?>
                                    <div class="mb-2"><a href="../data/covers/<?= $imgCover ?>" target="_blank">View Current Image</a></div>
                                <? } ?>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="isArchive">Archive Item</label>
                            </div>
                            <div class="form-group col-md-8">
                                <div class="form-check form-check-inline ml-0 ml-sm-2 w-25">
                                    <label class="form-control radio-inline border-0 pl-1 pr-0"><input type="radio" id="isArchive" name="isArchive" value="1" <? if ($isArchive == 1) { ?> CHECKED<? } ?>> Yes</label>
                                    <label class="form-control radio-inline border-0 pl-1 pr-0"><input type="radio" id="isArchive" name="isArchive" value="0" <? if ($isArchive == 0) { ?> CHECKED<? } ?>> No</label>
                                </div>
                            </div>
                        </div>
                        <?
                        if ($gUserPlan == 0) {
                            echo '<div class="alert alert-success" role="alert">Call to Action section is only available in <b>Pro</b> plan! Go to My Account to upgrade.</div>';
                        } else {
                        ?>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="isReadMore">Show Call To Action Button</label>
                                </div>
                                <div class="form-group col-md-8">
                                    <div class="form-check form-check-inline ml-0 ml-sm-2 w-25">
                                        <label class="form-control radio-inline border-0 pl-1 pr-0"><input type="radio" id="isReadMore" name="isReadMore" value="1" <? if ($isReadMore == 1) { ?> CHECKED<? } ?>> Yes</label>
                                        <label class="form-control radio-inline border-0 pl-1 pr-0"><input type="radio" id="isReadMore" name="isReadMore" value="0" <? if ($isReadMore == 0) { ?> CHECKED<? } ?>> No</label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="readMoreTxt">Call To Action Button Text</label>
                                </div>
                                <div class="form-group col-md-8">
                                    <input type="text" class="form-control" id="readMoreTxt" name="readMoreTxt" maxlength="50" value="<?= $readMoreTxt ?>" />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4 pt-2">
                                    <label for="readMoreResponse">Call To Action Response</label>
                                </div>
                                <div class="form-group col-md-8">
                                    <input type="text" class="form-control" id="readMoreResponse" name="readMoreResponse" maxlength="300" value="<?= $readMoreResponse ?>" />
                                    <small class="form-text text-muted">Enter a URL (including http or https) or if left blank, will show a default Thank you page</small>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4 pt-2">
                                    <label for="readMoreResponse">Call To Action Email</label>
                                </div>
                                <div class="form-group col-md-8">
                                    <input type="text" class="form-control" id="readMoreEmail" name="readMoreEmail" maxlength="300" value="<?= $readMoreEmail ?>" />
                                    <small class="form-text text-muted">Enter a valid email addresses seperated by commas where you want the lead details to be emailed</small>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="isMandatoryCompany">Show Call To Action Mandatory Fields</label>
                                </div>
                                <div class="form-group col-md-8">
                                    <div class="row no-gutters">
                                        <div class="col-12 col-lg-4"><label class="form-control border-0"><input type="checkbox" id="isMandatoryCompany" name="isMandatoryCompany" value="1" <? if ($isMandatoryCompany == 1) { ?> CHECKED<? } ?>> Company/Institution</label></div>
                                        <div class="col-12 col-lg-4"><label class="form-control border-0"><input type="checkbox" id="isMandatoryEmail" name="isMandatoryEmail" value="1" <? if ($isMandatoryEmail == 1) { ?> CHECKED<? } ?>> Email</label></div>
                                        <div class="col-12 col-lg-4"><label class="form-control border-0"><input type="checkbox" id="isMandatoryMobile" name="isMandatoryMobile" value="1" <? if ($isMandatoryMobile == 1) { ?> CHECKED<? } ?>> Mobile</label></div>
                                    </div>
                                </div>
                            </div>
                        <? } ?>
                    <? } ?>
                </div>
                <div id="widget_F" style="border-top:1px solid #ebedf2;padding:20px 10px;">
                    <div class="col">
                        <button type="submit" class="btn btn-primary" onclick="return chkEditPost()">Update</button>
                        <button type="submit" class="btn btn-light ml-1" onclick="return clearForm('frmEdit')">Reset</button>
                        <div id="panelStatus" class="float-right text-sm text-danger" style="margin-top:5px" align="right"></div>
                    </div>
                </div>
                <input type="hidden" id="editId" name="editId" value="<?= $postId ?>" />
                <input type="hidden" id="act" name="act" value="edit" />
            </form>
        </div>
    </div>
<?
}


// Show Email
if ($act == 'emailForm') {
    $isValid = 0;
    $postId = isset($_POST['id']) ? $_POST['id'] : '';
    $sql = "SELECT * FROM user_collection WHERE id=$postId";
    $result = mysqli_query($db, $sql);
    $numRows = mysqli_num_rows($result);
    if ($numRows > 0) {
        $row = mysqli_fetch_assoc($result);
        $postTitle = $row['title'];
        $postBody = $row['description'];
        $postURL = $row['url'];
        $postLink = '/view/' . $postId . '/' . createArticleURL($postTitle);
        if ($gUserSubdomain <> '') {
            $postLinkFull = 'https://newsjunction.net' . $postLink;
        } else {
            $postLinkFull = 'https://newsjunction.net' . $postLink;
        }
        $imgCover = $row['cover_img'];
        $isValid = 1;
    }
?>
    <div class="popup" style="max-width:800px">
        <div class="widget">
            <form id="frmEdit" name="frmEdit">
                <div class="card">
                    <div class="card-header bg-dark">
                        <h5 class="mb-0 text-light">Email Item</h5>
                    </div>
                </div>
                <div id="widget_B" style="padding:15px 25px; max-height:calc(100vh - 200px);overflow-y:auto;">
                    <? if ($isValid == 1) { ?>
                        <div class="form-row">
                            <div class="form-group col-6">
                                <label for="sendName">Recipient Name</label>
                                <input type="text" class="form-control" id="sendName" name="sendName" maxlength="100" />
                            </div>
                            <div class="form-group col-6">
                                <label for="sendEmail">Recipient Email</label>
                                <input type="email" class="form-control" id="sendEmail" name="sendEmail" maxlength="100" />
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col">
                                <textarea class="form-control" id="sendBody" name="sendBody" rows="10">
     <h3><a href="<?= $postLinkFull ?>" target="_blank" style="color:#000"><?= $postTitle ?></a></h3>
     <?= $postBody ?>
    </textarea>
                            </div>
                        </div>
                    <? } ?>
                </div>
                <div id="widget_F" style="border-top:1px solid #ebedf2;padding:20px 10px;">
                    <div class="col">
                        <button type="submit" class="btn btn-primary" onclick="return chkSendPost()">Send Email</button>
                        <div id="panelStatus" class="float-right text-sm text-danger" style="margin-top:5px" align="right"></div>
                    </div>
                </div>
                <input type="hidden" id="editId" name="editId" value="<?= $postId ?>" />
                <input type="hidden" id="act" name="act" value="sendEmail" />
            </form>
        </div>
    </div>
<?
}


// Default
if ($act == '') {
    $sql = "SELECT is_doctor FROM user WHERE id=$gUserId";
    $result = mysqli_query($db, $sql);
    $row = mysqli_fetch_assoc($result);
    $isDoctor = $row['is_doctor'];
?>

    <!doctype html>
    <?
    $specialIds = [370, 415];
    ?>
    <html lang="en">

    <head>

        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>News Junction</title>
        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>




        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <!-- Font Awesome CSS -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.0.0/animate.min.css" />
        <!-- <link rel="stylesheet" href="inc/fontawesome/css/all.min.css" /> -->
        <link rel="stylesheet" href="assets/old/magnific-popup.css" />
        <link rel="stylesheet" href="assets/old/style.css" />
        <link rel="stylesheet" href="inc/genai_style.css" />
        <link rel="icon" type="image/x-icon" href="/img/logo.ico">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
        <script src="https://cdn.tiny.cloud/1/u5oz235qw5jiqww4udm90ocp9zg2rncblqo2ch0ym3twjgyt/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
        <script src="https://js.zohostatic.com/books/zfwidgets/assets/js/zf-widget.js"></script>
        <script src="assets/js/magnific-popup.min.js"></script>
        <script src="assets/old/common.js"></script>
        <script src="assets/old/genai_func.js"></script>
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

            .newsroll-dropdown-content {
                display: none;
                outline: none;
                position: relative;
                background-color: #212529;
                min-width: 160px;
                /* box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2); */
                /* z-index: 1; */
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
        </style>
    </head>

    <body class="sb-nav-fixed">

        <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
            <a class="navbar-brand" href="dashboard.php">
                <img src="/inc/img/logo.black.png" height='40px'>
            </a>
            <div class="navbar-nav ml-auto">
                <!-- <div class="navbar-nav text-light" style="margin-top:6px;margin-right:20px"><?= strtok($gUserName, " "); ?></div> -->
                <div class="navbar-nav text-light" style="margin-top:6px;margin-right:20px"><? if ($gUserPlan == 1) { ?>Pro<? } else { ?>Free<? } ?></div>
                <div class="navbar-nav text-light cursorH" style="margin-top:10px;margin-right:10px" onclick="goSection('utils','','showNotifications')"><i class="fas fa-bell"></i></div>
                <ul class="navbar-nav ml-md-0">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" id="userDropdown" href="#" role="button" data-toggle="dropdown"><i class="fas fa-user fa-fw"></i></a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="javascript:goSection('account')">My Account</a>
                            <a class="dropdown-item" href="javascript:goSection('settings')">My Settings</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="process/logout.php" onclick="removeSignedIn()">Logout</a>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>

        <main style="margin-top: 80px">
            <div id="panelContent" class="container-fluid">

                <ol class="breadcrumb my-3">
                    <li class="breadcrumb-item w-100">
                        <div class="text-left w-50">
                            <h4 class="m-0">My Collections</h4>
                        </div>
                        <div class="text-right w-50">
                            <button type="button" id="buttonAddNewsletter" class="hide btn btn-sm btn-success" onclick="chkAddNewsletter()">Generate Newsletter</button>
                            <button type="button" id="buttonAddCovid" class="hide btn btn-sm btn-success" onclick="chkAddCovid()">Add to Covid page</button>
                            <button type="button" id="buttonAddLanding" class="hide btn btn-sm btn-success" onclick="chkAddLanding()">Add to Showcase</button>

                        </div>
                    </li>
                </ol>
                <!-- <ul class="nav nav-tabs mb-4">
                    <li class="nav-item"><a class="nav-link<? if ($actAfter == '' || $actAfter == 'itemActive') { ?> active<? } ?>" data-toggle="tab" href="#itemActive" role="tab" onclick="gActAfter='itemActive'">Active</a></li>
                    <li class="nav-item"><a class="nav-link<? if ($actAfter == 'myShowcase') { ?> active<? } ?>" data-toggle="tab" href="#myShowcase" role="tab" onclick="gActAfter='myShowcase'">My Showcase</a></li>
                    <li class="nav-item"><a class="nav-link<? if ($actAfter == 'itemSharedBy') { ?> active<? } ?>" data-toggle="tab" href="#itemSharedBy" role="tab" onclick="gActAfter='itemSharedBy'">Shared by me</a></li>
                    <li class="nav-item"><a class="nav-link<? if ($actAfter == 'itemSharedWith') { ?> active<? } ?>" data-toggle="tab" href="#itemSharedWith" role="tab" onclick="gActAfter='itemSharedWith'">Shared with me</a></li>
                    <li class="nav-item"><a class="nav-link<? if ($actAfter == 'itemArchived') { ?> active<? } ?>" data-toggle="tab" href="#itemArchived" role="tab" onclick="gActAfter='itemArchived'">Archived</a></li>
                </ul> -->

                <div class="tab-content my-3">

                    <div class="tab-pane fade<? if ($actAfter == '' || $actAfter == 'itemActive') { ?> show active<? } ?>" id="itemActive" role="tabpanel">
                        <div class="panelFeeds" style="max-height:calc(100vh - 245px)">
                            <?
                            $sql = "SELECT id,title,url FROM user_collection WHERE user_id=$gUserId AND share_user_id IS NULL AND is_archive IS NULL ORDER BY id DESC";
                            $result = mysqli_query($db, $sql);
                            $numRows = mysqli_num_rows($result);
                            if ($numRows == 0) {
                                echo '<div class="p-2">You do not have any items in your collections!</div>';
                            } else {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $collectionId = $row['id'];
                                    $collectionTitle = $row['title'];
                                    $collectionURL = $row['url'];
                                    $collectionPublisher = substr($collectionURL, strpos($collectionURL, ".") + 1);
                                    $collectionPublisher = ucfirst(strtok($collectionPublisher, '.'));
                                    if ($collectionPublisher == '') $collectionPublisher = 'Cream';
                                    $collectionLink = '/view/' . $collectionId . '/' . createArticleURL($collectionTitle);
                                    if ($gUserSubdomain <> '') {
                                        $collectionLinkFull = 'https://newsjunction.net' . $collectionLink;
                                    } else {
                                        $collectionLinkFull = 'https://newsjunction.net' . $collectionLink;
                                    }
                            ?>
                                    <div class="card p-0 mb-3 mr-2 border-0" style="background-color:#f5ede7">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-12 col-md-10">
                                                    <h4 class="mb-0"><a href="<?= $collectionLinkFull ?>" target="_blank" style="color:#f26522"><?= $collectionTitle ?></a></h4>
                                                    <div class="mb-3">Publisher: <?= $collectionPublisher ?></div>
                                                </div>
                                                <div class="col-12 col-md-2 text-center text-md-right my-2 m-md-0">
                                                    <? if ($collectionURL == '') { ?>
                                                        <a href="#" title="Edit Item"><i data-id="<?= $collectionId ?>" class="editRow far fa-edit fa-lg text-muted pr-2"></i></a>
                                                    <? } ?>
                                                    <a href="#" title="Archive Item"><i data-id="<?= $collectionId ?>" class="archiveRow fas fa-download fa-lg text-muted pr-2"></i></a>
                                                    <a href="#" title="Delete Item"><i data-id="<?= $collectionId ?>" class="delRow far fa-trash-alt fa-lg text-muted"></i></a>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-12 col-md-5 text-center text-md-left">
                                                    <span id="panelShowcase_<?= $collectionId ?>">
                                                        <?
                                                        $sql = "SELECT article_id FROM user_landing WHERE article_id=$collectionId";
                                                        $resultInner = mysqli_query($db, $sql);
                                                        $numRowsInner = mysqli_num_rows($resultInner);
                                                        if ($numRowsInner == 0) {
                                                        ?>
                                                            <label class="btn btn-outline-danger m-0 mr-3 px-2 py-1 float-left" style="user-select:none"><input type="checkbox" name="landingData" data-feed-id="<?= $collectionId ?>" onclick="addLanding()"> Select for Showcase</label>
                                                        <? } else { ?>
                                                            <div class="text-danger m-0 py-1 pr-3 float-left" style="user-select:none"><i class="far fa-check-square"></i> Showcase (<a href="javascript:np()" onclick="removeShowcase(<?= $collectionId ?>)">Remove</a>)</div>
                                                        <?
                                                        }
                                                        ?>
                                                    </span>
                                                    <?
                                                    if ($isDoctor) {
                                                        $sql = "SELECT article_id FROM user_covid WHERE article_id=$collectionId";
                                                        $resultInner = mysqli_query($db, $sql);
                                                        $numRowsInner = mysqli_num_rows($resultInner);
                                                        if ($numRowsInner == 0) {
                                                    ?>
                                                            <label class="btn btn-outline-danger m-0 mr-3 px-2 py-1 float-left" style="user-select:none"><input type="checkbox" name="covidData" data-feed-id="<?= $collectionId ?>" onclick="addCovid()"> Select for Covid Page</label>
                                                        <? } else { ?>
                                                            <div class="text-danger m-0 py-1 float-left" style="user-select:none"><i class="far fa-check-square"></i> Covid Page</div>
                                                    <?
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                                <div class="data col-12 col-md-7 text-center text-md-right 1mt-2 1m-mt-0" data-feed-id="<?= $collectionId ?>" data-feed-title="<?= $collectionTitle ?>" data-feed-url="<?= $collectionLinkFull ?>" data-feed-publisher="<?= $collectionPublisher ?>">
                                                    <label class="btn-secondary m-0 px-2 py-1" style="user-select:none"><input type="checkbox" name="feedData" onclick="addNewsletter()"> Send to Newsletter</label>
                                                    <label class="buttonCreamShare btn-info m-0 px-2 py-1 mr-2" data-id="<?= $collectionId ?>" style="user-select:none">Cream<i>Share</i></label>
                                                    <a href="#"><i data-id="<?= $collectionId ?>" class="emailRow fas fa-2x fa-envelope-square" style="position:relative;top:5px;color:#f58020;"></i></a>
                                                    <a data-sbg-1="" data-sbg-2="" data-sbg-3="" class="sbg-button pt-1" data-sbg-network="facebook" data-sbg-width="600" data-sbg-height="368"><i class="mt-1 fab fa-facebook-square fa-2x cursorH" style="position:relative;top:5px;color:#f58020;"></i></a>
                                                    <a data-sbg-1="" data-sbg-2="" data-sbg-3="" class="sbg-button" data-sbg-network="twitter" data-sbg-width="600" data-sbg-height="258"><i class="fab fa-twitter-square fa-2x cursorH" style="position:relative;top:5px;color:#f58020;"></i></a>
                                                    <a data-sbg-1="" data-sbg-2="" data-sbg-3="" class="sbg-button" data-sbg-network="linkedin" data-sbg-width="585" data-sbg-height="471"><i class="fab fa-linkedin fa-2x cursorH" style="position:relative;top:5px;color:#f58020;"></i></a>
                                                    <a data-sbg-1="" data-sbg-2="" data-sbg-3="" class="sbg-button" data-sbg-network="pinterest" data-sbg-width="600" data-sbg-height="368"><i class="fab fa-pinterest-square fa-2x cursorH" style="position:relative;top:5px;color:#f58020;"></i></a>
                                                    <a href="http://web.whatsapp.com/send?text=<?= $collectionTitle ?> <?= $collectionLinkFull ?>" target="_blank"><i class="fab fa-2x fa-whatsapp-square" style="position:relative;top:5px;color:#f58020;"></i></a>
                                                    <? esamudaayshare($collectionId, $gUserId) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            <?
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <div class="tab-pane fade<? if ($actAfter == 'myShowcase') { ?> show active<? } ?>" id="myShowcase" role="tabpanel">
                        <div class="panelFeeds" style="max-height:calc(100vh - 163px)">
                            <?
                            $sql = "SELECT A.id,A.title,A.url FROM user_collection A,user_landing B WHERE A.id=B.article_id AND A.user_id=$gUserId ORDER BY A.id DESC";
                            $result = mysqli_query($db, $sql);
                            $numRows = mysqli_num_rows($result);
                            if ($numRows == 0) {
                                echo '<div class="p-2">You do not have selected any items for your Showcase!</div>';
                            } else {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $collectionId = $row['id'];
                                    $collectionTitle = $row['title'];
                                    $collectionURL = $row['url'];
                                    $collectionPublisher = substr($collectionURL, strpos($collectionURL, ".") + 1);
                                    $collectionPublisher = ucfirst(strtok($collectionPublisher, '.'));
                                    if ($collectionPublisher == '') $collectionPublisher = 'Cream';
                                    $collectionLink = '/view/' . $collectionId . '/' . createArticleURL($collectionTitle);
                                    if ($gUserSubdomain <> '') {
                                        $collectionLinkFull = 'https://newsjunction.net' . $collectionLink;
                                    } else {
                                        $collectionLinkFull = 'https://newsjunction.net' . $collectionLink;
                                    }
                            ?>
                                    <div class="card p-0 mb-3 mr-2 border-0" style="background-color:#f5ede7">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-12 col-md-10">
                                                    <h4 class="mb-0"><a href="<?= $collectionLinkFull ?>" target="_blank" style="color:#f26522"><?= $collectionTitle ?></a></h4>
                                                    <div class="mb-3">Publisher: <?= $collectionPublisher ?></div>
                                                </div>
                                                <div class="col-12 col-md-2 text-center text-md-right my-2 m-md-0">
                                                    <? if ($collectionURL == '') { ?>
                                                        <a href="#" title="Edit Item"><i data-id="<?= $collectionId ?>" class="editRow far fa-edit fa-lg text-muted pr-2"></i></a>
                                                    <? } ?>
                                                    <a href="#" title="Archive Item"><i data-id="<?= $collectionId ?>" class="archiveRow fas fa-download fa-lg text-muted pr-2"></i></a>
                                                    <a href="#" title="Delete Item"><i data-id="<?= $collectionId ?>" class="delRow far fa-trash-alt fa-lg text-muted"></i></a>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-12 col-md-4 text-center text-md-left">
                                                    <span id="panelShowcase_<?= $collectionId ?>">
                                                        <div class="text-danger m-0 py-1" style="user-select:none"><i class="far fa-check-square"></i> Showcase (<a href="javascript:np()" onclick="removeShowcase(<?= $collectionId ?>)">Remove</a>)</div>
                                                    </span>
                                                </div>
                                                <div class="data col-12 col-md-8 text-center text-md-right mt-2 m-md-0" data-feed-id="<?= $collectionId ?>" data-feed-title="<?= $collectionTitle ?>" data-feed-url="<?= $collectionLinkFull ?>" data-feed-publisher="<?= $collectionPublisher ?>">
                                                    <label class="btn-secondary m-0 px-2 py-1" style="user-select:none"><input type="checkbox" name="feedData" onclick="addNewsletter()"> Send to Newsletter</label>
                                                    <label class="buttonCreamShare btn-info m-0 px-2 py-1 mr-2" data-id="<?= $collectionId ?>" style="user-select:none">Cream<i>Share</i></label>
                                                    <a href="#"><i data-id="<?= $collectionId ?>" class="emailRow fas fa-2x fa-envelope-square" style="position:relative;top:5px;color:#f58020;"></i></a>
                                                    <a data-sbg-1="" data-sbg-2="" data-sbg-3="" class="sbg-button pt-1" data-sbg-network="facebook" data-sbg-width="600" data-sbg-height="368"><i class="mt-1 fab fa-facebook-square fa-2x cursorH" style="position:relative;top:5px;color:#f58020;"></i></a>
                                                    <a data-sbg-1="" data-sbg-2="" data-sbg-3="" class="sbg-button" data-sbg-network="twitter" data-sbg-width="600" data-sbg-height="258"><i class="fab fa-twitter-square fa-2x cursorH" style="position:relative;top:5px;color:#f58020;"></i></a>
                                                    <a data-sbg-1="" data-sbg-2="" data-sbg-3="" class="sbg-button" data-sbg-network="linkedin" data-sbg-width="585" data-sbg-height="471"><i class="fab fa-linkedin fa-2x cursorH" style="position:relative;top:5px;color:#f58020;"></i></a>
                                                    <a data-sbg-1="" data-sbg-2="" data-sbg-3="" class="sbg-button" data-sbg-network="pinterest" data-sbg-width="600" data-sbg-height="368"><i class="fab fa-pinterest-square fa-2x cursorH" style="position:relative;top:5px;color:#f58020;"></i></a>
                                                    <a href="http://web.whatsapp.com/send?text=<?= $collectionTitle ?> <?= $collectionLinkFull ?>" target="_blank"><i class="fab fa-2x fa-whatsapp-square" style="position:relative;top:5px;color:#f58020;"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            <?
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <div class="tab-pane fade<? if ($actAfter == 'itemSharedBy') { ?> show active<? } ?>" id="itemSharedBy" role="tabpanel">
                        <div class="panelFeeds" style="max-height:calc(100vh - 260px)">
                            <?
                            $sql = "SELECT id,title,url FROM user_collection WHERE share_user_id=$gUserId GROUP BY title,id ORDER BY title";
                            $result = mysqli_query($db, $sql);
                            $numRows = mysqli_num_rows($result);
                            if ($numRows == 0) {
                                echo '<div class="p-2">You have not shared items with anybody!</div>';
                            } else {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $collectionId = $row['id'];
                                    $collectionTitle = $row['title'];
                                    $collectionURL = $row['url'];
                                    $collectionPublisher = substr($collectionURL, strpos($collectionURL, ".") + 1);
                                    $collectionPublisher = ucfirst(strtok($collectionPublisher, '.'));
                                    if ($collectionPublisher == '') $collectionPublisher = 'Cream';
                                    $collectionLink = '/view/' . $collectionId . '/' . createArticleURL($collectionTitle);
                                    $collectionLinkFull = 'https://newsjunction.net' . $collectionLink;
                            ?>
                                    <div class="card p-0 mb-3 mr-2 border-0" style="background-color:#f5ede7">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col">
                                                    <h4><a href="<?= $collectionLink ?>" target="_blank" style="color:#f26522"><?= $collectionTitle ?></a></h4>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-12 col-md-4 text-center text-md-left">Publisher: <?= $collectionPublisher ?></div>
                                                <div class="col-12 col-md-8 text-center text-md-right my-2 m-md-0">
                                                    <?
                                                    $txtSharedWith = '';
                                                    $sql = "SELECT B.full_name FROM user_collection A,user B WHERE A.user_id=B.id AND A.title='$collectionTitle' AND A.share_user_id=$gUserId ORDER BY B.full_name";
                                                    $resultInner = mysqli_query($db, $sql);
                                                    while ($rowInner = mysqli_fetch_assoc($resultInner)) {
                                                        $txtSharedWith .= $rowInner['full_name'] . ', ';
                                                    }
                                                    if ($txtSharedWith != '') $txtSharedWith = rtrim($txtSharedWith, ', ');
                                                    ?>
                                                    <label class="badge-warning m-0 px-3 py-1" style="user-select:none">Shared with <?= $txtSharedWith ?></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            <?
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <div class="tab-pane fade<? if ($actAfter == 'itemSharedWith') { ?> show active<? } ?>" id="itemSharedWith" role="tabpanel">
                        <div class="panelFeeds" style="max-height:calc(100vh - 260px)">
                            <?
                            $sql = "SELECT A.id,A.title,A.url,B.full_name FROM user_collection A,user B WHERE A.share_user_id=B.id AND A.user_id=$gUserId AND A.share_user_id IS NOT NULL AND A.is_archive IS NULL ORDER BY A.id DESC";
                            $result = mysqli_query($db, $sql);
                            $numRows = mysqli_num_rows($result);
                            if ($numRows == 0) {
                                echo '<div class="p-2">You do not have any shared items in your collections!</div>';
                            } else {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $shareName =  $row['full_name'];
                                    $collectionId = $row['id'];
                                    $collectionTitle = $row['title'];
                                    $collectionURL = $row['url'];
                                    $collectionPublisher = substr($collectionURL, strpos($collectionURL, ".") + 1);
                                    $collectionPublisher = ucfirst(strtok($collectionPublisher, '.'));
                                    if ($collectionPublisher == '') $collectionPublisher = 'Cream';
                                    $collectionLink = '/view/' . $collectionId . '/' . createArticleURL($collectionTitle);
                                    $collectionLinkFull = 'https://newsjunction.net' . $collectionLink;
                            ?>
                                    <div class="card p-0 mb-3 mr-2 border-0" style="background-color:#f5ede7">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-12 col-md-10">
                                                    <h4><a href="<?= $collectionLink ?>" target="_blank" style="color:#f26522"><?= $collectionTitle ?></a></h4>
                                                    <div class="mb-3">Publisher: <?= $collectionPublisher ?></div>
                                                </div>
                                                <div class="col-12 col-md-2 text-center text-md-right my-2 m-md-0">
                                                    <a href="#" title="Archive Item"><i data-id="<?= $collectionId ?>" class="archiveRow fas fa-download fa-lg text-muted pr-2"></i></a>
                                                    <a href="#" title="Delete Item"><i data-id="<?= $collectionId ?>" class="delRow far fa-trash-alt fa-lg text-muted"></i></a>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-12 col-md-4 text-center text-md-left">
                                                    <?
                                                    $sql = "SELECT article_id FROM user_landing WHERE article_id=$collectionId";
                                                    $resultInner = mysqli_query($db, $sql);
                                                    $numRowsInner = mysqli_num_rows($resultInner);
                                                    if ($numRowsInner == 0) {
                                                    ?>
                                                        <label class="btn btn-outline-danger m-0 mr-3 px-2 py-1 float-left" style="user-select:none"><input type="checkbox" name="landingData" data-feed-id="<?= $collectionId ?>" onclick="addLanding()"> Select for Showcase</label>
                                                    <? } else { ?>
                                                        <div class="text-danger m-0 py-1 pr-3 float-left" style="user-select:none"><i class="far fa-check-square"></i> Showcase</div>
                                                    <?
                                                    }
                                                    ?>
                                                </div>
                                                <div class="data col-12 col-md-8 text-center text-md-right mt-2 m-md-0" data-feed-id="<?= $collectionId ?>" data-feed-title="<?= $collectionTitle ?>" data-feed-url="<?= $collectionLinkFull ?>" data-feed-publisher="<?= $collectionPublisher ?>">
                                                    <label class="badge-warning m-0 px-3 py-1" style="user-select:none">Shared by <?= $shareName ?></label>
                                                    <label class="btn-secondary m-0 px-2 py-1 mr-2" style="user-select:none"><input type="checkbox" name="feedData" onclick="addNewsletter()"> Send to Newsletter</label>
                                                    <a href="#"><i data-id="<?= $collectionId ?>" class="emailRow fas fa-2x fa-envelope-square" style="position:relative;top:5px;color:#f58020;"></i></a>
                                                    <a data-sbg-1="" data-sbg-2="" data-sbg-3="" class="sbg-button pt-1" data-sbg-network="facebook" data-sbg-width="600" data-sbg-height="368"><i class="mt-1 fab fa-facebook-square fa-2x cursorH" style="position:relative;top:5px;color:#f58020;"></i></a>
                                                    <a data-sbg-1="" data-sbg-2="" data-sbg-3="" class="sbg-button" data-sbg-network="twitter" data-sbg-width="600" data-sbg-height="258"><i class="fab fa-twitter-square fa-2x cursorH" style="position:relative;top:5px;color:#f58020;"></i></a>
                                                    <a data-sbg-1="" data-sbg-2="" data-sbg-3="" class="sbg-button" data-sbg-network="linkedin" data-sbg-width="585" data-sbg-height="471"><i class="fab fa-linkedin fa-2x cursorH" style="position:relative;top:5px;color:#f58020;"></i></a>
                                                    <a data-sbg-1="" data-sbg-2="" data-sbg-3="" class="sbg-button" data-sbg-network="pinterest" data-sbg-width="600" data-sbg-height="368"><i class="fab fa-pinterest-square fa-2x cursorH" style="position:relative;top:5px;color:#f58020;"></i></a>
                                                    <a href="http://web.whatsapp.com/send?text=<?= $collectionTitle ?> <?= $collectionLinkFull ?>" target="_blank"><i class="fab fa-2x fa-whatsapp-square" style="position:relative;top:5px;color:#f58020;"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            <?
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <div class="tab-pane fade<? if ($actAfter == 'itemArchived') { ?> show active<? } ?>" id="itemArchived" role="tabpanel">

                        <div class="panelFeeds" style="max-height:calc(100vh - 260px)">
                            <?
                            $sql = "SELECT A.id,A.title,A.url,B.full_name FROM user_collection A LEFT JOIN user B ON A.share_user_id=B.id WHERE A.user_id=$gUserId AND A.is_archive=1 ORDER BY id DESC";
                            $result = mysqli_query($db, $sql);
                            $numRows = mysqli_num_rows($result);
                            if ($numRows == 0) {
                                echo '<div class="p-2">You do not have any archived items in your collections!</div>';
                            } else {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $shareName =  $row['full_name'];
                                    $collectionId = $row['id'];
                                    $collectionTitle = $row['title'];
                                    $collectionURL = $row['url'];
                                    $collectionPublisher = substr($collectionURL, strpos($collectionURL, ".") + 1);
                                    $collectionPublisher = ucfirst(strtok($collectionPublisher, '.'));
                                    if ($collectionPublisher == '') $collectionPublisher = 'Cream';
                                    $collectionLink = '/view/' . $collectionId . '/' . createArticleURL($collectionTitle);
                            ?>
                                    <div class="card p-0 mb-3 mr-2 border-0" style="background-color:#f5ede7">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-12 col-md-10">
                                                    <h4><a href="<?= $collectionLink ?>" target="_blank" style="color:#f26522"><?= $collectionTitle ?></a></h4>
                                                </div>
                                                <div class="col-12 col-md-2 text-center text-md-right my-2 m-md-0">
                                                    <? if ($collectionURL == '') { ?>
                                                        <a href="#" title="Edit Item"><i data-id="<?= $collectionId ?>" class="editRow far fa-edit fa-lg text-muted pr-2"></i></a>
                                                    <? } ?>
                                                    <a href="#" title="Restore Item"><i data-id="<?= $collectionId ?>" class="restoreRow fas fa-upload fa-lg text-muted pr-2"></i></a>
                                                    <a href="#" title="Delete Item"><i data-id="<?= $collectionId ?>" class="delRow far fa-trash-alt fa-lg text-muted"></i></a>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-12 col-md-8 text-center text-md-left">Publisher: <?= $collectionPublisher ?></div>
                                                <? if ($shareName != '') { ?>
                                                    <div class="col-12 col-md-4 text-center text-md-right mt-2 m-md-0">
                                                        <label class="badge-warning m-0 px-3 py-1" style="user-select:none">Shared by <?= $shareName ?></label>
                                                    </div>
                                                <? } ?>
                                            </div>
                                        </div>
                                    </div>
                            <?
                                }
                            }
                            ?>
                        </div>

                    </div>
                </div>
                <script type="text/javascript">
                    $(function() {
                        $('.buttonCreamShare').magnificPopup({
                            type: 'ajax',
                            closeBtnInside: true,
                            ajax: {
                                settings: {
                                    method: 'POST',
                                    url: 'process/get.section.utils.php'
                                }
                            },
                            callbacks: {
                                elementParse: function(item) {
                                    var data = $(item.el[0]).closest('.data');
                                    postData = {
                                        act: 'showCreamShare',
                                        id: $(item.el[0]).data('id')
                                    }
                                    this.st.ajax.settings.data = postData;
                                }
                            }
                        });
                        $('.editRow').magnificPopup({
                            type: 'ajax',
                            closeBtnInside: true,
                            ajax: {
                                settings: {
                                    method: 'POST',
                                    url: 'process/get.section.collection.php'
                                }
                            },
                            callbacks: {
                                open: function() {
                                    $.magnificPopup.instance._onFocusIn = function(e) {
                                        if ($(e.target).closest('.mce-window')) {
                                            return true;
                                        }
                                        $.magnificPopup.proto._onFocusIn.call(this, e);
                                    };
                                },
                                elementParse: function(item) {
                                    postData = {
                                        act: 'editForm',
                                        id: $(item.el[0]).data('id')
                                    }
                                    this.st.ajax.settings.data = postData;
                                },
                                ajaxContentAdded: function(e) {
                                    tinymce.remove();
                                    tinymce.init({
                                        selector: 'textarea#postBody',
                                        height: 400,
                                        statusbar: false,
                                        forced_root_block: '',
                                        force_br_newlines: true,
                                        force_p_newlines: false,
                                        file_picker_types: 'image',
                                        automatic_uploads: true,
                                        paste_data_images: true,
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
                                        toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright alignjustify | outdent indent | numlist bullist | table | forecolor backcolor removeformat | code fullscreen preview | hr charmap image media link',
                                        plugins: 'image imagetools code preview table charmap paste media autolink link hr fullscreen lists',
                                        contextmenu: 'link image imagetools table spellchecker lists'
                                    });
                                }
                            }
                        });
                        $('.emailRow').magnificPopup({
                            type: 'ajax',
                            closeBtnInside: true,
                            ajax: {
                                settings: {
                                    method: 'POST',
                                    url: 'process/get.section.collection.php'
                                }
                            },
                            callbacks: {
                                elementParse: function(item) {
                                    postData = {
                                        act: 'emailForm',
                                        id: $(item.el[0]).data('id')
                                    }
                                    this.st.ajax.settings.data = postData;
                                },
                                ajaxContentAdded: function() {
                                    tinymce.remove();
                                    tinymce.init({
                                        selector: 'textarea#sendBody',
                                        height: 400,
                                        statusbar: false,
                                        forced_root_block: '',
                                        force_br_newlines: true,
                                        force_p_newlines: false,
                                        file_picker_types: 'image',
                                        automatic_uploads: true,
                                        paste_data_images: true,
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
                                                    cb(blobInfo.blobUri(), {
                                                        title: file.name
                                                    });
                                                };
                                                reader.readAsDataURL(file);
                                            };
                                            input.click();
                                        },
                                        menubar: 'file edit view format',
                                        toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | charmap | fullscreen preview | image media link',
                                        plugins: 'image imagetools code preview table charmap paste media autolink link',
                                        contextmenu: "link image imagetools table spellchecker lists"
                                    });
                                }
                            }
                        });
                        $('.restoreRow').magnificPopup({
                            type: 'ajax',
                            closeBtnInside: true,
                            ajax: {
                                settings: {
                                    method: 'POST',
                                    url: 'process/get.section.collection.php'
                                }
                            },
                            callbacks: {
                                elementParse: function(item) {
                                    postData = {
                                        act: 'restoreCollection',
                                        id: $(item.el[0]).data('id')
                                    }
                                    this.st.ajax.settings.data = postData;
                                }
                            }
                        });
                        $('.archiveRow').magnificPopup({
                            type: 'ajax',
                            closeBtnInside: true,
                            ajax: {
                                settings: {
                                    method: 'POST',
                                    url: 'process/get.section.collection.php'
                                }
                            },
                            callbacks: {
                                elementParse: function(item) {
                                    postData = {
                                        act: 'archiveCollection',
                                        id: $(item.el[0]).data('id')
                                    }
                                    this.st.ajax.settings.data = postData;
                                }
                            }
                        });
                        $('.delRow').magnificPopup({
                            type: 'ajax',
                            closeBtnInside: true,
                            ajax: {
                                settings: {
                                    method: 'POST',
                                    url: 'process/get.section.collection.php'
                                }
                            },
                            callbacks: {
                                elementParse: function(item) {
                                    postData = {
                                        act: 'delCollection',
                                        id: $(item.el[0]).data('id')
                                    }
                                    this.st.ajax.settings.data = postData;
                                }
                            }
                        });
                        generateSM();
                    });
                </script>
            </div>
        </main>

        <footer class="py-2 bg-light">
            <div class="container-fluid">
                <div class="text-muted text-right small">Powered by Knobly Consulting</div>
            </div>
        </footer>





    </body>
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


    </html>


<?
}
?>


<script>
    function esamudaayshare(feedId) {
        console.log(feedId);

        $.ajax({
            url: 'clients/esamudaay.php',
            method: 'POST',
            data: {
                feedId: feedId
            },
            success: function(response) {
                // Handle success
                alert("Added to eSamudaay!!");
                $('#eSamudaayShare').replaceWith(`
        <button class="btn btn-success" id="eSamudaayShare" disabled>Shared to eSamudaay</button>
    `);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                // Handle error
                console.error("Error:", textStatus, errorThrown);
            }
        });

    }
</script>