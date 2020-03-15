<?php
require 'functions.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST,GET,OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

if(isset($_REQUEST['method'])) {
    $method = $_REQUEST['method'];
    switch ($method) {
        case "createContact":
            handleContactCreation();
            break;
        case "getContacts":
            handleGetContacts();
            break;
        case "getComments":
            handleGetComments();
            break;
        case "addComment":
            handleAddComment();
            break;
        default:
            echo 'No method found.';
    }
} else {
  echo 'nop';
}
