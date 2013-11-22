<?php

require_once dirname(__file__)."/../../../../cli/studip_cli_env.inc.php";
require_once dirname(__file__)."/models/MailProcessor.class.php";

$emailContent = file_get_contents('php://stdin');
mail('ras@fuhse.org','Email Pipe Works!',  $emailContent);