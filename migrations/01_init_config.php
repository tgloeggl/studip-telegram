<?php

class InitConfig extends Migration {
    
    function description() {
        return 'initializes the config-entries for this plugin';
    }

    public function up() {
	$config = Config::get();
        if (!isset($config["BLUBBERMAIL_MAILACCOUNT"])) {
            $config->create("BLUBBERMAIL_MAILACCOUNT", array('section' => "Blubbermail", 'is_default' => 'blubb', 'value' => 'blubb', 'type' => "string", 'range' => "global", 'description' => "The account on the mailserver that fetches the incoming blubber-mails and pipes it to Stud.IP. If you have it 'blubb' than emailadresses would look like blubb+fe3756894df2c16fc43c4ec820692ff0@yourserver.com", 'comment' => ""));
        }
        if (!isset($config["BLUBBERMAIL_DELIMITER"])) {
            $config->create("BLUBBERMAIL_DELIMITER", array('section' => "Blubbermail", 'is_default' => '+', 'value' => '+', 'type' => "string", 'range' => "global", 'description' => "Delimiter in the adresses for incoming blubber-mails between the account-name and the ID of the blubber. Only one character please and the + should be perfect for most cases.", 'comment' => ""));
        }
        if (!isset($config["BLUBBERMAIL_DOMAIN"])) {
            $config->create("BLUBBERMAIL_DOMAIN", array('section' => "Blubbermail", 'is_default' => '', 'value' => '', 'type' => "string", 'range' => "global", 'description' => "Domain-part (everything behind the @) of the blubber-mail email-adress. Usually the domain of your Stud.IP, but it could be even something differnt. For example studip.de if your Stud.IP is at develop.studip.de . Whatever is best for your mailserver. If left blank this plugin will take the domain of the Stud.IP.", 'comment' => ""));
        }
        if (!isset($config["BLUBBERMAIL_CREATE_THREADS_ALLOWED"])) {
            $config->create("BLUBBERMAIL_CREATE_THREADS_ALLOWED", array('section' => "Blubbermail", 'is_default' => '1', 'value' => '1', 'type' => "boolean", 'range' => "global", 'description' => "Is it allowed to post a new blubber thread by sending an email to blubb+public@yourserver.com ?", 'comment' => ""));
        }
    }
	
    public function down() {
        Config::get()->delete("BLUBBERMAIL_MAILACCOUNT");
        Config::get()->delete("BLUBBERMAIL_DELIMITER");
        Config::get()->delete("BLUBBERMAIL_DOMAIN");
    }
}