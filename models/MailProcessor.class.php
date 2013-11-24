<?php

require_once dirname(__file__)."/vendor/PlancakeEmailParser.php";
require_once dirname(__file__)."/../../../core/Blubber/models/BlubberPosting.class.php";

class MailProcessor {
    
    static private $instance = null;
    
    //These three variables may be overwritten by local configs. See constructor.
    protected $mailaccount = "discussion";
    protected $delimiter   = "+";
    protected $maildomain  = null;
    
    static public function getInstance() {
        if (self::$instance === null) {
            self::$instance = new MailProcessor();
        }
        return self::$instance;
    }
    
    public function __construct() {
        $this->maildomain = $_SERVER['SERVER_NAME'];
        if (strtolower(substr($this->maildomain, 0, 4)) === "www.") {
            $this->maildomain = substr($this->maildomain, 4);
        }
        //init configs
        $mailaccount = get_config("BLUBBERMAIL_ACCOUNT");
        if ($mailaccount) {
            $this->mailaccount = $mailaccount;
        }
        $delimiter = get_config("BLUBBERMAIL_DELIMITER");
        if ($delimiter) {
            $this->delimiter = $delimiter;
        }
        $maildomain = get_config("BLUBBERMAIL_MAILDOMAIN");
        if ($maildomain) {
            $this->maildomain = $maildomain;
        }
    }
    
    public function sendBlubberMails($event, BlubberPosting $blubber) {
        if (!$blubber['user_id'] || !$blubber['description']) {
            return;
        }
        $thread = new BlubberPosting($blubber['root_id']);
        $author = $blubber->getUser();
        $reply_mail = $this->mailaccount.$this->delimiter.$thread->getId()."@".$this->maildomain;
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
            $recipient = new User($user_id);
            $body = $blubber['description'];
            
            //Noch den Originalbeitrag
            if ($thread->getId() !== $blubber->getId()) {
                $body .= "\n\n\n".sprintf(_("Am %s schrieb %s"), date("j.n.Y G:i", $thread['mkdate']), $thread->getUser()->getName()).":\n";
                foreach (explode("\n", $thread['description']) as $line) {
                    $body .= ">".$line."\n";
                }
            }
            
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
    
    public function processBlubberMail($rawmail) {
        $email_regular_expression='/([-+.0-9=?A-Z_a-z{|}~])+@([-.0-9=?A-Z_a-z{|}~])+\.[a-zA-Z]{2,6}/i';
        $mail = new PlancakeEmailParser($rawmail);
        $from = $mail->getHeader("From");
        preg_match($email_regular_expression, $from, $matches);
        $frommail = $matches[0];
        
        $recipients = $mail->getTo() + $mail->getCc();
        foreach ($recipients as $recipient) {
            $me = preg_match('/'.$this->mailaccount.'['.$this->delimiter.']([^@]+)@'.$this->maildomain.'/i', $recipient, $matches);
            if ($me) {
                $thread_id = $matches[1];
            }
        }
        $thread = new BlubberPosting($thread_id);
        $author = User::findBySQL("Email = ?", array($frommail));
        $author = $author[0];
        if (!$author) {
            throw new AccessDeniedException("Emailadress not registered. Maybe you should try to send this with another email-adress?");
        }
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
            $body = $this->tranformBody(studip_utf8decode($mail->getBody()));
            
            
            if ($check && $body) {
                //Blubber hinzufügen:
                $output .= "Und wir haben die Rechte. ";
                $comment = new BlubberPosting();
                $comment['description'] = $body;
                $comment['name'] = $thread['name'];
                $comment['parent_id'] = $comment['root_id'] = $thread->getId();
                $comment['context_type'] = $thread['context_type'];
                $comment['Seminar_id'] = $thread['Seminar_id'];
                $comment['external_contact'] = 0;
                $comment['user_id'] = $author['user_id'];
                $comment->store();
            } elseif (!$check) {
                throw new AccessDeniedException("You have no permission to comment here or this blubber does not exist anymore.");
            }
         } else {
             throw new AccessDeniedException("You have no permission to comment here or this blubber does not exist anymore.");
         }
        return $output;
    }
    
    protected function tranformBody($body) {
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
    
    public function eraseTOFUQuotes($body) {
        $body = preg_replace('/\nAm (.*):\s*\n(>.*\n)+/i', "", $body);
        return $body;
    }
}