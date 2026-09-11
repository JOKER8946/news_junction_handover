<?
// Cream: Cream Curated

require_once '../inc/validate.logged.php';
require_once '../inc/config.php';
require_once '../inc/simplepie/autoloader.php';

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
               <button class="btn btn-outline-secondary mic-button broadcast-pause" style="display:none;"><i class="fas fa-pause"></i></button>
               <button class="btn btn-outline-secondary mic-button broadcast-resume" style="display:none;"><i class="fas fa-play"></i></button>
               <button class="btn btn-outline-secondary mic-button broadcast-stop" style="display:none;"><i class="fas fa-stop"></i></button>
               <button class="btn btn-outline-secondary mic-button broadcast-play " title="Cream Broadcast" onclick="broadcast('<? include '../texttovoice/broadcast.php' ?>')">Cream Broadcast <i class="fas fa-podcast"></i></button>


          </li>
     </ol>
     <div class="panelFeeds pr-2">
          <?
          foreach ($feed->get_items() as $item) {
               $isExist = 0;
               $feedTitle = $item->get_title();
               $feedURL = $item->get_permalink();
               $feedPublisher = substr($feedURL, strpos($feedURL, ".") + 1);
               $feedPublisher = ucfirst(strtok($feedPublisher, '.'));
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
                                        </div>





                                        <label class="btn-secondary m-0 px-2 py-1 mr-2" style="user-select:none"><input type="checkbox" name="feedData" onclick="addCollection()"> Add to My Collection</label>
                                   <? } else { ?>
                                        <label class="badge-warning m-0 px-3 py-1 mr-2" style="user-select:none">My Collection</label>
                                   <? } ?>
                                   <label class="btn-success m-0 px-2 py-1" id="button1" onclick="CopyToClipboard('<?= $feedTitle ?>')">Share</label>

                                   <!-- <a data-sbg-1="" data-sbg-2="" data-sbg-3="" class="sbg-button" data-sbg-network="facebook" data-sbg-width="600" data-sbg-height="368"><i class="fab fa-facebook-square fa-2x cursorH" style="position:relative;top:5px;color:#f58020;"></i></a>
     <a data-sbg-1="" data-sbg-2="" data-sbg-3="" class="sbg-button" data-sbg-network="twitter" data-sbg-width="600" data-sbg-height="258"><i class="fab fa-twitter-square fa-2x cursorH" style="position:relative;top:5px;color:#f58020;"></i></a>
     <a data-sbg-1="" data-sbg-2="" data-sbg-3="" class="sbg-button" data-sbg-network="linkedin" data-sbg-width="585" data-sbg-height="471"><i class="fab fa-linkedin fa-2x cursorH" style="position:relative;top:5px;color:#f58020;"></i></a>
     <a data-sbg-1="" data-sbg-2="" data-sbg-3="" class="sbg-button" data-sbg-network="pinterest" data-sbg-width="600" data-sbg-height="368"><i class="fab fa-pinterest-square fa-2x cursorH" style="position:relative;top:5px;color:#f58020;"></i></a>
     <a href="http://web.whatsapp.com/send?text=<?= $feedTitle ?> <?= $feedURL ?>" target="_blank"><i class="fab fa-2x fa-whatsapp-square" style="position:relative;top:5px;color:#f58020;"></i></a> -->
                              </div>
                         </div>
                    </div>
               </div>
          <? } ?>
     </div>
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


          function broadcast(link) {

               if (isPlaying) {
                    console.log("Audio is already playing. Cannot start another instance.");
                    return;
               }

               console.log("Starting audio playback...");

               isPlaying = true; // Set flag to true to indicate audio playback has started

               var braudio = new Audio();
               console.log(link);
               braudio.src = link;
               braudio.play();

               $('.broadcast-pause').show();
               $('.broadcast-stop').show();

               $('.broadcast-pause').on('click', function(){
                    braudio.pause();
                    $('.broadcast-pause').hide();
                    $('.broadcast-resume').show();
               });

               $('.broadcast-resume').on('click', function(){
                    braudio.play();
                    $('.broadcast-resume').hide();
                    $('.broadcast-pause').show();
               });

               $('.broadcast-stop').on('click', function(){
                    braudio.pause();
                    braudio.currentTime = 0;
                    $('.broadcast-pause').hide();
                    $('.broadcast-resume').hide();
                    $('.broadcast-stop').hide();
                    isPlaying = false;
                    
               });


               braudio.onended = function() {
                    isPlaying = false;
                    $('.broadcast-pause').hide();
                    $('.broadcast-resume').hide();
                    $('.broadcast-stop').hide();

               };
          }
     </script>








     <style>
          .mic-button {
               transition: transform 0.3s ease;
               /* Example transition */
          }

          .mic-button:hover {
               transform: scale(1.1);
               /* Example transform on hover */
          }
     </style>
<?
}
?>