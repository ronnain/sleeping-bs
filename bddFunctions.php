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
}
