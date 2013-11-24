#!/usr/bin/php -q
<?php

require_once dirname(__file__)."/../../../../cli/studip_cli_env.inc.php";
require_once dirname(__file__)."/models/MailProcessor.class.php";

$rawmail = file_get_contents('php://stdin');
try {
    MailProcessor::getInstance()->processBlubberMail($rawmail);
} catch(Exception $e) {
    echo $e->getMessage();
    exit(69);
}