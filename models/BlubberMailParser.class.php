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
                case "8bit":
                default: 
                    $this->content = $rawbody;
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
    
    public function getContent() {
        return $this->content;
    }
    
}
