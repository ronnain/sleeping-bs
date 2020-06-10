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
        if($articleName === $article["articleName"]){
            $file->articleDateModified = $article["dateModified"];
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
    $nbImg = articleImgCounter($articleName);
    $articleData = getArticleConfigDataByName($articleName);
    $articleData->nbImg = $nbImg;
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
    $htmlContent = preg_replace('/id="[a-zA-Z0-9_= .]*"/', '', $htmlContent);
    $htmlContent = preg_replace('/class="[a-zA-Z0-9_= .]*"/', '', $htmlContent);
    $htmlContent = preg_replace('/style="[a-zA-Z0-9_= .:;()\-#&]*"/', '', $htmlContent);
    $htmlContent = preg_replace('/<span\s*>/', '', $htmlContent);
    $htmlContent = preg_replace('/<\/span>/', '', $htmlContent);
    $htmlContent = preg_replace('/\s+>/', '>', $htmlContent);
    $htmlContent = preg_replace('/<p><\/p>/', '', $htmlContent);
    $htmlContent = preg_replace('/<(html|\/html|body|\/body)>/', '', $htmlContent);
    $htmlContent = preg_replace('/\s*href="https:\/\/www\.google\.com\/url\?q=/', ' href="', $htmlContent);
    $htmlContent = preg_replace('/&[a-zA-Z0-9_= .:;()\-#&]*"/', '"', $htmlContent);
    $htmlContent = preg_replace('/<p><img\s*(alt="[a-zA-Z0-9_= .]*"|src="[a-zA-Z0-9_= .:\-\/]*"|title="[a-zA-Z0-9_= .]*"|\s*)*><\/p>/', '<img/>', $htmlContent);
    return $htmlContent;
}

function imgToPicture($articleContent, $images) {
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
    foreach ($images as $img){
        $picture = "<picture>";
        foreach ($imgSizes as $key => $value){
            $picture .= '<source  media="(min-width: '.$value.'px)" srcset="'.$backendLink.'sleeping-bs/img/'.$img->imgPath.'/article/'.$key.'.jpg">';
        }
        $picture .= '<img src="'.$backendLink.'sleeping-bs/img/'.$img->imgPath.'/article/'.$key.'.jpg" alt="'.$img->articleTitle.'" title="'.$img->articleTitle.'" class="noMarginBottom fullWidth">';
        $picture .= "</picture>";
        if(property_exists($img, 'linkImgCreator')){
            $picture .= '<div class="creditImgDiv"><a class="creditImg" href="'.$img->linkImgCreator.'" target="_blank" rel="nofollow">Lien Créateur Image</a></div>';
        }
        $articleContent = preg_replace('/<img\/>/', $picture, $articleContent, 1);
    }
    return $articleContent;
}