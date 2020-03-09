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
    getComments(1);
}

function handleAddComment() {
    if (!isset($_REQUEST['firstName']) || !isset($_REQUEST['comment']) || !isset($_REQUEST['articleId'])) {
        echo 'fail retrieving parameters';
        return;
    }
    $mainCommentId = isset($_REQUEST['mainCommentId']) ? $_REQUEST['mainCommentId'] : null;

    addComment($_REQUEST['firstName'], $_REQUEST['comment'], $_REQUEST['articleId'], $mainCommentId);
}