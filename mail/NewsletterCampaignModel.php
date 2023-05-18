<?php
namespace Mail;

use Contact\ContactModel;


class NewsLetterCampaignModel {
    public int $id;
    public string $subject;

    public string $content;
    /*type Datetime*/
    public string $creation_date;
    public array $invalid_contact;
    /** @var ContactModel[] $contacts */
    public array $contacts;
    public int $send_total;
    public int $not_send_total;
}
