#!/usr/bin/php -q
<?php

require_once dirname(__file__)."/../../../../cli/studip_cli_env.inc.php";
require_once dirname(__file__)."/models/MailProcessor.class.php";

$rawmail = file_get_contents('php://stdin');
$output = MailProcessor::getInstance()->processBlubberMail($rawmail);
mail('ras@fuhse.org','Email Pipe Works!',  $output);