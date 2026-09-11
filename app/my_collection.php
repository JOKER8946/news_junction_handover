<?
require_once './inc/php/validate.logged.php';
require_once 'inc/config.php';
// require_once 'inc/function.php';
include './inc/php/db_config.php';
include './inc/php/function.php';

function esamudaayshare($feedId, $gUserId)
{
    global $creamdb;
    $esamudaayUsers = [319, 400];
    if (in_array($gUserId, $esamudaayUsers)) {
        if (checkesamudaay($creamdb, $feedId)) { ?>
            <button class="btn btn-success" id="eSamudaayShare" disabled>Shared to eSamudaay</button>
        <? } else { ?>
            <button class="btn btn-primary" id="eSamudaayShare" onclick="esamudaayshare(<?= $feedId ?>)">eSamudaay Share</button>
        <? } ?>

    <? }
}

function checkesamudaay($creamdb, $feedId)
{
    $sql = "SELECT COUNT(*) AS count FROM user_collection WHERE id = ? AND esamudaay = 1";
    $stmt = $creamdb->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $feedId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $count = $row['count'];
        $stmt->close();
        return $count == 1;
    } else {
        echo "Error preparing statement: " . $creamdb->error;
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

$act = isset($_POST["act"]) ? $_POST["act"] : '';
$actAfter = isset($_POST["actAfter"]) ? $_POST["actAfter"] : '';

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
            $result = mysqli_query($creamdb, $sql);
            $numRows = mysqli_num_rows($result);
            if ($numRows > 0) {
                echo "NO";
            } else {
                $sql = "INSERT INTO user_newsletter(user_id,article_id,date_created) VALUES($gUserId,'$listId',Now())";
                mysqli_query($creamdb, $sql);
                echo "OK";
            }
        } else {
            $sql = "INSERT INTO user_newsletter(user_id,article_id,date_created) VALUES($gUserId,'$listId',Now())";
            mysqli_query($creamdb, $sql);
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
            mysqli_query($creamdb, $sql);
        }
        echo "OK";
    }
}

// Remove from Showcase
if ($act == 'removeShowcase') {
    $collectionId = isset($_POST['collectionId']) ? $_POST['collectionId'] : '';
    if ($collectionId != '') {
        $sql = "DELETE FROM user_landing WHERE user_id=$gUserId AND article_id=$collectionId";
        mysqli_query($creamdb, $sql);
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
            mysqli_query($creamdb, $sql);
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
    // Ensure the content is in UTF-8 encoding
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
    if ($isArchive == 0 || $isArchive == '' || $isArchive === 'undefined')
        $isArchive = 'NULL';
    if ($isReadMore == 0 || $isReadMore == '' || $isReadMore === 'undefined')
        $isReadMore = 'NULL';
    if ($isMandatoryCompany == '' || $isMandatoryCompany == 'undefined')
        $isMandatoryCompany = 'NULL';
    if ($isMandatoryEmail == '' || $isMandatoryEmail == 'undefined')
        $isMandatoryEmail = 'NULL';
    if ($isMandatoryMobile == '' || $isMandatoryMobile == 'undefined')
        $isMandatoryMobile = 'NULL';
    if ($isReadMore == '') {
        $isMandatoryCompany = 'NULL';
        $isMandatoryEmail = 'NULL';
        $isMandatoryMobile = 'NULL';
    }
    if ($editId != '' && $postTitle != '' && $postBody != '') {
        $postTitle = mysqli_real_escape_string($creamdb, $postTitle);
        $postBody = mysqli_real_escape_string($creamdb, $postBody);

        $sql = "UPDATE user_collection SET title='$postTitle',description='$postBody',is_archive=$isArchive,is_read_more=$isReadMore,read_more_txt='$readMoreTxt',read_more_response='$readMoreResponse',read_more_email='$readMoreEmail',is_mandatory_company=$isMandatoryCompany,is_mandatory_email=$isMandatoryEmail,is_mandatory_mobile=$isMandatoryMobile,date_modified=Now() WHERE id=$editId AND user_id=$gUserId";
        mysqli_query($creamdb, $sql);
        $sql = "UPDATE user_collection SET title='$postTitle',description='$postBody',date_modified=Now() WHERE share_collection_id=$editId AND share_user_id=$gUserId";
        mysqli_query($creamdb, $sql);

        // For Business Gyan
        if ($gUserId == 287) {
            $datePublished = isset($_POST['datePublished']) ? $_POST['datePublished'] : '';
            if ($datePublished <> '') {
                $sql = "UPDATE user_collection SET date_published='$datePublished' WHERE id=$editId AND user_id=$gUserId";
                mysqli_query($creamdb, $sql);
            }
            $pageViewStart = isset($_POST['pageViewStart']) ? $_POST['pageViewStart'] : '';
            if ($pageViewStart <> '') {
                $sql = "UPDATE user_collection SET page_view_start=$pageViewStart WHERE id=$editId AND user_id=$gUserId";
                mysqli_query($creamdb, $sql);
            }
            $author = isset($_POST['author']) ? $_POST['author'] : '';
            if ($author <> '') {
                $author = mysqli_real_escape_string($creamdb, $author);
                $sql = "UPDATE user_collection SET author='$author' WHERE id=$editId AND user_id=$gUserId";
                mysqli_query($creamdb, $sql);
            }
            $sql = "DELETE FROM user_collection_tag WHERE articleId=$editId";
            mysqli_query($creamdb, $sql);
            $articleTag = isset($_POST['articleTag']) ? $_POST['articleTag'] : '';
            if ($articleTag <> '') {
                $arrArticleTags = explode(',', $articleTag);
                foreach ($arrArticleTags as $value) {
                    $sql = "INSERT INTO user_collection_tag(articleId,articleTag) VALUES($editId,'$value')";
                    mysqli_query($creamdb, $sql);
                }
            }
        }

        if (isset($_FILES['uploadCover'])) {
            $temp = $_FILES['uploadCover'];
            if (is_uploaded_file($temp['tmp_name'])) {
                $fileExt = strtolower(pathinfo($temp['name'], PATHINFO_EXTENSION));
                $fileUpload = $editId . '-' . time() . '.' . $fileExt;
                move_uploaded_file($temp['tmp_name'], 'data/covers/' . $fileUpload);
                $sql = "UPDATE user_collection SET cover_img='$fileUpload' WHERE id=$editId AND user_id=$gUserId";
                mysqli_query($creamdb, $sql);
                $sql = "UPDATE user_collection SET cover_img='$fileUpload' WHERE share_collection_id=$editId AND share_user_id=$gUserId";
                mysqli_query($creamdb, $sql);
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
        mysqli_query($creamdb, $sql);
        echo "OK";
    }
}


// Archive Collection
if ($act == 'archivedCollection') {
    $collectionId = isset($_POST['delId']) ? $_POST['delId'] : '';
    if ($collectionId != '') {
        $sql = "UPDATE user_collection SET is_archive=1 WHERE id=$collectionId AND user_id=$gUserId";
        mysqli_query($creamdb, $sql);
        echo "OK";
    }
}


// Delete Collection
if ($act == 'deleteCollection') {
    $collectionId = isset($_POST['delId']) ? $_POST['delId'] : '';
    if ($collectionId != '') {
        $sql = "DELETE FROM user_collection WHERE id=$collectionId AND user_id=$gUserId";
        mysqli_query($creamdb, $sql);
        echo "OK";
    }
}


// Show Restore Collection
if ($act == 'restoreCollection') {
    $collectionId = isset($_POST['id']) ? $_POST['id'] : '';
    ?>
    <div class="popup">
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
                        <button type="button" class="btn btn-primary"
                            onclick="return chkRestoreCollection()">Confirm</button>
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
    <div class="popup">
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
                        <button type="button" class="btn btn-primary"
                            onclick="return chkArchiveCollection()">Confirm</button>
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
    <div class="popup">
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
    $result = mysqli_query($creamdb, $sql);
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
            $resultInner = mysqli_query($creamdb, $sql);
            while ($rowInner = mysqli_fetch_assoc($resultInner)) {
                $itemArticleTag = $rowInner['articleTag'];
                $articleTag .= $itemArticleTag . ',';
            }
        }
    }
?>
    <div class="popup">
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
                                <input type="text" class="form-control" id="postTitle" name="postTitle"
                                    value="<?= $postTitle ?>" />
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col">
                                <textarea id="postBody" name="postBody"><?= $postBody ?></textarea>
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
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Branding & Advertising" <? if (strpos($articleTag, ',Branding & Advertising,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Branding & Advertising</label>
                                        </div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Customer" <? if (strpos($articleTag, ',Customer,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Customer</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Competition" <? if (strpos($articleTag, ',Competition,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Competition</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Networking" <? if (strpos($articleTag, ',Networking,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Networking</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Business Networking" <? if (strpos($articleTag, ',Business Networking,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Business Networking</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Sales" <? if (strpos($articleTag, ',Sales,') !== false) { ?>
                                                CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Sales</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Social Media" <? if (strpos($articleTag, ',Social Media,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Social Media</label></div>
                                    </div>

                                    <div class="col-12 mt-4 col-md-4 mt-md-0">
                                        <b>Human Resource</b><br>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="HR Management" <? if (strpos($articleTag, ',HR Management,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">HR Management</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Organization Structures" <? if (strpos($articleTag, ',Organization Structures,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Organization Structures</label>
                                        </div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Training" <? if (strpos($articleTag, ',Training,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Training</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Recruit" <? if (strpos($articleTag, ',Recruit,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Recruit</label></div>
                                    </div>

                                    <div class="col-12 mt-4 col-md-4 mt-md-0">
                                        <b>Technology</b><br>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Enterprise Software" <? if (strpos($articleTag, ',Enterprise Software,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Enterprise Software</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Personal Software" <? if (strpos($articleTag, ',Personal Software,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Personal Software</label></div>
                                    </div>

                                    <div class="col-12 col-md-4 mt-4">
                                        <b>Finance</b><br>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Funding" <? if (strpos($articleTag, ',Funding,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Funding</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Accounting" <? if (strpos($articleTag, ',Accounting,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Accounting</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Taxation" <? if (strpos($articleTag, ',Taxation,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Taxation</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Treasury" <? if (strpos($articleTag, ',Treasury,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Treasury</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Management Information Systems" <? if (strpos($articleTag, ',Management Information Systems,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Management Information Systems
                                            </label></div>
                                    </div>

                                    <div class="col-12 col-md-4 mt-4">
                                        <b>Operations</b><br>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Quality" <? if (strpos($articleTag, ',Quality,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Quality</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Facility management" <? if (strpos($articleTag, ',Facility management,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Facility management</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Infrastructure" <? if (strpos($articleTag, ',Infrastructure,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Infrastructure</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Office Automation" <? if (strpos($articleTag, ',Office Automation,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Office Automation</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Customer Service" <? if (strpos($articleTag, ',Customer Service,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Customer Service</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Automation" <? if (strpos($articleTag, ',Automation,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Automation</label></div>
                                    </div>

                                    <div class="col-12 col-md-4 mt-4">
                                        <b>Industry</b><br>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Ites & Bpo" <? if (strpos($articleTag, ',Ites & Bpo,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Ites & Bpo</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Hospitality" <? if (strpos($articleTag, ',Hospitality,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Hospitality</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="IT" <? if (strpos($articleTag, ',IT,') !== false) { ?>
                                                CHECKED<? } ?>><label class="pl-1 small text-secondary form-check-label">IT</label>
                                        </div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Manufacturing" <? if (strpos($articleTag, ',Manufacturing,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Manufacturing</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Retail" <? if (strpos($articleTag, ',Retail,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Retail</label></div>
                                    </div>

                                    <div class="col-12 col-md-4 mt-4">
                                        <b>Leadership</b><br>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Empowerment" <? if (strpos($articleTag, ',Empowerment,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Empowerment</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Innovation" <? if (strpos($articleTag, ',Innovation,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Innovation</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Inspiration" <? if (strpos($articleTag, ',Inspiration,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Inspiration</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Srategy" <? if (strpos($articleTag, ',Srategy,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Srategy</label></div>
                                    </div>

                                    <div class="col-12 col-md-4 mt-4">
                                        <b>Work Life</b><br>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Personality Development" <? if (strpos($articleTag, ',Personality Development,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Personality Development</label>
                                        </div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Success" <? if (strpos($articleTag, ',Success,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Success</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Health" <? if (strpos($articleTag, ',Health,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Health</label></div>
                                        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag"
                                                name="articleTag" value="Personal Finance" <? if (strpos($articleTag, ',Personal Finance,') !== false) { ?> CHECKED<? } ?>><label
                                                class="pl-1 small text-secondary form-check-label">Personal Finance</label></div>
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
                                    <input type="text" class="form-control" id="author" name="author" maxlength="200"
                                        value="<?= $author ?>" />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="pageViewStart">Published Date</label>
                                </div>
                                <div class="form-group col-md-8">
                                    <input type="date" class="form-control" id="datePublished" name="datePublished"
                                        style="width:145px" value="<?= $datePublished ?>" />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="pageViewStart">Initial Page View</label>
                                </div>
                                <div class="form-group col-md-8">
                                    <input type="text" class="form-control" id="pageViewStart" name="pageViewStart"
                                        style="width:100px" onkeyPress="return numCheck(event)" maxlength="8"
                                        value="<?= $pageViewStart ?>" />
                                </div>
                            </div>
                        <? } ?>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="uploadCover">Cover Image</label>
                            </div>
                            <div class="form-group col-md-8">
                                <div class="form-check form-check-inline ml-0 ml-sm-2 w-50">
                                    <input type="file" class="form-control-file" id="uploadCover" name="uploadCover"
                                        accept="image/*" />
                                </div>
                                <? if ($imgCover <> '') { ?>
                                    <div class="mb-2"><a href="/data/covers/<?= $imgCover ?>" target="_blank">View Current Image</a>
                                    </div>
                                <? } ?>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="isArchive">Archive Item</label>
                            </div>
                            <div class="form-group col-md-8">
                                <div class="form-check form-check-inline ml-0 ml-sm-2 w-25">
                                    <label class="form-control radio-inline border-0 pl-1 pr-0"><input type="radio"
                                            id="isArchive" name="isArchive" value="1" <? if ($isArchive == 1) { ?> CHECKED<? } ?>>
                                        Yes</label>
                                    <label class="form-control radio-inline border-0 pl-1 pr-0"><input type="radio"
                                            id="isArchive" name="isArchive" value="0" <? if ($isArchive == 0) { ?> CHECKED<? } ?>>
                                        No</label>
                                </div>
                            </div>
                        </div>
                        <?
                        if ($gUserPlan == 0) {
                            echo '<div class="alert alert-success" role="alert">Call to Action section is only available in <b>Pro</b> plan! Go to My Account to upgrade.</div>';
                        } else {
                        ?>

                            <!-- <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="isReadMore">Show Call To Action Button</label>
                                </div>
                                <div class="form-group col-md-8">
                                    <div class="form-check form-check-inline ml-0 ml-sm-2 w-25">
                                        <label class="form-control radio-inline border-0 pl-1 pr-0"><input type="radio"
                                                id="isReadMore" name="isReadMore" value="1" <? if ($isReadMore == 1) { ?> CHECKED<? } ?>> Yes</label>
                                        <label class="form-control radio-inline border-0 pl-1 pr-0"><input type="radio"
                                                id="isReadMore" name="isReadMore" value="0" <? if ($isReadMore == 0) { ?> CHECKED<? } ?>> No</label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="readMoreTxt">Call To Action Button Text</label>
                                </div>
                                <div class="form-group col-md-8">
                                    <input type="text" class="form-control" id="readMoreTxt" name="readMoreTxt" maxlength="50"
                                        value="<?= $readMoreTxt ?>" />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4 pt-2">
                                    <label for="readMoreResponse">Call To Action Response</label>
                                </div>
                                <div class="form-group col-md-8">
                                    <input type="text" class="form-control" id="readMoreResponse" name="readMoreResponse"
                                        maxlength="300" value="<?= $readMoreResponse ?>" />
                                    <small class="form-text text-muted">Enter a URL (including http or https) or if left blank, will
                                        show a default Thank you page</small>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4 pt-2">
                                    <label for="readMoreResponse">Call To Action Email</label>
                                </div>
                                <div class="form-group col-md-8">
                                    <input type="text" class="form-control" id="readMoreEmail" name="readMoreEmail" maxlength="300"
                                        value="<?= $readMoreEmail ?>" />
                                    <small class="form-text text-muted">Enter a valid email addresses seperated by commas where you
                                        want the lead details to be emailed</small>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="isMandatoryCompany">Show Call To Action Mandatory Fields</label>
                                </div>
                                <div class="form-group col-md-8">
                                    <div class="row no-gutters">
                                        <div class="col-12 col-lg-4"><label class="form-control border-0"><input type="checkbox"
                                                    id="isMandatoryCompany" name="isMandatoryCompany" value="1" <? if ($isMandatoryCompany == 1) { ?> CHECKED<? } ?>> Company/Institution</label></div>
                                        <div class="col-12 col-lg-4"><label class="form-control border-0"><input type="checkbox"
                                                    id="isMandatoryEmail" name="isMandatoryEmail" value="1" <? if ($isMandatoryEmail == 1) { ?> CHECKED<? } ?>> Email</label></div>
                                        <div class="col-12 col-lg-4"><label class="form-control border-0"><input type="checkbox"
                                                    id="isMandatoryMobile" name="isMandatoryMobile" value="1" <? if ($isMandatoryMobile == 1) { ?> CHECKED<? } ?>> Mobile</label></div>
                                    </div>
                                </div>
                            </div> -->
                        <? } ?>
                    <? } ?>
                </div>
                <div id="widget_F" style="border-top:1px solid #ebedf2;padding:20px 10px;">
                    <div class="col">
                        <button type="submit" class="btn btn-primary" onclick="return chkEditPost()">Update</button>
                        <button type="submit" class="btn btn-light ml-1"
                            onclick="return clearForm('frmEdit')">Reset</button>
                        <div id="panelStatus" class="float-right text-sm text-danger" style="margin-top:5px" align="right">
                        </div>
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
    $result = mysqli_query($creamdb, $sql);
    $numRows = mysqli_num_rows($result);
    if ($numRows > 0) {
        $row = mysqli_fetch_assoc($result);
        $postTitle = $row['title'];
        $postBody = $row['description'];
        $postURL = $row['url'];
        $postLink =  '/view.php?id=' . $postId . '&title=' .$postTitle ;
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
                        <div id="panelStatus" class="float-right text-sm text-danger" style="margin-top:5px" align="right">
                        </div>
                    </div>
                </div>
                <input type="hidden" id="editId" name="editId" value="<?= $postId ?>" />
                <input type="hidden" id="act" name="act" value="sendEmail" />
            </form>
        </div>
    </div>
<?
}


if ($act == "showFBShare") {
    include 'social_media/facebook/fb_function.php';
    $title = (isset($_POST['title']) ? $_POST['title'] : null);
    $url = (isset($_POST['url']) ? $_POST['url'] : null);
    $fbLoggedStatus = facebook_login_status($creamdb, $gUserId);
?>
    <form id="FBform">
        <? if ($fbLoggedStatus) { ?>
            <div class="form-group">
                <label for="fbArticleTitle">Enter the text to be shared:</label>
                <textarea id="fbArticleTitle" class="form-control" rows="3" placeholder=""><?= $title ?></textarea>
            </div>

            <input type="hidden" id="fbArticleLink" class="form-control" value="<?= $url ?>">

            <? $pages_data = facebook_fetch_user_pages($creamdb, $gUserId); ?>
            <h5>Select Facebook Pages:</h5>
            <div class="list-group">
                <? if (!empty($pages_data)) {
                    foreach ($pages_data as $page_data) { ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="<?= $page_data['pages_id'] ?>" id="fbPage-<?= $page_data['pages_id'] ?>">
                            <label class="form-check-label" for="fbPage-<?= $page_data['pages_id'] ?>">
                                <?= $page_data['pages_name'] ?>
                            </label>
                        </div>
                    <?php }
                } else { ?>
                    <div class="form-check">
                        You dont have any Facebook Pages Associated with your account. Please Create the Facebook Page to use this feature
                    </div>
                <? } ?>
            </div>

            <div id="fbScheduleDateTimeDiv" class="form-group" style="display: none; padding-top: 10px;">
                <label for="fbScheduleDateTime">
                    <h5>Schedule Date and Time:</h5>
                </label>
                <div id="timeAlert" class="alert alert-warning" style="display: none;">
                    The selected time should be at least 20 minutes ahead of the current time.
                </div>
                <input type="datetime-local" class="form-control" id="fbScheduleDateTime">
            </div>

            <button type="button" class="btn btn-primary" id="fbPostNow">Post Now</button>
            <button type="button" class="btn btn-warning" id="fbSetSchedule">Schedule Post</button>
            <button type="button" class="btn btn-info" id="fbSchedulePost" style="display: none;">Schedule Now</button>

        <? } else { ?>
            You have not logged in to Facebook to use this feature.
            <a href="social_media/facebook/facebook_setup.php"><button type="button" class="btn btn-primary">Setup Facebook</button></a>
        <? } ?>
    </form>
<?
}


if ($act == "showLIShare") {
    include 'social_media/linkedin/linkedin_function.php';
    $title = (isset($_POST['title']) ? $_POST['title'] : null);
    $url = (isset($_POST['url']) ? $_POST['url'] : null);
    $desc = (isset($_POST['desc']) ? $_POST['desc'] : null);
    $liLoggedStatus = linkedin_login_status($creamdb, $gUserId)

?>
    <form id="LIform">
        <? if ($liLoggedStatus) { ?>
            <div class="mb-3">
                <input type="hidden" class="form-control" id="LIUrl" value="<?= $url ?>">
            </div>
            <div class="mb-3">
                <label for="LITitle" class="form-label">Title</label>
                <input type="text" class="form-control" id="LITitle" value="<?= $title ?>" disabled>
            </div>
            <div class="mb-3">
                <label for="LIDescription" class="form-label">Add your Description</label>
                <textarea class="form-control" id="LIDescription"><?= htmlspecialchars($desc) ?></textarea>
            </div>
            <div class="mb-3">
                <label for="LIText" class="form-label">Enter the text to be shared:</label>
                <textarea class="form-control" id="LIText"></textarea>
            </div>
            <button type="button" class="btn btn-primary" id="Li-post">Post on LinkedIn</button>
        <? } else { ?>
            You have not logged in to LinkedIn to use this feature.
            <a href="social_media/linkedin/linkedin_setup.php"><button type="button" class="btn btn-primary">Setup LinkedIn</button></a>
        <? } ?>
    </form>
<?
}


if ($act == '') { ?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>My Collections | News Junction</title>

        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">

        <!-- Bootstrap CSS -->
        <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">

        <!-- Magnific Popup CSS (only if you need it) -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/magnific-popup.min.css">

        <link rel="stylesheet" href="inc/css/stream.css">
        <link rel="stylesheet" href="inc/css/social.css">
        <link rel="icon" type="image/x-icon" href="grfx/img/logo.ico">

        <!-- jQuery (always load jQuery first) -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

        <!-- Popper.js (Bootstrap 4 requires Popper.js) -->
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>

        <!-- Bootstrap JS (needed for Bootstrap 4) -->
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

        <script src="https://cdn.tiny.cloud/1/720y3p95h0psi2n78gt09nbiyiqixqixlrezcaaelxhh34sd/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>

        <!-- Your Custom JavaScript -->
        <script src="inc/js/common.js"></script>
        <script src="inc/js/stream.js"></script>

        <style>
            #panelContent {
                /* padding-right: 8px; */
                height: calc(100vh - 140px);
                overflow: auto;
            }

            .main-content {
                padding-bottom: 0px;
            }

            .tox-promotion {
                display: none !important;
            }

            .breadcrumb {
                display: -ms-flexbox;
                display: flex;
                -ms-flex-wrap: wrap;
                flex-wrap: wrap;
                padding: .75rem 0rem;
                margin-bottom: 1rem;
                list-style: none;
                background-color: transparent !important;
                border-radius: .25rem;
            }

            .badge-warning {
                background-color: #818182;
                border-radius: 10px;
            }

            .breadcrumb {
                color: var(--text-primary);
            }

            .card {
                background-color: #00000000;
            }

            .card-body {
                background-color: var(--bg-card);
                ;
                color: #ffffff87 !important;
                border-radius: 12px;
                -ms-flex: 1 1 auto;
                flex: 1 1 auto;
                min-height: 1px;
                padding: 1.25rem;
                padding-bottom: 5px;
                padding-top: 5px;
            }


            .buttonCreamShare {
                background-color: #6c757d;
            }

            #eSamudaayShare {
                border: none;
                background-color: #6c757d;
            }

            .card-body a {
                color: var(--text-primary);
            }

            .nav-tabs {
                display: flex;
                flex-direction: row;
                flex-wrap: nowrap;
                /* Align items in a row */
                overflow-x: auto;
                /* Enable horizontal scrolling when content overflows */
                padding-bottom: 10px;
                /* Optional: add some spacing at the bottom */
                margin-bottom: 0;
                /* Optional: remove margin at the bottom */
            }

            .nav-tabs::-webkit-scrollbar {
                height: 8px;
            }

            .nav-tabs::-webkit-scrollbar-thumb {
                background-color: #888;
                border-radius: 4px;
            }

            .nav-tabs::-webkit-scrollbar-thumb:hover {
                background-color: #555;
            }

            .hyperlink img {
                border-radius: 10px;
                margin-top: 10px;
                max-width: 30%;
                margin-bottom: 10px;
            }

            @media screen and (min-width:768px) {
                .modal-content {
                    width: 800px !important;
                }

            }

            /* Optional: Add a subtle shadow effect to indicate scrollable content */
            .nav-tabs::after {
                content: '';
                /* Empty content */
                position: absolute;
                top: 0;
                right: 0;
                width: 20px;
                /* Shadow width */
                height: 100%;
                /* background: linear-gradient(to right, rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0)); */
                /* Fade shadow */
                pointer-events: none;
                /* Prevent interference with clicking */
            }

            /* Individual list item style */
            .nav-item {
                flex-shrink: 0;
                /* Prevent items from shrinking */
            }

            /* Optional: Add styles for the active state */
            .nav-link.active {
                font-weight: bold;
                /* Make active item bold or any other style */
            }

            .openModalButton {
                height: 32px;
                user-select: none;
                cursor: pointer;
                padding-bottom: 4px;
                width: auto;
            }


            @media screen and (max-width:600px) {
                .text-center {
                    text-align: left !important;
                }

            }

            @media (min-width: 576px) {
                .modal-dialog {
                    max-width: 100%;
                    margin: 1.75rem auto;
                }
            }

            .modal-content,
            .close,
            .form-control,
            .nav-link.active {
                background-color: var(--bg-card);
                color: var(--text-primary);
            }

            a {
                color: var(--text-primary);

            }

            .btn {
                background-color: var(--primary);
            }

            .sideMaincontent {
                width: 70%;
                height: 85vh;
                overflow-y: scroll;
                padding: 30px 20px;
            }

            .first_left_container {
                height: 85vh !important;
            }

            @media (min-width: 768px) {
                .myCollectionSide {
                    padding: 0px !important;
                }

            }

            @media (max-width: 768px) {
                .myCollectionSide {
                    display: none !important;
                }

                .sideMaincontent {
                    width: 100%;
                    height: 85vh;
                    overflow-y: scroll;
                    padding: 0px 0px;
                }

            }

            @media screen and (min-width:1024px) {
                .sideMaincontent {
                    width: 90%;
                    height: 85vh;
                    overflow-y: scroll;
                    padding: 30px 20px;
                }

            }

            .modal {
                position: fixed;
                left: 0;
                z-index: 1050;
                display: none;
                width: 100%;
                height: 100%;
                overflow: hidden;
                outline: 0;
            }
        </style>
    </head>

    <body>
        <!-- Modal Structure -->
        <div class="modal fade" id="responseModal" tabindex="-1" role="dialog" aria-labelledby="responseModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <!-- Modal Header -->
                    <div class="modal-header">
                        <h5 class="modal-title" id="responseModalLabel">Response</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <!-- <span aria-hidden="true">&times;</span> -->
                            <span inert>&times;</span>
                        </button>
                    </div>
                    <!-- Modal Body -->
                    <div class="modal-body">
                        <!-- Response content will go here -->
                        Loading...
                    </div>
                    <!-- Modal Footer -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="uploadModal" tabindex="-1" role="dialog" aria-labelledby="uploadModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="uploadModalLabel">Share</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <!-- <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#myModal" style="background-color:#555"></button> -->
                    </div>
                    <div class="modal-body">
                        <!-- Your upload form goes here -->
                        <div class="upload-section">
                            <div class="d-flex align-items-start">
                                <div class="w-100">
                                    <div id="loadingIndicator" style="display: none; font-size: 14px; color: gray;">Generating<span id="dots">...</span></div>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <textarea id="contentTextarea" class="form-control mb-2" placeholder="What would you like to share?"
                                            style="flex: 1; overflow: hidden; resize: none;" oninput="adjustTextareaHeight(this)"><? $collectionURL ?></textarea>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <input type="file" id="fileInput" accept="image/*,video/*" class="d-none" onchange="previewMedia();">
                                            <button type="button" class="btn btn-link text-decoration-none text-light bg-none" onclick="document.getElementById('fileInput').click();">
                                                <i class="bi bi-image"></i>
                                            </button>
                                        </div>
                                        <div class="d-flex">
                                            <button type="button" onclick="fetchGenAIContent()" style="text-decoration: none; border:none; border-radius:5px">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                                    <g fill="none" fill-rule="evenodd">
                                                        <path d="m12.594 23.258l-.012.002l-.071.035l-.02.004l-.014-.004l-.071-.036q-.016-.004-.024.006l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.016-.018m.264-.113l-.014.002l-.184.093l-.01.01l-.003.011l.018.43l.005.012l.008.008l.201.092q.019.005.029-.008l.004-.014l-.034-.614q-.005-.019-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.003-.011l.018-.43l-.003-.012l-.01-.01z" />
                                                        <path fill="currentColor" d="M19 19a1 1 0 0 1 .117 1.993L19 21h-7a1 1 0 0 1-.117-1.993L12 19zm.631-14.632a2.5 2.5 0 0 1 0 3.536L8.735 18.8a1.5 1.5 0 0 1-.44.305l-3.804 1.729c-.842.383-1.708-.484-1.325-1.326l1.73-3.804a1.5 1.5 0 0 1 .304-.44L16.096 4.368a2.5 2.5 0 0 1 3.535 0m-2.12 1.414L6.677 16.614l-.589 1.297l1.296-.59L18.217 6.49a.5.5 0 1 0-.707-.707M6 1a1 1 0 0 1 .946.677l.13.378a3 3 0 0 0 1.869 1.87l.378.129a1 1 0 0 1 0 1.892l-.378.13a3 3 0 0 0-1.87 1.869l-.129.378a1 1 0 0 1-1.892 0l-.13-.378a3 3 0 0 0-1.869-1.87l-.378-.129a1 1 0 0 1 0-1.892l.378-.13a3 3 0 0 0 1.87-1.869l.129-.378A1 1 0 0 1 6 1m0 3.196A5 5 0 0 1 5.196 5q.448.355.804.804q.355-.448.804-.804A5 5 0 0 1 6 4.196" />
                                                    </g>
                                                </svg>
                                            </button>
                                            <button class="btn btn-upload ms-2" onclick="uploadPost()">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                                    <path fill="none" stroke="currentColor" d="m6.998 10.247l.435.76c.277.485.415.727.415.993s-.138.508-.415.992l-.435.761c-1.238 2.167-1.857 3.25-1.375 3.788c.483.537 1.627.037 3.913-.963l6.276-2.746c1.795-.785 2.693-1.178 2.693-1.832s-.898-1.047-2.693-1.832L9.536 7.422c-2.286-1-3.43-1.5-3.913-.963s.137 1.62 1.375 3.788Z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- <div id="mediaPreview" class="mt-3"></div> -->
                                    <div id="mediaPreview" class="" style="max-height:300px;overflow:scroll;"></div>

                                    <div id="linkPreview" class="mt-3" style="display: none; width: 100%; height:auto;">
                                        <div class="hyperlink" style="padding: 10px;">
                                            <img src="" alt="Card image">
                                            <div style="padding: 0px; flex-grow: 1;">
                                                <h3 id="linkHeading" style="font-size: 16px; margin: 0 0 5px; color: #333;"></h3>
                                                <p id="linkDesc" style="margin: 0 0 10px; font-size: 14px; color: #555; line-height: 1.4;"></p>
                                                <a id="linkUrl" href="" style="font-size: 13px; color: #007bff; text-decoration: none;"></a>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="ytPreview" class="mt-3 ytprew"></div>
                                    <div id="loadingIcon" class="text-center" style="display:none;">
                                        <div class="spinner-border" role="status">
                                            <span class="sr-only">Loading...</span>
                                        </div>
                                    </div>

                                    <div>
                                        <input type="hidden" id="hiddenTitle">
                                        <input type="hidden" id="hiddenDesc">
                                        <input type="hidden" id="hiddenUrl">
                                        <input type="hidden" id="hiddenImage">
                                        <input type="hidden" id="hiddenDomain">
                                        <input type="hidden" id="hiddenYTLink">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navbar -->

        <div>
            <?php include 'inc/php/social_navbar.php' ?>
            <? include 'inc/php/social_sidebar.php' ?>
            <div class="search-main-content main-content">

                <div id="panelContent">
                    <ol class="breadcrumb my-3">
                        <li class="breadcrumb-item w-100" style="justify-content: space-between;">
                            <div class="text-left ">
                                <h4 class="m-0">My Collections</h4>
                            </div>
                            <div class="text-right ">
                                <button type="button" id="buttonAddNewsletter" class="d-none btn btn-sm btn-success"
                                    onclick="chkAddNewsletter()">Generate Newsletter</button>
                                <button type="button" id="buttonAddCovid" class="d-none btn btn-sm btn-success"
                                    onclick="chkAddCovid()">Add to Covid page</button>
                                <button type="button" id="buttonAddLanding" class="d-none btn btn-sm btn-success"
                                    onclick="chkAddLanding()">Add to Showcase</button>
                            </div>
                        </li>
                    </ol>
                    <ul class="nav nav-tabs mb-4">
                        <li class="nav-item"><a
                                class="nav-link<? if ($actAfter == '' || $actAfter == 'itemActive') { ?> active<? } ?>"
                                data-toggle="tab" href="#itemActive" role="tab" onclick="gActAfter='itemActive'">Active</a></li>
                        <li class="nav-item"><a class="nav-link<? if ($actAfter == 'myShowcase') { ?> active<? } ?>"
                                data-toggle="tab" href="#myShowcase" role="tab" onclick="gActAfter='myShowcase'">My Showcase</a>
                        </li>
                        <li class="nav-item"><a class="nav-link<? if ($actAfter == 'itemSharedBy') { ?> active<? } ?>"
                                data-toggle="tab" href="#itemSharedBy" role="tab" onclick="gActAfter='itemSharedBy'">Shared by
                                me</a></li>
                        <li class="nav-item"><a class="nav-link<? if ($actAfter == 'itemSharedWith') { ?> active<? } ?>"
                                data-toggle="tab" href="#itemSharedWith" role="tab" onclick="gActAfter='itemSharedWith'">Shared
                                with me</a></li>
                        <li class="nav-item"><a class="nav-link<? if ($actAfter == 'itemArchived') { ?> active<? } ?>"
                                data-toggle="tab" href="#itemArchived" role="tab"
                                onclick="gActAfter='itemArchived'">Archived</a></li>
                    </ul>

                    <div class="tab-content my-3">
                        <div class="tab-pane fade<? if ($actAfter == '' || $actAfter == 'itemActive') { ?> show active<? } ?>"
                            id="itemActive" role="tabpanel">
                            <div class="panelFeeds" style="max-height:calc(100vh - 245px)">
                                <?
                                $sql = "SELECT id,title,url FROM user_collection WHERE user_id=$gUserId AND share_user_id IS NULL AND is_archive IS NULL ORDER BY id DESC";
                                $result = mysqli_query($creamdb, $sql);
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
                                        if ($collectionPublisher == '')
                                            $collectionPublisher = 'News Junction';
                                        $collectionLink =  '/view.php?id=' . $collectionId . '&title=' .$collectionTitle;
                                        if ($gUserSubdomain <> '') {
                                            $collectionLinkFull = 'https://newsjunction.net' . $collectionLink;
                                        } else {
                                            $collectionLinkFull = 'https://newsjunction.net' . $collectionLink;
                                        }
                                ?>
                                        <div class="card p-0 mb-3  border-0">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-12 col-md-10">
                                                        <h4 class="mb-0"><a href="<?= $collectionLinkFull ?>" target="_blank"><?= $collectionTitle ?></a></h4>
                                                        <div style="color:#f26522" class="mb-3">Publisher: <?= $collectionPublisher ?></div>
                                                    </div>
                                                    <div class="col-12 col-md-2 text-center text-md-right my-2 m-md-0">
                                                        <? if ($collectionURL == '') { ?>
                                                            <a href="#" title="Edit Item"><i data-id="<?= $collectionId ?>"
                                                                    class="editRow far fa-edit fa-lg text-muted pr-2"></i></a>
                                                        <? } ?>
                                                        <a href="#" title="Archive Item"><i data-id="<?= $collectionId ?>"
                                                                class="archiveRow fas fa-download fa-lg text-muted pr-2"></i></a>
                                                        <a href="#" title="Delete Item"><i data-id="<?= $collectionId ?>"
                                                                class="delRow far fa-trash-alt fa-lg text-muted"></i></a>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-12 col-md-5 text-center text-md-left">
                                                        <span id="panelShowcase_<?= $collectionId ?>">
                                                            <?
                                                            $sql = "SELECT article_id FROM user_landing WHERE article_id=$collectionId";
                                                            $resultInner = mysqli_query($creamdb, $sql);
                                                            $numRowsInner = mysqli_num_rows($resultInner);
                                                            if ($numRowsInner == 0) {
                                                            ?>
                                                                <label
                                                                    class="btn btn-outline-danger mb-2 m-0  px-2 py-1 float-left"><input
                                                                        type="checkbox" name="landingData"
                                                                        data-feed-id="<?= $collectionId ?>" onclick="addLanding()"> Select
                                                                    for Showcase</label>
                                                            <? } else { ?>
                                                                <div class="text-danger m-0 py-1 pr-3 float-left" style="user-select:none">
                                                                    <i class="far fa-check-square"></i> Showcase (<a href="javascript:np()"
                                                                        onclick="removeShowcase(<?= $collectionId ?>)">Remove</a>)
                                                                </div>
                                                            <?
                                                            }
                                                            ?>
                                                        </span>
                                                    </div>
                                                    <div class="data col-12 col-md-7 text-center text-md-right 1mt-2 1m-mt-0"
                                                        data-feed-id="<?= $collectionId ?>" data-feed-title="<?= $collectionTitle ?>"
                                                        data-feed-url="<?= $collectionLinkFull ?>"
                                                        data-feed-publisher="<?= $collectionPublisher ?>">
                                                        <!-- <label class="btn-secondary px-2 py-1" style="user-select:none">
                                                            <input type="checkbox" name="feedData" onclick="addNewsletter()"> Send to Newsletter
                                                        </label> -->
                                                        <!-- <label class="buttonCreamShare btn-info px-2 py-1 m-0"
                                                            data-id="<?= $collectionId ?>"
                                                            style="user-select:none">Cream<i>Share</i>
                                                        </label> -->
                                                        <div style="display: inline-block; padding-bottom: 4px;">
                                                            <a href="#">
                                                                <i data-id="<?= $collectionId ?>"
                                                                    class="emailRow fas fa-2x fa-envelope-square"
                                                                    style="position:relative;top:5px;color:#6c757d;">
                                                                </i>
                                                            </a>
                                                            <!-- <a class="sbg-button pt-1 fb-share"
                                                                data-feed-title="<?= $collectionTitle ?>" data-feed-url="<?= $collectionLinkFull ?>">
                                                                <i class="mt-1 fab fa-facebook-square fa-2x cursorH"
                                                                    style="position:relative;top:5px;color:#6c757d;">
                                                                </i>
                                                            </a> -->


                                                            <!-- <a data-sbg-1="" data-sbg-2="" data-sbg-3="" class="sbg-button"
                                                            data-sbg-network="twitter" data-sbg-width="600" data-sbg-height="258"><i
                                                                class="fab fa-twitter-square fa-2x cursorH"
                                                                style="position:relative;top:5px;color:#6c757d;"></i></a> -->

                                                            <!-- <a class="sbg-button li-share"
                                                                data-feed-title="<?= $collectionTitle ?>" data-feed-url="<?= $collectionLinkFull ?>" data-feed-desc="<?= $collectionPublisher ?>">
                                                                <i class="fab fa-linkedin fa-2x cursorH"
                                                                    style="position:relative;top:5px;color:#6c757d;"></i></a> -->


                                                            <!-- <a data-sbg-1="" data-sbg-2="" data-sbg-3="" class="sbg-button"
                                                            data-sbg-network="pinterest" data-sbg-width="600"
                                                            data-sbg-height="368"><i class="fab fa-pinterest-square fa-2x cursorH"
                                                                style="position:relative;top:5px;color:#6c757d;"></i></a> -->


                                                            <a href="http://web.whatsapp.com/send?text=<?= $collectionTitle ?> <?= $collectionLinkFull ?>"
                                                                target="_blank"><i class="fab fa-2x fa-whatsapp-square"
                                                                    style="position:relative;top:5px;color:#6c757d;"></i></a>
                                                            <!-- <label class="buttonCreamShare btn-info px-2 py-1 m-0"
                                                           
                                                            style="user-select:none">Socail<i>Share</i></label> -->
                                                            <img
                                                                src="images/newsjunction.png"
                                                                alt="Social Share"
                                                                class="openModalButton" />
                                                        </div>
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
                        <div class="tab-pane fade<? if ($actAfter == 'myShowcase') { ?> show active<? } ?>" id="myShowcase"
                            role="tabpanel">
                            <div class="panelFeeds" style="max-height:calc(100vh - 245px)">
                                <?
                                $sql = "SELECT A.id,A.title,A.url FROM user_collection A,user_landing B WHERE A.id=B.article_id AND A.user_id=$gUserId ORDER BY A.id DESC";
                                $result = mysqli_query($creamdb, $sql);
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
                                        if ($collectionPublisher == '')
                                            $collectionPublisher = 'Cream';
                                        $collectionLink = '/view.php?id=' . $collectionId .'&title=' .$collectionTitle;
                                        if ($gUserSubdomain <> '') {
                                            $collectionLinkFull = 'https://newsjunction.net' . $collectionLink;
                                        } else {
                                            $collectionLinkFull = 'https://newsjunction.net' . $collectionLink;
                                        }
                                ?>
                                        <div class="card p-0 mb-3 mr-2 border-0">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-12 col-md-10">
                                                        <h4 class="mb-0"><a href="<?= $collectionLinkFull ?>" target="_blank"><?= $collectionTitle ?></a></h4>
                                                        <div style="color:#f26522" class="mb-3">Publisher: <?= $collectionPublisher ?></div>
                                                    </div>
                                                    <div class="col-12 col-md-2 text-center text-md-right my-2 m-md-0">
                                                        <? if ($collectionURL == '') { ?>
                                                            <a href="#" title="Edit Item"><i data-id="<?= $collectionId ?>"
                                                                    class="editRow far fa-edit fa-lg text-muted pr-2"></i></a>
                                                        <? } ?>
                                                        <a href="#" title="Archive Item"><i data-id="<?= $collectionId ?>"
                                                                class="archiveRow fas fa-download fa-lg text-muted pr-2"></i></a>
                                                        <a href="#" title="Delete Item"><i data-id="<?= $collectionId ?>"
                                                                class="delRow far fa-trash-alt fa-lg text-muted"></i></a>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-12 col-md-4 text-center text-md-left">
                                                        <span id="panelShowcase_<?= $collectionId ?>">
                                                            <div class="text-danger  py-1" style="user-select:none"><i
                                                                    class="far fa-check-square"></i> Showcase (<a href="javascript:np()"
                                                                    onclick="removeShowcase(<?= $collectionId ?>)">Remove</a>)</div>
                                                        </span>
                                                    </div>
                                                    <div class="data col-12 col-md-8 text-center text-md-right mt-2 m-md-0"
                                                        data-feed-id="<?= $collectionId ?>" data-feed-title="<?= $collectionTitle ?>"
                                                        data-feed-url="<?= $collectionLinkFull ?>"
                                                        data-feed-publisher="<?= $collectionPublisher ?>">
                                                        <!-- <label class="btn-secondary px-2 py-1" style="user-select:none"><input
                                                                type="checkbox" name="feedData" onclick="addNewsletter()"> Send to
                                                            Newsletter</label> -->
                                                        <!-- <label class="buttonCreamShare btn-info px-2 py-1 m-0"
                                                            data-id="<?= $collectionId ?>"
                                                            style="user-select:none">Cream<i>Share</i></label> -->
                                                        <div style="display: inline-block; padding-bottom: 4px; ">
                                                            <a href="#"><i data-id="<?= $collectionId ?>"
                                                                    class="emailRow fas fa-2x fa-envelope-square"
                                                                    style="position:relative;top:5px;color:#6c757d;"></i></a>


                                                            <!-- <a class="fb-share" data-feed-title="<?= $collectionTitle ?>" data-feed-url="<?= $collectionLinkFull ?>" class="sbg-button pt-1"
                                                                data-sbg-network="facebook" data-sbg-width="600"
                                                                data-sbg-height="368"><i
                                                                    class="mt-1 fab fa-facebook-square fa-2x cursorH"
                                                                    style="position:relative;top:5px;color:#6c757d;"></i></a> -->


                                                            <!-- <a data-sbg-1="" data-sbg-2="" data-sbg-3="" class="sbg-button"
                                                                data-sbg-network="twitter" data-sbg-width="600" data-sbg-height="258"><i
                                                                    class="fab fa-twitter-square fa-2x cursorH"
                                                                    style="position:relative;top:5px;color:#6c757d;"></i></a> -->


                                                            <!-- <a class="sbg-button li-share"
                                                                data-feed-title="<?= $collectionTitle ?>" data-feed-url="<?= $collectionLinkFull ?>" data-feed-desc="<?= $collectionPublisher ?>" class="sbg-button"
                                                                data-sbg-network="linkedin" data-sbg-width="585"
                                                                data-sbg-height="471"><i class="fab fa-linkedin fa-2x cursorH"
                                                                    style="position:relative;top:5px;color:#6c757d;"></i></a> -->


                                                            <!-- <a data-sbg-1="" data-sbg-2="" data-sbg-3="" class="sbg-button"
                                                            data-sbg-network="pinterest" data-sbg-width="600"
                                                            data-sbg-height="368"><i class="fab fa-pinterest-square fa-2x cursorH"
                                                                style="position:relative;top:5px;color:#6c757d;"></i></a> -->
                                                            <a href="http://web.whatsapp.com/send?text=<?= $collectionTitle ?> <?= $collectionLinkFull ?>"
                                                                target="_blank"><i class="fab fa-2x fa-whatsapp-square"
                                                                    style="position:relative;top:5px;color:#6c757d;"></i></a>
                                                        </div>
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
                        <div class="tab-pane fade<? if ($actAfter == 'itemSharedBy') { ?> show active<? } ?>" id="itemSharedBy"
                            role="tabpanel">
                            <div class="panelFeeds" style="max-height:calc(100vh - 260px)">
                                <?
                                $sql = "SELECT id,title,url FROM user_collection WHERE share_user_id=$gUserId GROUP BY title,id ORDER BY title";
                                $result = mysqli_query($creamdb, $sql);
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
                                        if ($collectionPublisher == '')
                                            $collectionPublisher = 'Cream';
                                        $collectionLink =  '/view.php?id=' . $collectionId .'&title=' .$collectionTitle ;
                                        $collectionLinkFull = 'https://newsjunction.net' . $collectionLink;
                                ?>
                                        <div class="card p-0 mb-3 mr-2 border-0">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col">
                                                        <h4><a href="<?= $collectionLink ?>" target="_blank"><?= $collectionTitle ?></a></h4>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div style="color:#f26522" class="col-12 col-md-4 text-center text-md-left">Publisher:
                                                        <?= $collectionPublisher ?>
                                                    </div>
                                                    <div class="col-12 col-md-8 text-center text-md-right my-2 m-md-0">
                                                        <?
                                                        $txtSharedWith = '';
                                                        $sql = "SELECT B.full_name FROM user_collection A,user B WHERE A.user_id=B.id AND A.title='$collectionTitle' AND A.share_user_id=$gUserId ORDER BY B.full_name";
                                                        $resultInner = mysqli_query($creamdb, $sql);
                                                        while ($rowInner = mysqli_fetch_assoc($resultInner)) {
                                                            $txtSharedWith .= $rowInner['full_name'] . ', ';
                                                        }
                                                        if ($txtSharedWith != '')
                                                            $txtSharedWith = rtrim($txtSharedWith, ', ');
                                                        ?>
                                                        <label class="badge-warning m-0 px-3 py-1" style="user-select:none">Shared with
                                                            <?= $txtSharedWith ?></label>
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
                        <div class="tab-pane fade<? if ($actAfter == 'itemSharedWith') { ?> show active<? } ?>"
                            id="itemSharedWith" role="tabpanel">
                            <div class="panelFeeds" style="max-height:calc(100vh - 260px)">
                                <?
                                $sql = "SELECT A.id,A.title,A.url,B.full_name FROM user_collection A,user B WHERE A.share_user_id=B.id AND A.user_id=$gUserId AND A.share_user_id IS NOT NULL AND A.is_archive IS NULL ORDER BY A.id DESC";
                                $result = mysqli_query($creamdb, $sql);
                                $numRows = mysqli_num_rows($result);
                                if ($numRows == 0) {
                                    echo '<div class="p-2">You do not have any shared items in your collections!</div>';
                                } else {
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $shareName = $row['full_name'];
                                        $collectionId = $row['id'];
                                        $collectionTitle = $row['title'];
                                        $collectionURL = $row['url'];
                                        $collectionPublisher = substr($collectionURL, strpos($collectionURL, ".") + 1);
                                        $collectionPublisher = ucfirst(strtok($collectionPublisher, '.'));
                                        if ($collectionPublisher == '')
                                            $collectionPublisher = 'Cream';
                                        $collectionLink = '/view.php?id=' . $collectionId .'&title=' .$collectionTitle;
                                        $collectionLinkFull = 'https://newsjunction.net' . $collectionLink;
                                ?>
                                        <div class="card p-0 mb-3 mr-2 border-0">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-12 col-md-10">
                                                        <h4><a href="<?= $collectionLink ?>" target="_blank"><?= $collectionTitle ?></a></h4>
                                                        <div style="color:#f26522" class="mb-3">Publisher: <?= $collectionPublisher ?></div>
                                                    </div>
                                                    <div class="col-12 col-md-2 text-center text-md-right my-2 m-md-0">
                                                        <a href="#" title="Archive Item"><i data-id="<?= $collectionId ?>"
                                                                class="archiveRow fas fa-download fa-lg text-muted pr-2"></i></a>
                                                        <a href="#" title="Delete Item"><i data-id="<?= $collectionId ?>"
                                                                class="delRow far fa-trash-alt fa-lg text-muted"></i></a>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-12 col-md-4 text-center text-md-left">
                                                        <?
                                                        $sql = "SELECT article_id FROM user_landing WHERE article_id=$collectionId";
                                                        $resultInner = mysqli_query($creamdb, $sql);
                                                        $numRowsInner = mysqli_num_rows($resultInner);
                                                        if ($numRowsInner == 0) {
                                                        ?>
                                                            <label class="btn btn-outline-danger mr-3 px-2 py-1 float-left"
                                                                style="user-select:none"><input type="checkbox" name="landingData"
                                                                    data-feed-id="<?= $collectionId ?>" onclick="addLanding()"> Select for
                                                                Showcase</label>
                                                        <? } else { ?>
                                                            <div class="text-danger py-1 pr-3 float-left" style="user-select:none"><i
                                                                    class="far fa-check-square"></i> Showcase</div>
                                                        <?
                                                        }
                                                        ?>
                                                    </div>
                                                    <div class="data col-12 col-md-8 text-center text-md-right mt-2 m-md-0"
                                                        data-feed-id="<?= $collectionId ?>" data-feed-title="<?= $collectionTitle ?>"
                                                        data-feed-url="<?= $collectionLinkFull ?>"
                                                        data-feed-publisher="<?= $collectionPublisher ?>">
                                                        <label class="badge-warning px-3 py-1" style="user-select:none">Shared by
                                                            <?= $shareName ?></label>
                                                        <!-- <label class="btn-secondary px-2 py-1 mr-2" style="user-select:none"><input
                                                                type="checkbox" name="feedData" onclick="addNewsletter()"> Send to
                                                            Newsletter</label> -->
                                                        <div style="display: inline-block; padding-bottom: 4px; ">
                                                            <a href="#"><i data-id="<?= $collectionId ?>"
                                                                    class="emailRow fas fa-2x fa-envelope-square"
                                                                    style="position:relative;top:5px;color:#6c757d;"></i></a>

                                                            <!-- <a class="fb-share" data-feed-title="<?= $collectionTitle ?>" data-feed-url="<?= $collectionLinkFull ?>" class="sbg-button pt-1"
                                                                data-sbg-network="facebook" data-sbg-width="600" data-sbg-height="368"><i
                                                                    class="mt-1 fab fa-facebook-square fa-2x cursorH"
                                                                    style="position:relative;top:5px;color:#6c757d;"></i></a> -->

                                                            <!-- <a data-sbg-1="" data-sbg-2="" data-sbg-3="" class="sbg-button"
                                                            data-sbg-network="twitter" data-sbg-width="600" data-sbg-height="258"><i
                                                                class="fab fa-twitter-square fa-2x cursorH"
                                                                style="position:relative;top:5px;color:#6c757d;"></i></a> -->


                                                            <!-- <a class="sbg-button li-share"
                                                                data-feed-title="<?= $collectionTitle ?>" data-feed-url="<?= $collectionLinkFull ?>" data-feed-desc="<?= $collectionPublisher ?>" class="sbg-button"
                                                                data-sbg-network="linkedin" data-sbg-width="585" data-sbg-height="471"><i
                                                                    class="fab fa-linkedin fa-2x cursorH"
                                                                    style="position:relative;top:5px;color:#6c757d;"></i></a> -->


                                                            <!-- <a data-sbg-1="" data-sbg-2="" data-sbg-3="" class="sbg-button"
                                                        data-sbg-network="pinterest" data-sbg-width="600" data-sbg-height="368"><i
                                                            class="fab fa-pinterest-square fa-2x cursorH"
                                                            style="position:relative;top:5px;color:#6c757d;"></i></a> -->
                                                            <a href="http://web.whatsapp.com/send?text=<?= $collectionTitle ?> <?= $collectionLinkFull ?>"
                                                                target="_blank"><i class="fab fa-2x fa-whatsapp-square"
                                                                    style="position:relative;top:5px;color:#6c757d;"></i></a>
                                                        </div>
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
                        <div class="tab-pane fade<? if ($actAfter == 'itemArchived') { ?> show active<? } ?>" id="itemArchived"
                            role="tabpanel">

                            <div class="panelFeeds" style="max-height:calc(100vh - 260px)">
                                <?
                                $sql = "SELECT A.id,A.title,A.url,B.full_name FROM user_collection A LEFT JOIN user B ON A.share_user_id=B.id WHERE A.user_id=$gUserId AND A.is_archive=1 ORDER BY id DESC";
                                $result = mysqli_query($creamdb, $sql);
                                $numRows = mysqli_num_rows($result);
                                if ($numRows == 0) {
                                    echo '<div class="p-2">You do not have any archived items in your collections!</div>';
                                } else {
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $shareName = $row['full_name'];
                                        $collectionId = $row['id'];
                                        $collectionTitle = $row['title'];
                                        $collectionURL = $row['url'];
                                        $collectionPublisher = substr($collectionURL, strpos($collectionURL, ".") + 1);
                                        $collectionPublisher = ucfirst(strtok($collectionPublisher, '.'));
                                        if ($collectionPublisher == '')
                                            $collectionPublisher = 'Cream';
                                        $collectionLink = '/view.php?id=' . $collectionId .'&title=' .$collectionTitle ;
                                ?>
                                        <div class="card p-0 mb-3 mr-2 border-0">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-12 col-md-10">
                                                        <h4><a href="<?= $collectionLink ?>" target="_blank"><?= $collectionTitle ?></a></h4>
                                                    </div>
                                                    <div class="col-12 col-md-2 text-center text-md-right my-2 m-md-0">
                                                        <? if ($collectionURL == '') { ?>
                                                            <a href="#" title="Edit Item"><i data-id="<?= $collectionId ?>"
                                                                    class="editRow far fa-edit fa-lg text-muted pr-2"></i></a>
                                                        <? } ?>
                                                        <a href="#" title="Restore Item"><i data-id="<?= $collectionId ?>"
                                                                class="restoreRow fas fa-upload fa-lg text-muted pr-2"></i></a>
                                                        <a href="#" title="Delete Item"><i data-id="<?= $collectionId ?>"
                                                                class="delRow far fa-trash-alt fa-lg text-muted"></i></a>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div style="color:#f26522" class="col-12 col-md-8 text-center text-md-left">Publisher:
                                                        <?= $collectionPublisher ?>
                                                    </div>
                                                    <? if ($shareName != '') { ?>
                                                        <div class="col-12 col-md-4 text-center text-md-right mt-2 m-md-0">
                                                            <label class="badge-warning m-0 px-3 py-1" style="user-select:none">Shared by
                                                                <?= $shareName ?></label>
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
                </div>
            </div>
        </div>

        <? include 'inc/php/footer.php' ?>
    </body>

    <script>
        $(document).on('click', '#Li-post', function() {
            // Get form data
            var url = $('#LIUrl').val();
            var title = $('#LITitle').val();
            var description = $('#LIDescription').val();
            var text = $('#LIText').val();

            // Prepare data to be sent to server
            var data = {
                url: url,
                title: title,
                description: description,
                text: text
            };

            // Send AJAX request
            $.ajax({
                url: 'social_media/linkedin/linkedin_post_handler.php', // Replace with your server-side URL
                method: 'POST',
                contentType: 'application/json', // Send JSON data
                data: JSON.stringify(data), // Correctly stringify the data for JSON format
                dataType: 'json', // Expecting a JSON response
                success: function(response) {
                    // Handle success response
                    if (response.status === 'success') {
                        $('#responseModal .modal-body').html('<div class="alert alert-success">Posted on LinkedIn!!</div>');
                    } else {
                        $('#responseMessage').html('<div class="alert alert-danger">Failed to share on LinkedIn. Please try again.</div>');
                    }
                },
                error: function() {
                    // Handle errors
                    $('#responseModal .modal-body').html('<div class="alert alert-danger">An error occurred. Please try again later.</div>');
                }
            });
        });

        $(document).on('click', '#FB-post', function() {
            // Get form data
            var url = $('#LIUrl').val();
            var title = $('#LITitle').val();
            var description = $('#LIDescription').val();
            var text = $('#LIText').val();

            // Prepare data to be sent to server
            var data = {
                url: url,
                title: title,
                description: description,
                text: text
            };

            // Send AJAX request
            $.ajax({
                url: 'social_media/linkedin/linkedin_post_handler.php', // Replace with your server-side URL
                method: 'POST',
                contentType: 'application/json', // Send JSON data
                data: JSON.stringify(data), // Correctly stringify the data for JSON format
                dataType: 'json', // Expecting a JSON response
                success: function(response) {
                    // Handle success response
                    if (response.status === 'success') {
                        $('#responseModal .modal-body').html('<div class="alert alert-success">Posted on LinkedIn!!</div>');
                    } else {
                        $('#responseMessage').html('<div class="alert alert-danger">Failed to share on LinkedIn. Please try again.</div>');
                    }
                },
                error: function() {
                    // Handle errors
                    $('#responseModal .modal-body').html('<div class="alert alert-danger">An error occurred. Please try again later.</div>');
                }
            });
        });

        $(document).on('click', '#fbSetSchedule', function() {
            setMinDateTime();
            $('#fbScheduleDateTimeDiv').show();
            $('#fbSchedulePost').show();
            $('#fbSetSchedule').hide();
        });

        // Post Now button click
        $(document).on('click', '#fbPostNow', function() {
            fbhandlePost('postNow');
        });

        // Schedule Post button click
        $(document).on('click', '#fbSchedulePost', function() {
            fbhandlePost('schedulePost');
        });

        // Function to handle posting
        function fbhandlePost(action) {
            var selectedPages = [];
            $('.form-check-input:checked').each(function() {
                selectedPages.push({
                    id: $(this).val(),
                    name: $(this).next('label').text().trim() // Trim whitespace
                });
            });

            var scheduleDateTime = $('#fbScheduleDateTime').val();

            if (scheduleDateTime) {
                scheduleDateTime = new Date(scheduleDateTime).toISOString();
            } else {
                scheduleDateTime = undefined; // Or some other value
            }

            var articleTitle = $('#fbArticleTitle').val();
            var articleLink = $('#fbArticleLink').val();

            if (selectedPages.length > 0) {
                if (action === 'schedulePost' && !scheduleDateTime) {
                    alert('Please select a date and time to schedule the post.');
                    return;
                }

                var postData = {
                    action: action,
                    pages: selectedPages,
                    title: articleTitle,
                    link: articleLink,
                    schedule: action === 'schedulePost' ? scheduleDateTime : undefined
                };

                // AJAX call to the post handler
                $.ajax({
                    url: 'social_media/facebook/fb_post_handler.php', // Update this path to your PHP file
                    type: 'POST',
                    data: JSON.stringify(postData),
                    contentType: 'application/json',
                    success: function(response) {
                        if (response.status == 'success') {
                            // Iterate through each item in the 'data' array
                            $('#responseModal .modal-body').html("<div class=\"alert alert-success\">" + (action === 'postNow' ? 'Posted ' : 'Scheduled ') + "on Facebook!!</div><br>Facebook Pages:");
                            response.data.forEach(function(item) {
                                var message = item.message;
                                var page = item.page;
                                $('#responseModal .modal-body').append('<br><strong>' + page + '</strong>');
                            });
                        } else {
                            alert('Post Unsuccessful..' + response.error_data);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('An error occurred: ' + error);
                    }
                });
            } else {
                alert('Please select at least one page to ' + (action === 'postNow' ? 'post.' : 'schedule.'));
            }
        }

        function setMinDateTime() {
            // Get the current date and time (local time)
            var now = new Date();

            // Add 20 minutes to the current time
            now.setMinutes(now.getMinutes() + 20);

            // Format the date and time correctly for the datetime-local input (YYYY-MM-DDTHH:MM)
            var year = now.getFullYear();
            var month = ('0' + (now.getMonth() + 1)).slice(-2); // Month is 0-based, so +1
            var day = ('0' + now.getDate()).slice(-2);
            var hour = ('0' + now.getHours()).slice(-2);
            var minutes = ('0' + now.getMinutes()).slice(-2);

            // Build the datetime-local compatible string (YYYY-MM-DDTHH:MM)
            var formattedDateTime = `${year}-${month}-${day}T${hour}:${minutes}`;

            // Set the 'min' attribute to 20 minutes ahead
            $('#fbScheduleDateTime').attr('min', formattedDateTime);

            // Optional: Set the value to 20 minutes ahead (this helps show the default value)
            $('#fbScheduleDateTime').val(formattedDateTime);
        }

        $(document).on('input', '#fbScheduleDateTime', function() {
            var now = new Date();
            // Add 20 minutes to the current time
            now.setMinutes(now.getMinutes() + 20);

            // Get the selected date-time and convert it to a Date object
            var selectedDateTime = new Date($('#fbScheduleDateTime').val());

            // Compare the selected time with the current time plus 20 minutes
            if (selectedDateTime <= now) {
                // Disable the button if the selected time is not valid (less than or equal to 20 minutes from now)
                $('#fbSchedulePost').prop('disabled', true);
                $('#timeAlert').show();
            } else {
                // Enable the button if the selected time is valid (greater than 20 minutes from now)
                $('#fbSchedulePost').prop('disabled', false);
                $('#timeAlert').hide();
            }
        });

        $(document).ready(function() {
            $('.buttonCreamShare').on('click', function() {
                var postData = {
                    act: 'showCreamShare',
                    id: $(this).data('id') // Get the data-id attribute of the clicked button
                };
                $.ajax({
                    url: 'process/get.section.utils.php', // Endpoint to send the request to
                    method: 'POST', // HTTP method (POST)
                    data: postData, // Data to send in the request
                    success: function(response) {
                        $('#responseModalLabel').text('Share on Cream');
                        // Place the response inside the #responseModal body
                        $('#responseModal .modal-body').html(response);
                        // Show the modal
                        $('#responseModal').modal('show');
                    },
                    error: function(xhr, status, error) {
                        // Handle errors here
                        console.error('Request failed: ' + error);
                        $('#responseModalLabel').text('Error');
                        // Optionally show an error message inside the modal
                        $('#responseModal .modal-body').html('An error occurred while fetching the data.');
                        $('#responseModal').modal('show');
                    }
                });
            });


            $('.fb-share').on('click', function() {
                console.log($(this).data('feed-title'));
                console.log($(this).data('feed-url'));

                var postData = {
                    act: 'showFBShare',
                    title: $(this).data('feed-title'),
                    url: $(this).data('feed-url')
                };
                $.ajax({
                    url: '', // Endpoint to send the request to
                    method: 'POST', // HTTP method (POST)
                    data: postData, // Data to send in the request
                    success: function(response) {
                        $('#responseModalLabel').text('Share on Facebook');
                        // Place the response inside the #responseModal body
                        $('#responseModal .modal-body').html(response);
                        // Show the modal
                        $('#responseModal').modal('show');
                        if ($('#fbScheduleDateTime').length) {
                            setMinDateTime();
                        }
                    },
                    error: function(xhr, status, error) {
                        // Handle errors here
                        console.error('Request failed: ' + error);
                        $('#responseModalLabel').text('Error');
                        // Optionally show an error message inside the modal
                        $('#responseModal .modal-body').html('An error occurred while fetching the data.');
                        $('#responseModal').modal('show');
                    }
                });
            });

            $('.li-share').on('click', function() {
                var postData = {
                    act: 'showLIShare',
                    title: $(this).data('feed-title'),
                    url: $(this).data('feed-url'),
                    desc: $(this).data('feed-desc')
                };
                $.ajax({
                    url: '', // Endpoint to send the request to
                    method: 'POST', // HTTP method (POST)
                    data: postData, // Data to send in the request
                    success: function(response) {
                        $('#responseModalLabel').text('Share on LinkedIn');
                        // Place the response inside the #responseModal body
                        $('#responseModal .modal-body').html(response);
                        // Show the modal
                        $('#responseModal').modal('show');
                    },
                    error: function(xhr, status, error) {
                        // Handle errors here
                        console.error('Request failed: ' + error);
                        // Optionally show an error message inside the modal
                        $('#responseModal .modal-body').html('An error occurred while fetching the data.');
                        $('#responseModal').modal('show');
                    }
                });
            });

            // Open the modal when an email row is clicked
            $('.emailRow').on('click', function() {
                var postData = {
                    act: 'emailForm',
                    id: $(this).data('id') // Get the 'id' from the clicked element
                };

                // Make an AJAX request to fetch the content for the modal
                $.ajax({
                    method: 'POST',
                    url: '', // Add your URL here for processing the request
                    data: postData,
                    success: function(response) {
                        // Assuming your response contains the HTML to be displayed in the modal
                        $('#responseModal .modal-body').html(response);

                        // Initialize TinyMCE once the content has been loaded into the modal
                        tinymce.remove();
                        tinymce.init({
                            selector: 'textarea#sendBody',
                            height: 400,
                            statusbar: false,
                            // forced_root_block: '',
                            force_br_newlines: true,
                            // force_p_newlines: false,
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
                            menubar: 'file edit view format',
                            toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright alignjustify | outdent indent | numlist bullist | forecolor backcolor removeformat | charmap | fullscreen preview | image media link',
                            plugins: [
                                // Core editing features
                                'anchor', 'autolink', 'charmap', 'codesample', 'emoticons', 'image', 'link', 'lists', 'media', 'searchreplace', 'table', 'visualblocks', 'wordcount', 'imagetools'
                            ],

                            contextmenu: "link image imagetools table spellchecker lists"
                        });

                        $('#responseModalLabel').text('Email');

                        // Show the modal
                        $('#responseModal').modal('show');
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error: ' + error);
                    }
                });
            });

            // Triggering the edit functionality when '.editRow' is clicked
            $('.editRow').on('click', function() {
                // Get the data-id attribute of the clicked element
                var itemId = $(this).data('id');

                // Send AJAX request to fetch the content
                $.ajax({
                    type: 'POST',
                    url: '', // Replace with your actual URL
                    data: {
                        act: 'editForm',
                        id: itemId
                    },
                    success: function(response) {
                        // Inject the response into the modal body
                        $('#responseModal .modal-body').html(response);

                        // Initialize or reinitialize TinyMCE after loading the content
                        tinymce.remove(); // Remove any existing TinyMCE instance

                        // tinymce.init({
                        //     selector: 'textarea#postBody', // The textarea we want to turn into a TinyMCE editor
                        //     height: 400,
                        //     statusbar: false,
                        //     // forced_root_block: '',
                        //     force_br_newlines: true,
                        //     // force_p_newlines: false,
                        //     file_picker_types: 'image',
                        //     automatic_uploads: true,
                        //     paste_data_images: true,
                        //     invalid_elements: 'span',
                        //     extended_valid_elements: 'script[src|async|defer|type|charset]',
                        //     images_upload_url: 'process/upload.php',
                        //     file_picker_callback: function(callback, value, meta) {
                        //         var input = document.createElement('input');
                        //         input.setAttribute('type', 'file');
                        //         input.setAttribute('accept', 'image/*');
                        //         input.onchange = function() {
                        //             var file = this.files[0];
                        //             var reader = new FileReader();
                        //             reader.onload = function() {
                        //                 var id = 'blob' + (new Date()).getTime();
                        //                 var blobCache = tinymce.activeEditor.editorUpload.blobCache;
                        //                 var base64 = reader.result.split(',')[1];
                        //                 var blobInfo = blobCache.create(id, file, base64);
                        //                 blobCache.add(blobInfo);
                        //                 callback(blobInfo.blobUri(), {
                        //                     title: file.name
                        //                 });
                        //             };
                        //             reader.readAsDataURL(file);
                        //         };
                        //         input.click();
                        //     },
                        //     menubar: 'edit format',
                        //     toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright alignjustify | outdent indent | numlist bullist | table | forecolor backcolor removeformat | code fullscreen preview | hr charmap image media link',
                        //     plugins: [
                        //         // Core editing features
                        //         'anchor', 'autolink', 'charmap', 'codesample', 'emoticons', 'image', 'link', 'lists', 'media', 'searchreplace', 'table', 'visualblocks', 'wordcount', 'imagetools',
                        //         // Premium features
                        //         'checklist', 'mediaembed', 'casechange', 'export', 'formatpainter', 'pageembed', 'permanentpen', 'advtable', 'advcode', 'editimage', 'advtemplate', 'mentions', 'tableofcontents', 'footnotes', 'mergetags', 'inlinecss', 'markdown',
                        //     ],
                        //     contextmenu: 'link image imagetools table spellchecker lists'
                        // });



                        //this is paid version commented on 07/02/2025
                        // tinymce.init({
                        //     selector: 'textarea#postBody',
                        //     height: 400, 
                        //     statusbar: false,
                        //     force_br_newlines: true, 
                        //     file_picker_types: 'image',
                        //     automatic_uploads: true,
                        //     paste_data_images: true, 
                        //     invalid_elements: 'span', 
                        //     extended_valid_elements: 'script[src|async|defer|type|charset]',
                        //     images_upload_url: 'process/upload.php',

                        //     file_picker_callback: function(callback, value, meta) {
                        //         var input = document.createElement('input'); 
                        //         input.setAttribute('type', 'file'); 
                        //         input.setAttribute('accept', 'image/*'); 
                        //         input.onchange = function() {
                        //             var file = this.files[0];
                        //             var reader = new FileReader();
                        //             reader.onload = function() {
                        //                 var id = 'blob' + (new Date()).getTime();
                        //                 var blobCache = tinymce.activeEditor.editorUpload.blobCache;
                        //                 var base64 = reader.result.split(',')[1]; 
                        //                 var blobInfo = blobCache.create(id, file, base64); 
                        //                 blobCache.add(blobInfo); 
                        //                 callback(blobInfo.blobUri(), { 
                        //                     title: file.name
                        //                 });
                        //             };
                        //             reader.readAsDataURL(file); 
                        //         };
                        //         input.click(); 
                        //     },

                        //     menubar: 'edit format',
                        //     toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright alignjustify | ' +
                        //         'outdent indent | numlist bullist | table | forecolor backcolor removeformat | ' +
                        //         'code fullscreen preview | hr charmap image media link', 
                        //     plugins: [ 

                        //         'anchor', 'autolink', 'charmap', 'codesample', 'emoticons', 'image', 'link', 'lists',
                        //         'media', 'searchreplace', 'table', 'visualblocks', 'wordcount', 'imagetools',

                        //         'checklist', 'mediaembed', 'casechange', 'export', 'formatpainter', 'pageembed',
                        //         'permanentpen', 'advtable', 'advcode', 'editimage', 'advtemplate', 'mentions',
                        //         'tableofcontents', 'footnotes', 'mergetags', 'inlinecss', 'markdown'
                        //     ],
                        //     contextmenu: 'link image imagetools table spellchecker lists' 
                        // });

                        tinymce.init({
                            selector: 'textarea#postBody', // The textarea we want to turn into a TinyMCE editor
                            height: 400, // Set the height of the editor
                            statusbar: false, // Disable the status bar at the bottom
                            force_br_newlines: true, // Force line breaks with <br> tags instead of new paragraphs
                            file_picker_types: 'image', // Enable file picker for images
                            automatic_uploads: true, // Automatically upload images
                            paste_data_images: true, // Allow pasting images as base64-encoded
                            invalid_elements: 'span', // Disallow <span> tags in the content
                            extended_valid_elements: 'script[src|async|defer|type|charset]', // Allow valid <script> tags
                            images_upload_url: 'process/upload.php', // URL to upload images

                            // File picker callback for custom image upload handling
                            file_picker_callback: function(callback, value, meta) {
                                var input = document.createElement('input'); // Create an input element
                                input.setAttribute('type', 'file'); // Set it as a file input
                                input.setAttribute('accept', 'image/*'); // Accept only images
                                input.onchange = function() {
                                    var file = this.files[0]; // Get the selected file
                                    var reader = new FileReader(); // Create a FileReader
                                    reader.onload = function() {
                                        var id = 'blob' + (new Date()).getTime(); // Create a unique ID
                                        var blobCache = tinymce.activeEditor.editorUpload.blobCache; // Get the blob cache
                                        var base64 = reader.result.split(',')[1]; // Get the base64 part of the image
                                        var blobInfo = blobCache.create(id, file, base64); // Create a blob info object
                                        blobCache.add(blobInfo); // Add it to the blob cache
                                        callback(blobInfo.blobUri(), { // Pass the blob URI to the callback
                                            title: file.name // Set the title as the file name
                                        });
                                    };
                                    reader.readAsDataURL(file); // Read the file as a data URL
                                };
                                input.click(); // Trigger the file input click
                            },

                            menubar: 'edit format', // Define the menu bar
                            toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright alignjustify | ' +
                                'outdent indent | numlist bullist | table | forecolor backcolor removeformat | ' +
                                'code fullscreen preview | hr charmap image media link', // Define the toolbar
                            plugins: [ // List of plugins to include (free version)
                                'anchor', 'autolink', 'charmap', 'codesample', 'emoticons', 'image', 'link', 'lists',
                                'media', 'searchreplace', 'table', 'visualblocks', 'wordcount', 'imagetools'
                            ],
                            contextmenu: 'link image imagetools table spellchecker lists' // Define the context menu
                        });


                        $('#responseModalLabel').text('Edit');

                        // Open the modal (show the modal)
                        $('#responseModal').modal('show');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching content: ', error);
                        alert('An error occurred while loading the content.');
                    }
                });
            });

            // Triggering the restore functionality when '.restoreRow' is clicked
            $('.restoreRow').on('click', function() {
                // Get the data-id attribute of the clicked element
                var itemId = $(this).data('id');

                // Send AJAX request to fetch the content
                $.ajax({
                    type: 'POST',
                    url: '', // Replace with your actual URL
                    data: {
                        act: 'restoreCollection',
                        id: itemId
                    },
                    success: function(response) {
                        // Inject the response into the modal body
                        $('#responseModal .modal-body').html(response);

                        $('#responseModalLabel').text('Restore');
                        // Open the modal (show the modal)
                        $('#responseModal').modal('show');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching content: ', error);
                        alert('An error occurred while restoring the collection.');
                    }
                });
            });

            // Archive Row action
            $('.archiveRow').on('click', function() {
                var itemId = $(this).data('id'); // Get the data-id attribute

                $.ajax({
                    type: 'POST',
                    url: '', // Replace with your actual URL
                    data: {
                        act: 'archiveCollection',
                        id: itemId
                    },
                    success: function(response) {
                        $('#responseModalLabel').text('Archive');
                        // Inject the response into the modal body
                        $('#responseModal .modal-body').html(response);

                        // Open the modal
                        $('#responseModal').modal('show');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error archiving collection: ', error);
                        alert('An error occurred while archiving the collection.');
                    }
                });
            });

            // Delete Row action
            $('.delRow').on('click', function() {
                var itemId = $(this).data('id'); // Get the data-id attribute

                $.ajax({
                    type: 'POST',
                    url: '', // Replace with your actual URL
                    data: {
                        act: 'delCollection',
                        id: itemId
                    },
                    success: function(response) {
                        $('#responseModalLabel').text('Delete');
                        // Inject the response into the modal body
                        $('#responseModal .modal-body').html(response);

                        // Open the modal
                        $('#responseModal').modal('show');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error deleting collection: ', error);
                        alert('An error occurred while deleting the collection.');
                    }
                });
            });
        });
    </script>

    <script>
        var myModal;
        let tempUrl = '';
        let letUrl = true;


        function removeMeta() {
            $('#linkPreview').hide();
            $('#hiddenTitle').val('');
            $('#hiddenDesc').val('');
            $('#hiddenUrl').val('');
            $('#hiddenDomain').val('');
            $('#hiddenImage').val('');
        }

        function removeYT() {
            $('ytPreview').html('');
            $('#ytPreview').hide();
        }

        function uploadPost() {
            // Collect the data from the inputs
            var content = $("#contentTextarea").val(); // Textarea content
            var fileInput = $("#fileInput")[0].files[0]; // The selected file
            var hiddenTitle = $("#hiddenTitle").val();
            var hiddenDesc = $("#hiddenDesc").val();
            var hiddenUrl = $("#hiddenUrl").val();
            var hiddenImage = $("#hiddenImage").val();
            var hiddenDomain = $("#hiddenDomain").val();
            var hiddenYTLink = $("#hiddenYTLink").val();

            // Create a FormData object to send data (use FormData if sending files)
            var formData = new FormData();

            // Conditionally append fields if they have values
            if (content) formData.append("content", content);
            if (fileInput) formData.append("media", fileInput); // Attach the file if selected
            if (hiddenTitle) formData.append("hiddenTitle", hiddenTitle);
            if (hiddenDesc) formData.append("hiddenDesc", hiddenDesc);
            if (hiddenUrl) formData.append("hiddenUrl", hiddenUrl);
            if (hiddenImage) formData.append("hiddenImage", hiddenImage);
            if (hiddenDomain) formData.append("hiddenDomain", hiddenDomain);
            if (hiddenYTLink) formData.append("hiddenYTLink", hiddenYTLink);

            // Show loading spinner
            $("#loadingIcon").show();

            // Send the data to PHP via AJAX
            $.ajax({
                url: 'process_data.php', // The PHP page where you want to process the data
                type: 'POST',
                data: formData,
                processData: false, // Important for sending files
                contentType: false, // Important for sending files
                success: function(response) {
                    // Hide loading spinner after response
                    $("#loadingIcon").hide();

                    if (response.status === 'success') {
                        alert("Posted successfully!");
                        myModal.hide(); // Use myModal here to hide the modal after successful post
                    } else {
                        alert("Error: " + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    $("#loadingIcon").hide();
                    alert("An error occurred: " + error);
                }
            });
        }

        $(document).ready(function() {
            $('.openModalButton').on('click', function() {
                // Get the parent div of the clicked label
                var parentDiv = $(this).closest('.data');

                // Fetch the value of the data-feed-url attribute
                var feedUrl = parentDiv.data('feed-url');

                // Use the fetched URL (for example, setting it in the textarea)
                $('#contentTextarea').text(feedUrl);


                // Show the modal using Bootstrap's modal API
                myModal = new bootstrap.Modal($('#uploadModal')[0]);
                myModal.show();

                $('#contentTextarea').trigger('input');
            });

            $('#contentTextarea').on('input', function() {
                var content = $('#contentTextarea').val().trim();
                console.log(content);

                // Updated regular expression to match full URLs, including subdomains and paths
                var url_pattern = /(?:https?|ftp):\/\/(?:[a-zA-Z0-9-]+\.)?(?:[a-zA-Z0-9-]+\.[a-zA-Z]{2,})(?:\/[^\s]*)?/g;

                var youtube_pattern = /(?:v=|youtu\.be\/|embed\/)([a-zA-Z0-9_-]+)/;

                // Attempt to match all URLs within the content
                var matchedUrls = content.match(url_pattern);
                var youtubeUrl = content.match(youtube_pattern);

                if (youtubeUrl) {
                    youtubeUrl.forEach(function(url) {
                        console.log('Matched URL:', url); // Log each matched URL
                    });
                    if (tempUrl != youtubeUrl[0]) {
                        letUrl = true;
                        tempUrl = youtubeUrl[0];
                        removeMeta();
                        // Show the loading animation
                        $('#loadingIcon').show();
                        // $('#postedContent').html(''); // Clear any previous metadata
                        // Send the first matched URL to the server for metadata fetching
                        $.ajax({
                            url: 'link.php', // Submit to the same page
                            type: 'POST',
                            data: {
                                ytUrl: youtubeUrl[0]
                            }, // Send the first matched URL
                            success: function(response) {
                                // Parse the response JSON
                                var responseData = JSON.parse(response);
                                // Hide the loading animation
                                $('#loadingIcon').hide();
                                if (responseData.iframe) {
                                    $('#ytPreview').html(responseData.iframe);
                                    $('#hiddenYTLink').val(responseData.iframe || '');
                                    $('#hiddenTitle').val(responseData.title || '');
                                    $('#ytPreview').show();
                                } else {
                                    return;
                                }
                            },
                            error: function() {
                                // Hide the loading animation on error
                                $('#loadingIcon').hide();
                                console.log("Error fetching Youtube Link");
                            }
                        });
                    }
                } else if (matchedUrls) {
                    // Log all matched URLs to the console (for testing purposes)
                    matchedUrls.forEach(function(url) {
                        console.log('Matched URL:', url); // Log each matched URL
                    });
                    if (tempUrl != matchedUrls[0]) {
                        letUrl = true;
                        tempUrl = matchedUrls[0];
                        removeYT();
                        // Show the loading animation
                        $('#loadingIcon').show();
                        // $('#postedContent').html(''); // Clear any previous metadata
                        // Send the first matched URL to the server for metadata fetching
                        $.ajax({
                            url: 'link.php', // Submit to the same page
                            type: 'POST',
                            data: {
                                url: matchedUrls[0]
                            }, // Send the first matched URL
                            success: function(responseData) {
                                // Parse the response JSON
                                // var responseData = JSON.parse(response);
                                // Hide the loading animation
                                $('#loadingIcon').hide();
                                $('#linkPreview').show();
                                if (responseData.url) {
                                    $('#linkPreview #linkHeading').html(responseData.title || ''); // Set heading text
                                    $('#linkPreview #linkDesc').html(responseData.description || ''); // Set description text
                                    $('#linkPreview #linkUrl').attr('href', responseData.url || ''); // Set link URL
                                    $('#linkPreview #linkUrl').html(responseData.domain || '');
                                    $('#hiddenTitle').val(responseData.title || '');
                                    $('#hiddenDesc').val(responseData.description || '');
                                    $('#hiddenUrl').val(responseData.url || '');
                                    $('#hiddenDomain').val(responseData.domain || '');
                                    if (responseData.image) {
                                        $('#linkPreview img').attr('src', responseData.image); // Set image source
                                        $('#hiddenImage').val(responseData.image);
                                    }
                                } else {
                                    return;
                                }
                            },
                            error: function() {
                                // Hide the loading animation on error
                                $('#loadingIcon').hide();
                                console.log("Error fetching the metadata");
                            }
                        });
                    }
                } else {
                    // Clear content and hide the loading animation if no valid URL is found
                    $('#loadingIcon').hide();
                }
                if (tempUrl != youtubeUrl[0]) {
                    removeYT();
                }
                if (tempUrl != matchedUrls[0]) {
                    removeMeta()
                }
                if (youtubeUrl[0] == '') {
                    removeYT();
                }
                if (matchedUrls[0] == '') {
                    removeMeta();
                }
            });
        });
    </script>

    <script>
        function esamudaayshare(feedId) {

            $.ajax({
                url: 'clients/esamudaay.php',
                method: 'POST',
                data: {
                    feedId: feedId
                },
                success: function(response) {
                    // Handle success
                    alert("Added to eSamudaay!!");
                    $('#eSamudaayShare').replaceWith(`<button class="btn btn-success" id="eSamudaayShare" disabled>Shared to eSamudaay</button>`);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    // Handle error
                    console.error("Error:", textStatus, errorThrown);
                }
            });

        }
    </script>

    </html>

<? } ?>