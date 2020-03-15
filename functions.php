<?php
require 'bddFunctions.php';

function handleContactCreation() {
    echo $_POST['method'];
    if(!isset($_POST['firstName']) || !isset($_POST['mail'])){
        echo 'fail retrieving parameters';
        return null;
    }

    createContact($_POST['firstName'], $_POST['mail']);
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
    $comment = $data->comment;

    if (!property_exists($comment, 'firstName') ||
        !property_exists($comment, 'comment') ||
        !property_exists($comment, 'articleId')) {

        echo 'fail retrieving parameters';
        return;
    }
    $mainCommentId = property_exists($comment, 'mainCommentId') ? $comment->mainCommentId : null;

    addComment($comment->firstName, $comment->comment, $comment->articleId, $mainCommentId);
}