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
        preg_match("/^([.\n]+)(\r?\n|\r)(\r?\n|\r)([.\n]*)$/", $rawmail, $matches);
        $this->parseHeaders($matches[1]);
        $this->parseBodies($matches[4]);
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
                $headers[count($headers) - 1]['value'] .= substr($line, 1);
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
            preg_match("/^(.*?) /", $this->headers['content-type'], $matches);
            $this->content_type = strtolower($matches[1]);
        }
    }
    
    public function getContentType() {
        return $this->content_type;
    }
    
    private function isLineStartingWithPrintableChar($line)
    {
        return preg_match('/^[A-Za-z]/', $line);
    }
    
    protected function parseBodies($rawbodies) {
        //we assume that we already have the headers
        $content_type = $this->headers['content-type'];
        preg_match('/boundary=(.*)$/mi', $rawbodies, $matches);
        $boundary = str_replace(array("'", '"'), '', $matches[1]);
        if ($boundary) {
            $parts = explode("--".$boundary, $rawbodies);
            array_pop($parts);
            foreach ($parts as $rawpart) {
                $this->bodies[] = new BlubberMailParser($rawpart);
            }
        } else {
            //the whole body is one part and has no further headers
            switch ($this->getHeader("Content-Transfer-Encoding")) {
                case "quoted-printable":
                    $this->content = quoted_printable_decode($rawbodies);
                    break;
                case "base64":
                    $this->content = base64_decode(preg_replace("/(\r?\n|\r)/", "", trim($rawbodies)));
                    break;
                default: 
                    $this->content = $rawbodies;
            }
        }
    }
    
    public function getTextBody() {
        if (is_array($this->bodies)) {
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
    
    public function getContent() {
        return $this->content;
    }
    
}
