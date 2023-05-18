<?php
namespace Contact;
use Contact\ContactModel;
use Contact\ContactDto;

require_once 'bddConnexion.php';
require_once 'modeles.php';
require_once 'contact/ContactModel.php';
require_once 'contact/ContactDto.php';


class Contact {

    static function createContact($firstName, $mail, $referer, $browser) {
        $bdd = connect();

        if ($unsubscribeKey = self::getContactUnsubscribeKey($mail)) {
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

    static function createContactProblem(string $mail, string $message) {

        if (!empty($mail)) {
            self::getContactByMail($mail);
        }

        $bdd = connect();

        $req = $bdd->prepare('INSERT INTO `contact_problem` (`idContact`, `mail`, `message`) VALUES (NULL, :mail, :message);');
        $success = $req->execute(array(
            'mail' => $mail,
            'message' => $message,
            ));

        // Close connection in PDO
        $bdd = null;

        return $success;
    }

    static function getContactUnsubscribeKey($mail) {
        $bdd = connect();
        $sth = $bdd->prepare("SELECT `unsubscribe` FROM `contact` WHERE `mail` = :mail ");
        $sth->execute(array(
            'mail' => $mail
            ));
        $bdd = null;
        $result = $sth->fetch(\PDO::FETCH_ASSOC);
        return !empty($result) ? $result['unsubscribe'] : null;
    }

    static function unsubscribeContact($unsubscribeKey) {
        $bdd = connect();

        $req = $bdd->prepare('UPDATE `contact` SET `subscribe`= false,`unsubscribeDate`= NOW() WHERE `unsubscribe` = :unsubscribe');
        $req->execute(array(
            'unsubscribe' => $unsubscribeKey
            ));
        // Close connection in PDO
        $bdd = null;
    }

    static function getContactByMail(string $mail) {
        $bdd = connect();

        $req = $bdd->prepare('SELECT `id`  FROM `contact` WHERE `mail` LIKE :mail');
        $req->execute(array(
            'mail' => $mail
            ));
        $bdd = null;
    }

    static function getAllMailContacts() {
        $contactsDto = self::getAllMailContactsDTO();

        $contacts = array_map(function ($contact) {
                                    return new ContactModel(
                                        +$contact->id,
                                        $contact->firstName,
                                        $contact->mail,
                                        new \DateTime($contact->creationDate),
                                        $contact->unsubscribe,
                                        $contact->subscribe,
                                        isset($contact->unsubscribeDate) ? $contact->unsubscribeDate : null,
                                        isset($contact->source) ? $contact->source : null,
                                        isset($contact->browser) ? $contact->browser : null
                                    );
                                },
                                $contactsDto);

        return $contacts;
    }

    static function getAllMailContactsDTO() {
        $bdd = connect();

        $sth = $bdd->prepare("SELECT * FROM `contact` WHERE `subscribe` = 1 ORDER BY `id`");
        $sth->execute();
        $contactsData = $sth->fetchAll(\PDO::FETCH_OBJ);

        $contacts = array_map(function ($contactData) {
            return new ContactDto(
                (int)$contactData->id,
                $contactData->firstName,
                $contactData->mail,
                $contactData->creationDate,
                $contactData->unsubscribe,
                (bool)$contactData->subscribe,
                isset($contactData->unsubscribeDate) ? $contactData->unsubscribeDate : null,
                isset($contactData->source) ? $contactData->source : null,
                isset($contactData->browser) ? $contactData->browser : null
            );
        }, $contactsData);

        // Close connection in PDO
        $bdd = null;

        return $contacts;
    }

    static function deserializeContacts(string $contacts) {
        return array_map(function ($contact) {
            return new ContactModel(
                +$contact->id,
                $contact->firstName,
                $contact->mail,
                is_object($contact->creationDate) && isset($contact->creationDate->date) ?new \DateTime($contact->creationDate->date) : new \DateTime($contact->creationDate),
                $contact->unsubscribe,
                $contact->subscribe,
                isset($contact->unsubscribeDate) ? $contact->unsubscribeDate : null,
                isset($contact->source) ? $contact->source : null,
                isset($contact->browser) ? $contact->browser : null
            );
        },
        json_decode($contacts));
    }

    /**
     * Send an email, in order to notify when a campaign end
     */
    static function getContactEndCampaign() {
        return new ContactModel(
            0, // id
            'Romain', // firstName
            'romain.geffrault+endCampaign@gmail.com', // mail
            new \DateTime(), // creationDate (utilise la date et l'heure actuelles)
            '', // unsubscribe
            true, // subscribe (valeur par défaut)
            null, // unsubscribeDate (valeur par défaut, nullable)
            '', // source
            '' // browser
        );
    }
    /*
    static function countSubByMounth() {
        SELECT DATE_FORMAT(creationDate, '%Y-%M') as date, COUNT(*) as entityCount FROM `contact` GROUP BY YEAR(creationDate), MONTH(creationDate)
    } */
}