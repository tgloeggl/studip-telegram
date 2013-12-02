<?php

class SendMailTreads extends CronJob
{
    /**
     * Returns the name of the cronjob.
     */
    public static function getName()
    {
        return _('Neue Blubber-Threads an Nutzer verschicken.');
    }

    /**
     * Returns the description of the cronjob.
     */
    public static function getDescription()
    {
        return _('Nutzer können Blubber-Streams unter Einstellungen->Blubber->Mails abonieren. Mit diesem Cronjob wird gesichert, dass neue Blubber-Threads (keine Kommentare) verschickt werden.');
    }

    /**
     * Setup method. Loads neccessary classes and checks environment. Will
     * bail out with an exception if environment does not match requirements.
     */
    public function setUp()
    {
        require_once 'lib/language.inc.php';
        require_once 'lib/functions.php';
        require_once 'lib/classes/StudipMail.class.php';
        require_once 'lib/classes/User.class.php';
        require_once 'lib/classes/URLHelper.php';
        require_once dirname(__file__)."/../../core/Blubber/models/BlubberStream.class.php";
        require_once dirname(__file__)."/models/MailProcessor.class.php";
    }

    /**
     * Return the paremeters for this cronjob.
     *
     * @return Array Parameters.
     */
    public static function getParameters()
    {
        return array();
    }

    /**
     * Executes the cronjob.
     *
     * @param mixed $last_result What the last execution of this cronjob
     *                           returned.
     * @param Array $parameters Parameters for this cronjob instance which
     *                          were defined during scheduling.
     *                          Only valid parameter at the moment is
     *                          "verbose" which toggles verbose output while
     *                          purging the cache.
     */
    public function execute($last_result, $parameters = array())
    {
        $statement = DBManager::get()->prepare("
            SELECT DISTINCT user_id FROM blubbermail_abos
        ");
        $statement->execute();
        $user_ids = $statement->fetchAll(PDO::FETCH_COLUMN, 0);
        foreach ($user_ids as $user_id) {
            $sent_thread_ids = array();
            $stream_statement = DBManager::get()->prepare("
                SELECT * FROM blubbermail_abos WHERE user_id = ?
            ");
            $stream_statement->execute(array($user_id));
            foreach ($stream_statement->fetchAll(PDO::FETCH_ASSOC) as $stream_abo) {
                if ($stream_abo['stream_id'] === "global") {
                    $stream = BlubberStream::getGlobalStream($user_id);
                } else {
                    $stream = new BlubberStream($stream_abo['stream_id']);
                }
                $threads = $stream->fetchThreads(0, 50);
                foreach ($threads as $thread) {
                    if (!in_array($thread->getId(), $sent_thread_ids) 
                            && $thread['mkdate'] >= $stream_abo['last_update']
                            && !($thread['user_id'] === $user_id && $thread['external_contact'] == 0)) {
                        //send thread to user_id
                        $body = $thread['description'];
                        $body .= "\n\n"._("Stud.IP verschickt Ihnen Antworten auf Ihre Blubber bzw. Kommentare per Mail. Wenn Sie das abstellen oder konfigurieren wollen, melden Sie sich in Stud.IP an und gehen Sie auf folgende URL:\n");
                        $body .= URLHelper::getURL("plugins.php/blubbermail/settings");
                        $body .= "\n\n";
                        $user = new User($user_id);
                        $reply_mail = MailProcessor::getInstance()->getReplyMail($thread->getId());
                        $mail = new StudipMail();
                        $mail->setSubject("Re: ".$thread['name']);
                        $mail->setSenderName($thread->getUser()->getName());
                        $mail->setSenderEmail($reply_mail);
                        $mail->setReplyToEmail($reply_mail);
                        $mail->setBodyText($body);
                        $mail->addRecipient($user['Email'], $user['Vorname']." ".$user['Nachname']);
                        return;
                        if (!get_config("MAILQUEUE_ENABLE")) {
                            $mail->send();
                        } else {
                            MailQueueEntries::add($mail, null, $user_id);
                        }
                        restoreLanguage();
                        
                        $sent_thread_ids[] = $thread->getId();
                    } //else we already sent it.
                }
                $update_last_update_time = DBManager::get()->prepare("
                    UPDATE blubbermail_abos 
                    SET last_update = UNIX_TIMESTAMP()
                    WHERE user_id = :user_id
                        AND stream_id = :stream_id
                ");
                $update_last_update_time->execute(array(
                    'user_id' => $user_id,
                    'stream_id' => $stream_abo['stream_id']
                ));
            }
        }
    }
}
