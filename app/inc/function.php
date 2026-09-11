<?php

function checkLike($conn, $userId, $articleId)
{
    try {
        $sql = "SELECT COUNT(*) as count FROM reader_thumbs_up WHERE articleId = ? AND userId = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ii", $articleId, $userId);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return $row['count'] == 1;
    } catch (Exception $e) {
        // Log the error message
        error_log($e->getMessage());
        return false; // Indicate failure
    } finally {
        if (isset($stmt) && $stmt) {
            $stmt->close();
        }
    }
}

function likeCount($conn, $articleId)
{
    try {
        $sql = "SELECT COUNT(*) as count FROM reader_thumbs_up WHERE articleId = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("i", $articleId);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        if ($row['count'] === 0) {
            return '';
        }
        return $row['count'];
    } catch (Exception $e) {
        // Log the error message
        error_log($e->getMessage());
        return false; // Indicate failure
    } finally {
        if (isset($stmt) && $stmt) {
            $stmt->close();
        }
    }
}

function find_ipgeo_location()
{
    // Fetch the client's IP address
    $ip = getenv('HTTP_CLIENT_IP') ?: getenv('HTTP_X_FORWARDED_FOR') ?: getenv('HTTP_X_FORWARDED') ?: getenv('HTTP_FORWARDED_FOR') ?: getenv('HTTP_FORWARDED') ?: getenv('REMOTE_ADDR');

    // Default values
    $visitCity = '';
    $visitCountry = '';

    // Fetch geolocation data
    $response = @file_get_contents('http://www.geoplugin.net/php.gp?ip=' . urlencode($ip));
    if ($response !== false) {
        $data = unserialize($response);
        if ($data && isset($data['geoplugin_city']) && isset($data['geoplugin_countryName'])) {
            $visitCity = $data['geoplugin_city'];
            $visitCountry = $data['geoplugin_countryName'];
        }
    }

    // Prepare the response in JSON format
    $result = [
        'City' => $visitCity,
        'Country' => $visitCountry
    ];

    return json_encode($result);
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

function buildNewsletter($newsId)
{
    $returnHTML = '';
    $returnMETA = '';
    $metaCoverImg = '';
    $metaTitle = '';
    $metaDesc = '';

    global $creamdb;
    $sql = "SELECT A.*,B.company,B.news_title,B.news_logo,B.subdomain FROM user_newsletter A INNER JOIN user B ON A.user_id=B.id WHERE A.id=$newsId";
    $result = mysqli_query($creamdb, $sql);
    $row = mysqli_fetch_assoc($result);
    $userSubdomain = $row['subdomain'];
    $companyName = $row['company'];
    $newsTitle = $row['news_title'];
    $newsLogo = $row['news_logo'];
    $newsDate = $row['date_created'];
    $newsArticles = $row['article_id'];
    $arrArticles = explode(',', $newsArticles);
    $returnHTML .= '<table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:650px;border:1px solid #ccc;margin:30px 0;">';
    $returnHTML .= '<tr>';
    $returnHTML .= ' <td style="width:40px"></td>';
    $returnHTML .= ' <td style="padding-top:20px" align="center">';
    $returnHTML .= '  <img src="https://newsjunction.net/data/logos/' . $newsLogo . '" width="150" /><br><br>';
    $returnHTML .= '  <div style="font-size:20px;font-family:Helvetica,Arial,sans-serif; color:#000;">' . $newsTitle . '</div>';
    $returnHTML .= '  <div style="font-size:13px;font-family:Helvetica,Arial,sans-serif; color:#000;">' . date('M d, Y') . ' | Publisher: ' .  $companyName . '</div>';
    $returnHTML .= ' </td>';
    $returnHTML .= ' <td style="width:40px"></td>';
    $returnHTML .= '</tr>';
    $returnHTML .= '<tr>';
    $returnHTML .= ' <td></td>';
    $returnHTML .= ' <td style="padding-top:40px;padding-bottom:20px;font-family:Georgia,serif;font-size:16px;line-height:1.5em;" align="left">';
    foreach ($arrArticles as $nl) {
        if ($nl <> '') {
            $sql = "SELECT * FROM user_collection WHERE id=$nl";
            $result = mysqli_query($creamdb, $sql);
            $numRows = mysqli_num_rows($result);
            if ($numRows > 0) {
                $row = mysqli_fetch_assoc($result);
                $artId = $row['id'];
                $artTitle = $row['title'];
                $artDesc = $row['description'];

                if ($userSubdomain <> '') {
                    $artURL = 'https://' . $userSubdomain . '.newsjunction.net/view/' . $artId . '/' . createArticleURL($artTitle);
                    $artDesc = str_replace('<img src="data/posts/', '<img src="https://' . $userSubdomain . '.newsjunction.net/data/posts/', $artDesc);
                } else {
                    $artURL = 'https://newsjunction.net/view/' . $artId . '/' . createArticleURL($artTitle);
                    $artDesc = str_replace('<img src="data/posts/', '<img src="https://newsjunction.net/data/posts/', $artDesc);
                }

                $artDesc = str_replace("\\n", "<br>", $artDesc);
                $artDesc = stripslashes($artDesc);

                $artCoverImg = $row['cover_img'];
                $artIsReadMore = $row['is_read_more'];
                $artReadMoreTxt = $row['read_more_txt'];
                if ($artReadMoreTxt == '') $artReadMoreTxt = "Read More";

                if ($metaCoverImg == '') $metaCoverImg = $artCoverImg;
                if ($metaTitle == '') $metaTitle = $artTitle;
                if ($metaDesc == '') $metaDesc = $artDesc;



                $returnHTML .= '  <div style="padding-bottom:40px">';
                if ($artCoverImg <> '') {
                    $returnHTML .= '  <div style="padding-bottom:10px"><a href="' . $artURL . '" target="_blank"><img src="https://newsjunction.net/data/covers/' . $artCoverImg . '" style="max-width:650px" width="100%" /></a></div>';
                }
                $returnHTML .= '   <div style="padding-bottom:10px;font-size:14pt;"><a href="' . $artURL . '" target="_blank"><strong>' . $artTitle . '</strong></a></div>';
                $returnHTML .= '   <span class="newsletterPara" style="font-weight:400" style="color:black";>' . $artDesc . '</span>';
                if ($artIsReadMore <> '') {
                    if ($userSubdomain <> '') {
                        $returnHTML .= '   <center><a href="https://' . $userSubdomain . '.newsjunction.net/more.php?id=' . $nl . '" target="_blank"><div style="display:inline-block;font-size:0.75em;margin-top:10px;padding:8px 15px;background-color:#ffc107;border-radius:5px;text-decoration:none;">' . $artReadMoreTxt . '</div></a></center>';
                    } else {
                        $returnHTML .= '   <center><a href="https://newsjunction.net/more.php?id=' . $nl . '" target="_blank"><div style="display:inline-block;font-size:0.75em;margin-top:10px;padding:8px 15px;background-color:#ffc107;border-radius:5px;text-decoration:none;">' . $artReadMoreTxt . '</div></a></center>';
                    }
                }
                $returnHTML .= '   <br clear="all">';
                $returnHTML .= '  </div>';
            }
        }
    }

    $returnHTML .= ' </td>';
    $returnHTML .= ' <td></td>';
    $returnHTML .= '</tr>';
    $returnHTML .= '<tr>';
    $returnHTML .= ' <td></td>';
    $returnHTML .= ' <td align="center">';
    $returnHTML .= '  Powered by <a href="https://newsjunction.net/"><img src="https://newsjunction.net/assets/img/logo.black.png" width="100" align="middle" style="padding-bottom:10px"></a><br><br>';
    $returnHTML .= ' </td>';
    $returnHTML .= ' <td></td>';
    $returnHTML .= '</tr>';
    $returnHTML .= '</table>';


    $collectionLink = 'https://' . $_SERVER['SERVER_NAME'] . '/newsletter.php?id=' . $newsId;
    $returnMETA .= '<meta property="og:url" content=' . $collectionLink . ' />';
    $returnMETA .= '<meta property="og:type" content="website" />';
    $returnMETA .= '<meta property="og:title" content=' . $metaTitle . ' />';
    $returnMETA .= '<meta property="og:description" content=' . htmlspecialchars($metaDesc) . ' />';
    $returnMETA .= '<meta property="og:image" content="https://' . $_SERVER['SERVER_NAME'] . '/data/covers/' . $metaCoverImg . '" />';
    $returnMETA .= '<meta property="og:image:secure-url" itemprop="image" content="https://' . $_SERVER['SERVER_NAME'] . '/data/covers/' . $metaCoverImg . '" />';

    $returnMETA .= '<meta property="twitter:url" content="' . $collectionLink . '" />';
    $returnMETA .= '<meta name="twitter:card" content="summary" />';
    $returnMETA .= '<meta name="twitter:title" content="' . $metaTitle . '" />';
    $returnMETA .= '<meta name="twitter:description" content="' . htmlspecialchars($metaDesc) . '" />';
    $returnMETA .= '<meta name="twitter:image" content="https://' . $_SERVER['SERVER_NAME'] . '/data/logos/' . $metaCoverImg . '" />';

    return ['meta_tag' => $returnMETA, 'html_data' => $returnHTML];
}
