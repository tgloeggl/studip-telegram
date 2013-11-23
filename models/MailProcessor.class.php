<?php

require_once dirname(__file__)."/vendor/PlancakeEmailParser.php";

class MailProcessor {
    
    static public function getInstance() {
        $processor = new MailProcessor();
        return $processor;
    }
    
    public function __construct() {
        //init configs
    }
    
    public function sendBlubberMails(BlubberPosting $blubber) {
        
    }
    
    public function processBlubberMail($rawmail) {
        $mail = new PlancakeEmailParser($rawmail);
        $frommail = $mail->getHeader("From");
        $frommail = preg_match(email_validation_class::$email_regular_expression, $frommail);
        $frommail = $frommail[0];
        return $frommail;
    }
}