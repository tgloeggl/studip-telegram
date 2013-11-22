<?php

require_once dirname(__file__)."/models/MailProcessor.class.php";

class BlubberMail extends StudIPPlugin implements SystemPlugin {
    
    public function __construct() {
        parent::__construct();
        NotificationCenter::addObserver(MailProcessor::getInstance(), "sendBlubberMails", "BlubberHasSaved");
    }
    
}
