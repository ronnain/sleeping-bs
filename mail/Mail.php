<?php

namespace Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Template\NewsletterTemplate\NewsletterTemplate;
use Template\SubscriptionTemplate\SubscriptionTemplate;

require_once 'vendor/phpmailer/phpmailer/src/Exception.php';
require_once 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once 'vendor/phpmailer/phpmailer/src/SMTP.php';
require_once 'template/NewsletterTemplate.php';
require_once 'template/SubscriptionTemplate.php';

class Mail {

    static function setHeadermail($mail) {
        global $mailHost, $mailPort, $mailAdressServer, $mailPassWord;

        //Server settings
        $mail->isSMTP();
        //Enable SMTP debugging
        // 0 = off (for production use)
        // 1 = client messages
        // 2 = client and server messages
        $mail->SMTPDebug = 0;
        //Ask for HTML-friendly debug output
        $mail->Debugoutput = 'html';
        //Set the hostname of the mail server
        $mail->Host = $mailHost;
        // use
        // $mail->Host = gethostbyname('smtp.gmail.com');
        // if your network does not support SMTP over IPv6
        //Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
        $mail->Port = $mailPort;
        //Set the encryption system to use - ssl (deprecated) or tls
        $mail->SMTPSecure = 'tls';
        //Whether to use SMTP authentication
        $mail->SMTPAuth = true;
        //Username to use for SMTP authentication - use full email address for gmail
        $mail->Username = $mailAdressServer;
        //Password to use for SMTP authentication
        $mail->Password = $mailPassWord;
        //Set who the message is to be sent from
        $mail->setFrom( $mailAdressServer, 'Sommeil Profond');

        //Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
    }

    static function sendBonus($firstName, $subscriberMail, $unsubcribeKey) {

        global $siteWebLink;
        $unsubcribeLink = $siteWebLink.'/desabonnement/'.$unsubcribeKey;

        $mail = new PHPMailer(true);                              // Passing `true` enables exceptions
        try {
            Mail::setHeadermail($mail);
            //Set who the message is to be sent to
            $mail->addAddress($subscriberMail, $firstName);

            $mail->addAttachment('./bonus/Guide_du_bon_dormeur.pdf');

            //Set the subject line
            $mail->Subject = "Bienvenue $firstName - Guide du Bon Dormeur";
            //Read an HTML message body from an external file, convert referenced images to embedded,
            //convert HTML into a basic plain-text alternative body

            $messageHTML =  'Bonjour '.$firstName.',<br/>
            <br/>
            Merci pour ton inscription.<br/>
            <br/>
            À travers mon blog et ce guide du Bon Dormeur, j\'espère pouvoir t\'apporter les réponses que tu cherches.<br/>
            <br/>
            Passe une bonne nuit,<br/>
            Romain';

            $mail->Body = SubscriptionTemplate::get($messageHTML, $unsubcribeLink, $subscriberMail);

            //Replace the plain text body with one created manually
            $messageText = "Bonjour ".$firstName.",

            Merci pour ton inscription.

            À travers mon blog et ce guide du Bon Dormeur, j'espère pouvoir t'apporter les réponses que tu cherches.

            Passe une bonne nuit,

            Romain

            Lien de désabonnement:".$unsubcribeLink;


            $mail->AltBody = $messageText;

            $mail->send();

            $result = new \stdClass();
            $result->success = true;

            echo json_encode($result);

        } catch (Exception $e) {
            echo 'Message could not be sent.';
            echo 'Mailer Error: ' . $mail->ErrorInfo;
        }
    }

    static function sendTextMailToAll($subject, $body, $contacts, $altBody) {

        global $siteWebLink;

        $mail = new PHPMailer(true);
        Mail::setHeadermail($mail);
        $mail->SMTPKeepAlive = true;

        $mail->CharSet = 'UTF-8';
        //Set the subject line
        $mail->Subject = $subject;

        $mailAddressSend = [];
        $wrongMailAddress = [];

        foreach ($contacts as $contact){
            $subscriberMail = $contact["mail"];
            if (array_search ($subscriberMail, $mailAddressSend) !== false) {
                continue;
            }
            try {
                $mail->addAddress($subscriberMail, $contact["firstName"]);
                $unsubcribeLink = $siteWebLink.'/desabonnement/'.$contact["unsubscribe"];
                $mail->Body = NewsletterTemplate::get($body, $unsubcribeLink, $subscriberMail);
                $mail->AltBody = "$altBody \n\n $unsubcribeLink";
            } catch (Exception $e) {
                array_push($wrongMailAddress, $subscriberMail);
                echo 'Invalid address skipped: ' . htmlspecialchars($subscriberMail) . '<br>';
                continue;
            }
            try {
                if ($mail->send()) {
                    array_push($mailAddressSend, $subscriberMail);
                } else {
                    array_push($wrongMailAddress, $subscriberMail);
                }
                break;
            } catch (Exception $e) {
                echo 'Mailer Error (' . htmlspecialchars($subscriberMail) . ') ' . $mail->ErrorInfo . '<br>';
                //Reset the connection to abort sending this message
                //The loop will continue trying to send to the reset of the list
                $mail->getSMTPInstance()->reset();
            }
            $mail->clearAddresses();
        }
        $result = new \stdClass();
        $result->success = true;
        $result->sendTo = $mailAddressSend;
        $result->totalSend = sizeof($mailAddressSend);
        $result->wrongMail = $wrongMailAddress;

        echo json_encode($result);
    }

    static function sendSubNotification($firstName, $email, $referer, $browser) {
        $mail = new PHPMailer(true);
        Mail::setHeaderMail($mail);
        global $mailAdressServer;
        $mail->setFrom( $mailAdressServer, 'Bot Sommeil Profond');
        $mail->SMTPKeepAlive = true;

        $mail->CharSet = 'UTF-8';
        //Set the subject line

        $mail->Subject = "New sub : $firstName";
        $mail->Body = "You receive a new sub from ". $firstName.": ".$email.".<br/><br>
        From the page : ". $referer ."<br/><br/>
        Browser : ". $browser . "<br/><br/>
        Putain continue comme ça! Tu gères ! :D";

        $mail->AltBody = "You receive a new sub from $firstName ($email).\n
        From the page : ". $referer .".\n\n
        Browser : ". $browser . "\n\n
        Putain continue comme ça! Tu gères ! :D";

        $mail->addAddress('romain.geffrault+sub@gmail.com', 'Romain');

        try {
            $mail->send();
        } catch (Exception $e) {
            //Reset the connection to abort sending this message
            //The loop will continue trying to send to the rest of the list
            $mail->getSMTPInstance()->reset();
        }
        $mail->clearAddresses();
    }

    static function sendContactProblem($email, $message) {
        $mail = new PHPMailer(true);
        Mail::setHeaderMail($mail);
        global $mailAdressServer;
        $mail->setFrom( $mailAdressServer, 'Bot Sommeil Profond');
        $mail->SMTPKeepAlive = true;

        $mail->CharSet = 'UTF-8';
        //Set the subject line

        $mail->Subject = "New problem";
        $mail->Body = "You receive a new problem from : ".$email.".<br/><br>".
        $message;

        $mail->AltBody = "You receive a new problem from : ".$email.".\n\n".
        $message;

        $mail->addAddress('romain.geffrault+contactproblem@gmail.com', 'Romain');

        try {
            $mail->send();
        } catch (Exception $e) {
            //Reset the connection to abort sending this message
            //The loop will continue trying to send to the rest of the list
            $mail->getSMTPInstance()->reset();
        }
        $mail->clearAddresses();
    }

    static function sendCommentNotification($firstName) {
        $mail = new PHPMailer(true);
        Mail::setHeadermail($mail);
        global $mailAdressServer;
        $mail->setFrom( $mailAdressServer, 'Bot Sommeil Profond');
        $mail->SMTPKeepAlive = true;

        $mail->CharSet = 'UTF-8';
        //Set the subject line

        $mail->Subject = "New comment : $firstName";
        $mail->Body = "You receive a new comment from ". $firstName.".<br/><br>
        From the page :". $_SERVER['HTTP_REFERER'].".<br/><br/>
        C'est bon signe mon gars !";

        $mail->AltBody = "You receive a new comment from". $firstName.".\n\n
        From the page :". $_SERVER['HTTP_REFERER'].".\n\n
        C'est bon signe mon gars !";

        $mail->addAddress('romain.geffrault+sub@gmail.com', 'Romain');

        try {
            $mail->send();
        } catch (Exception $e) {
            //Reset the connection to abort sending this message
            //The loop will continue trying to send to the rest of the list
            $mail->getSMTPInstance()->reset();
        }
        $mail->clearAddresses();
    }

    static function sendMailMessage($firstName, $email, $message) {
        $mail = new PHPMailer(true);
        Mail::setHeadermail($mail);
        global $mailAdressServer;
        $mail->setFrom( $mailAdressServer, 'Message Sommeil Profond');
        $mail->SMTPKeepAlive = true;

        $mail->CharSet = 'UTF-8';
        //Set the subject line

        $mail->Subject = "New message from : $firstName $email";
        $mail->Body = $message;
        $mail->AltBody = $message;

        $mail->addAddress('romain.geffrault@gmail.com', 'Sommeil Profond');
        $isSend = false;
        try {
        $isSend = $mail->send();
        } catch (Exception $e) {
            //Reset the connection to abort sending this message
            //The loop will continue trying to send to the rest of the list
            $mail->getSMTPInstance()->reset();
            $isSend = false;
        }
        $mail->clearAddresses();

        return $isSend;
    }
}


?>