<?php
require_once 'bddFunctions.php';
require 'img.php';
require_once 'contact/contact.php';
require_once 'mail/Mail.php';
require_once 'mail/NewsletterCampaign.php';

use Mail\Mail;
use Contact\Contact;
use Mail\NewsLetterCampaign;
// TODO check user list
function getBrowser()
{
  $user_agent = $_SERVER['HTTP_USER_AGENT'];
  $browser = "N/A";

  $browsers = [
    '/msie/i' => 'Internet explorer',
    '/firefox/i' => 'Firefox',
    '/safari/i' => 'Safari',
    '/chrome/i' => 'Chrome',
    '/edge/i' => 'Edge',
    '/opera/i' => 'Opera',
    '/mobile/i' => 'Mobile browser',
  ];

  foreach ($browsers as $regex => $value) {
    if (preg_match($regex, $user_agent)) {
      $browser = $value;
    }
  }

  return $browser;
}

function handleContactCreation() {
    // Takes raw data from the request
    $json = file_get_contents('php://input');
    // Converts it into a PHP object
    $data = json_decode($json);
    if (!property_exists($data, 'firstName') ||
        !property_exists($data, 'mail')) {
        echo 'fail retrieving parameters';
        return null;
    }
    $referer = property_exists($data, 'referer') ? $data->referer : $_SERVER['HTTP_REFERER'];
    $browser = getBrowser();
    $unsubcribeKey = Contact::createContact(htmlspecialchars($data->firstName), htmlspecialchars($data->mail), $referer, $browser);
    Mail::sendBonus($data->firstName, $data->mail, $unsubcribeKey);
    Mail::sendSubNotification($data->firstName, $data->mail, $referer, $browser);
}

function storeContactProblem() {
    // Takes raw data from the request
    $json = file_get_contents('php://input');
    // Converts it into a PHP object
    $data = json_decode($json);
    if (!property_exists($data, 'message')) {
        echo json_encode('fail retrieving parameters');
        return null;
    }

    $mail = isset($data->mail) ? $data->mail : null;

    $success = Contact::createContactProblem(htmlspecialchars($mail), htmlspecialchars($data->message));
    Mail::sendContactProblem(htmlspecialchars($mail), htmlspecialchars($data->message));
    echo json_encode($success);
}

function handleUnsubscribe() {
    // Takes raw data from the request
    $json = file_get_contents('php://input');
    // Converts it into a PHP object
    $data = json_decode($json);

    if (!$data || !property_exists($data, 'key')) {
            echo "{ 'fail': true }";
        return;
    }
    $unsubscribeKey = htmlspecialchars($data->key);
    Contact::unsubscribeContact($unsubscribeKey);
    echo '{ "success": true }';
}

function handleGetComments() {
    if (!isset($_REQUEST['articleId'])) {
        echo 'fail retrieving parameters';
        return;
    }
    getComments($_REQUEST['articleId']);
}

function handleAddComment() {
    // Takes raw data from the request
    $json = file_get_contents('php://input');
    // Converts it into a PHP object
    $data = json_decode($json);

    if (!property_exists($data, 'firstName') ||
        !property_exists($data, 'comment') ||
        !property_exists($data, 'articleId')) {

        echo 'fail retrieving parameters';
        return;
    }
    $mainCommentId = property_exists($data, 'mainCommentId') ? $data->mainCommentId : null;

    addComment($data->firstName, htmlspecialchars($data->comment), $data->articleId, $mainCommentId);
    Mail::sendCommentNotification($data->firstName);
}

function handleGetArticles() {
    getArticles();
}

function handleGetArticleByName() {
    if (!isset($_REQUEST['articleName'])) {
        echo 'fail retrieving parameters';
        return;
    }
    getArticleByName(htmlspecialchars($_REQUEST['articleName']));
}

function handleGetOrtherArticles() {
    if (!isset($_REQUEST['articleName']) || !isset($_REQUEST['category'])) {
        echo 'fail retrieving parameters';
        return;
    }

    $category = $_REQUEST['category'];
    if ($category === 'all') {
        $category = 'other';
    }
    getOrtherArticles(htmlspecialchars($_REQUEST['articleName']), htmlspecialchars($category));
}

function handleMailToAll() {
    // Takes raw data from the request
    $json = file_get_contents('php://input');
    // Converts it into a PHP object
    $data = json_decode($json);

    if (!property_exists($data, 'object') ||
        !property_exists($data, 'body') ||
        !property_exists($data, 'pseudo') ||
        !property_exists($data, 'token')) {
        echo 'fail retrieving parameters';
        return null;
    }

    if(!checkUserToken(htmlspecialchars($data->pseudo), htmlspecialchars($data->token))) {
        echo 'Token expiry';
        return;
    }

    // Format body
    $htmlBody = str_replace('\n', '<br/>', $data->body);
    $htmlBody = preg_replace('/(^"|"$)*/', '', $htmlBody);
    $htmlBody = str_replace('\"', '"', $htmlBody);

    $altBody = str_replace('\n', ' ', $data->body);
    $altBody = preg_replace('/(^"|"$)*/', '', $altBody);
    $altBody = str_replace('\"', '"', $altBody);

    $subject = $data->object;

    $contacts = Contact::getAllMailContacts();
    array_push($contacts, Contact::getContactEndCampaign());

    NewsLetterCampaign::storeNewsletterCampaign($subject,$htmlBody, $contacts);

    $newsletterCampaign = NewsLetterCampaign::getLastNewsletterCampaign();
    $response = NewsLetterCampaign::sendNewsletterCampaign($newsletterCampaign);
// TODO ABORT USER CONNECt
    echo json_encode($response);
}

function handleGetAllMails() {
    if(!isset($_REQUEST['pseudo']) || !isset($_REQUEST['token'])) {
        return;
    }
    if(!checkUserToken(htmlspecialchars($_REQUEST['pseudo']), htmlspecialchars($_REQUEST['token']))) {
        print_r(json_encode("Token expiry"));
        return;
    }
    $contacts = Contact::getAllMailContacts();
    print_r(json_encode($contacts));
}

function handleLogin(){
    sleep(1);
    if(!isset($_REQUEST['pseudo']) || !isset($_REQUEST['password'])) {
        return;
    }
    $password = md5(PREFIX_SALT.htmlspecialchars($_REQUEST['password']).SUFFIX_SALT);
    $token = userLogin(htmlspecialchars($_REQUEST['pseudo']),  $password);
    print_r(json_encode($token));
}

function addNewArticle() {
    // Takes raw data from the request
    $json = file_get_contents('php://input');
    // Converts it into a PHP object
    $data = json_decode($json);

    if (!property_exists($data, 'articleText') ||
        !property_exists($data, 'article') ||
        !property_exists($data, 'pseudo') ||
        !property_exists($data, 'token')) {
        echo 'fail retrieving parameters';
        return null;
    }

    if(!checkUserToken(htmlspecialchars($data->pseudo), htmlspecialchars($data->token))) {
        echo 'Token expiry';
        return;
    }

    $article = $data->article;

    if(!property_exists($article, 'title') ||
        !property_exists($article, 'articleName') ||
        !property_exists($article, 'img') ||
        !property_exists($article, 'imgTitle') ||
        !property_exists($article, 'description') ||
        !property_exists($article, 'metaDesc')){
        echo 'fail retrieving article parameters';
    }

    newArticleFile($article->articleName, $data->articleText);
    addNewArticleinfoToBDD($article);
    addArticleToSitemap($article->articleName);
}

function newArticleFile($articleName, $content) {
    $articlePath = "articles/" . $articleName . '.html';
    file_put_contents($articlePath, $content, LOCK_EX);
}

function addArticleToSitemap($articleName) {
    $url = 'https://sommeilprofond.fr/articles/' . $articleName;
    $xml = simplexml_load_file("../sitemap.xml") or die("Failed to load");
    $urlArticles = "https://sommeilprofond.fr/articles";
    $update = false;
    //print_r($xml);
    foreach ($xml->children() as $child)
    {
        $urlSitemap = (string) $child->loc;
        if($urlSitemap === $url) {
            $child->lastmod[0] = date("Y-m-d");
            $update = true;
            break;
        }
        // Update articles date
        if($urlSitemap === $urlArticles) {
            $child->lastmod[0] = date("Y-m-d");
        }
    }
    if(!$update){
        $newUrl = $xml->addChild('url');
        $newUrl->addChild('loc', 'https://sommeilprofond.fr/articles/' . $articleName);
        // save with the current date
        $newUrl->addChild('lastmod', date("Y-m-d"));
    }

    file_put_contents('../sitemap.xml', $xml->asXML());
}

function updateArticle() {
    // Takes raw data from the request
    $json = file_get_contents('php://input');
    // Converts it into a PHP object
    $data = json_decode($json);

    if (!property_exists($data, 'article') ||
        !property_exists($data, 'articleCreation') ||
        !property_exists($data, 'pseudo') ||
        !property_exists($data, 'token')) {
        echo 'fail retrieving parameters';
        return null;
    }

    if(!checkUserToken(htmlspecialchars($data->pseudo), htmlspecialchars($data->token))) {
        echo 'Token expiry';
        return;
    }

    // Retrieve the data send in the request
    $article = $data->article;
    if (!property_exists($article, 'articleName')){
        echo 'fail retrieving article parameters';
        return;
    }

    $response = new stdClass();

    $articleContent = getFileDriveContentByName($article->articleName);
    $imgPropertiesList = getImgPropertiesList($articleContent);
    $articleContent = formatDriveDocContent($articleContent);
    $articleContent = getImgLinkCreditor($imgPropertiesList, $articleContent);
    $articleContent = imgToPicture($articleContent, $imgPropertiesList, $article->articleName);
    $articleContent = handleExternalLink($articleContent);

    $articleContent = prepareArticleProperties($article, $articleContent, $imgPropertiesList, $data);
    if (!$article->metaDesc || !$article->title) {
        echo "fail to retrieve meta-description or title : meta $article->metaDesc / title : $article->title";
        return;
    }

    // Finalisation
    newArticleFile($article->articleName, $articleContent);
    $response->fileCreation = true;
    // Add/update sitemap
    addArticleToSitemap($article->articleName);

    // Create IMG from article, if no params updateTextOnly
    if (!property_exists($data, 'updateTextOnly')) {
        createArticleImg($imgPropertiesList, $article->articleName, $response);
        $response->imgList = $imgPropertiesList;
    }

    // Update the bdd
    updateArticleTable($article);
    // Don't update articleconfig, if only the text has changed
    if (!property_exists($data, 'updateTextOnly')) {
        $articleId = getArticleId($article);
        updateArticleConfigTable($imgPropertiesList, $articleId);
    }

    echo json_encode($response);
}

function getArticleId($article) {
    return checkArticleExists($article->articleName);
}

function prepareArticleProperties($article, $articleContent, $imgPropertiesList, $data) {
    // Get title, if the title is not send in the request retrieve the H1 title in the content
    if (!property_exists($data, 'title')) {
        $article->title = html_entity_decode(getTitleFromArticleContent($articleContent), ENT_QUOTES);
    }

    // Get the meta description, if the meta is not send in the request, it is retrieved in the content ( ## Meta : ...##)
    if (!property_exists($data, 'metaDesc')) {
        $article->metaDesc = html_entity_decode(getMetaDescriptionFromArticleContent($articleContent), ENT_QUOTES);
    }
    $articleContent = removeMetaDescription($articleContent);
    $article->description = $article->metaDesc;

    // Get the img display in articles page
    if (!empty($imgPropertiesList)) {
        $article->img = "$article->articleName/img1";
        $article->imgTitle = $imgPropertiesList[0]->title;
    }
    return $articleContent;
}

function retryImgUpload() {
    if(!isset($_REQUEST['pseudo']) || !isset($_REQUEST['token'])) {
        return;
    }
    if(!checkUserToken(htmlspecialchars($_REQUEST['pseudo']), htmlspecialchars($_REQUEST['token']))) {
        print_r(json_encode("Token expiry"));
        return;
    }
    if (!isset($_REQUEST['articleName'])) {
        echo 'fail retrieving parameters';
        return;
    }

    $response = new stdClass();

    $articleName = $_REQUEST['articleName'];
    $articleDataConfig = getArticleConfigDataByName($articleName);
    $listImg = $articleDataConfig->articleConfig['img'];
    createArticleImg($listImg, $articleName, $response);
    updateArticleConfigTable($listImg, $articleDataConfig->article['id']);

    $response->imgList = $listImg;
    echo json_encode($response);
}

function sendMessage() {
    // Takes raw data from the request
    $json = file_get_contents('php://input');
    // Converts it into a PHP object
    $data = json_decode($json);

    $return = new stdClass();

    if (empty($data->firstName) || empty($data->email) || empty($data->message)) {
        $return->error = 'fail retrieving parameters';
        echo json_encode($return);
        return;
    }

    $message = htmlspecialchars($data->message);
    $message = preg_replace('/\\n/', '<br/>', $message);

    $return->isSend = Mail::sendMailMessage($data->firstName, $data->email, $message);

    echo json_encode($return);
}