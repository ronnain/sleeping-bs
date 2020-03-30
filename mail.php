<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';
require 'config.php';

function sendBonus($firstName, $mailAdresse, $unsubcribeKey) {

    global $mailHost, $mailPort, $mailAdressServer, $mailPassWord, $siteWebLink;

    $mail = new PHPMailer(true);                              // Passing `true` enables exceptions
    try {
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
        //Set an alternative reply-to address
        //$mail->addReplyTo('replyto@example.com', 'First Last');

        //Set who the message is to be sent to
        $mail->addAddress($mailAdresse, $firstName);

        //$mail->addAttachment('./bonus.txt', 'NewBonus.txt');

        //Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        //Set the subject line
        $mail->Subject = "Bienvenue $firstName - Bonus Gratuit";
        //Read an HTML message body from an external file, convert referenced images to embedded,
        //convert HTML into a basic plain-text alternative body
        $messageHTML =  'Bonjour '.$firstName.',<br/>
        <br/>
        Merci pour ton inscription.<br/>
        À travers mon blog et ce bonus, j\'espère pouvoir t\'apporter les réponses que tu cherches.<br/>
        <br/>
        Excuse-moi, mais je n\'ai pas encore eu le temps de terminer la rédaction du bonus.<br/>
        Le site est très récent (28/03/2020).<br/>
        Je vais le terminer pour le dimanche 05/04/20.<br/>
        Tu recevras un mail avec le bonus.<br/>
        <br/>
        A+<br/>
        Romain<br/>
        <br/>
        <br/>
        <a href= "'.$siteWebLink.'/desabonnement/'.$unsubcribeKey.'">se désabonner</a>';

        $mail->Body = $messageHTML;

        //Replace the plain text body with one created manually
        $messageText = "Bonjour ".$firstName.",
        Merci pour ton inscription.
        À travers mon blog et ce bonus, j'espère pouvoir t'apporter les réponses que tu cherches.

        Excuse-moi, mais je n'ai pas encore eu le temps de terminer la rédaction du bonus.
        Le site est très récent (28/03/2020).
        Je vais le terminer pour le dimanche 05/04/20.
        Tu recevras un mail avec le bonus.

        Lien de désabonnement:".$siteWebLink."/desabonnement/".$unsubcribeKey;

        $mail->AltBody = $messageText;

        $mail->send();
        echo '{ "success": true }';
    } catch (Exception $e) {
        echo 'Message could not be sent.';
        echo 'Mailer Error: ' . $mail->ErrorInfo;
    }
}
?>