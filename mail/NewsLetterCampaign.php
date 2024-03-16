<?php

namespace Mail;

use Contact\ContactModel;
use Contact\Contact;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Mail\Mail;
use Template\NewsletterTemplate\NewsletterTemplate;

require_once 'bddConnexion.php';
require_once 'vendor/phpmailer/phpmailer/src/Exception.php';
require_once 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once 'vendor/phpmailer/phpmailer/src/SMTP.php';
require_once 'template/NewsletterTemplate.php';
require_once 'template/SubscriptionTemplate.php';
require_once 'mail/NewsletterCampaignModel.php';
require_once 'contact/ContactModel.php';


class NewsLetterCampaign {

    static function storeNewsletterCampaign($subject, $content, $contacts) {
        $bdd = connect();

        $req = $bdd->prepare('INSERT INTO `newsletter_campaign` (`subject`, `content`, `contacts`) VALUES (:subject, :content, :contacts);');

        $result = $req->execute(array(
            'subject' => $subject,
            'content' => $content,
            'contacts' => json_encode($contacts),
            ));

        // Close connection in PDO
        $bdd = null;

        return $result;
    }

    static function getLastNewsletterCampaign(): NewsLetterCampaignModel {
        $bdd = connect();
        $sth = $bdd->prepare("SELECT * FROM `newsletter_campaign` ORDER BY id DESC LIMIT 1;");
        $sth->execute();
        $campaignData = $sth->fetch(\PDO::FETCH_OBJ);

        $campaign = new NewsLetterCampaignModel();
        $campaign->id = $campaignData->id;
        $campaign->subject = $campaignData->subject;
        $campaign->content = $campaignData->content;
        $campaign->creation_date = $campaignData->creation_date;
        $campaign->send_total = $campaignData->send_total;
        $campaign->not_send_total = $campaignData->not_send_total;

        $campaign->contacts = Contact::deserializeContacts($campaignData->contacts);

        $campaign->invalid_contact = Contact::deserializeContacts($campaignData->invalid_contact);

        return $campaign;
    }

    static function getNewsletterCampaignById($id) {
        $bdd = connect();
        $sth = $bdd->prepare("SELECT * FROM `newsletter_campaign` WHERE `id` = :id ORDER BY id DESC LIMIT 1;");
        $sth->execute(array(
            'id' => $id,
            ));
        return $sth->fetch(\PDO::FETCH_OBJ);
    }

    static function newsletterSendSuccess($bdd, NewsLetterCampaignModel $newsletterCampaign, ContactModel $contact) {
        $newsletterCampaign->send_total++;
        $newsletterCampaign->contacts = self::removeContactFromListToSend($contact->mail, $newsletterCampaign->contacts);

        $req = $bdd->prepare('UPDATE `newsletter_campaign` SET `contacts` = :contacts, `send_total` = :send_total WHERE `id` = :id');

        $result = $req->execute(array(
            'send_total' => $newsletterCampaign->send_total,
            'contacts' => json_encode($newsletterCampaign->contacts),
            'id'=> $newsletterCampaign->id
            ));
        return $result;
    }

    static function newsletterSendFailed($bdd, NewsLetterCampaignModel $newsletterCampaign, ContactModel $contact) {
        $newsletterCampaign->not_send_total++;
        $newsletterCampaign->contacts = self::removeContactFromListToSend($contact->id, $newsletterCampaign->contacts);
        array_push($newsletterCampaign->invalid_contact, $contact);
        $req = $bdd->prepare('UPDATE `newsletter_campaign` SET `contacts` = :contacts, `not_send_total` = :send_total, `invalid_contact` = :invalid_contact WHERE `id` = :id');
        $result = $req->execute(array(
            'contacts' => json_encode($newsletterCampaign->contacts),
            'send_total' => $newsletterCampaign->send_total,
            'invalid_contact' => json_encode($newsletterCampaign->invalid_contact),
            'id'=> $newsletterCampaign->id
        ));
        return $result;
    }

    static function removeContactFromListToSend(string $contactMail, array $contacts) {
        return array_values(array_filter($contacts, function (ContactModel $contact) use ($contactMail)
        {
            return $contact->mail !== $contactMail;
        }));
    }

    static function sendNewsletterCampaign(NewsletterCampaignModel $newsletterCampaign) {

        global $siteWebLink;
        $bdd = connect();

        $mail = new PHPMailer(true);
        Mail::setHeadermail($mail);
        $mail->SMTPKeepAlive = true;

        $mail->CharSet = 'UTF-8';
        //Set the subject line
        $mail->Subject = $newsletterCampaign->subject;

        $mailAddressSend = [];

        $awaitMailSend = 0;

        foreach ($newsletterCampaign->contacts as $contact) {
            $awaitMailSend++;

            if ($awaitMailSend % 300 == 0) { // Wait 5 minutes before sending the other mails
                sleep(5*60);
            }

            $subscriberMail = $contact->mail;
            if (array_search($subscriberMail, $mailAddressSend) !== false) {
                continue;
            }
            try {
                $mail->addAddress($subscriberMail, $contact->firstName);
                $unsubcribeLink = $siteWebLink.'/desabonnement/'.$contact->unsubscribe;
                $mail->Body = NewsletterTemplate::get($newsletterCampaign->content, $unsubcribeLink, $subscriberMail);
                $mail->AltBody = Mail::removeHTMLTagAndKeepLink($mail->Body) . "\n\n $unsubcribeLink";
            } catch (Exception $e) {
                self::newsletterSendFailed($bdd, $newsletterCampaign, $contact);
                continue;
            }
            try {
                if ($mail->send()) {
                    array_push($mailAddressSend, $subscriberMail);
                    self::newsletterSendSuccess($bdd, $newsletterCampaign, $contact);
                } else {
                    self::newsletterSendFailed($bdd, $newsletterCampaign, $contact);
                }
            } catch (Exception $e) {
                // self::newsletterSendFailed($bdd, $newsletterCampaign, $contact);
                //Reset the connection to abort sending this message
                //The loop will continue trying to send to the reset of the list
                $mail->getSMTPInstance()->reset();
            }
            $mail->clearAddresses();
        }
        $result = new \stdClass();
        $result->success = true;
        $result->sendTo = $mailAddressSend;
        $result->totalSend = $newsletterCampaign->send_total;
        $result->wrongMail = $newsletterCampaign->invalid_contact;


        $bdd = null;
        return $result;
    }
}