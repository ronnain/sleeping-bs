<?php
require_once 'bddConnexion.php';
require_once 'modeles.php';

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

function getArticlesData() {
    $bdd = connect();


    $sth = $bdd->prepare("SELECT * FROM `article` WHERE 1 ORDER BY `datePublished` DESC");
    $sth->execute();
    $articlesArray = array();
    while ($article = $sth->fetch(PDO::FETCH_ASSOC)) {
        array_push($articlesArray, $article);
    }
    // Close connection in PDO
    $bdd = null;
    return $articlesArray;
}

function getArticleByName($articleName) {
    $bdd = connect();


    $sth = $bdd->prepare("SELECT * FROM `article` WHERE `articleName` LIKE '$articleName'");
    $sth->execute();
    if ($article = $sth->fetch(PDO::FETCH_ASSOC)) {
        // Second param display the accents
        echo json_encode($article, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    // Close connection in PDO
    $bdd = null;
}

function getOrtherArticles($articleName, $category) {
    $bdd = connect();


    $sth = $bdd->prepare("SELECT * FROM `article` WHERE `articleName` NOT LIKE '$articleName' AND FIND_IN_SET('$category',`categories`)>0 ORDER BY `id` DESC LIMIT 4");
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

function userLogin($pseudo, $password) {
    $bdd = connect();

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

function checkUserToken($pseudo, $token) {
    $bdd = connect();

    // Check user account
    $response = $bdd->query("SELECT * FROM `user` WHERE `pseudo` LIKE '$pseudo' AND `token` LIKE '$token' AND `expiryDate` >= NOW()")
                     or die( print_r($bdd->errorinfo()));

    // if the user not exist, return
    if (!$data = $response->fetch()){
        return false;
    }
    return true;
}

function addNewArticleinfoToBDD($article) {
    insertArticle($article);
    print_r('{ "success": true }');
}

function insertArticle($article) {
    $bdd = connect();


    $req = $bdd->prepare('INSERT INTO `article` (`title`, `description`, `metaDesc`, `datePublished`, `dateModified`, `img`, `imgTitle`, `articleName`, `categories`)
     VALUES (:title, :descr, :metaDesc, :datePublished, :dateModified, :img, :imgTitle, :articleName, :categories)');
    $req->execute(array(
        'title' => $article->title,
        'descr' => $article->description,
        'metaDesc' => $article->metaDesc,
        'datePublished' => property_exists($article, 'datePublished') ? $article->datePublished : date("Y-m-d G:i:s"),
        'dateModified' => property_exists($article, 'dateModified') ? $article->dateModified : date("Y-m-d G:i:s"),
        'img' => $article->img,
        'imgTitle' => html_entity_decode($article->imgTitle),
        'articleName' => $article->articleName,
        'categories' =>  implode(',', $article->categories)
        ));

    // Close connection in PDO
    $bdd = null;
}

function updateArticleDB($article) {
    $bdd = connect();


    $categories = implode(',', $article->categories);

    $req = $bdd->prepare('UPDATE `article` SET `title` = :title, `description` = :descr, `metaDesc` = :metaDesc, `datePublished` = :datePublished, `dateModified` = :dateModified, `img` = :img, `imgTitle` = :imgTitle, `articleName` = :articleName, `categories` = :categories WHERE `article`.`id` = :id;');
    $req->execute(array(
        'title' => $article->title,
        'descr' => $article->description,
        'metaDesc' => $article->metaDesc,
        'datePublished' => property_exists($article, 'datePublished') ? $article->datePublished : date("Y-m-d G:i:s"),
        'dateModified' => date("Y-m-d G:i:s"),
        'img' => $article->img,
        'imgTitle' => html_entity_decode($article->imgTitle),
        'articleName' => $article->articleName,
        'categories' => $categories,
        'id' => $article->id
        ));
    // Close connection in PDO
    $bdd = null;
}

function getArticleConfigDataByName($articleName) {
    $bdd = connect();


    // Get article
    $sth = $bdd->prepare("SELECT * FROM `article` WHERE `articleName` LIKE '$articleName'");
    $sth->execute();
    $article = $sth->fetch(PDO::FETCH_ASSOC);

    $articleConfig = '';
    // Get article config
    if($article){
        $articleId = $article['id'];
        $sth = $bdd->prepare("SELECT * FROM `articleconfig` WHERE `idArticle` = $articleId");
        $sth->execute();
        $articleConfig = $sth->fetch(PDO::FETCH_ASSOC);
        if(!$articleConfig) {
            $articleConfig = '';
        }else {
            $articleConfig['img'] = json_decode($articleConfig['img']);
        }
    }
    // Close connection in PDO
    $bdd = null;

    $result = new stdClass();
    $result->article = $article;
    $result->articleConfig = $articleConfig;

    return $result;
}

function updateArticleTable($article){
    if(!checkArticleExists($article->articleName)){
        insertArticle($article);
    } else { // update Article
        updateArticleDB($article);
    }
}

function checkArticleExists($articleName){
    $bdd = connect();

    $response = $bdd->query("SELECT `id` FROM `article` WHERE `articleName` LIKE '$articleName'")
    or die( print_r($bdd->errorinfo()));
    // if the user not exist, return
    $data = $response->fetch();
    $bdd = null;
    return $data ? $data['id'] : false;
}

function checkArticleConfigExists($articleId){
    $bdd = connect();

    $response = $bdd->query("SELECT `id` FROM `articleconfig` WHERE `idArticle` = $articleId")
    or die( print_r($bdd->errorinfo()));
    // if the user not exist, return
    $data = $response->fetch();
    $bdd = null;
    return $data ? $data['id'] : false;
}

function updateArticleConfigTable($imgPropertiesList, $articleId) {
    $articleConfig = new stdClass();
    $articleConfig->img = $imgPropertiesList;
    if(!checkArticleConfigExists($articleId)){
        insertArticleConfig($articleConfig, $articleId);
    } else { // update Article
        updateArticleConfigDB($articleConfig, $articleId);
    }
}

function insertArticleConfig($articleConfig, $articleId) {
    $bdd = connect();


    $req = $bdd->prepare('INSERT INTO `articleconfig` (`idArticle`, `img`) VALUES (:idArticle, :img);');
    $req->execute(array(
        'idArticle' => $articleId,
        'img' => json_encode($articleConfig->img)
        ));

    // Close connection in PDO
    $bdd = null;
}

function updateArticleConfigDB($articleConfig, $articleId) {
    $bdd = connect();

    $req = $bdd->prepare('UPDATE `articleconfig` SET `img` = :img WHERE `articleconfig`.`idArticle` = :idArticle');
    $req->execute(array(
        'img' => json_encode($articleConfig->img),
        'idArticle' => $articleId
        ));
    // Close connection in PDO
    $bdd = null;
}