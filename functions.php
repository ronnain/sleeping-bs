<?php
require 'bddFunctions.php';
require 'mail.php';

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

    $unsubcribeKey = createContact(htmlspecialchars($data->firstName), htmlspecialchars($data->mail));
    sendBonus($data->firstName, $data->mail, $unsubcribeKey);
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
    unsubscribeContact($unsubscribeKey);
    echo '{ "success": true }';
}

function handleGetContacts() {
    getContacts();
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

    addComment($data->firstName, $data->comment, $data->articleId, $mainCommentId);
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
    if (!isset($_REQUEST['articleName'])) {
        echo 'fail retrieving parameters';
        return;
    }
    getOrtherArticles(htmlspecialchars($_REQUEST['articleName']));
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

    $contacts = getAllMailContacts();
    sendTextMailToAll($data->object,$htmlBody, $contacts, $altBody);
}

function handleGetAllMails() {
    if(!isset($_REQUEST['pseudo']) || !isset($_REQUEST['token'])) {
        return;
    }
    if(!checkUserToken(htmlspecialchars($_REQUEST['pseudo']), htmlspecialchars($_REQUEST['token']))) {
        print_r(json_encode("Token expiry"));
        return;
    }
    $contacts = getAllMailContacts();
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