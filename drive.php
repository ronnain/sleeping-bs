<?php
require_once 'bddFunctions.php';

function getAllFilesFromDrive() {
    if(!isset($_REQUEST['pseudo']) || !isset($_REQUEST['token'])) {
        return;
    }
    if(!checkUserToken(htmlspecialchars($_REQUEST['pseudo']), htmlspecialchars($_REQUEST['token']))) {
        print_r(json_encode("Token expiry"));
        return;
    }
    global $apiKey, $folder;
    // Get all the files of the folder, order by modified date desc, in json format
    $allFiles = json_decode(file_get_contents("https://www.googleapis.com/drive/v3/files?q='$folder'%20in%20parents&fields=files(id%2C%20name%2C%20modifiedTime)&key=$apiKey"));
    $articlesData = getArticlesData();

    // add last modified date of the article online to the matching file
    foreach ($allFiles->files as $file){
        addArticleDataToFile($file->name, $articlesData, $file);
    }
    // print all files
    print_r(json_encode($allFiles->files));
}

function addArticleDataToFile($articleName, $articlesData, $file) {
    foreach ($articlesData as $article){
        if ($articleName === $article["articleName"]){
            $articleDate = new DateTime($article["dateModified"]);
            $file->articleDateModified = $articleDate->format('c');
            $file->articleId = $article["id"];
        }
    }
}

function getArticleDriveData() {
    if(!isset($_REQUEST['pseudo']) || !isset($_REQUEST['token']) || !isset($_REQUEST['articleName'])) {
        echo 'fail retrieving parameters';
        return;
    }
    if(!checkUserToken(htmlspecialchars($_REQUEST['pseudo']), htmlspecialchars($_REQUEST['token']))) {
        print_r(json_encode("Token expiry"));
        return;
    }
    $articleName = $_REQUEST['articleName'];
    $articleData = getArticleConfigDataByName($articleName);
    echo json_encode($articleData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}


function getFileDriveIdByName($articleName){
    global $apiKey, $folder;
    // Get all the files of the folder, order by modified date desc, in json format
    $allFiles = json_decode(file_get_contents("https://www.googleapis.com/drive/v3/files?q='$folder'%20in%20parents&fields=files(id%2C%20name)&key=$apiKey"));
    foreach ($allFiles->files as $file){
        if ($file->name === $articleName) {
            return $file->id;
        }
    }
}

function articleImgCounter($articleName) {
    $articleContent = getFileDriveContentByName($articleName);
    return preg_match_all('/<img/', $articleContent);
}

function getFileDriveContent($id){
    global $apiKey;
    return file_get_contents("https://www.googleapis.com/drive/v2/files/$id/export?mimeType=text%2Fhtml&key=$apiKey");
}

function getFileDriveContentByName($articleName){
    $fileId = getFileDriveIdByName($articleName);
    if(!$fileId){
        echo 'fail to retrieve file drive id';
        return;
    }
    return getFileDriveContent($fileId);
}

function formatDriveDocContent($htmlContent) {
    $htmlContent = preg_replace('/<head>.*<\/head>/', '', $htmlContent);
    // remove empty span
    $htmlContent = preg_replace('/<span style="[\w\-;:&#]+"><\/span>/', '', $htmlContent);
    $htmlContent = cleanSpanStyles($htmlContent);
    $htmlContent = preg_replace('/<span\s*>(.*?)<\/span>/', '$1', $htmlContent);
    $htmlContent = preg_replace('/(id|class|style|alt|src|title)=".*?"/', '', $htmlContent);
    $htmlContent = preg_replace('/\s+>/', '>', $htmlContent);
    $htmlContent = preg_replace('/<p><\/p>/', '', $htmlContent);
    $htmlContent = preg_replace('/<(html|\/html|body|\/body)>/', '', $htmlContent);
    $htmlContent = preg_replace('/\s*href="https:\/\/www\.google\.com\/url\?q=/', ' href="', $htmlContent);
    // utilisé pour les paramètres du lien href
    $htmlContent = preg_replace('/&[a-zA-Z0-9_= .:;()\-#&]*"/', '"', $htmlContent);
    return $htmlContent;
}

function imgToPicture($articleContent, $imgPropertiesList, $articleName) {
    global $backendLink;
    /* <picture>
        <source  media="(min-width: 1200px)" srcset="http://localhost:80/sleeping-bs/img/sommeil-reparateur/article/xl.jpg">
        <source  media="(min-width: 992px)" srcset="http://localhost:80/sleeping-bs/img/sommeil-reparateur/article/l.jpg">
        <source  media="(min-width: 768px)" srcset="http://localhost:80/sleeping-bs/img/sommeil-reparateur/article/xm.jpg">
        <source  media="(min-width: 576px)" srcset="http://localhost:80/sleeping-bs/img/sommeil-reparateur/article/m.jpg">
        <img src="http://localhost:80/sleeping-bs/img/sommeil-reparateur/article/xs.jpg" alt="Mer calme et réparatrice" title="Mer calme et réparatrice" class="noMarginBottom fullWidth">
      </picture>
      <div class="creditImgDiv"><a class="creditImg" href="https://photostockeditor.com/" target="_blank" rel="nofollow">Lien Créateur Image</a></div>
 */
    $imgSizes = array (
        "xl"  => "1200",
        "l"  => "992",
        "xm"  => "768",
        "m"  => "576"
    );
    for ($i = 0; $i < count($imgPropertiesList); $i++) {

        $img = $imgPropertiesList[$i];
        $srcLink = $backendLink.'sleeping-bs/img/'. $articleName . '/img' . ($i+1) .'/article/';

        $picture = "<picture>";
        foreach ($imgSizes as $key => $value){
            $picture .= '<source  media="(min-width: '.$value.'px)" srcset="'. $srcLink .$key.'.jpg">';
        }
        $picture .= '<img src="'. $srcLink .$key.'.jpg" alt="'. addslashes($img->alt) .'" title="'. addslashes($img->title) .'" class="'. (property_exists($img, 'linkImgCreator') ? "noMarginBottom" : "") .' fullWidth">';
        $picture .= "</picture>";

        // Add creator link
        if (property_exists($img, 'linkImgCreator')) {
            $picture .= '<div class="creditImgDiv"><a class="creditImg" href="'.$img->linkImgCreator.'" target="_blank" rel="nofollow noreferrer">Lien Créateur Image</a></div>';
        }
        $articleContent = preg_replace('/<img\/?>/', $picture, $articleContent, 1);
    }
    return $articleContent;
}

function handleExternalLink($articleContent) {
    // The link added previously for img creditor start <a class=... and the link in the text start with <a href=...
    $findLink = "<a href=\"";
    $pieces = explode($findLink, $articleContent);
    $pieces_length = count($pieces);

    for ($i = 1; $i < $pieces_length; $i++) {
        // add target="_blank" rel="nofollow noreferrer" to external link
        if('https://sommeilprofond.fr' !== substr($pieces[$i], 0, 25)) {
            //$pieces[$i] = '<a target="_blank" rel="nofollow noreferrer" href="' . $pieces[$i];
            $pieces[$i] = '<a target="_blank" href="' . $pieces[$i];
        } else {
            $pieces[$i] = '<a href="' . $pieces[$i];
        }
    }
    return implode($pieces);
}

function getImgPropertiesList($articleContent) {
    // return array with each img title alt src
    $imgPropertiesList = array();
    $pattern = "/<img (?:alt=([\"'])(|.*?[^\\\\])\\1|src=([\"'])(|.*?[^\\\\])\\3|title=([\"'])(|.*?[^\\\\])\\5|.*?)*>/m";

    $nbImg = preg_match_all($pattern, $articleContent, $matches);

    for ($i = 0; $i < $nbImg; $i++) {
        $imgAttributes = new stdClass();
        $imgAttributes->alt = $matches[2][$i];
        $imgAttributes->title = $matches[6][$i];
        $imgAttributes->src = $matches[4][$i];
        $imgPropertiesList[] = $imgAttributes;
    }

    return $imgPropertiesList;
}

function getImgLinkCreditor($imgPropertiesList, $articleContent) {
    $startPosBaliseImg = -1;

    // Get all the balise with link of img creditor
    preg_match_all(
        '/<p><a\s*(href="[a-zA-Z0-9_= .:\-\/%]*"|\s*)*>(&nbsp;|\s)*(creator|creditor|createur|credit|Lien createur|lien|link|Lien)(&nbsp;|\s)*<\/a><\/p>/',
        $articleContent, $matches,
        PREG_OFFSET_CAPTURE);

    for ($i = 0; $i < sizeof($imgPropertiesList); $i++) {

        // Get the position of the img balise and the next img balise
        $startPosBaliseImg = strpos($articleContent, '<img/>', $startPosBaliseImg + 1); // +1 to not found the same balise
        $posNextBasliseImg = false;

        if ($i < sizeof($imgPropertiesList) -1) {
            $posNextBasliseImg = strpos($articleContent, '<img/>', $startPosBaliseImg + 1);
        }

        // Search for link between the two img balise position
        foreach ($matches[0] as $matchItem) {

            // The finding match is after an img and before the next img
            if ($matchItem[1] > $startPosBaliseImg && (!$posNextBasliseImg || ($posNextBasliseImg && $matchItem[1] < $posNextBasliseImg))) {

                // Get only the link
                preg_match('/href="[a-zA-Z0-9_= .:\-\/%]*"/', $matchItem[0], $matchLink, PREG_OFFSET_CAPTURE);
                $linkCreditor = $matchLink[0][0];
                $linkCreditor = substr($linkCreditor, 6, strlen($linkCreditor) - 7); // remove href and "

                $imgPropertiesList[$i]->linkImgCreator = $linkCreditor;
                break;
            }
        }
    }

    return preg_replace('/<p><a\s*(href="[a-zA-Z0-9_= .:\-\/%]*"|\s*)*>(&nbsp;|\s)*(creator|creditor|createur|credit|Lien createur|lien|link|Lien)(&nbsp;|\s)*<\/a><\/p>/',
     '', $articleContent);
}

function getTitleFromArticleContent($articleContent) {
    preg_match('/<h1>(.*?)<\/h1>/', $articleContent, $matchTitle, PREG_OFFSET_CAPTURE);
    $title = !empty($matchTitle[1]) && !empty($matchTitle[1][0]) ? trim($matchTitle[1][0]) : null;
    if (!empty($title)) {
        $title = preg_replace('/<(.*?)>/', '', $title);
    }
    return $title;
}

function getMetaDescriptionFromArticleContent($articleContent) {
    preg_match('/<p>(?:&nbsp;|\s)*##(?:&nbsp;|\s)*(?:meta|Meta)(?:&nbsp;|\s)*:(.*?)##(?:&nbsp;|\s)*<\/p>/', $articleContent, $matchMeta, PREG_OFFSET_CAPTURE);
    return !empty($matchMeta[1]) && !empty($matchMeta[1][0]) ? trim($matchMeta[1][0]) : null;
}

function removeMetaDescription($articleContent) {
    return preg_replace('/<p>(&nbsp;|\s)*##(&nbsp;|\s)*(meta|Meta)(&nbsp;|\s)*:.*?##(&nbsp;|\s)*<\/p>/',
    '', $articleContent);
}

function handleStyles($fullStyle, $styleValue, $defautValue) {
    return $fullStyle && $styleValue && $styleValue !== $defautValue ? $fullStyle . ';' : '';
}

function cleanSpanStyles($htmlContent) {
    return preg_replace_callback(
        '/<span style="(?:(font-weight:(\d+))|(text-decoration:(\w+))|(color:#(\w+))|(font-size:(\w+))|(font-style:(\w+))|font-family:[\w&]+;[\w&]+;|[\w\-&]+:[\w\-&\.]+|;)+">/m',
        function ($matches) {
            $styles = "";
            // Font weight
            if (!empty($matches[2])) {
                $styles .= handleStyles($matches[1], $matches[2], "400");
            }
            // text decoration
            if (!empty($matches[4])) {
                $styles .= handleStyles($matches[3], $matches[4], "none");
            }
            // color
            if (!empty($matches[6])) {
                $styles .= handleStyles($matches[5], $matches[6], "000000");
            }
            // font size
            if (!empty($matches[8])) {
                $styles .= handleStyles($matches[7], $matches[8], "11pt");
            }
            // font size
            if (!empty($matches[10])) {
                $styles .= handleStyles($matches[9], $matches[10], "normal");
            }
            // Warning : dont change simple quote to double quote, ortherwise the styles will be removed
            return !empty($styles) ? "<span style='$styles'>" : "<span>";
        },
        $htmlContent
    );
}