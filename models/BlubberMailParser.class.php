<?php

class BlubberMailParser {
    
    protected $headers = array();
    protected $bodies = array();
    protected $content_type = null;
    protected $content = null;
    
    public function __construct($rawmail) {
        $this->parseMail($rawmail);
    }
    
    protected function parseMail($rawmail) {
        list($head, $body) = preg_split('/(\r?\n|\r)(\r?\n|\r)/', $rawmail, 2);
        $this->parseHeaders($head);
        $this->parseBodies($body);
    }
    
    protected function parseHeaders($rawheaders) {
        $lines = preg_split("/(\r?\n|\r)/", $rawheaders);
        $headers = array();
        foreach ($lines as $line) {
            if ($this->isLineStartingWithPrintableChar($line)) {
                $lineIsHeader = preg_match('/([^:]+):\s*(.*)$/', $line, $matches);
                if ($lineIsHeader) {
                    $headers[] = array('index' => strtolower(trim($matches[1])), 'value' => trim($matches[2]));
                }
            } else {
                end($headers);
                $lastkey = key($headers);
                $headers[$lastkey]['value'] .= " ".substr($line, 1);
            }
        }
        foreach ($headers as $header) {
            $this->headers[$header['index']] = $header['value'];
        }
        $this->getContentTypeFromHeaders();
    }
    
    public function getHeader($header) {
        return $this->headers[strtolower($header)];
    }
    
    private function getContentTypeFromHeaders() 
    {
        if (isset($this->headers['content-type'])) {
            preg_match("/^([^;\s]*)/", $this->getHeader('Content-Type'), $matches);
            $this->content_type = strtolower($matches[1]);
        }
    }
    
    public function getContentType() {
        return $this->content_type;
    }
    
    private function isLineStartingWithPrintableChar($line)
    {
        return preg_match('/^[^\s]/', $line);
    }
    
    protected function parseBodies($rawbody) {
        //we assume that we already have the headers
        $raw_content_type = $this->getHeader('Content-Type');
        preg_match('/boundary=(.*)$/mi', $raw_content_type, $matches);
        $boundary = str_replace(array("'", '"'), '', $matches[1]);
        if ($boundary) {
            $parts = explode("--".$boundary, $rawbody);
            array_pop($parts);
            array_shift($parts);
            foreach ($parts as $rawpart) {
                $this->bodies[] = new BlubberMailParser($rawpart);
            }
        } else {
            //the whole body is one part and has no further headers
            switch (strtolower($this->getHeader("Content-Transfer-Encoding"))) {
                case "quoted-printable":
                    $this->content = quoted_printable_decode($rawbody);
                    break;
                case "base64":
                    $this->content = base64_decode(preg_replace("/(\r?\n|\r)/", "", trim($rawbody)));
                    break;
                case "7bit":
                    $this->content = prlbr_78::to7($rawbody);
                    break;
                case "8bit":
                default: 
                    $this->content = $rawbody;
            }
            $charset = $this->getCharset();
            StudipMail::sendMessage("ras@fuhse.org", "Anhänge", print_r($this->content, true));
            if ($charset !== null) {
                $this->content = mb_convert_encoding($this->content, "UTF-8", $charset);
                //$this->content = iconv($charset, 'UTF-8//TRANSLIT', $this->content);
            }
        }
    }
    
    public function getTextBody() {
        if ($this->isMultipart()) {
            foreach ($this->bodies as $part) {
                if ($part->getContentType() === "text/plain") {
                    return $part->getContent();
                }
            }
        } else {
            if ($this->getContentType() === "text/plain") {
                return $this->getContent();
            }
        }
    }
    
    public function getHtmlBody() {
        if ($this->isMultipart()) {
            foreach ($this->bodies as $part) {
                if ($part->getContentType() === "text/html") {
                    return $part->getContent();
                }
            }
        } else {
            if ($this->getContentType() === "text/html") {
                return $this->getContent();
            }
        }
    }
    
    public function getAttachments() {
        if ($this->isMultipart()) {
            $attachments = array();
            foreach ($this->bodies as $part) {
                foreach ($part->getAttachments() as $attachment) {
                    $attachments[] = $attachment;
                };
            }
            return $attachments;
        } elseif($this->isAttachment()) {
            return array(array(
                'filename' => $this->getFilename(),
                'content_type' => $this->getContentType(),
                'content' => $this->getContent()
            ));
        } else {
            return array();
        }
    }
    
    public function isMultipart() {
        return substr($this->getContentType(), 0, 10) === "multipart/";
    }
    
    public function isAttachment() {
        preg_match("/^([^;\s]*)/", $this->getHeader('Content-Disposition'), $matches);
        $disposition = strtolower($matches[1]);
        
        return ($disposition && $disposition !== "inline") || 
                (
                    substr($this->getContentType(), 0, 5) !== "text/"
                    && !$this->isMultipart()
                );
    }
    
    public function getFilename() {
        $raw_content_disposition = $this->getHeader('Content-Disposition');
        preg_match('/filename=(.*)$/mi', $raw_content_disposition, $matches);
        $filename = str_replace(array("'", '"'), '', $matches[1]);
        if (!$filename) {
            $raw_content_type = $this->getHeader('Content-Type');
            preg_match('/filename=(.*)$/mi', $raw_content_type, $matches);
            $filename = str_replace(array("'", '"'), '', $matches[1]);
        }
        return $filename;
    }
    
    public function getCharset() {
        $raw_content_type = $this->getHeader("Content-Type");
        preg_match('/charset=(.*)$/mi', $raw_content_type, $matches);
        return $matches[1] ? strtolower($matches[1]) : null;
    }
    
    public function getContent() {
        return $this->content;
    }
    
}


/* prlbr_78
*  is a class that converts between 7- and 8-bit encoded strings
*/

class prlbr_78 {    

    private static $up = array (1, 3, 7, 15, 31, 63, 127);    
    private static $down = array (254, 252, 248, 240, 224, 192, 128, 0);

    /* to7
    *  converts an 8-bit encoded $input string into a 7-bit encoded string
    */
    public static function to7 ($input) {

        // the empty string is encoded as empty string
        if ($input === ''):
            return '';
        endif;

        // initialize the output string and carry
        $output = '';
        $carry = 0;

        for ($i = 0, $length = strlen ($input); $i < $length; $i++):
            // calculate the round number modulo 7
            $r = $i % 7;
            // add the carry as a character to the output every seventh round
            if (($r === 0) && ($i !== 0)):
                $output .= chr ($carry);
                $carry = 0;
            endif;
            // represent an input byte as 8-bit integer
            $integer = ord ($input[$i]);
            // add a 7-bit output byte created from the r-bit carry and the
            // lower 7 - r bits from the 8-bit input integer
            $output .= chr ($carry | (($integer & self::$down[$r]) >> 1));
            // save the other r + 1 bits of the 8-bit integer as new carry
            $carry = $integer & self::$up[$r];
        endfor;
        
        // add the remaining carry as a character to the output
        return $output . chr ($carry);

    } // public static function to7
    

    /* to8
    *  converts a 7-bit encoded $input string into an 8-bit encoded string
    */
    public static function to8 ($input) {
    
        // initialize the output string and carry
        $output = '';
        $carry = 0;
        
        for ($i = 0, $length = strlen ($input); $i < $length; $i++):
            // calculate the round number modulo 8
            $r = $i % 8;
            // represent an input byte as a 7-bit integer
            $integer = ord ($input[$i]);
            // add an 8-bit output byte created from the 8 - r bits carry
            // and r bits from the 7-bit integer
            if ($r !== 0):
                $output .= chr ($carry | ($integer & self::$up[$r - 1]));
            endif;
            // save the other 7 - r bits of the 7-bit integer as new carry
            $carry = ($integer << 1) & self::$down[$r];
        endfor;

        return $output;

    } // public static function to8

} // class prlbr_78