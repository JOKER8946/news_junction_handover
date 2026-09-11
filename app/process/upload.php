<?

  session_start();

  if (isset($_SESSION['userId'])) {
   $gUserId = $_SESSION['userId'];
  } else {
   die();
  }

  /*******************************************************
   * Only these origins will be allowed to upload images *
   ******************************************************/
  $accepted_origins = array("http://localhost", "https://staging.newsjunction.net", "http://staging.newsjunction.net", "https://www.newsjunction.net", "http://www.newsjunction.net", "https://newsjunction.net", "http://newsjunction.net");

  /*********************************************
   * Change this line to set the upload folder *
   *********************************************/
  $imageFolder = "data/posts/$gUserId/";

  $chkFolder = "../data/posts/$gUserId";
  if (!file_exists($chkFolder)) {
   mkdir($chkFolder, 0777, true);
  }
  
  reset ($_FILES);
  $temp = current($_FILES);
  if (is_uploaded_file($temp['tmp_name'])){
    if (isset($_SERVER['HTTP_ORIGIN'])) {
      // same-origin requests won't set an origin. If the origin is set, it must be valid.
      if (in_array($_SERVER['HTTP_ORIGIN'], $accepted_origins)) {
        header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
      } else {
        header("HTTP/1.1 403 Origin Denied");
        return;
      }
    }

    // Sanitize input
    if (preg_match("/([^\w\s\d\-_~,;:\[\]\(\).])|([\.]{2,})/", $temp['name'])) {
        header("HTTP/1.1 400 Invalid file name.");
        return;
    }

    // Verify extension
    if (!in_array(strtolower(pathinfo($temp['name'], PATHINFO_EXTENSION)), array("gif", "jpg", "png"))) {
        header("HTTP/1.1 400 Invalid extension.");
        return;
    }

    // Accept upload if there was no origin, or if it is an accepted origin
    // $filetowrite = $imageFolder . $temp['name'];

    $fileRaw = hash('ripemd160', time().mt_rand(10,1000)) . '.' . pathinfo($temp['name'], PATHINFO_EXTENSION);
    $fileWrite = '../' . $imageFolder . $fileRaw;
    $fileShow = $imageFolder . $fileRaw;

    move_uploaded_file($temp['tmp_name'], $fileWrite);

    // Respond to the successful upload with JSON.
    // Use a location key to specify the path to the saved image resource.
    // { location : '/your/uploaded/image/file'}
    echo json_encode(array('location' => $fileShow));
  } else {
    // Notify editor that the upload failed
    header("HTTP/1.1 500 Server Error");
  }
?>