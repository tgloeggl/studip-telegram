<?php

require_once dirname(__file__)."/BlubberMailParser.class.php";
// require_once dirname(__file__)."/../../../core/Blubber/models/BlubberPosting.class.php";

class MailProcessor {

    static private $instance = null;

    //These three variables may be overwritten by local configs. See constructor.
    protected $mailaccount = "blubb";
    protected $delimiter   = "+";
    protected $maildomain  = null;

    static public function getInstance() {
        if (self::$instance === null) {
            self::$instance = new MailProcessor();
            bindtextdomain("blubbermail", dirname(__file__)."/../locale");
        }
        return self::$instance;
    }

    public function __construct() {
        $this->maildomain = $_SERVER['SERVER_NAME'];
        if (strtolower(substr($this->maildomain, 0, 4)) === "www.") {
            $this->maildomain = substr($this->maildomain, 4);
        }
        //init configs
        $mailaccount = get_config("BLUBBERMAIL_MAILACCOUNT");
        if ($mailaccount) {
            $this->mailaccount = $mailaccount;
        }
        $delimiter = get_config("BLUBBERMAIL_DELIMITER");
        if ($delimiter) {
            $this->delimiter = $delimiter;
        }
        $maildomain = get_config("BLUBBERMAIL_DOMAIN");
        if (trim($maildomain)) {
            $this->maildomain = $maildomain;
        }
    }

    public function getReplyMail($thread_id) {
        return $this->mailaccount.$this->delimiter.$thread_id."@".$this->maildomain;
    }

    public function sendBlubberMails($event, BlubberPosting $blubber) {
        if (!$blubber['user_id'] || !$blubber['description'] || $blubber['root_id'] === $blubber->getId()) {
            return;
        }
        $thread = new BlubberPosting($blubber['root_id']);
        $author = $blubber->getUser();
        $reply_mail = $this->getReplyMail($blubber['root_id']);
        $recipients_statement = DBManager::get()->prepare(
            "SELECT DISTINCT user_id " .
            "FROM blubber " .
            "WHERE root_id = :thread_id " .
                "AND external_contact = '0' " .
                "AND user_id != :author_id " .
        "");
        $recipients_statement->execute(array(
            'thread_id' => $blubber['root_id'],
            'author_id' => $blubber['user_id']
        ));
        $recipient_ids = $recipients_statement->fetchAll(PDO::FETCH_COLUMN, 0);

        foreach ($recipient_ids as $user_id) {
            if ($this->userWantsMail($user_id, $blubber['root_id'])) {
                $recipient = new User($user_id);
                $body = $this->getMailText($blubber, $user_id);

                $mail = new StudipMail();
                $mail->setSubject("Re: ".$thread['name']);
                $mail->setSenderName($author->getName());
                $mail->setSenderEmail($reply_mail);
                $mail->setReplyToEmail($reply_mail);
                $mail->setBodyText($body);
                $mail->addRecipient($recipient['Email'], $recipient['Vorname']." ".$recipient['Nachname']);
                if (!get_config("MAILQUEUE_ENABLE")) {
                    $mail->send();
                } else {
                    MailQueueEntries::add($mail, null, $user_id);
                }
            }
        }
    }

    protected function userWantsMail($user_id, $thread_id) {
        $max_notifications = UserConfig::get($user_id)->getValue("BLUBBER_MAX_USER_NOTIFICATIONS");
        if ($max_notifications === "all" OR $max_notifications === null) {
            return true;
        } elseif($max_notifications > 0) {
            $statement = DBManager::get()->prepare(
                "SELECT COUNT(*) " .
                "FROM blubber " .
                "WHERE root_id = :thread_id " .
                    "AND mkdate > (SELECT mkdate FROM blubber WHERE root_id = :thread_id AND user_id = :user_id ORDER BY mkdate DESC LIMIT 1) " .
            "");
            $statement->execute(array(
                'thread_id' => $thread_id,
                'user_id' => $user_id
            ));
            $new_blubbers = $statement->fetch(PDO::FETCH_COLUMN, 0);
            return ($max_notifications >= $new_blubbers);
        } else {
            return false;
        }
    }

    public function processBlubberMail($rawmail) {
        $email_regular_expression='/([-+.0-9=?A-Z_a-z{|}~])+@([-.0-9=?A-Z_a-z{|}~])+\.[a-zA-Z]{2,6}/i';
        $success = false;
        $mail = new BlubberMailParser($rawmail);
        $from = $mail->getHeader("From");
        preg_match($email_regular_expression, $from, $matches);
        $frommail = $matches[0];

        foreach (explode(",", $mail->getHeader("To").",".$mail->getHeader("CC")) as $recipient) {
            $me = preg_match('/'.$this->mailaccount.'['.$this->delimiter.']([^@]*)@'.$this->maildomain.'/i', $recipient, $matches);
            if ($me) {
                $thread_id = $matches[1];
            }
        }
        switch ($thread_id) {
            case "public":
                $thread = new BlubberPosting();
                $thread['context_type'] = "public";
                break;
            case "private":
                $thread = new BlubberPosting();
                $thread['context_type'] = "private";
                break;
            default:
                $thread = new BlubberPosting($thread_id ? $thread_id : null);
        }
        $author = User::findBySQL("Email = ?", array($frommail));
        $author = $author[0];
        if (!$author) {
            throw new AccessDeniedException("Emailadress not registered. Maybe you should try to send this with another email-adress?");
        }
        $body = $this->transformBody(studip_utf8decode($mail->getTextBody()));
        if (!$thread->isNew() && $thread->isThread()) {
            //Rechtecheck TODO
            $check = false;
            switch ($thread['context_type']) {
                case "public":
                    $check = true;
                    break;
                case "course":
                    if ($GLOBALS['perm']->have_perm("admin", $author['user_id'])) {
                        $check = true;
                    } else {
                        $statement = DBManager::get()->prepare(
                            "SELECT 1 " .
                            "FROM seminar_user " .
                            "WHERE Seminar_id = :seminar_id " .
                                "AND user_id = :user_id " .
                                "AND status IN ('autor','tutor','dozent') " .
                        "");
                        $statement->execute(array('user_id' => $author['user_id'], 'seminar_id' => $thread['Seminar_id']));
                        $check = (bool) $statement->fetch(PDO::FETCH_COLUMN, 0);
                    }
                    break;
                case "private":
                    $related_users = $thread->getRelatedUsers();
                    $check = in_array($author['user_id'], $related_users);
                    break;
            }
            if ($check && $body) {
                //Anhänge hinzufügen:
                $body = $this->appendAttachments($body, $mail->getAttachments(), $author, $thread);
                //Blubber hinzufügen:
                $old_fake_root = $GLOBALS['user'];
                $faked_root = new User();
                $faked_root->user_id = $author['user_id'];
                $faked_root->username = 'cli';
                $faked_root->perms = 'root';
                $GLOBALS['user'] = new Seminar_User($faked_root);
                BlubberPosting::$mention_posting_id = $thread->getId();
                StudipTransformFormat::addStudipMarkup("mention1", '@\"[^\n\"]*\"', "", "BlubberPosting::mention");
                StudipTransformFormat::addStudipMarkup("mention2", '@[^\s]*[\d\w_]+', "", "BlubberPosting::mention");
                $body = transformBeforeSave($body);
                $GLOBALS['user'] = $old_fake_root;

                $comment = new BlubberPosting();
                $comment['description'] = $body;
                $comment['name'] = $thread['name'];
                $comment['parent_id'] = $comment['root_id'] = $thread->getId();
                $comment['context_type'] = $thread['context_type'];
                $comment['Seminar_id'] = $thread['Seminar_id'];
                $comment['external_contact'] = 0;
                $comment['user_id'] = $author['user_id'];
                $success = $comment->store();

                if (true) { //to be version-dependend
                    //Notifications:
                    $user_ids = array();
                    if ($thread['user_id'] && $thread['user_id'] !== $comment['user_id']) {
                        $user_ids[] = $thread['user_id'];
                    }
                    foreach ((array) $thread->getChildren() as $child) {
                        if ($child['user_id'] && ($child['user_id'] !== $comment['user_id']) && (!$child['external_contact'])) {
                            $user_ids[] = $child['user_id'];
                        }
                    }
                    $user_ids = array_unique($user_ids);
                    foreach ($user_ids as $user_id) {
                        setTempLanguage($user_id);
                        $avatar = Visibility::verify('picture', $comment['user_id'], $user_id)
                                ? Avatar::getAvatar($comment['user_id'])
                                : Avatar::getNobody();
                        PersonalNotifications::add(
                            $user_id,
                            URLHelper::getURL(
                                "plugins.php/blubber/streams/thread/".$thread->getId(),
                                array('cid' => $thread['context_type'] === "course" ? $thread['Seminar_id'] : null)
                            ),
                            sprintf(_("%s hat einen Kommentar geschrieben"), get_fullname($comment['user_id'])),
                            "posting_".$comment->getId(),
                            $avatar->getURL(Avatar::MEDIUM)
                        );
                        restoreLanguage();
                    }
                }
            } elseif (!$check) {
                throw new AccessDeniedException("You have no permission to comment here or this blubber does not exist anymore.");
            }
        } elseif($thread['context_type'] && get_config("BLUBBERMAIL_CREATE_THREADS_ALLOWED")) {
            //create a new blubber. This is the drop-dead-evil api function to
            //create a public or private blubber-thread with an email.
            $thread->setId($thread->getNewId());

            //transform before save
            BlubberPosting::$mention_posting_id = $thread->getId();
            StudipTransformFormat::addStudipMarkup("mention1", '@\"[^\n\"]*\"', "", "BlubberPosting::mention");
            StudipTransformFormat::addStudipMarkup("mention2", '@[^\s]*[\d\w_]+', "", "BlubberPosting::mention");
            $old_fake_root = $GLOBALS['user'];
            $faked_root = new User();
            $faked_root->user_id = $author['user_id'];
            $faked_root->username = 'cli';
            $faked_root->perms = 'root';
            $GLOBALS['user'] = new Seminar_User($faked_root);
            $body = transformBeforeSave($body);
            $GLOBALS['user'] = $old_fake_root;

            $thread['description'] = $body;
            $thread['name'] = $thread['name'];
            $thread['parent_id'] = 0;
            $thread['root_id'] = $thread->getId();
            $thread['Seminar_id'] = $author['user_id'];
            $thread['external_contact'] = 0;
            $thread['user_id'] = $author['user_id'];
            $success = $thread->store();
            if ($success) {
                $statement = DBManager::get()->prepare(
                    "INSERT IGNORE INTO blubber_mentions " .
                    "SET user_id = :user_id, " .
                        "topic_id = :thread_id, " .
                        "mkdate = UNIX_TIMESTAMP() " .
                "");
                $statement->execute(array(
                    'user_id' => $author['user_id'],
                    'thread_id' => $thread->getId()
                ));
            }
        } else {
            throw new AccessDeniedException("You have no permission to post here or this blubber does not exist anymore.");
        }
        return $success;
    }

    protected function appendAttachments($body, $attachments, $author, $context = null) {
        if (!count($attachments)) {
            return $body;
        }

        $db = DBManager::get();
        $folder_context = $context && $context['context_type'] === "course" ? $context['Seminar_id'] : $author['user_id'];
        $folder_id = md5("Blubber_".$folder_context."_".$author['user_id']);
        $parent_folder_id = md5("Blubber_".$folder_context);
        if (!$context || $context['context_type'] !== "course") {
            $folder_id = $parent_folder_id;
        }
        $folder = $db->query(
            "SELECT * " .
            "FROM folder " .
            "WHERE folder_id = ".$db->quote($folder_id)." " .
        "")->fetch(PDO::FETCH_COLUMN, 0);
        if (!$folder) {
            $folder = $db->query(
                "SELECT * " .
                "FROM folder " .
                "WHERE folder_id = ".$db->quote($parent_folder_id)." " .
            "")->fetch(PDO::FETCH_COLUMN, 0);
            if (!$folder) {
                $db->exec(
                    "INSERT IGNORE INTO folder " .
                    "SET folder_id = ".$db->quote($parent_folder_id).", " .
                        "range_id = ".$db->quote($folder_context).", " .
                        "user_id = ".$db->quote($author['user_id']).", " .
                        "name = ".$db->quote("BlubberDateien").", " .
                        "permission = '7', " .
                        "mkdate = ".$db->quote(time()).", " .
                        "chdate = ".$db->quote(time())." " .
                "");
            }
            if ($context && $context['context_type'] === "course") {
                $db->exec(
                    "INSERT IGNORE INTO folder " .
                    "SET folder_id = ".$db->quote($folder_id).", " .
                        "range_id = ".$db->quote($parent_folder_id).", " .
                        "user_id = ".$db->quote($author['user_id']).", " .
                        "name = ".$db->quote(get_fullname()).", " .
                        "permission = '7', " .
                        "mkdate = ".$db->quote(time()).", " .
                        "chdate = ".$db->quote(time())." " .
                "");
            }
        }

        foreach ($attachments as $attachment) {
            $doc = array();
            $doc['user_id'] = $author['user_id'];
            $doc['name'] = $doc['filename'] = $attachment['filename'] ? studip_utf8decode($attachment['filename']) : md5(uniqid());
            $doc['author_name'] = get_fullname($author['user_id']);
            $doc['seminar_id'] = $folder_context;
            $doc['range_id'] = $context && $context['context_type'] === "course" ? $folder_id : $parent_folder_id;
            $doc['filesize'] = strlen($attachment['content']);
            $temp_name = $GLOBALS['TMP_PATH']."/file_".md5(uniqid());
            file_put_contents($temp_name, $attachment['content']);
            $newfile = StudipDocument::createWithFile($temp_name, $doc);
            if ($newfile) {
                $type = get_mime_type($newfile['filename']);
                $type = substr($type, 0, strpos($type, "/"));
                $url = GetDownloadLink($newfile->getId(), $newfile['filename']);
                if (in_array($type, array("image", "video", "audio"))) {
                    $body = "[".($type !== "image" ? $type : "img")."]".$url . "\n\n" . $body;
                } else {
                    $body .= "\n\n[".$newfile['filename']."]".$url;
                }
            }
        }
        return $body;
    }

    protected function transformBody($body) {
        $body = $this->eraseSignature($body);
        $body = $this->eraseTOFUQuotes($body);
        return trim($body);
    }

    public function eraseSignature($body) {
        if (stripos($body, "\n-- \n") !== false) {
            $body = substr($body, 0, stripos($body, "\n-- \n"));
        }
        return $body;
    }

    /**
     * Most people are answering mails by quoting the whole discussion on the bottom
     * of the email. This is called TOFU. In Blubber, we always have the context
     * of a message and display it directly. So we don't need this TOFU-stuff and
     * want to get rid of them.
     * @param string $body : plaint text body message of the email
     * @return string : altered body message.
     */
    public function eraseTOFUQuotes($body) {
        do {
            $old_body = $body;
            $body = trim($body);
            $body = preg_replace('/\n(\s*Am (.*):\s*\n)?(>.*\n?)+\Z/i', "", $body);
        } while($old_body !== $body);
        return $body;
    }

    public function getMailText($blubber, $user_id) {
        setTempLanguage($user_id);
        $body = $blubber['description'];
        //vorherigen Blubber zitieren:
        if ($blubber['root_id'] !== $blubber->getId()) {
            $thread = new BlubberPosting($blubber['root_id']);
            $before_blubb = BlubberPosting::findBySQL("root_id = :thread_id AND mkdate < :mkdate ORDER BY mkdate DESC LIMIT 1", array(
                'thread_id' => $blubber['root_id'],
                'mkdate' => $blubber['mkdate']
            ));
            $before_blubb = $before_blubb[0];
            $body .= "\n\n\n>".sprintf(_("Am %s schrieb %s"), date("j.n.Y G:i", $before_blubb['mkdate']), $before_blubb->getUser()->getName()).":\n";
            foreach (explode("\n", $before_blubb['description']) as $line) {
                $body .= ">".$line."\n";
            }
        }
        //Noch den Originalbeitrag zitieren (wenn nötig)
        if (($blubber['root_id'] !== $blubber->getId()) && ($before_blubb->getId() !== $blubber['root_id'])) {
            $body .= "\n\n>".sprintf(_("Am %s schrieb %s"), date("j.n.Y G:i", $thread['mkdate']), $thread->getUser()->getName()).":\n";
            foreach (explode("\n", $thread['description']) as $line) {
                $body .= ">".$line."\n";
            }
        }

        $body .= "\n\n";
        $body .= "-- \n";
        $body .= dgettext("blubbermail", "Sie können auf diese Mail ganz normal antworten und Ihre Antwort wird zu einem Blubber-Kommentar.");
        $body .= "\n";
        $body .= dgettext("blubbermail", "Zum Abstellen oder konfigurieren der Blubber-Mails melden Sie sich in Stud.IP an und gehen Sie auf folgende URL:");
        $body .= "\n";
        $body .= $GLOBALS['ABSOLUTE_URI_STUDIP']."plugins.php/blubbermail/settings";
        $body .= "\n\n";
        restoreLanguage();
        return $body;
    }
}
