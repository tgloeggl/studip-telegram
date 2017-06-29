<?php

require_once dirname(__file__)."/models/MailProcessor.class.php";
require __DIR__ . '/vendor/autoload.php';

class BlubberMail extends StudIPPlugin implements SystemPlugin {

    public function __construct() {
        parent::__construct();
        if ($GLOBALS['user']->id !== "nobody") {
            if (!Navigation::hasItem("/profile/settings/blubber")) {
                $settings_tab = new Navigation(_("Blubber"), PluginEngine::getURL($this, array(), "settings"));
                Navigation::addItem("/profile/settings/blubber", $settings_tab);
            }
            $settings_tab = new AutoNavigation(_("Mails"), PluginEngine::getURL($this, array(), "settings"));
            Navigation::addItem("/profile/settings/blubber/mails", $settings_tab);
        }
        NotificationCenter::addObserver(MailProcessor::getInstance(), "sendBlubberMails", "PostingHasSaved");
    }

    public function settings_action() {
        $config = UserConfig::get($GLOBALS['user']->id);
        if (Request::isPost()) {
            $config->store('BLUBBER_MAX_USER_NOTIFICATIONS', Request::option("BLUBBER_MAX_USER_NOTIFICATIONS"));
            $delete_statement = DBManager::get()->prepare("
                DELETE FROM blubbermail_abos WHERE user_id = ? AND stream_id NOT IN (?)
            ");
            $delete_statement->execute(array($GLOBALS['user']->id, Request::getArray("streams")));
            foreach (Request::getArray("streams") as $stream_id) {
                $add_statement = DBManager::get()->prepare("
                    INSERT IGNORE INTO blubbermail_abos
                    SET user_id = :user_id,
                        stream_id = :stream_id,
                        last_update = UNIX_TIMESTAMP()
                ");
                $add_statement->execute(array(
                    'user_id' => $GLOBALS['user']->id,
                    'stream_id' => $stream_id,
                ));
            }
            PageLayout::postMessage(MessageBox::success(_("Daten erfolgreich gespeichert.")));
        }
        $get_abos = DBManager::get()->prepare("
            SELECT stream_id FROM blubbermail_abos WHERE user_id = ?
        ");
        $get_abos->execute(array($GLOBALS['user']->id));
        $abo_streams = $get_abos->fetchAll(PDO::FETCH_COLUMN, 0);
        $template = $this->getTemplate("mails.php");
        $template->set_attribute("streams", BlubberStream::findMine());
        $template->set_attribute("abo_streams", $abo_streams);
        echo $template->render();
    }

    protected function getTemplate($template_file_name, $layout = "without_infobox") {
        if (!$this->template_factory) {
            $this->template_factory = new Flexi_TemplateFactory(dirname(__file__)."/templates");
        }
        $template = $this->template_factory->open($template_file_name);
        if ($layout) {
            if (method_exists($this, "getDisplayName")) {
                PageLayout::setTitle($this->getDisplayName());
            } else {
                PageLayout::setTitle(get_class($this));
            }
            $template->set_layout($GLOBALS['template_factory']->open('layouts/base'));
        }
        return $template;
    }

    public function setHook_action($api_key, $bot_name)
    {
        // $API_KEY = '388534691:AAHAe6amae5e8rSpQuGyuK2B3ltnyZuiZGE';
        // $BOT_NAME = 'studip_bot';
        // $hook_url = 'https://messenger.smartsh.it/tel_hook.php';

        $hook_url = URLHelper::getLink($GLOBALS['ABSOLUTE_URI_STUDIP'] . 'plugins.php/blubbermail/webhook/' . $bot_name);

        $stmt = DBManager::get()->prepare("INSERT IGNORE INTO telegram_bot_config (botname, api_key, owner_id, webhook) VALUES (?, ?, ?, ?)");
        $stmt->execute(array($bot_name, $api_key, $GLOBALS['user']->id, $hook_url));

        die;
        try {
            // Create Telegram API object
            $telegram = new Longman\TelegramBot\Telegram($api_key, $bot_name);

            // Set webhook
            $result = $telegram->setWebhook($hook_url);
            if ($result->isOk()) {
                echo $result->getDescription();
            }
        } catch (Longman\TelegramBot\Exception\TelegramException $e) {
            echo $e;
        }

        die;
    }

    public function webhook_action($bot_name)
    {
        // $api_key = '388534691:AAHAe6amae5e8rSpQuGyuK2B3ltnyZuiZGE';

        $stmt_log = DBManager::get()->prepare("INSERT INTO telegram_log (data) VALUES (?)");
        $stmt_log->execute(array("Aufruf des Webhooks: \n\n" . $bot_name . "\n\n" . print_r($_REQUEST, 1) . "\n\n" .print_r($_SERVER, 1)));

        $stmt = DBManager::get()->prepare("SELECT api_key FROM telegram_bot_config WHERE botname = ?");
        $stmt->execute(array($bot_name));
        $api_key = $stmt->fetchColumn();

        try {
            // Create Telegram API object
            $telegram = new Longman\TelegramBot\Telegram($api_key, $bot_name);

            // Handle telegram webhook request
            $telegram->handle();
        } catch (Longman\TelegramBot\Exception\TelegramException $e) {
            // Silence is golden!
            // log telegram errors
            $stmt_log->execute(array('Fehler im Webhook: ' . $e));
            // echo $e;
        }
    }
}
