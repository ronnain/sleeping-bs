<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';
require 'config.php';

function sendBonus($firstName, $mailAdresse, $unsubcribeKey) {

    global $mailHost, $mailPort, $mailAdressServer, $mailPassWord;

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
        $mail->setFrom( $mailAdressServer, 'Romain');
        //Set an alternative reply-to address
        //$mail->addReplyTo('replyto@example.com', 'First Last');

        //Set who the message is to be sent to
        $mail->addAddress($mailAdresse, $firstName);

        $mail->addAttachment('./bonus.txt', 'NewBonus.txt');

        //Content
        $mail->isHTML(true);
        //Set the subject line
        $mail->Subject = "Bienvenue $firstName - Bonus Gratuit";
        //Read an HTML message body from an external file, convert referenced images to embedded,
        //convert HTML into a basic plain-text alternative body
        $mail->Body    = 'Bla bla bla vous trouverez ci-joint le bonus gratuit <b>in bold!</b><br>
        <a href="http://localhost:4200/desabonnement/'.$unsubcribeKey.'">se désabonner</a>';
        //Replace the plain text body with one created manually
        $mail->AltBody = 'Ceci est un message texte';

        $mail->send();
        echo '{ "success": true }';
    } catch (Exception $e) {
        echo 'Message could not be sent.';
        echo 'Mailer Error: ' . $mail->ErrorInfo;
    }
}
?>