<?
// Cream: My Account

require_once '../inc/validate.logged.php';
require_once '../inc/php/db_config.php';

$act = '';
if (!empty($_POST)) $act = isset($_POST["act"]) ? $_POST["act"] : '';


// Check if already exists on update
if ($act == 'chkExist') {
      $chkEmail = isset($_POST['email']) ? $_POST['email'] : '';
      $sql = "SELECT id FROM user WHERE email='$chkEmail' AND id<>$gUserId";
      $result = mysqli_query($creamdb, $sql);
      $numRows = mysqli_num_rows($result);
      if ($numRows == 0) {
            echo 'OK';
      }
}


// Update Profile
if ($act == 'updateProfile') {
      $userName = isset($_POST['userName']) ? $_POST['userName'] : '';
      $userEmail = isset($_POST['userEmail']) ? $_POST['userEmail'] : '';
      $userCompany = isset($_POST['userCompany']) ? $_POST['userCompany'] : '';
      $userCategoryId = isset($_POST['userCategoryId']) ? $_POST['userCategoryId'] : 0;
      $userWebsite = isset($_POST['userWebsite']) ? $_POST['userWebsite'] : '';
      $userBio = isset($_POST['userBio']) ? $_POST['userBio'] : '';
      $userPincode = isset($_POST['userPincode']) ? $_POST['userPincode'] : '';

      // Basic phone number validation (digits only, 10-15 characters)
      $countryCode = isset($_POST['countryCode']) ? trim($_POST['countryCode']) : '';
      $localPhone  = isset($_POST['userPhone']) ? trim($_POST['userPhone']) : '';
      $localPhone  = ltrim(str_replace([' ', '-'], '', $localPhone), '0');

      // Validation
      if (!preg_match('/^\d{7,15}$/', $localPhone)) {
            echo "Invalid local phone number.";
            exit;
      }
      if (!preg_match('/^\+\d{1,4}$/', $countryCode)) {
            echo "Invalid country code.";
            exit;
      }

      // Store both separately
      // Save $countryCode to `country_code` column
      // Save $localPhone to `user_phone` column

      // Update session with new pincode
      $_SESSION['userPincode'] = $userPincode;

      // Update cookie if it exists
      if (isset($_COOKIE['knobly_user_data'])) {
            $userData = json_decode($_COOKIE['knobly_user_data'], true);
            if ($userData) {
                  $userData['userPincode'] = $userPincode;
                  setcookie('knobly_user_data', json_encode($userData), time() + (86400 * 365), '/');
            }
      }
      
      if ($userName != '' && $userEmail != '') {
            $sql = "UPDATE user 
            SET full_name = '$userName',
                email = '$userEmail',
                phone_no = '$localPhone',
                country_code = '$countryCode',
                company = '$userCompany',
                category_id = $userCategoryId,
                website = '$userWebsite',
                bio = '$userBio',
                pincode = '$userPincode',
                date_modified = NOW() 
            WHERE id = $gUserId";

            mysqli_query($creamdb, $sql);
            echo "OK";
      }
}



// Update Password
if ($act == 'updatePass') {
      $userPassCurrent = isset($_POST['userPassCurrent']) ? $_POST['userPassCurrent'] : '';
      $userPassNew = isset($_POST['userPassNew']) ? $_POST['userPassNew'] : '';
      if ($userPassCurrent != '' && $userPassNew != '') {
            $sql = "SELECT id FROM user WHERE id=$gUserId AND password='$userPassCurrent'";
            $result = mysqli_query($creamdb, $sql);
            $numRows = mysqli_num_rows($result);
            if ($numRows == 0) {
                  echo "IncorrectPassword";
                  die();
            }
            $sql = "UPDATE user SET password='$userPassNew',date_modified=Now() WHERE id=$gUserId";
            mysqli_query($creamdb, $sql);
            echo "OK";
      }
}


// Default
if ($act == '') {
      $sql = "SELECT * FROM user WHERE id=$gUserId";
      $result = mysqli_query($creamdb, $sql);
      $numRows = mysqli_num_rows($result);
      if ($numRows > 0) {
            $row = mysqli_fetch_assoc($result);
            $userName = $row['full_name'];
            $userCompany = $row['company'];
            $userEmail = $row['email'];
            $userPhone = $row['phone_no'];
            $userWebsite = $row['website'];
            $userCategoryId = $row['category_id'];
            $countryCode    = $row['country_code']; // ✅ Added this line

?>
            <ol class="breadcrumb my-3">
                  <li class="breadcrumb-item">
                        <h4 class="m-0">My Account</h4>
                  </li>
            </ol>
            <div class="row mb-4">
                  <div class="col">
                        <ul class="nav nav-tabs mb-4">
                              <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#profile" role="tab" onclick="$('#panelStatus').html('')">Profile</a></li>
                              <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#password" role="tab" onclick="$('#panelStatus').html('')">Password</a></li>
                        </ul>
                        <div class="tab-content m-3">
                              <div class="tab-pane fade show active" id="profile" role="tabpanel">
                                    <div class="form-group row">
                                          <label class="col-sm-2 col-form-label">Current Plan</label>
                                          <div class="col-sm-12 col-md-6 mt-2">
                                                <? if ($gUserPlan == 1) { ?>
                                                      Pro
                                                <? } else { ?>
                                                      Free <!-- <button id="btnGoPro" class="ml-2 btn btn-outline-secondary">Upgrade to Pro Plan</button> -->
                                                      <div id="zf-widget-root-id"></div>
                                                <? } ?>
                                          </div>
                                    </div>
                                    <?
                                    if ($gUserSubdomain <> '') {
                                          $landingPageURL = "https://$gUserSubdomain.newsjunction.net/";
                                    } else {
                                          $landingPageURL = "https://www.newsjunction.net/$gUserId/showcase";
                                    }
                                    ?>
                                    <div class="form-group row">
                                          <label class="col-sm-2 col-form-label">Showcase Page</label>
                                          <div class="col-sm-12 col-md-6 mt-2"><a href="<?= $landingPageURL ?>" target="_blank"><?= $landingPageURL ?></a></div>
                                    </div>
                                    <div class="form-group row">
                                          <label for="inputPassword" class="col-sm-2 col-form-label">Full Name</label>
                                          <div class="col-sm-12 col-md-6">
                                                <input type="text" class="form-control" id="userName" name="userName" value="<?= $userName ?>" maxlength="100" />
                                          </div>
                                    </div>
                                    <div class="form-group row">
                                          <label for="userEmail" class="col-sm-2 col-form-label">Email</label>
                                          <div class="col-sm-12 col-md-6">
                                                <input type="email" class="form-control" id="userEmail" name="userEmail" value="<?= $userEmail ?>" maxlength="100" />
                                          </div>
                                    </div>
                                    <div class="form-group row">
                                          <label for="userPhone" class="col-sm-2 col-form-label">Phone Number</label>

                                          <!-- Country Code Dropdown -->
                                          <div class="col-sm-2">
                                                <select class="form-control" id="countryCode" name="countryCode" required>
                                                      <option value="+1" <?= ($countryCode == '+1') ? 'selected' : '' ?>>🇺🇸 +1 (USA)</option>
                                                      <option value="+44" <?= ($countryCode == '+44') ? 'selected' : '' ?>>🇬🇧 +44 (UK)</option>
                                                      <option value="+91" <?= ($countryCode == '+91') ? 'selected' : '' ?>>🇮🇳 +91 (India)</option>
                                                      <option value="+61" <?= ($countryCode == '+61') ? 'selected' : '' ?>>🇦🇺 +61 (Australia)</option>
                                                      <option value="+81" <?= ($countryCode == '+81') ? 'selected' : '' ?>>🇯🇵 +81 (Japan)</option>
                                                      <option value="+49" <?= ($countryCode == '+49') ? 'selected' : '' ?>>🇩🇪 +49 (Germany)</option>
                                                      <option value="+33" <?= ($countryCode == '+33') ? 'selected' : '' ?>>🇫🇷 +33 (France)</option>
                                                      <option value="+971" <?= ($countryCode == '+971') ? 'selected' : '' ?>>🇦🇪 +971 (UAE)</option>
                                                      <option value="+63" <?= ($countryCode == '+63') ? 'selected' : '' ?>>🇵🇭 +63 (Philippines)</option>
                                                      <option value="+234" <?= ($countryCode == '+234') ? 'selected' : '' ?>>🇳🇬 +234 (Nigeria)</option>
                                                      <!-- Add more as needed -->
                                                </select>
                                          </div>

                                          <!-- Phone Number Input -->
                                          <div class="col-sm-10 col-md-4">
                                                <input type="tel" class="form-control" id="userPhone" name="userPhone"
                                                      value="<?= $userPhone ?>" maxlength="15" required>
                                          </div>
                                    </div>

                                    <div class="form-group row">
                                          <label for="userCompany" class="col-sm-2 col-form-label">Company</label>
                                          <div class="col-sm-12 col-md-6">
                                                <input type="text" class="form-control" id="userCompany" name="userCompany" value="<?= $userCompany ?>" maxlength="100" />
                                          </div>
                                    </div>
                                    <div class="form-group row">
                                          <label for="userCategoryId" class="col-sm-2 col-form-label">Business Type</label>
                                          <div class="col-sm-12 col-md-6">
                                                <select id="userCategoryId" name="userCategoryId" class="form-control">
                                                      <option value="">--[select]--
                                                            <?
                                                            $sql = "SELECT id,category FROM category ORDER BY category";
                                                            $result = mysqli_query($creamdb, $sql);
                                                            $numRows = mysqli_num_rows($result);
                                                            if ($numRows > 0) {
                                                                  for ($i = 0; $i < $numRows; $i++) {
                                                                        $row = mysqli_fetch_array($result);
                                                                        $tmpCategoryId = $row['id'];
                                                                        $tmpCategoryName = $row['category'];
                                                                        if ($userCategoryId == $tmpCategoryId) {
                                                                              echo '   <option value="' . $tmpCategoryId . '" SELECTED>' . $tmpCategoryName;
                                                                        } else {
                                                                              echo '   <option value="' . $tmpCategoryId . '">' . $tmpCategoryName;
                                                                        }
                                                                  }
                                                            }
                                                            ?>
                                                      <option value="0">Others
                                                </select>
                                          </div>
                                    </div>
                                    <div class="form-group row">
                                          <label for="userWebsite" class="col-sm-2 col-form-label">Website</label>
                                          <div class="col-sm-12 col-md-6">
                                                <input type="text" class="form-control" id="userWebsite" name="userWebsite" value="<?= $userWebsite ?>" maxlength="100" />
                                                <small class="form-text text-muted">Please enter the URL with http:// or https://</small>
                                          </div>
                                    </div>
                                    <div class="form-group row mt-4">
                                          <label class="col-sm-2 col-form-label"></label>
                                          <div class="col-sm-12 col-md-6">
                                                <button class="btn btn-primary" onclick="return chkUpdateProfile()">Update Profile</button>
                                          </div>
                                    </div>
                              </div>
                              <div class="tab-pane fade" id="password" role="tabpanel">
                                    <div class="form-group row">
                                          <label for="userPassCurrent" class="col-sm-2 col-form-label">Current password</label>
                                          <div class="col-sm-12 col-md-3">
                                                <input type="password" class="form-control" id="userPassCurrent" name="userPassCurrent" maxlength="20" />
                                          </div>
                                    </div>
                                    <div class="form-group row">
                                          <label for="userPassNew1" class="col-sm-2 col-form-label">New password</label>
                                          <div class="col-sm-12 col-md-3">
                                                <input type="password" class="form-control" id="userPassNew1" name="userPassNew1" maxlength="20" />
                                          </div>
                                    </div>
                                    <div class="form-group row">
                                          <label for="userPassNew2" class="col-sm-2 col-form-label">Retype Password</label>
                                          <div class="col-sm-12 col-md-3">
                                                <input type="password" class="form-control" id="userPassNew2" name="userPassNew2" maxlength="20" />
                                          </div>
                                    </div>
                                    <div class="form-group row mt-4">
                                          <label class="col-sm-2 col-form-label"></label>
                                          <div class="col-sm-12 col-md-3">
                                                <button class="btn btn-primary" onclick="return chkUpdatePassword()">Update Password</button>
                                          </div>
                                    </div>
                              </div>
                              <div id="panelStatus" class="mt-4"></div>
                        </div>
                  </div>
            </div>
            <script type="text/javascript">
                  $(function() {
                        $('#btnGoPro').magnificPopup({
                              type: 'ajax',
                              closeBtnInside: true,
                              ajax: {
                                    settings: {
                                          method: 'POST',
                                          url: 'process/get.section.general.php'
                                    }
                              },
                              callbacks: {
                                    elementParse: function(item) {
                                          postData = {
                                                act: 'showGoPro'
                                          }
                                          this.st.ajax.settings.data = postData;
                                    }
                              }
                        });
                        var pricingTableComponentOptions = {
                              id: 'zf-widget-root-id',
                              product_id: '2-a22c0be31501979fdf6d95705ea43113bde88338aa57ecb5f61153ce5d3090c3f48d7e9af6c8ae9e58bcad6fd428a6fde667b7eb5ddc5b2a318fde6c950d2d25',
                              template: 'combo_pro',
                              most_popular_plan: '',
                              is_group_by_frequency: true,
                              group_options: [{
                                    frequency: 'Monthly',
                                    frequency_recurrence_value: '1_months',
                                    most_popular_plan: '',
                                    plans: [{
                                          plan_code: 'M'
                                    }, ]
                              }, {
                                    frequency: 'Yearly',
                                    frequency_recurrence_value: '1_years',
                                    most_popular_plan: '',
                                    plans: [{
                                          plan_code: 'A'
                                    }, ]
                              }, ],
                              plans: [],
                              theme: {
                                    color: '#f58020',
                                    theme_color_light: ''
                              },
                              button_text: 'Upgrade to Pro Plan',
                              product_url: 'https://subscriptions.zoho.com',
                              price_caption: '',
                              language_code: 'en',
                              open_inSameTab: false
                        };
                        ZFWidget.init('zf-pricing-table', pricingTableComponentOptions);
                  });
            </script>
<?
      }
}
?>