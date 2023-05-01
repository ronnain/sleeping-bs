<?php

namespace Template\NewsletterTemplate;

class NewsletterTemplate {

    static function get($body, $unsubcribeLink, $subsriberMail) {
        $template = file_get_contents('template/newsletter.html');

        return preg_replace([
            '/{{\s*\$body\s*}}/',
            '/{{\s*\$unsubcribeLink\s*}}/',
            '/{{\s*\$subsriberMail\s*}}/',
        ], [
            $body,
            $unsubcribeLink,
            $subsriberMail
        ], $template);
    }

}