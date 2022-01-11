<?php
require 'functions.php';
require 'drive.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST,GET,OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

if(isset($_REQUEST['method'])) {
    $method = htmlspecialchars($_REQUEST['method']);
    switch ($method) {
        case "createContact":
            handleContactCreation();
            break;
        case "unsubscribe":
            handleUnsubscribe();
            break;
        case "getComments":
            handleGetComments();
            break;
        case "addComment":
            handleAddComment();
            break;
        case "getArticles":
            handleGetArticles();
            break;
        case "getArticleByName":
            handleGetArticleByName();
            break;
        case "getOtherArticles":
            handleGetOrtherArticles();
            break;
        case "mailToAll":
            handleMailToAll();
            break;
        case "getAllMails":
            handleGetAllMails();
            break;
        case "addNewArticle":
            addNewArticle();
            break;
        case "login":
            handleLogin();
            break;
        case "getAllFilesFromDrive":
            getAllFilesFromDrive();
            break;
        case "getArticleDriveData":
            getArticleDriveData();
            break;
        case "updateArticle":
            updateArticle();
            break;
        case "retryImgUpload":
            retryImgUpload();
            break;
        case "sendMessage":
            sendMessage();
            break;
        default:
            echo 'No method found.';
    }
} else {
  echo 'nop';
}
