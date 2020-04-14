<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';
require 'config.php';

function setHeaderMail($mail) {
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

function sendBonus($firstName, $mailAdresse, $unsubcribeKey) {

    global $siteWebLink;

    $mail = new PHPMailer(true);                              // Passing `true` enables exceptions
    try {
        setHeaderMail($mail);
        //Set who the message is to be sent to
        $mail->addAddress($mailAdresse, $firstName);

        $mail->addAttachment('./bonus/Sommeil_Profond_Bonus.pdf');

        //Set the subject line
        $mail->Subject = "Bienvenue $firstName - Guide du Bon Dormeur";
        //Read an HTML message body from an external file, convert referenced images to embedded,
        //convert HTML into a basic plain-text alternative body

        $messageHTML =  'Bonjour '.$firstName.',<br/>
        <br/>
        Merci pour ton inscription.<br/>
        <br/>
        À travers mon blog et ce bonus, j\'espère pouvoir t\'apporter les réponses que tu cherches.<br/>
        <br/>
        Si tu rencontres toujours des problèmes après avoir mis en pratique le Programme du Bon Dormeur, c\'est sans doute que comme moi, tu as un sommeil fragile.<br/>
        <br/>
        Ne t\'en fais pas, après 20 ans sans avoir dormi correctement, j\'ai enfin retrouvé un super sommeil.<br/>
        <br/>
        C\'est à la porté de tous !<br/>
        <br/>
        Pour t\'aider davantage, regarde les services que je propose.<br/>
        <br/>
        Tu peux aussi naviguer dans mes différents articles, tu trouveras peut-être une réponse.<br/>
        <br/>
        À bientôt,<br/>
        Romain<br/>
        <br/>
        <br/>
        <a href= "'.$siteWebLink.'/desabonnement/'.$unsubcribeKey.'">se désabonner</a>';

        $mail->Body = $messageHTML;

        //Replace the plain text body with one created manually
        $messageText = "Bonjour ".$firstName.",

        Merci pour ton inscription.

        À travers mon blog et ce bonus, j'espère pouvoir t'apporter les réponses que tu cherches.

        Si tu rencontres toujours des problèmes après avoir mis en pratique le Programme du Bon Dormeur, c'est sans doute que comme moi, tu as un sommeil fragile.

        Ne t'en fais pas, après 20 ans sans avoir dormi correctement, j'ai enfin retrouvé un super sommeil.

        C'est à la porté de tous !

        Pour t'aider davantage, regarde les services que je propose.

        Tu peux aussi naviguer dans mes différents articles, tu trouveras peut-être une réponse.

        À bientôt,

        Romain

        Lien de désabonnement:".$siteWebLink."/desabonnement/".$unsubcribeKey;

        $mail->AltBody = $messageText;

        $mail->send();
        echo '{ "success": true }';
    } catch (Exception $e) {
        echo 'Message could not be sent.';
        echo 'Mailer Error: ' . $mail->ErrorInfo;
    }
}

function sendTextMailToAll($subject, $body, $contacts, $altBody) {
    global $siteWebLink;

    $mail = new PHPMailer(true);
    setHeaderMail($mail);
    $mail->SMTPKeepAlive = true;

    $mail->CharSet = 'UTF-8';
    //Set the subject line
    $mail->Subject = $subject;
    $mail->AltBody = $body;

    $mailAdressesSend = array();

    foreach ($contacts as $contact){
        if (array_search ($contact["mail"], $mailAdressesSend) !== false) {
            continue;
        }
        try {
            $mail->addAddress($contact["mail"], $contact["firstName"]);
            $unsubcribeLink = '<br/><br/><a href= "'.$siteWebLink.'/desabonnement/'.$contact["unsubscribe"].'">se désabonner</a>';
            $mail->Body = $body . $unsubcribeLink;
            $mail->AltBody = $altBody;
        } catch (Exception $e) {
            echo 'Invalid address skipped: ' . htmlspecialchars($contact["mail"]) . '<br>';
            continue;
        }
        try {
            $mail->send();
            array_push($mailAdressesSend, $contact["mail"]);
        } catch (Exception $e) {
            echo 'Mailer Error (' . htmlspecialchars($contact["mail"]) . ') ' . $mail->ErrorInfo . '<br>';
            //Reset the connection to abort sending this message
            //The loop will continue trying to send to the rest of the list
            $mail->getSMTPInstance()->reset();
        }
        $mail->clearAddresses();
    }
    echo '{ "success": true }';
}
?>