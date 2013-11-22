<?php

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
    
    public function fetchBlubberMails() {
        
    }
}