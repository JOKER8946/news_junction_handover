<?
// Cream: Newsletter

require_once '../inc/validate.logged.php';
require_once '../inc/config.php';

$act = '';
if (!empty($_POST)) $act = isset($_POST["act"]) ? $_POST["act"] : '';


// Send Newsletter
if ($act == 'sendNewsletter') {
      $newsId = isset($_POST['newsId']) ? $_POST['newsId'] : '';
      $sendGridSenderId = isset($_POST['sendGridSenderId']) ? $_POST['sendGridSenderId'] : '';
      $sendGridListId = isset($_POST['sendGridListId']) ? $_POST['sendGridListId'] : '';
      $sendGridSubject = isset($_POST['sendGridSubject']) ? $_POST['sendGridSubject'] : '';
      if ($newsId != '' && $sendGridSenderId != '' && $sendGridListId != '' && $sendGridSubject != '') {
            $newsletterBody = buildNewsletter($newsId);
            $newsletterBody = str_replace('"', '\"', $newsletterBody);
            $newsletterBody = str_replace(array("\r\n", "\n\r", "\n", "\r"), "", $newsletterBody);

            $sql = "SELECT send_grid_key,send_grid_suppression_id FROM user WHERE id=$gUserId";
            $result = mysqli_query($db, $sql);
            $row = mysqli_fetch_assoc($result);
            $userSendGridKey = $row['send_grid_key'];
            $userSendGridSuppressionId = $row['send_grid_suppression_id'];
            $curl = curl_init();
            curl_setopt_array($curl, array(
                  CURLOPT_URL => "https://api.sendgrid.com/v3/marketing/singlesends",
                  CURLOPT_SSL_VERIFYPEER => false,
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_CUSTOMREQUEST => "POST",
                  CURLOPT_POSTFIELDS => "{\"name\":\"Sent from News Junction\",\"send_to\":{\"list_ids\":[\"$sendGridListId\"]},\"email_config\":{\"sender_id\":$sendGridSenderId,\"suppression_group_id\":$userSendGridSuppressionId,\"subject\":\"$sendGridSubject\",\"html_content\":\"$newsletterBody\"}}",
                  CURLOPT_HTTPHEADER => array(
                        "authorization: Bearer $userSendGridKey",
                        "content-type: application/json"
                  ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            if ($err) {
            } else {
                  $arrResponse = json_decode($response, true);
                  if (array_key_exists("id", $arrResponse)) {
                        $sendId = $arrResponse['id'];
                        curl_setopt_array($curl, array(
                              CURLOPT_URL => "https://api.sendgrid.com/v3/marketing/singlesends/$sendId/schedule",
                              CURLOPT_RETURNTRANSFER => true,
                              CURLOPT_CUSTOMREQUEST => "PUT",
                              CURLOPT_POSTFIELDS => "{\"send_at\":\"now\"}",
                              CURLOPT_HTTPHEADER => array(
                                    "authorization: Bearer $userSendGridKey",
                                    "content-type: application/json"
                              ),
                        ));
                        $response = curl_exec($curl);
                        $sql = "INSERT INTO user_blast(user_id,newsletter_id,blast_provider,blast_subject,blast_id,blast_key,date_created) VALUES($gUserId,$newsId,'SendGrid','$sendGridSubject','$sendId','$userSendGridKey',Now())";
                        mysqli_query($db, $sql);
                        echo 'OK';
                  }
            }
            curl_close($curl);
      }
}


// Delete Newsletter
if ($act == 'deleteNewsletter') {
      $newsletterId = isset($_POST['delId']) ? $_POST['delId'] : '';
      if ($newsletterId != '') {
            $sql = "DELETE FROM user_newsletter WHERE id=$newsletterId AND user_id=$gUserId";
            mysqli_query($db, $sql);
            echo "OK";
      }
}


// Show Delete Newsletter
if ($act == 'delNewsletter') {
      $newsletterId = isset($_POST['id']) ? $_POST['id'] : '';
?>
      <div class="popup" style="width:420px">
            <div class="widget">
                  <form id="frmDel" name="frmDel">
                        <div class="card">
                              <div class="card-header bg-dark">
                                    <h5 class="mb-0 text-light">Delete Newsletter</h5>
                              </div>
                        </div>
                        <div id="widget_B" style="padding:15px 25px">
                              You are about to delete this Newsletter from your account!
                        </div>
                        <div id="widget_F" style="border-top:1px solid #ebedf2;padding:20px 10px;">
                              <div class="col">
                                    <button type="button" class="btn btn-primary" onclick="return chkDelNewsletter()">Confirm</button>
                              </div>
                        </div>
                        <input type="hidden" id="delId" name="delId" value="<?= $newsletterId ?>" />
                        <input type="hidden" id="act" name="act" value="deleteNewsletter" />
                  </form>
            </div>
      </div>
<?
}


// Default
if ($act == '') {

      if ($gUserPlan == 1) {
            $htmlSendGridListId = '';
            $htmlSendGridSenderId = '';
            $sql = "SELECT send_grid_key FROM user WHERE id=$gUserId";
            $result = mysqli_query($db, $sql);
            $row = mysqli_fetch_assoc($result);
            $userSendGridKey = $row['send_grid_key'];

            if ($userSendGridKey <> '') {
                  $curl = curl_init();
                  curl_setopt_array($curl, array(
                        CURLOPT_URL => "https://api.sendgrid.com/v3/marketing/lists?page_size=50",
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_CUSTOMREQUEST => "GET",
                        CURLOPT_POSTFIELDS => "{}",
                        CURLOPT_HTTPHEADER => array(
                              "authorization: Bearer $userSendGridKey",
                              "content-type: application/json"
                        ),
                  ));
                  $response = curl_exec($curl);
                  $err = curl_error($curl);
                  if ($err) {
                        echo "cURL Error #:" . $err;
                  } else {
                        $arrResponse = json_decode($response, true);
                        if (array_key_exists("result", $arrResponse)) {
                              $arrList = $arrResponse['result'];
                              foreach ($arrList as &$value) {
                                    $htmlSendGridListId .= "<option value='" . $value['id'] . "'>" . $value['name'] . "</option>";
                              }
                              curl_setopt_array($curl, array(
                                    CURLOPT_URL => "https://api.sendgrid.com/v3/verified_senders",
                                    CURLOPT_SSL_VERIFYPEER => false,
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_CUSTOMREQUEST => "GET",
                                    CURLOPT_POSTFIELDS => "{}",
                                    CURLOPT_HTTPHEADER => array(
                                          "authorization: Bearer $userSendGridKey",
                                          "content-type: application/json"
                                    ),
                              ));
                              $response = curl_exec($curl);
                              $arrResponse = json_decode($response, true);
                              if (array_key_exists("results", $arrResponse)) {
                                    $arrList = $arrResponse['results'];
                                    foreach ($arrList as &$value) {
                                          $htmlSendGridSenderId .= "<option value='" . $value['id'] . "'>" . $value['nickname'] . "</option>";
                                    }
                              }
                        }
                  }
            }
      }
?>



      <ol class="breadcrumb my-3">
            <li class="breadcrumb-item">
                  <h4 class="m-0">Newsletter</h4>
            </li>
      </ol>
      <?
      $sql = "SELECT id,date_created FROM user_newsletter WHERE user_id=$gUserId ORDER BY date_created DESC";
      $result = mysqli_query($db, $sql);
      $numRows = mysqli_num_rows($result);
      if ($numRows == 0) {
      ?>
            <div class="px-3">You have not created any newsletters!</div>
      <?
      } else {
      ?>
            <div class="row mb-4">
                  <div class="col">
                        <div class="accordion" id="accordionNewletter">
                              <?
                              $numNewsletter = 1;
                              while ($row = mysqli_fetch_assoc($result)) {
                                    $newsId = $row['id'];

                                    $newsDate = date('M d, Y', strtotime($row['date_created']));
                              ?>

                                    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
                                    <div class="card">
                                          <div class="card-header m-0 p-0 py-1" id="heading<?= $numNewsletter ?>">
                                                <div class="float-left"><a class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapse<?= $numNewsletter ?>" aria-expanded="true" aria-controls="collapse<?= $numNewsletter ?>"><?= $newsDate ?></a></div>
                                                <div class="float-right pt-2 pr-3">
                                                      <? if ($gUserSubdomain <> '') { ?>
                                                            <a href="https://<?= $gUserSubdomain ?>.newsjunction.net/newsletter.php?id=<?= $newsId ?>" title="Publish Newsletter" target="_blank"><i class="fas fa-upload 1fa-sm text-muted pr-2"></i></a>
                                                      <? } else { ?>

                                                            <a href="newsletter.php?id=<?= $newsId ?>" title="Publish Newsletter" target="_blank"><i class="fas fa-upload 1fa-sm text-muted pr-2"></i></a>
                                                      <? } ?>
                                                      <a href="#" title="Delete Newsletter"><i data-id="<?= $newsId ?>" class="delRow far fa-trash-alt 1fa-sm text-muted"></i></a>
                                                </div>
                                          </div>
                                          <div id="collapse<?= $numNewsletter ?>" class="collapse<? if ($numNewsletter == 1) { ?> show<? } ?>" aria-labelledby="heading<?= $numNewsletter ?>" data-parent="#accordionNewletter">
                                                <div id="panelNewsletter<?= $numNewsletter ?>" class="card-body p-0" align="center">
                                                      <?= buildNewsletter($newsId) ?>
                                                      <!-- <button class="btn btn-primary" onclick="fetchNewsletterContent($('#panelNewsletter<?= $numNewsletter ?> table').html())">Send Email</button> -->

                                                      <button class="btn btn-primary" onclick="fetchNewsletterContent($('#panelNewsletter<?= $numNewsletter ?> table')[0].outerHTML)">Send Email</button>
                                                     

                                                      <?
                                                      if ($gUserPlan == 0) {
                                                            echo '<div class="alert alert-success" role="alert">Sending of Newsletter feature is only available in <b>Pro</b> plan! Go to My Account to upgrade.</div>';
                                                      } else {
                                                            if ($htmlSendGridListId <> '') {
                                                      ?>
                                                                  <table cellpadding="5">
                                                                        <tr>
                                                                              <td>Send via SendGrid:</td>
                                                                              <td>
                                                                                    <select id="sendGridSenderId_<?= $newsId ?>" name="sendGridSenderId_<?= $newsId ?>" class="form-control">
                                                                                          <option value="">--[select sender]--
                                                                                                <?= $htmlSendGridSenderId ?>
                                                                                    </select>
                                                                              </td>
                                                                              <td>
                                                                                    <select id="sendGridListId_<?= $newsId ?>" name="sendGridListId_<?= $newsId ?>" class="form-control">
                                                                                          <option value="">--[select list]--
                                                                                                <?= $htmlSendGridListId ?>
                                                                                    </select>
                                                                              </td>
                                                                              <td><input type="text" id="sendGridSubject_<?= $newsId ?>" name="sendGridSubject_<?= $newsId ?>" class="form-control" placeholder="Enter Subject" /></td>
                                                                              <td><button type="button" class="btn btn-primary" onclick="chkSendNewsletter(<?= $newsId ?>)">Send</button></td>
                                                                        </tr>
                                                                  </table>
                                                                  <br>
                                                      <?
                                                            }
                                                      }
                                                      ?>
                                                </div>
                                          </div>
                                    </div>
                              <?
                                    $numNewsletter += 1;
                              }
                              ?>
                        </div>
                  </div>
            </div>
            <script type="text/javascript">
                  $(function() {
                        $('.delRow').magnificPopup({
                              type: 'ajax',
                              closeBtnInside: true,
                              ajax: {
                                    settings: {
                                          method: 'POST',
                                          url: 'process/get.section.newsletter.php'
                                    }
                              },
                              callbacks: {
                                    elementParse: function(item) {
                                          postData = {
                                                act: 'delNewsletter',
                                                id: $(item.el[0]).data('id')
                                          }
                                          this.st.ajax.settings.data = postData;
                                    }
                              }
                        });
                  });
            </script>

            <script>
                  // Function to fetch newsletter content and update index.php
                  function fetchNewsletterContent($content) {
                        var newsContent = $content.trim();

                        console.log(newsContent);

                        try {
                              // Open index.php in a new tab
                              var indexPage = window.open('aws-ses/index.php', '_blank');
                              indexPage.onload = function() {
                                    // Populate emailBody textarea with fetched content
                                    indexPage.document.getElementById('emailBody').value = newsContent;
                                    indexPage.document.getElementById('preview').innerHTML = newsContent;

                              }
                        } catch {
                              console.error('Error fetching newsletter content:', error);
                        }




                        // fetch('get.section.newsletter.php')
                        //       .then(response => response.text())
                        //       .then(data => {
                        //             // Open index.php in a new tab
                        //             var indexPage = window.open('/aws-ses/index.php', '_blank');
                        //             indexPage.onload = function() {
                        //                   // Populate emailBody textarea with fetched content
                        //                   indexPage.document.getElementById('emailBody').value = data;
                        //             };
                        //       })
                        //       .catch(error => console.error('Error fetching newsletter content:', error));
                  }
            </script>
<?
      }
}
?>