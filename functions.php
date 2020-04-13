<?php
require 'bddFunctions.php';
require 'mail.php';

function handleContactCreation() {
    // Takes raw data from the request
    $json = file_get_contents('php://input');
    // Converts it into a PHP object
    $data = json_decode($json);
    $mailData = $data->mail;

    if (!property_exists($mailData, 'firstName') ||
        !property_exists($mailData, 'mail')) {
        echo 'fail retrieving parameters';
        return null;
    }

    $unsubcribeKey = createContact(htmlspecialchars($mailData->firstName), htmlspecialchars($mailData->mail));
    sendBonus($mailData->firstName, $mailData->mail, $unsubcribeKey);
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
        !property_exists($data, 'password')) {
        echo 'fail retrieving parameters';
        return null;
    }

    // Until user session creation get a password
    if ($data->password != "35370") {
        echo 'wrong password';
        return;
    }

    // Format body
    $bodyFormated = str_replace('\n', '<br/>', $data->body);
    $bodyFormated = preg_replace('/(^"|"$)*/', '', $bodyFormated);
    $bodyFormated = str_replace('\"', '"', $bodyFormated);

    $contacts = getAllMailContacts();
    sendTextMailToAll($data->object,$bodyFormated, $contacts);
}
