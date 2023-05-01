<?php
require_once 'bddConnexion.php';
require_once 'modeles.php';

function createContact($firstName, $mail, $referer, $browser) {
    $bdd = connect();
    if(!$bdd){
        echo 'Echec de la connexion avec la base de données';
        return false;
    }

    if ($unsubscribeKey = getContactUnsubscribeKey($mail)) {
        // Warning : lost info resubcribe
        $req = $bdd->prepare('UPDATE `contact` SET `subscribe`= true, `unsubscribeDate`= NULL WHERE `mail` = :mail');
        $req->execute(array(
            'mail' => $mail
            ));
        return $unsubscribeKey;
    }

    $unsubscribeKey = bin2hex(random_bytes(32));

    $req = $bdd->prepare('INSERT INTO contact(firstName, mail, creationDate, unsubscribe, source, browser) VALUES(:firstName, :mail, NOW(), :unsubscribe, :source, :browser)');
    $req->execute(array(
        'firstName' => $firstName,
        'mail' => $mail,
        'unsubscribe' => $unsubscribeKey,
        'source' => $referer,
        'browser' => $browser
        ));

    // Close connection in PDO
    $bdd = null;

    return $unsubscribeKey;
}

function createContactProblem(string $mail, string $message) {

    if (!empty($mail)) {
        getContactByMail($mail);
    }

    $bdd = connect();
    if(!$bdd){
        echo 'Echec de la connexion avec la base de données';
        return false;
    }

    $req = $bdd->prepare('INSERT INTO `contact_problem` (`idContact`, `mail`, `message`) VALUES (NULL, :mail, :message);');
    $success = $req->execute(array(
        'mail' => $mail,
        'message' => $message,
        ));

    // Close connection in PDO
    $bdd = null;

    return $success;
}

function getContactUnsubscribeKey($mail) {
    $bdd = connect();
    $sth = $bdd->prepare("SELECT `unsubscribe` FROM `contact` WHERE `mail` = :mail ");
    $sth->execute(array(
        'mail' => $mail
        ));
    $bdd = null;
    $result = $sth->fetch(PDO::FETCH_ASSOC);
    return !empty($result) ? $result['unsubscribe'] : null;
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

function getContactByMail(string $mail) {
    $bdd = connect();
    if(!$bdd){
        echo 'Echec de la connexion avec la base de données';
        return false;
    }
    $req = $bdd->prepare('SELECT `id`  FROM `contact` WHERE `mail` LIKE :mail');
    $req->execute(array(
        'mail' => $mail
        ));
    $bdd = null;
}

function getAllMailContacts() {
    $bdd = connect();
    if(!$bdd){
        echo 'Echec de la connexion avec la base de données';
        return false;
    }

    $sth = $bdd->prepare("SELECT * FROM `contact` WHERE `subscribe` = 1 ORDER BY `id`");
    $sth->execute();
    $contacts = array();

    while ($contact = $sth->fetch(PDO::FETCH_ASSOC)) {
        array_push($contacts, $contact);
    }
    // Close connection in PDO
    $bdd = null;

    return $contacts;
}
/*
function countSubByMounth() {
    SELECT DATE_FORMAT(creationDate, '%Y-%M') as date, COUNT(*) as entityCount FROM `contact` GROUP BY YEAR(creationDate), MONTH(creationDate)
} */