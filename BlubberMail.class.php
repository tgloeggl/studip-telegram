<?php

require_once dirname(__file__)."/models/MailProcessor.class.php";

class BlubberMail extends StudIPPlugin implements SystemPlugin {
    
    public function __construct() {
        parent::__construct();
        if ($GLOBALS['user']->id !== "nobody") {
            if (!Navigation::hasItem("/links/settings/blubber")) {
                $settings_tab = new Navigation(_("Blubber"), PluginEngine::getURL($this, array(), "settings"));
                Navigation::addItem("/links/settings/blubber", $settings_tab);
            }
            $settings_tab = new AutoNavigation(_("Mails"), PluginEngine::getURL($this, array(), "settings"));
            Navigation::addItem("/links/settings/blubber/mails", $settings_tab);
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
        PageLayout::setTabNavigation('/links/settings');
        $template = $this->getTemplate("mails.php");
        $template->set_attribute("streams", BlubberStream::findMine());
        $template->set_attribute("abo_streams", $config->getValue("BLUBBER_USER_STREAM_ABO"));
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
            $template->set_layout($GLOBALS['template_factory']->open($layout === "without_infobox" ? 'layouts/base_without_infobox' : 'layouts/base'));
        }
        return $template;
    }
}
