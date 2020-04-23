<?php
require 'bddConnexion.php';

function getContacts() {
    $bdd = connect();
    $response = $bdd->query('SELECT * FROM `contact`') or die( print_r($bdd->errorinfo()));

    echo '{';

    while ($donnees = $response->fetch()){
        echo '"'.$donnees['firstName'].'": "'. $donnees['mail'].'       Date:'.$donnees['creationDate'].'",';
    }

    echo '"done" : "ok"}';

    $response->closeCursor();
    // Close connection in PDO
    $bdd = null;
}

function createContact($firstName, $mail) {
    $bdd = connect();
    if(!$bdd){
        echo 'Echec de la connexion avec la base de données';
        return false;
    }

    $unsubscribeKey = bin2hex(random_bytes(32));

    $req = $bdd->prepare('INSERT INTO contact(firstName, mail, creationDate, unsubscribe) VALUES(:firstName, :mail, NOW(), :unsubscribe)');
    $req->execute(array(
        'firstName' => $firstName,
        'mail' => $mail,
        'unsubscribe' => $unsubscribeKey
        ));

    // Close connection in PDO
    $bdd = null;

    return $unsubscribeKey;
}

function unsubscribeContact($unsubscribeKey) {
    $bdd = connect();
    if(!$bdd){
        echo 'Echec de la connexion avec la base de données';
        return false;
    }

    $req = $bdd->prepare('UPDATE `contact` SET `subscribe`= false,`unsubscribeDate`= NOW() WHERE `unsubscribe` = :unsubscribe');
    $req->execute(array(
        'unsubscribe' => $unsubscribeKey
        ));
    // Close connection in PDO
    $bdd = null;
}

class Comment {
    public $id;
    public $firstName;
    public $comment;
    public $date;
    public $articleId;
    public $mainCommentId;
    public $repliesComment;
}


function getComments($articleId) {
    $comments = array();
    $bdd = connect();

    $response = $bdd->query("SELECT * FROM `comments` WHERE `mainCommentId` IS NULL AND `articleId` = $articleId ORDER BY `date`") or die( print_r($bdd->errorinfo()));

    while ($donnees = $response->fetch()){
        //$comment = setComment($donnees['id'], $donnees['firstName'], $donnees['comment'], $donnees['date'], $donnees['articleId'], $donnees['mainCommentId']);
        $comment = new Comment();
        $comment->id = $donnees['id'];
        $comment->firstName = htmlspecialchars($donnees['firstName']);
        $comment->comment = htmlspecialchars($donnees['comment']);
        $comment->date = $donnees['date'];
        $comment->articleId = $donnees['articleId'];
        $comment->mainCommentId = $donnees['mainCommentId'];

        // Search for comments replies
        $searchReplies = $bdd->query("SELECT * FROM `comments` WHERE `mainCommentId` = $comment->id ORDER BY `date`" ) or die( print_r($bdd->errorinfo()));

        while ($data = $searchReplies->fetch()){
            if (!is_array($comment->repliesComment)) {
                $comment->repliesComment = array();
            }
            $replyComment = new Comment();
            $replyComment->id = $data['id'];
            $replyComment->firstName = htmlspecialchars($data['firstName']);
            $replyComment->comment = htmlspecialchars($data['comment']);
            $replyComment->date = $data['date'];
            $replyComment->articleId = $data['articleId'];
            $replyComment->mainCommentId = $data['mainCommentId'];
            array_push($comment->repliesComment, $replyComment);
        }

        array_push($comments,$comment);
        $searchReplies->closeCursor();

    }
    echo json_encode($comments, JSON_PRETTY_PRINT);
    $response->closeCursor();
    // Close connection in PDO
    $bdd = null;
}

function addComment($firstName, $comment, $articleId, $mainCommentId = null) {
    $bdd = connect();
    if(!$bdd){
        echo 'Echec de la connexion avec la base de données';
        return false;
    }

    $req = $bdd->prepare('INSERT INTO `comments` (`firstName`, `date`, `comment`, `mainCommentId`, `articleId`) VALUES (:firstName, NOW(), :comment, :mainCommentId, :articleId)');
    $req->execute(array(
        'firstName' => $firstName,
        'comment' => $comment,
        'mainCommentId' => $mainCommentId,
        'articleId' => $articleId
        ));
    echo '{ "success":' . $bdd->lastInsertId() . '}';
    // Close connection in PDO
    $bdd = null;
}



function getArticles() {
    $bdd = connect();
    if(!$bdd){
        echo 'Echec de la connexion avec la base de données';
        return false;
    }

    $sth = $bdd->prepare("SELECT * FROM `article` WHERE 1 ORDER BY `datePublished` DESC");
    $sth->execute();
    $articlesArray = array();
    while ($article = $sth->fetch(PDO::FETCH_ASSOC)) {
        array_push($articlesArray, $article);
    }
    // Second param display the accents
    echo json_encode($articlesArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    // Close connection in PDO
    $bdd = null;
}

function getArticleByName($articleName) {
    $bdd = connect();
    if(!$bdd){
        echo 'Echec de la connexion avec la base de données';
        return false;
    }

    $sth = $bdd->prepare("SELECT * FROM `article` WHERE `articleName` LIKE '$articleName'");
    $sth->execute();
    if ($article = $sth->fetch(PDO::FETCH_ASSOC)) {
        // Second param display the accents
        echo json_encode($article, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    // Close connection in PDO
    $bdd = null;
}

function getOrtherArticles($articleName) {
    $bdd = connect();
    if(!$bdd){
        echo 'Echec de la connexion avec la base de données';
        return false;
    }

    $sth = $bdd->prepare("SELECT * FROM `article` WHERE `articleName` NOT LIKE '$articleName'");
    $sth->execute();
    $articlesArray = array();

    while ($article = $sth->fetch(PDO::FETCH_ASSOC)) {
        array_push($articlesArray, $article);
    }

    // Second param display the accents
    echo json_encode($articlesArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // Close connection in PDO
    $bdd = null;
}

function getAllMailContacts() {
    $bdd = connect();
    if(!$bdd){
        echo 'Echec de la connexion avec la base de données';
        return false;
    }

    $sth = $bdd->prepare("SELECT `mail`, `firstName`, `unsubscribe` FROM `contact` WHERE `subscribe` = 1");
    $sth->execute();
    $contacts = array();

    while ($contact = $sth->fetch(PDO::FETCH_ASSOC)) {
        array_push($contacts, $contact);
    }
    // Close connection in PDO
    $bdd = null;

    return $contacts;
}

function userLogin($pseudo, $password) {
    $bdd = connect();
    if(!$bdd){
        echo 'Echec de la connexion avec la base de données';
        return false;
    }
    // Check user account
    $response = $bdd->query("SELECT * FROM `user` WHERE `pseudo` LIKE '$pseudo' AND `password` LIKE '$password'")
                     or die( print_r($bdd->errorinfo()));

    // if the user not exist, return
    if (!$data = $response->fetch()){
        return 'User not found';
    }
    // Token creation
    $token = bin2hex(random_bytes(32));

    // expiry Date 2h after the connexion
    $req = $bdd->prepare('UPDATE `user` SET `token`= :token,`expiryDate`= (NOW() + INTERVAL 2 HOUR) WHERE `pseudo` = :pseudo AND `password` = :password');
    $req->execute(array(
        'pseudo' => $pseudo,
        'password' => $password,
        'token' => $token
        ));

    // Close connection in PDO
    $bdd = null;

    return $token;
}