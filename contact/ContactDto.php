<?php
namespace Contact;

class ContactDto {
    public int $id;
    public string $firstName;
    public string $mail;
    public string $creationDate; // Modification de type en string
    public string $unsubscribe;
    public bool $subscribe;
    public ?string $unsubscribeDate;
    public ?string $source;
    public ?string $browser;

    public function __construct(
        int $id,
        string $firstName,
        string $mail,
        string $creationDate, // Modification de type en string
        string $unsubscribe,
        bool $subscribe,
        ?string $unsubscribeDate = '',
        ?string $source = '',
        ?string $browser = ''
    ) {
        $this->id = $id;
        $this->firstName = $firstName;
        $this->mail = $mail;
        $this->creationDate = $creationDate;
        $this->unsubscribe = $unsubscribe;
        $this->subscribe = $subscribe;
        $this->unsubscribeDate = $unsubscribeDate;
        $this->source = $source;
        $this->browser = $browser;
    }
}