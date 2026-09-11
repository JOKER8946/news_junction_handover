<?
// Cream: Cream Curated

require_once '../inc/validate.logged.php';
require_once '../inc/config.php';
require_once '../inc/simplepie/autoloader.php';


function getBaseDomainName($url)
{
     // Parse the URL to get its components
     $parsedUrl = parse_url($url);

     // Check if the 'host' component is present
     if (isset($parsedUrl['host'])) {
          // Extract the host part
          $host = $parsedUrl['host'];

          // Split the host into parts
          $parts = explode('.', $host);

          // Check the number of parts
          if (count($parts) >= 2) {
               // If the domain has at least a second-level domain, return the capitalized second-to-last part
               $baseDomain = $parts[count($parts) - 2];
          } else {
               // If there's no second-level domain, return the entire host
               $baseDomain = $host;
          }

          // Capitalize the first letter of the base domain
          return ucfirst($baseDomain);
     } else {
          // Return an error message if 'host' is not present in the parsed URL
          return null;
     }
}


$act = '';
if (!empty($_POST)) $act = isset($_POST["act"]) ? $_POST["act"] : '';


// Add to Collection
if ($act == 'addCollection') {
     $data = isset($_POST['data']) ? $_POST['data'] : '';
     if ($data != '') {
          $arrData = json_decode($data, true);
          foreach ($arrData as &$value) {
               $feedTitle = $value['title'];
               $feedURL = $value['url'];
               $feedDesc = $value['desc'];
               $feedPublisher = $value['publisher'];
               $sql = "SELECT url FROM user_collection WHERE url='$feedURL' AND user_id=$gUserId";
               $result = mysqli_query($db, $sql);
               $numRows = mysqli_num_rows($result);
               if ($numRows == 0) {
                    $feedTitle = mysqli_real_escape_string($db, $feedTitle);
                    $feedDesc = mysqli_real_escape_string($db, $feedDesc);
                    $feedPublisher = mysqli_real_escape_string($db, $feedPublisher);
                    $sql = "INSERT INTO user_collection(user_id,title,url,description,date_added) VALUES($gUserId,'$feedTitle','$feedURL','$feedDesc',Now())";
                    mysqli_query($db, $sql);
               }
          }
          echo "OK";
     }
}


// Default
if ($act == '') {
     $sql = "SELECT feed_url FROM feeds_cream";
     $result = mysqli_query($db, $sql);
     while ($row = mysqli_fetch_assoc($result)) {
          $array[] = $row['feed_url'];
     }
     $feed = new SimplePie();
     /*
 $feed->set_curl_options(
  array(
   CURLOPT_SSL_VERIFYHOST => false,
   CURLOPT_SSL_VERIFYPEER => false
  )
 );
*/
     $feed->set_feed_url($array);
     $feed->set_cache_location('../inc/simplepie/library/SimplePie/Cache');
     $feed->init();
     $feed->handle_content_type();
     if ($feed->error) {
          //print_r($feed->error);
          //die();
     }
?>
     <ol class="breadcrumb my-3">
          <li class="breadcrumb-item w-100" style="display:flex; justify-content:space-between;">
               <div class="text-left ">
                    <h4 class="mt-1">Cream Curated Feeds</h4>
               </div>
               <div class="text-right "><button type="button" id="buttonAddCollection" class="hide btn btn-success" onclick="chkAddCollection()">Add selected to My Collection</button></div>
               <div class="all-btns" style="display:flex; justify-content:right;">
                    <button class="btn btn-outline-secondary mic-button broadcast-pause" style="display:none;"><i class="fas fa-pause"></i></button>
                    <button class="btn btn-outline-secondary mic-button broadcast-resume" style="display:none;"><i class="fas fa-play"></i></button>
                    <button class="btn btn-outline-secondary mic-button broadcast-stop" style="display:none;"><i class="fas fa-stop"></i></button>
                    <button class="btn btn-outline-secondary mic-button broadcast-play " title="Cream Broadcast" onclick="broadcast()">Cream Broadcast <i class="fas fa-podcast"></i></button>
               </div>


          </li>
     </ol>
     <div class="panelFeeds pr-2">
          <?
          foreach ($feed->get_items() as $item) {
               $isExist = 0;
               $feedTitle = $item->get_title();
               $feedURL = $item->get_permalink();
               // $feedPublisher = substr($feedURL, strpos($feedURL, ".") + 1);
               // $feedPublisher = ucfirst(strtok($feedPublisher, '.'));
               $feedPublisher = getBaseDomainName($feedURL);
               $feedDesc = $item->get_description();
               $feedDate = $item->get_date('j F Y | g:i a');
               $sql = "SELECT url FROM user_collection WHERE url='$feedURL' AND user_id=$gUserId";
               $result = mysqli_query($db, $sql);
               $numRows = mysqli_num_rows($result);
               if ($numRows > 0) $isExist = 1;
          ?>
               <div class="card p-0 mb-3 border-0" style="background-color:#f5ede7">
                    <div class="panelFeed card-body">
                         <h4><a id="<?= $feedTitle ?>" href="<?= $feedURL ?>" target="_blank" style="color:#f26522"><?= $feedTitle ?><br><span style="position: absolute; top: -9999px; left: -9999px"><?= $feedURL ?></span></a></h4>
                         <p style="color:#7d7d7d"><?= $feedDesc ?></p>
                         <div class="row">
                              <div class="col-12 col-md-6 text-center text-md-left"><?= $feedPublisher ?> &bull; <?= $feedDate ?></div>
                              <div class="data col-12 col-md-6 text-center text-md-right mt-2 m-md-0" style="display:flex; justify-content:end" data-feed-title="<?= $feedTitle ?>" data-feed-url="<?= $feedURL ?>" data-feed-publisher="<?= $feedPublisher ?>" data-feed-desc="<?= htmlentities($feedDesc) ?>">
                                   <? if ($isExist == 0) { ?>
                                        <div style="margin-right:8px">
                                             <button class="btn btn-outline-secondary mic-button play-button" data-title="<?= urlencode($feedTitle ?? " ") ?>" data-description="<?= urlencode($feedDesc ?? " ") ?>">
                                                  <i class="fas fa-volume-up"></i>
                                             </button>
                                             <button class="btn btn-outline-secondary mic-button pause-button" style="display:none;">
                                                  <i class="fas fa-pause"></i>
                                             </button>
                                             <button class="btn btn-outline-secondary mic-button resume-button" style="display:none;">
                                                  <i class="fas fa-play"></i>
                                             </button>
                                             <button class="btn btn-outline-secondary mic-button stop-button" style="display:none;">
                                                  <i class="fas fa-stop"></i>
                                             </button>
                                             <button class="btn btn-outline-secondary mic-button" id="shareOnFacebook"
                                                  onclick='shareOnFB("<?= isset($feedTitle) ? htmlspecialchars($feedTitle, ENT_QUOTES) : "" ?>", "<?= isset($feedURL) ? htmlspecialchars($feedURL, ENT_QUOTES) : "" ?>")'>
                                                  <i class="fab fa-facebook-f"></i>
                                             </button>



                                             <button class="btn btn-outline-secondary mic-button" id="shareOnLinkedin"
                                                  onclick='shareOnLI("<?= isset($feedTitle) ? htmlspecialchars($feedTitle, ENT_QUOTES) : "" ?>", "<?= isset($feedURL) ? htmlspecialchars($feedURL, ENT_QUOTES) : "" ?>", "<?= isset($feedDesc) ? htmlspecialchars($feedDesc, ENT_QUOTES) : "" ?>")'>
                                                  <i class="fab fa-linkedin-in"></i>
                                             </button>

                                        </div>
                                        <label class="btn-secondary m-0 px-2 py-2 mr-2" style="user-select:none"><input type="checkbox" name="feedData" onclick="addCollection()"> Add to My Collection</label>
                                   <? } else { ?>
                                        <label class="badge-warning m-0 px-3 py-2 mr-2" style="user-select:none">My Collection</label>
                                   <? } ?>
                                   <label class="btn-success m-0 px-2 py-2" id="button1" onclick="copyToClipboard('<?php echo htmlspecialchars($feedTitle, ENT_QUOTES, 'UTF-8'); ?>')">Share</label>
                              </div>
                         </div>
                    </div>
               </div>
          <? } ?>
     </div>

     <!-- Facebook Share Modal -->
     <? include '../facebook/function.php';
     $fbLoggedStatus = facebook_login_status($db, $gUserId); ?>
     <div class="modal fade" id="fbShare" tabindex="-1" role="dialog" aria-labelledby="facebookSharePageModal" aria-hidden="true">
          <div class="modal-dialog" role="document">
               <div class="modal-content">
                    <div class="modal-header">
                         <h5 class="modal-title" id="facebookSharePageModal">Share Through Facebook Pages</h5>
                         <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                              <span aria-hidden="true">&times;</span>
                         </button>
                    </div>

                    <div class="modal-body">
                         <? if ($fbLoggedStatus) { ?>
                              <div class="form-group">
                                   <label for="fbArticleTitle">Enter the text to be shared:</label>
                                   <textarea id="fbArticleTitle" class="form-control" rows="3" placeholder=""></textarea>
                              </div>

                              <input type="hidden" id="fbArticleLink" class="form-control" value="">

                              <? $pages_data = facebook_fetch_user_pages($db, $gUserId); ?>
                              <h5>Select Facebook Pages:</h5>
                              <div class="list-group">
                                   <?php foreach ($pages_data as $page_data) { ?>
                                        <div class="form-check">
                                             <input class="form-check-input" type="checkbox" value="<?= $page_data['pages_id'] ?>" id="fbPage-<?= $page_data['pages_id'] ?>">
                                             <label class="form-check-label" for="fbPage-<?= $page_data['pages_id'] ?>">
                                                  <?= $page_data['pages_name'] ?>
                                             </label>
                                        </div>
                                   <?php } ?>
                              </div>
                              <div id="fbScheduleDateTimeDiv" class="form-group" style="display: none;">
                                   <label for="fbScheduleDateTime">Schedule Date and Time:</label>
                                   <input type="datetime-local" class="form-control" id="fbScheduleDateTime">
                              </div>
                         <? } else { ?>
                              You have not logged in to Facebook to use this feature.
                         <? } ?>
                    </div>

                    <div class="modal-footer">
                         <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                         <? if ($fbLoggedStatus) { ?>
                              <button type="button" class="btn btn-primary" id="fbPostNow">Post Now</button>
                              <button type="button" class="btn btn-info" id="fbSetSchedule">Schedule Post</button>
                              <button type="button" class="btn btn-info" id="fbSchedulePost" style="display: none;">Schedule Now</button>
                         <? } else { ?>
                              <a href="facebook/facebook_setup.php"><button type="button" class="btn btn-primary">Setup Facebook</button></a>
                         <? } ?>
                    </div>
               </div>
          </div>
     </div>

     <!-- LinkedIn Share Modal -->
     <? $liLoggedStatus = true;
     include '../social_media/linkedin/linkedin_function.php';
     // linkedin_login_status($db, $gUserId); 
     ?>
     <div class="modal fade" id="liShare" tabindex="-1" role="dialog" aria-labelledby="linkedinSharePageModal" aria-hidden="true">
          <div class="modal-dialog" role="document">
               <div class="modal-content">
                    <div class="modal-header">
                         <h5 class="modal-title" id="linkedinSharePageModal">Share Through LinkedIn</h5>
                         <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                              <span aria-hidden="true">&times;</span>
                         </button>
                    </div>

                    <div class="modal-body">
                         <? if ($liLoggedStatus) { ?>
                              <div class="form-group">
                                   <label for="liArticleText">Enter the text to be shared:</label>
                                   <textarea id="liArticleText" class="form-control" rows="3" placeholder=""></textarea>
                              </div>

                              <input type="hidden" id="liArticleTitle" class="form-control" value="">
                              <input type="hidden" id="liArticleLink" class="form-control" value="">
                              <input type="hidden" id="liArticleDesc" class="form-control" value="">


                              <h5>Share to the LinkedIn Account:</h5>
                              <div class="list-group">
                                   <? // the LinkedIn account to be displayed 
                                   linkedin_send_profile($db, $gUserId)
                                   ?>
                              </div>
                              <!-- Optional scheduling section -->
                              <!-- <div id="liScheduleDateTimeDiv" class="form-group" style="display: none;">
                        <label for="liScheduleDateTime">Schedule Date and Time:</label>
                        <input type="datetime-local" class="form-control" id="liScheduleDateTime">
                    </div> -->
                         <? } else { ?>
                              You have not logged in to LinkedIn to use this feature.
                         <? } ?>
                    </div>

                    <div class="modal-footer">
                         <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                         <? if ($liLoggedStatus) { ?>
                              <button type="button" class="btn btn-primary" id="liPostNow">Post Now</button>
                              <!-- Optional scheduling buttons -->
                              <!-- <button type="button" class="btn btn-info" id="liSetSchedule">Schedule Post</button>
                    <button type="button" class="btn btn-info" id="liSchedulePost" style="display: none;">Schedule Now</button> -->
                         <? } else { ?>
                              <a href="social_media/linkedin/linkedin_setup.php"><button type="button" class="btn btn-primary">Setup LinkedIn</button></a>
                         <? } ?>
                    </div>
               </div>
          </div>
     </div>


     <style>
          .mic-button {
               transition: transform 0.3s ease;
               /* Example transition */
          }

          .mic-button:hover {
               transform: scale(1.1);
               /* Example transform on hover */
          }

          .all-btns button {
               margin: 0 4px;
          }
     </style>

     <script type="text/javascript">
          $(function() {
               generateSM();
          });
     </script>

     <script>
          var isPlaying = false;

          $(document).ready(function() {

               // Play button click handler
               $('.play-button').on('click', function() {
                    var title = decodeURIComponent($(this).data('title'));
                    var desc = decodeURIComponent($(this).data('description'));
                    playAudio(title, desc, $(this));
               });

               // Function to play audio
               function playAudio(title, desc, button) {
                    var audio = new Audio();

                    if (isPlaying) {
                         console.log("Audio is already playing. Cannot start another instance.");
                         return;
                    }

                    console.log("Starting audio playback...");

                    isPlaying = true; // Set flag to true to indicate audio playback has started

                    // Ajax call to get audio file URL
                    $.ajax({
                         url: '../texttovoice/processvoice.php',
                         type: 'POST',
                         data: {
                              title: title,
                              description: desc
                         },
                         success: function(response) {
                              audio.src = response;
                              audio.play();

                              // Update button visibility
                              button.hide();
                              button.siblings('.pause-button').show();
                              button.siblings('.stop-button').show();

                              // Pause button click handler
                              button.siblings('.pause-button').on('click', function() {
                                   audio.pause();
                                   $(this).hide();
                                   $(this).siblings('.resume-button').show();
                              });

                              // Resume button click handler
                              button.siblings('.resume-button').on('click', function() {
                                   audio.play();
                                   $(this).hide();
                                   $(this).siblings('.pause-button').show();
                              });

                              button.siblings('.stop-button').on('click', function() {
                                   audio.pause(); // Pause the audio (assuming this stops playback)
                                   audio.currentTime = 0; // Reset audio playback to the beginning
                                   button.siblings('.resume-button').hide(); // Hide resume button if shown
                                   button.siblings('.stop-button').hide();
                                   button.siblings('.pause-button').hide();
                                   button.show();
                                   isPlaying = false;
                              });

                              // Reset when audio ends
                              audio.onended = function() {
                                   isPlaying = false;
                                   button.show();
                                   button.siblings('.pause-button').hide();
                                   button.siblings('.resume-button').hide();
                                   button.siblings('.stop-button').hide();
                              };
                         },
                         error: function(xhr, status, error) {
                              console.error("Error playing audio:", error);
                         }
                    });
               }
          });

          function broadcast() {
               // Check if audio is already playing
               if (isPlaying) {
                    console.log("Audio is already playing. Cannot start another instance.");
                    return;
               }

               // Start audio playback
               console.log("Starting audio playback...");

               // AJAX request to get the audio file URL
               $.ajax({
                    url: '../texttovoice/broadcast.php', // PHP file to return audio file URL
                    type: 'GET', // or 'POST' if needed
                    success: function(response) {
                         // Trim any extra whitespace or newlines
                         var audioFileUrl = response.trim();
                         if (!audioFileUrl) {
                              console.error("Received empty or invalid audio file URL.");
                              return;
                         }

                         console.log(response);

                         // Create a new Audio instance and set its source
                         var braudio = new Audio();
                         braudio.src = audioFileUrl;

                         // Play the audio
                         braudio.play();

                         // Set flag to true to indicate audio playback has started
                         isPlaying = true;

                         // Show control buttons
                         $('.broadcast-pause').show();
                         $('.broadcast-stop').show();

                         // Event listener for pause button
                         $('.broadcast-pause').on('click', function() {
                              braudio.pause();
                              $('.broadcast-pause').hide();
                              $('.broadcast-resume').show();
                         });

                         // Event listener for resume button
                         $('.broadcast-resume').on('click', function() {
                              braudio.play();
                              $('.broadcast-resume').hide();
                              $('.broadcast-pause').show();
                         });

                         // Event listener for stop button
                         $('.broadcast-stop').on('click', function() {
                              braudio.pause();
                              braudio.currentTime = 0;
                              $('.broadcast-pause').hide();
                              $('.broadcast-resume').hide();
                              $('.broadcast-stop').hide();
                              isPlaying = false;
                         });

                         // Event listener for when the audio ends
                         braudio.onended = function() {
                              isPlaying = false;
                              $('.broadcast-pause').hide();
                              $('.broadcast-resume').hide();
                              $('.broadcast-stop').hide();
                         };
                    },
                    error: function(xhr, status, error) {
                         console.error('An error occurred while fetching the audio file URL:', status, error);
                    }
               });
          }
     </script>

     <script>
          $(document).ready(function() {
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
                    scheduleDateTime = new Date(scheduleDateTime).toISOString();
                    console.log(scheduleDateTime);
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
                                        alert(action === 'postNow' ? 'Post successful: ' : 'Scheduled successfully: ' + response.data);
                                   } else {
                                        alert('Post Unsuccessful..' + response.error_data);
                                   }
                                   $('#fbShare').modal('hide');
                              },
                              error: function(xhr, status, error) {
                                   alert('An error occurred: ' + error);
                              }
                         });
                    } else {
                         alert('Please select at least one page to ' + (action === 'postNow' ? 'post.' : 'schedule.'));
                    }
               }

               $('#shareOnFacebook').click(function() {
                    $('#fbShare').modal('show');
               });

               // When the modal is hidden, reset the scheduling elements
               $('#fbShare').on('hidden.bs.modal', function() {
                    $('#fbScheduleDateTimeDiv').hide(); // Hide the date/time div
                    $('#fbSchedulePost').hide(); // Hide the schedule post button
                    $('#fbSetSchedule').show(); // Show the set schedule button
                    $('#fbScheduleDateTime').val(''); // Reset the schedule date/time input
                    $('.form-check-input').prop('checked', false); // Uncheck all checkboxes
               });

               $('#fbSetSchedule').click(function() {
                    $('#fbScheduleDateTimeDiv').show();
                    $('#fbSchedulePost').show();
                    $('#fbSetSchedule').hide();
               });

               // Post Now button click
               $('#fbPostNow').click(function() {
                    fbhandlePost('postNow');
               });

               // Schedule Post button click
               $('#fbSchedulePost').click(function() {
                    fbhandlePost('schedulePost');
               });
          });

          function shareOnFB(title, link) {
               console.log(title);
               console.log(link);
               fbUpdateModalContent(title, link);
               $('#fbShare').modal('show');
          }

          // Function to update modal content with the article title and link
          function fbUpdateModalContent(title, link) {
               $('#fbArticleTitle').val(title); // Set article title
               $('#fbArticleLink').val(link); // Set article link
          }
     </script>

     <script>
          $(document).ready(function() {

               $('#liPostNow').click(function() {
                    liHandlePost();
               })

               function liHandlePost() {
                    var articleTitle = $('#liArticleTitle').val();
                    var articleLink = $('#liArticleLink').val();
                    var articleText = $('#liArticleText').val();
                    var articleDesc = $('#liArticleDesc').val();

                    var postData = {
                         title: articleTitle,
                         link: articleLink,
                         text: articleText,
                         desc: articleDesc
                    };

                    // AJAX call to the post handler
                    $.ajax({
                         url: 'social_media/linkedin/linkedin_post_handler.php', // Update this path to your PHP file
                         type: 'POST',
                         data: JSON.stringify(postData),
                         contentType: 'application/json',
                         success: function(response) {
                              if (response.status == 'success') {
                                   alert('Successfully Posted.');
                              } else {
                                   alert('Post Unsuccessful..' + response.error_data);
                              }
                              $('#liShare').modal('hide');
                         },
                         error: function(xhr, status, error) {
                              alert('An error occurred: ' + error);
                         }
                    });

               }

               // // When the modal is hidden, reset the scheduling elements
               // $('#liShare').on('hidden.bs.modal', function() {
               //      // $('#scheduleDateTimeDiv').hide(); // Hide the date/time div
               //      // $('#schedulePost').hide(); // Hide the schedule post button
               //      // $('#setSchedule').show(); // Show the set schedule button
               //      // $('#scheduleDateTime').val(''); // Reset the schedule date/time input
               //      $('.form-check-input').prop('checked', false); // Uncheck all checkboxes
               // });

               // function fbhandlePost(action) {
               //      var selectedPages = [];
               //      $('.form-check-input:checked').each(function() {
               //           selectedPages.push({
               //                id: $(this).val(),
               //                name: $(this).next('label').text().trim() // Trim whitespace
               //           });
               //      });

               //      var scheduleDateTime = $('#scheduleDateTime').val();
               //      var articleTitle = $('#articleTitle').val();
               //      var articleLink = $('#articleLink').val();

               //      if (selectedPages.length > 0) {
               //           if (action === 'schedulePost' && !scheduleDateTime) {
               //                alert('Please select a date and time to schedule the post.');
               //                return;
               //           }

               //           var postData = {
               //                action: action,
               //                pages: selectedPages,
               //                title: articleTitle,
               //                link: articleLink,
               //                schedule: action === 'schedulePost' ? scheduleDateTime : undefined
               //           };

               //           // AJAX call to the post handler
               //           $.ajax({
               //                url: 'facebook/fb_post_handler.php', // Update this path to your PHP file
               //                type: 'POST',
               //                data: JSON.stringify(postData),
               //                contentType: 'application/json',
               //                success: function(response) {
               //                     if (response.status == 'success') {
               //                          alert(action === 'postNow' ? 'Post successful: ' : 'Scheduled successfully: ' + response.data);
               //                     } else {
               //                          alert('Post Unsuccessful..' + response.error_data);
               //                     }
               //                     $('#fbShare').modal('hide');
               //                },
               //                error: function(xhr, status, error) {
               //                     alert('An error occurred: ' + error);
               //                }
               //           });
               //      } else {
               //           alert('Please select at least one page to ' + (action === 'postNow' ? 'post.' : 'schedule.'));
               //      }
               // }


          });

          function shareOnLI(title, link, desc) {
               liUpdateModalContent(title, link, desc);
               $('#liShare').modal('show');
          }

          // Function to update modal content with the article title and link
          function liUpdateModalContent(title, link, desc) {
               $('#liArticleTitle').val(title); // Set article title
               $('#liArticleLink').val(link); // Set article link
               $('#liArticleText').val(title);
               $('#liArticleDesc').val(desc);
          }
     </script>

<?
}
?>