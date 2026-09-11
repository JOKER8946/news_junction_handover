<?
// Cream: My Settings

require_once '../inc/php/validate.logged.php';
require_once '../inc/config.php';

$act = '';
if (!empty($_POST)) $act = isset($_POST["act"]) ? $_POST["act"] : '';

function fetchProfession($db, $userId)
{
    $sql = "SELECT profession FROM user WHERE id = ?";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $db->error);
    }
    $stmt->bind_param("i", $userId);
    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $profession = $row['profession'];
    } else {
        $profession = null;
    }
    $stmt->close();
    return $profession;
}

$selectedProfession = fetchProfession($db, $gUserId);


// Check if Subdomain exists
if ($act == 'chkExistSubdomain') {
    $chkSubdomain = isset($_POST['subdomain']) ? $_POST['subdomain'] : '';
    if ($chkSubdomain == '') {
        echo 'OK';
    } else {
        $chkSubdomain = strtolower($chkSubdomain);
        $sql = "SELECT id FROM user WHERE subdomain='$chkSubdomain' AND id<>$gUserId";
        $result = mysqli_query($db, $sql);
        $numRows = mysqli_num_rows($result);
        if ($numRows == 0) {
            echo 'OK';
        }
    }
}


// Get Cream Feeds
if ($act == 'getCreamFeeds') {
    $categoryId = isset($_POST['categoryId']) ? $_POST['categoryId'] : '';
    if ($categoryId <> '') {
        $returnHTML = '';
        $sql = "SELECT * FROM feeds_rss WHERE category_id=$categoryId ORDER BY feed_url";
        $result = mysqli_query($db, $sql);
        $numRows = mysqli_num_rows($result);
        if ($numRows > 0) {
            $returnHTML .= 'Select Feeds';
            for ($i = 0; $i < $numRows; $i++) {
                $row = mysqli_fetch_array($result);
                $returnHTML .= '<div class="row my-3">';
                $returnHTML .= ' <div class="col-1"><input type="checkbox" id="feedData" name="feedData" value="' . $row['id'] . '" onclick="addCreamFeed()"></div>';
                $returnHTML .= ' <div class="col">' . $row['feed_url'] . '</div>';
                $returnHTML .= '</div>';
            }
        }
        echo $returnHTML;
    }
}


// Added Cream Feed
if ($act == 'addedCreamFeed') {
    $data = isset($_POST['data']) ? $_POST['data'] : '';
    if ($data != '') {
        $arrData = json_decode($data, true);
        foreach ($arrData as &$value) {
            $feedId = $value['id'];
            $sql = "SELECT category_id,feed_url FROM feeds_rss WHERE id=$feedId";
            $result = mysqli_query($db, $sql);
            $numRows = mysqli_num_rows($result);
            if ($numRows > 0) {
                $row = mysqli_fetch_array($result);
                $categoryId = $row['category_id'];
                $feedURL = $row['feed_url'];
                $sql = "SELECT id FROM user_feeds WHERE feed_url='$feedURL' AND user_id=$gUserId";
                $resultInner = mysqli_query($db, $sql);
                $numRowsInner = mysqli_num_rows($resultInner);
                if ($numRowsInner == 0) {
                    $sql = "INSERT INTO user_feeds(user_id,category_id,feed_url,is_feed_cream) VALUES($gUserId,$categoryId,'$feedURL',1)";
                    mysqli_query($db, $sql);
                }
            }
        }
        echo "OK";
    }
}


// Added External Feed
if ($act == 'addedExternalFeed') {
    $categoryId = isset($_POST['categoryId']) ? $_POST['categoryId'] : '';
    $feedURL = isset($_POST['feedURL']) ? $_POST['feedURL'] : '';
    if ($categoryId != '' && $feedURL != '') {
        $sql = "INSERT INTO user_feeds(user_id,category_id,feed_url,is_feed_cream) VALUES($gUserId,$categoryId,'$feedURL',0)";
        mysqli_query($db, $sql);
        echo "OK";
    }
}


// Delete User Feed
if ($act == 'deleteFeed') {
    $feedId = isset($_POST['feedId']) ? $_POST['feedId'] : '';
    if ($feedId != '') {
        $sql = "DELETE FROM user_feeds WHERE id=$feedId AND user_id=$gUserId";
        mysqli_query($db, $sql);
        echo "OK";
    }
}


// Update Subdomain
if ($act == 'editSubdomain') {
    $userSubdomain = isset($_POST['subdomain']) ? $_POST['subdomain'] : '';
    $_SESSION['userSubdomain'] = $userSubdomain;
    if ($userSubdomain == '') {
        $userSubdomain = NULL;
    } else {
        $userSubdomain = strtolower($userSubdomain);
    }
    $sql = "UPDATE user SET subdomain='$userSubdomain',date_modified=Now() WHERE id=$gUserId";
    mysqli_query($db, $sql);
    echo "OK";
}


// Update View Page Settings
if ($act == 'editView') {
    $userSidePanel = isset($_POST['userSidePanel']) ? $_POST['userSidePanel'] : '';
    $userSidePanelContent = isset($_POST['userSidePanelContent']) ? $_POST['userSidePanelContent'] : '';
    $sql = "UPDATE user SET is_side_panel=$userSidePanel,side_panel_content='$userSidePanelContent',date_modified=Now() WHERE id=$gUserId";
    mysqli_query($db, $sql);
    echo "OK";
}


// Update Newsletter Settings
if ($act == 'editNewsletter') {
    $userNewsletterTitle = isset($_POST['userNewsletterTitle']) ? $_POST['userNewsletterTitle'] : '';
    if ($userNewsletterTitle != '') {
        $fileUpload = '';
        if (isset($_FILES['userNewsletterLogo'])) {
            $temp = $_FILES['userNewsletterLogo'];
            if (is_uploaded_file($temp['tmp_name'])) {
                $fileExt = strtolower(pathinfo($temp['name'], PATHINFO_EXTENSION));
                $fileUpload = $gUserId . '-' . time() . '.' . $fileExt;
                move_uploaded_file($temp['tmp_name'], '../data/logos/' . $fileUpload);
            }
        }
        $sql = "UPDATE user SET news_title='$userNewsletterTitle',news_logo='$fileUpload',date_modified=Now() WHERE id=$gUserId";
        mysqli_query($db, $sql);
        echo "OK";
    }
}


// Update Send Grid Key
if ($act == 'editSendGridKey') {
    $userSendGridKey = isset($_POST['userSendGridKey']) ? $_POST['userSendGridKey'] : '';
    $userSendGridSuppressionId = isset($_POST['userSendGridSuppressionId']) ? $_POST['userSendGridSuppressionId'] : '';
    if ($userSendGridKey != '' && $userSendGridSuppressionId != '') {
        $sql = "UPDATE user SET send_grid_key='$userSendGridKey',send_grid_suppression_id='$userSendGridSuppressionId',date_modified=Now() WHERE id=$gUserId";
        mysqli_query($db, $sql);
        echo "OK";
    }
}


// Show Add from Cream Feed
if ($act == 'addCreamFeed') {
?>
    <div class="popup" id="widthPc">
        <div class="widget" style="overflow: scroll; max-height: 60vh;">
            <form id="frmAdd" name="frmAdd">
                <div class="card">
                    <div class="card-header bg-dark">
                        <h5 class="mb-0 text-light">Add from Cream Feed</h5>
                    </div>
                </div>
                <div id="widget_B" style="padding:15px 25px">
                    <?
                    if ($gUserPlan == 0) {
                        $sql = "SELECT id FROM user_feeds WHERE user_id=$gUserId";
                        $result = mysqli_query($db, $sql);
                        $numFeed = mysqli_num_rows($result);
                        if ($numFeed >= 5) {
                            echo '<div class="alert alert-success" role="alert">You have exceeded the number of feeds.<br>Go to My Account to upgrade.</div>';
                            die();
                        }
                    }
                    ?>
                    <div class="form-group">
                        <label for="categoryId">Category</label>
                        <select id="categoryId" name="categoryId" class="form-control" onchange="getCreamFeeds()">
                            <option value="">--[select]--
                                <?
                                $sql = "SELECT id,category FROM category ORDER BY category";
                                $result = mysqli_query($db, $sql);
                                $numRows = mysqli_num_rows($result);
                                if ($numRows > 0) {
                                    for ($i = 0; $i < $numRows; $i++) {
                                        $row = mysqli_fetch_array($result);
                                        $tmpCategoryId = $row['id'];
                                        $tmpCategoryName = $row['category'];
                                        echo '   <option value="' . $tmpCategoryId . '">' . $tmpCategoryName;
                                    }
                                }
                                ?>
                            <option value="0">Others
                        </select>
                    </div>
                    <div id="panelCreamFeeds">Select Category to see Feeds!</div>
                </div>
                <div id="widget_F" style="border-top:1px solid #ebedf2;padding:20px 10px;">
                    <div class="col">
                        <button id="buttonAddCreamFeed" class="hide btn btn-primary" onclick="return chkAddCreamFeed()">Add Selected Feed(s)</button>
                        <div id="panelStatus" class="float-right text-sm" style="margin-top:5px" align="right"></div>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?
}


// Show Add External Feed
if ($act == 'addExternalFeed') {
?>
    <div class="popup" id="widthPc">
        <div class="widget">
            <form id="frmAdd" name="frmAdd">
                <div class="card">
                    <div class="card-header bg-dark">
                        <h5 class="mb-0 text-light">Add External Feed</h5>
                    </div>
                </div>
                <div id="widget_B" style="padding:15px 25px">
                    <?
                    if ($gUserPlan == 0) {
                        $sql = "SELECT id FROM user_feeds WHERE user_id=$gUserId";
                        $result = mysqli_query($db, $sql);
                        $numFeed = mysqli_num_rows($result);
                        if ($numFeed >= 5) {
                            echo '<div class="alert alert-success" role="alert">You have exceeded the number of feeds.<br>Go to My Account to upgrade.</div>';
                            die();
                        }
                    }
                    ?>
                    <div class="form-group">
                        <label for="categoryId">Category</label>
                        <select id="categoryId" name="categoryId" class="form-control">
                            <option value="">--[select]--
                                <?
                                $sql = "SELECT id,category FROM category ORDER BY category";
                                $result = mysqli_query($db, $sql);
                                $numRows = mysqli_num_rows($result);
                                if ($numRows > 0) {
                                    for ($i = 0; $i < $numRows; $i++) {
                                        $row = mysqli_fetch_array($result);
                                        $tmpCategoryId = $row['id'];
                                        $tmpCategoryName = $row['category'];
                                        echo '   <option value="' . $tmpCategoryId . '">' . $tmpCategoryName;
                                    }
                                }
                                ?>
                            <option value="0">Others
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="feedURL">Feed URL</label>
                        <input type="text" class="form-control" id="feedURL" name="feedURL" maxlength="200" />
                        <small class="form-text text-muted">Please enter the URL with http:// or https://</small>
                    </div>
                </div>
                <div id="widget_F" style="border-top:1px solid #ebedf2;padding:20px 10px;">
                    <div class="col">
                        <button type="submit" class="btn btn-primary" onclick="return chkAddExternalFeed()">Add Feed</button>
                        <div id="panelStatus" class="float-right text-sm" style="margin-top:5px" align="right"></div>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?
}


// Show Delete Feed
if ($act == 'delFeed') {
    $feedId = isset($_POST['id']) ? $_POST['id'] : '';
?>
    <div class="popup" style="width:420px">
        <div class="widget">
            <form id="frmDel" name="frmDel">
                <div class="card">
                    <div class="card-header bg-dark">
                        <h5 class="mb-0 text-light">Delete Feed</h5>
                    </div>
                </div>
                <div id="widget_B" style="padding:15px 25px">
                    You are about to delete this feed from your account!
                </div>
                <div id="widget_F" style="border-top:1px solid #ebedf2;padding:20px 10px;">
                    <div class="col">
                        <button type="submit" class="btn btn-primary" onclick="return chkDelFeed()">Confirm</button>
                    </div>
                </div>
                <input type="hidden" id="delId" name="delId" value="<?= $feedId ?>" />
                <input type="hidden" id="act" name="act" value="deleteFeed" />
            </form>
        </div>
    </div>
<?
}

if ($act == 'genaiProfession') {
    $profession = $_POST['profession'];
    $sql = "UPDATE user SET profession = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("si", $profession, $gUserId);
    if ($stmt->execute()) {
        echo "OK";
    } else {
        echo "Error updating record: " . $stmt->error;
    }
}


// Default
if ($act == '') {
    $newsTitle = '';
    $newsLogo = '';
    $sql = "SELECT subdomain,news_title,news_logo,send_grid_key,send_grid_suppression_id,is_side_panel,side_panel_content FROM user WHERE id=$gUserId";
    $result = mysqli_query($db, $sql);
    $numRows = mysqli_num_rows($result);
    if ($numRows > 0) {
        $row = mysqli_fetch_assoc($result);
        $newsTitle = $row['news_title'];
        $newsLogo = $row['news_logo'];
        $userSubdomain = $row['subdomain'];
        $userSendGridKey = $row['send_grid_key'];
        $userSendGridSuppressionId = $row['send_grid_suppression_id'];
        $userSidePanel = $row['is_side_panel'];
        $userSidePanelContent = $row['side_panel_content'];
    }
?>
    <ol class="breadcrumb my-3">
        <li class="breadcrumb-item">
            <h4 class="m-0">My Settings</h4>
        </li>
    </ol>
    <div class="row mb-4">
        <div class="col">
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#rss" role="tab" onclick="$('#panelStatus').html('')">My RSS Feeds</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#view" role="tab" onclick="$('#panelStatus').html('')">View Page Settings</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#newsletter" role="tab" onclick="$('#panelStatus').html('')">Newsletter Settings</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#sendgrid" role="tab" onclick="$('#panelStatus').html('')">SendGrid Settings</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#genai" role="tab" onclick="$('#panelStatus').html('')">GenAI Settings</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#subdomain" role="tab" onclick="$('#panelStatus').html('')">Setup Subdomain</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#social" role="tab" onclick="$('#panelStatus').html('')">Social Media</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#api" role="tab" onclick="$('#panelStatus').html('')">Generate api key</a></li>
                <? if ($gUserId == 23) { ?>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#social" role="tab" onclick="$('#panelStatus').html('')">Social Media</a></li>

                <? } ?>
            </ul>
            <div class="tab-content m-3">
                <div class="tab-pane fade show active" id="rss" role="tabpanel">
                    <button type="button" id="buttonAddExternalFeed" class="btn btn-secondary mb-2 float-right"><i class="fas fa-plus"></i> Add External Feed</button>
                    <button type="button" id="buttonAddCreamFeed" class="btn btn-secondary mb-2 mr-2 float-right"><i class="fas fa-plus"></i> Add from Cream Feeds</button>
                    <br clear="all" />
                    <?
                    $numFeed = 1;
                    $sql = "SELECT A.*,B.category FROM user_feeds A LEFT JOIN category B ON A.category_id=B.id WHERE A.user_id=$gUserId ORDER BY category";
                    $result = mysqli_query($db, $sql);
                    $numRows = mysqli_num_rows($result);
                    if ($numRows > 0) {
                    ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Category</th>
                                        <th scope="col">Feed</th>
                                        <th scope="col"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $feedId = $row['id'];
                                        $feedURL = $row['feed_url'];
                                        $feedCategory = $row['category'];
                                        if ($feedCategory == '') $feedCategory = 'Others';
                                    ?>
                                        <tr>
                                            <th scope="row"><?= $numFeed ?></th>
                                            <td><?= $feedCategory ?></td>
                                            <td><?= $feedURL ?></td>
                                            <td><a href="#" title="Delete Feed"><i data-id="<?= $feedId ?>" class="delRow far fa-trash-alt fa-lg text-muted"></i></a></td>
                                        </tr>
                                    <?
                                        $numFeed += 1;
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    <?
                    } else {
                    ?>
                        You have not added any feeds!
                    <?
                    }
                    ?>
                </div>
                <div class="tab-pane fade" id="view" role="tabpanel">
                    <div class="form-group row">
                        <label for="userSidePanel" class="col-sm-2 col-form-label">Show Side Panel</label>
                        <div class="col-sm-12 col-md-6">
                            <div class="form-check form-check-inline ml-0 ml-sm-2 w-25">
                                <label class="form-control radio-inline border-0 pr-0"><input type="radio" id="userSidePanel" name="userSidePanel" value="1" <? if ($userSidePanel == 1) { ?> CHECKED<? } ?>> Yes</label>
                                <label class="form-control radio-inline border-0 pr-0"><input type="radio" id="userSidePanel" name="userSidePanel" value="0" <? if ($userSidePanel == 0 || $userSidePanel == '') { ?> CHECKED<? } ?>> No</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="userSidePanelContent" class="col-sm-2 col-form-label">Side Panel Content</label>
                        <div class="col-sm-12 col-md-3">
                            <textarea id="userSidePanelContent" name="userSidePanelContent"><?= $userSidePanelContent ?></textarea>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label"></label>
                        <div class="col-sm-12 col-md-6">
                            <button class="btn btn-primary" onclick="return chkUpdateView()">Update</button>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="newsletter" role="tabpanel">
                    <div class="form-group row">
                        <label for="userNewsletterTitle" class="col-sm-2 col-form-label">Newsletter Title</label>
                        <div class="col-sm-12 col-md-6">
                            <input type="text" class="form-control" id="userNewsletterTitle" name="userNewsletterTitle" value="<?= $newsTitle ?>" maxlength="100" />
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="userNewsletterLogo" class="col-sm-2 col-form-label">Newsletter Logo</label>
                        <div class="col-sm-12 col-md-3">
                            <input type="file" class="form-control-file" id="userNewsletterLogo" name="userNewsletterLogo" accept="image/*" />
                        </div>
                        <? if ($newsLogo <> '') { ?>
                            <div class="col-sm-12 col-md-3 mt-3 mt-md-0">
                                <a href="../data/logos/<?= $newsLogo ?>" target="_blank"><img src="../data/logos/<?= $newsLogo ?>" width="100" /></a>
                            </div>
                        <? } ?>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label"></label>
                        <div class="col-sm-12 col-md-6">
                            <button class="btn btn-primary" onclick="return chkUpdateNewsletter()">Update</button>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="sendgrid" role="tabpanel">
                    <?
                    if ($gUserPlan == 0) {
                        echo '<div class="alert alert-success" role="alert">This feature is only available in <b>Pro</b> plan! Go to My Account to upgrade.</div>';
                    } else {
                    ?>
                        <div class="form-group row">
                            <label for="userSendGridKey" class="col-sm-2 col-form-label">Authorization Key</label>
                            <div class="col-sm-12 col-md-6">
                                <input type="text" class="form-control" id="userSendGridKey" name="userSendGridKey" value="<?= $userSendGridKey ?>" maxlength="100" />
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="userSendGridSuppressionId" class="col-sm-2 col-form-label">Suppression Group ID</label>
                            <div class="col-sm-12 col-md-6">
                                <input type="text" class="form-control" id="userSendGridSuppressionId" name="userSendGridSuppressionId" value="<?= $userSendGridSuppressionId ?>" maxlength="10" />
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label"></label>
                            <div class="col-sm-12 col-md-6">
                                <button class="btn btn-primary" onclick="return chkUpdateSendGrid()">Update</button>
                            </div>
                        </div>
                    <? } ?>
                </div>
                <div class="tab-pane fade" id="subdomain" role="tabpanel">
                    <?
                    if ($gUserPlan == 0) {
                        echo '<div class="alert alert-success" role="alert">This feature is only available in <b>Pro</b> plan! Go to My Account to upgrade.</div>';
                    } else {
                    ?>
                        <div class="form-group row">
                            <label for="userSubdomain" class="col-sm-2 col-form-label">Subdomain</label>
                            <div class="col-sm-12 col-md-3">
                                <input type="text" class="form-control" id="userSubdomain" name="userSubdomain" maxlength="50" value="<?= $userSubdomain ?>" />
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label"></label>
                            <div class="col-sm-12 col-md-3">
                                <button class="btn btn-primary" onclick="return chkUpdateSubdomain()">Update Subdomain</button>
                            </div>
                        </div>
                    <? } ?>
                </div>
                <div class="tab-pane fade" id="genai" role="tabpanel">
                    <? if ($gUserPlan == 0) { ?>
                        <div class="alert alert-success" role="alert">This feature is only available in <b>Pro</b> plan! Go to My Account to upgrade.</div>
                    <? } else {
                        $professions = [
                            '' => '--Select a profession--',
                            'doctor' => 'Doctor',
                            'engineer' => 'Engineer',
                            'teacher' => 'Teacher',
                            'artist' => 'Artist',
                            'nurse' => 'Nurse',
                            'scientist' => 'Scientist',
                            'lawyer' => 'Lawyer',
                            'chef' => 'Chef',
                            'musician' => 'Musician',
                            'developer' => 'Software Developer',
                            'architect' => 'Architect',
                            'writer' => 'Writer',
                            'journalist' => 'Journalist',
                            'photographer' => 'Photographer',
                            'therapist' => 'Therapist',
                            'pilot' => 'Pilot',
                            'mechanic' => 'Mechanic',
                            'entrepreneur' => 'Entrepreneur',
                            'accountant' => 'Accountant',
                            'ca' => 'Chartered Accountant (CA)',
                            'pharmacist' => 'Pharmacist',
                            'social_worker' => 'Social Worker',
                            'dentist' => 'Dentist'
                        ];


                    ?>
                        <label for="profession">Choose a profession:</label>
                        <select id="profession" name="profession" class="form-control">
                            <?
                            foreach ($professions as $value => $label) {
                                $selected = ($value === $selectedProfession) ? ' selected' : '';
                                echo "<option value=\"$value\"$selected>$label</option>";
                            }
                            ?>
                        </select>

                        <button id='genaiProfession' class="btn btn-primary mt-3" onclick="fetchProfessionData()">Submit</button>
                        <div id="response" class="mt-3"></div>
                    <? } ?>
                </div>

                <div class="tab-pane fade" id="social" role="tabpanel">
                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label">Facebook</label>
                        <div class="col-sm-12 col-md-3">
                            <a href="social_media/facebook/facebook_setup.php"><button class="btn btn-primary" onclick="">Setup Facebook</button></a>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label">LinkedIn</label>
                        <div class="col-sm-12 col-md-3">
                            <a href="social_media/linkedin/linkedin_setup.php"><button class="btn btn-primary" onclick="">Setup LinkedIn</button></a>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="api" role="tabpanel">
                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label">Generate Token</label>
                        <div class="col-sm-12 col-md-3">
                            <button class="btn btn-primary" id="generateTokenBtn">Generate Api Key & Secret Token</button>
                        </div>
                    </div>

                    <!-- Table to display generated tokens -->
                    <div class="row">
                        <div class="col-sm-12">
                            <table class="table table-bordered" id="tokenTable">
                                <thead>
                                    <tr>
                                        <th>API Key </th>
                                        <th>Secret Token</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Tokens will be displayed here -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Instructions for using the API -->
                    <div class="mt-4">
                        <h4>Instructions for Fetching Data via API</h4>
                        <p>To retrieve data through the API, you will need to use your unique <strong>API Key</strong> and <strong>Secret Token</strong>. Please follow the steps below:</p>

                        <ol>
                            <li>
                                Copy the URL template provided below:
                                <p>
                                    <code>
                                        https://newsjunction.net/creamapi/fetch_data.php?api_key=<strong>API_KEY</strong>&secret_key=<strong>SECRET_TOKEN</strong>
                                    </code>
                                </p>
                            </li>
                            <li>Replace <strong>API_KEY</strong> with your actual API key.</li>
                            <li>Replace <strong>SECRET_TOKEN</strong> with your actual Secret token.</li>
                            <li>Open the updated URL in your browser or use it in your API client (like Postman) to fetch the data.</li>
                        </ol>

                        <h5>Optional Parameters for Filtering Data</h5>
                        <p>In addition to the required API and Secret keys, the following optional parameters can be included in your request to filter and paginate the data:</p>
                        <ul>
                            <li><strong>limit</strong>: Specifies the number of records to retrieve. Default is 10. Example: <code>&limit=20</code></li>
                            <li><strong>offset</strong>: Specifies the starting point of records (useful for pagination). Default is 0. Example: <code>&offset=10</code></li>
                            <li><strong>sort_order</strong>: Defines the sorting order of the data by date. Use <code>asc</code> for ascending or <code>desc</code> for descending. Default is <code>asc</code>. Example: <code>&sort_order=desc</code></li>
                        </ul>

                        <p>Example URL with filtering parameters:</p>
                        <p>
                            <code>
                                https://newsjunction.net/creamapi/fetch_data.php?api_key=YOUR_API_KEY&secret_key=YOUR_SECRET_KEY&limit=20&offset=10&sort_order=desc
                            </code>
                        </p>

                        <h5>Count API Call</h5>
                        <p>To retrieve only the count of records (instead of the full dataset), use the following URL:</p>
                        <p>
                            <code>
                                https://newsjunction.net/creamapi/fetch_data.php?api_key=YOUR_API_KEY&secret_key=YOUR_SECRET_KEY&action=count
                            </code>
                        </p>
                        <p>This will return a JSON response containing the total number of records available.</p>

                        <h5>Example Response for Count API</h5>
                        <p>A successful response from the Count API will look like this:</p>
                        <pre><code>
{
    "status": "success",
    "count": 150
}
    </code></pre>

                        <h5>Example Response for Data Fetching</h5>
                        <p>When using the fetch API (with filtering options or without), the response will contain the data in JSON format, as shown below:</p>
                        <pre><code>
{
    "status": "success",
    "data": [
        {
            "title": "Sample Title",
            "url": "https://example.com",
            "description": "Sample description of the data",
            "date_added": "2024-10-20"
        },
        ...
    ]
}
    </code></pre>
                    </div>


                </div>

                <script>
                    document.getElementById('generateTokenBtn').addEventListener('click', function() {
                        fetch('creamapi/generate_token.php')
                            .then(response => response.json())
                            .then(data => {
                                // Check for errors
                                if (data.error) {
                                    console.error('Error:', data.error);
                                    alert(data.error); // Show error to user
                                    return;
                                }

                                // Create a new row for the generated token and access key
                                const tokenTableBody = document.getElementById('tokenTable').querySelector('tbody');
                                const newRow = document.createElement('tr');

                                // Create cells for API token and Access key
                                const tokenCell = document.createElement('td');
                                tokenCell.textContent = data.api_token; // Set the token text

                                const accessKeyCell = document.createElement('td');
                                accessKeyCell.textContent = data.access_key; // Set the access key text

                                // Append cells to the new row
                                newRow.appendChild(tokenCell);
                                newRow.appendChild(accessKeyCell);
                                tokenTableBody.appendChild(newRow); // Add the new row to the table
                            })
                            .catch(error => console.error('Error:', error));
                    });
                </script>


                <!-- <? if ($gUserId == 23) { ?>
                    <div class="tab-pane fade" id="social" role="tabpanel">
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Facebook</label>
                            <div class="col-sm-12 col-md-3">
                                <button class="btn btn-primary" onclick="return authPopup('Facebook')">Link account</button>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Twitter</label>
                            <div class="col-sm-12 col-md-3">
                                <button class="btn btn-primary" onclick="return authPopup('Twitter')">Link account</button>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">LinkedIn</label>
                            <div class="col-sm-12 col-md-3">
                                <button class="btn btn-primary" onclick="return authPopup('LinkedIn')">Link account</button>
                            </div>
                        </div>
                    </div>
                <? } ?> -->

                <div id="panelStatus" class="mt-4"></div>
            </div>
        </div>
    </div>
    <script src="../inc/js/common.js"></script>
    <script type="text/javascript">
        $(function() {
            $('#buttonAddCreamFeed').magnificPopup({
                type: 'ajax',
                closeBtnInside: true,
                ajax: {
                    settings: {
                        method: 'POST',
                        url: 'process/get.section.settings.php'
                    }
                },
                callbacks: {
                    elementParse: function(item) {
                        postData = {
                            act: 'addCreamFeed',
                        }
                        this.st.ajax.settings.data = postData;
                    }
                }
            });
            $('#buttonAddExternalFeed').magnificPopup({
                type: 'ajax',
                closeBtnInside: true,
                ajax: {
                    settings: {
                        method: 'POST',
                        url: 'process/get.section.settings.php'
                    }
                },
                callbacks: {
                    elementParse: function(item) {
                        postData = {
                            act: 'addExternalFeed',
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
                        url: 'process/get.section.settings.php'
                    }
                },
                callbacks: {
                    elementParse: function(item) {
                        postData = {
                            act: 'delFeed',
                            id: $(item.el[0]).data('id')
                        }
                        this.st.ajax.settings.data = postData;
                    }
                }
            });

            tinymce.remove();
            tinymce.init({
                init_instance_callback: 'insert_contents',
                selector: 'textarea#userSidePanelContent',
                width: 300,
                height: 600,
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

        });

        function insert_contents(inst) {
            inst.setContent('<?= preg_replace("/\r|\n/", "", $userSidePanelContent) ?>');
        }
    </script>
<?
}
?>