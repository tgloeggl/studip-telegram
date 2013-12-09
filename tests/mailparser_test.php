<?php

require_once dirname(__file__)."/../models/BlubberMailParser.class.php";

class MailparserTestCase extends UnitTestCase {

    function test_actually_nothing() {
        $this->assertIsA(array("hell yeah!"), "array");
        $this->assertEqual("1", 1);
    }
    
    function test_mail_parser() {
        $rawmail = 'Message-ID: <52A38390.3020404@data-quest.de>
Date: Sat, 07 Dec 2013 21:22:40 +0100
From: Rasmus Fuhse <fuhse@data-quest.de>
User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:24.0) Gecko/20100101 Thunderbird/24.1.1
MIME-Version: 1.0
To: discussion+3bbb8eae1521c42331f9fec1138e8454@blubber.it
Subject: Re: yeah!
References: <20131207210746.9492.discussion--bbb-eae----c-----f-fec----e----@blubber.it> <52A3836A.9070106@data-quest.de>
In-Reply-To: <52A3836A.9070106@data-quest.de>
Content-Type: TEXT/PLAIN; charset=windows-1252
Content-Transfer-Encoding: 8bit

 Mäke me happy!';
        $mail = new BlubberMailParser($rawmail);
        $this->assertEqual($mail->getContent(), " Mäke me happy!");
        $this->assertEqual($mail->getContentType(), "text/plain");
    }
    
    function test_mail_parser_8bit() {
        $rawmail ='From fuhse@data-quest.de  Mon Dec  9 09:08:54 2013
Return-Path: <fuhse@data-quest.de>
X-Original-To: discussion+3bbb8eae1521c42331f9fec1138e8454@blubber.it
Delivered-To: discussion+3bbb8eae1521c42331f9fec1138e8454@blubber.it
Received: from server2.data-quest.de (server2.data-quest.de [178.63.1.144])
	by v22013021542010694.yourvserver.net (Postfix) with ESMTP id D4060AA80AC7
	for <discussion+3bbb8eae1521c42331f9fec1138e8454@blubber.it>; Mon,  9 Dec 2013 09:08:54 +0100 (CET)
Received: from localhost (localhost [127.0.0.1])
	by server2.data-quest.de (Postfix) with ESMTP id 9AE172A7612A
	for <discussion+3bbb8eae1521c42331f9fec1138e8454@blubber.it>; Mon,  9 Dec 2013 09:08:54 +0100 (CET)
X-Virus-Scanned: Debian amavisd-new at data-quest.de
Received: from server2.data-quest.de ([127.0.0.1])
	by localhost (server2.data-quest.de [127.0.0.1]) (amavisd-new, port 10024)
	with ESMTP id eNt7UAVwCmUI
	for <discussion+3bbb8eae1521c42331f9fec1138e8454@blubber.it>;
	Mon,  9 Dec 2013 09:08:52 +0100 (CET)
Received: from rasmuss-macbook-pro.fritz.box (gtng-4d08a2c1.pool.mediaWays.net [77.8.162.193])
	by server2.data-quest.de (Postfix) with ESMTPSA id 56C962A760AE
	for <discussion+3bbb8eae1521c42331f9fec1138e8454@blubber.it>; Mon,  9 Dec 2013 09:08:52 +0100 (CET)
Message-ID: <52A57A93.3000801@data-quest.de>
Date: Mon, 09 Dec 2013 09:08:51 +0100
From: Rasmus Fuhse <fuhse@data-quest.de>
User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:24.0) Gecko/20100101 Thunderbird/24.1.1
MIME-Version: 1.0
To: discussion+3bbb8eae1521c42331f9fec1138e8454@blubber.it
Subject: Re: yeah!
References: <20131207210746.9492.discussion--bbb-eae----c-----f-fec----e----@blubber.it> <52A3836A.9070106@data-quest.de> <52A38390.3020404@data-quest.de> <52A57678.1050300@data-quest.de>
In-Reply-To: <52A57678.1050300@data-quest.de>
Content-Type: text/plain; charset=windows-1252
Content-Transfer-Encoding: 8bit

Mäke me happy!';
        $mail = new BlubberMailParser($rawmail);
        $this->assertEqual($mail->getContent(), "Mäke me happy!");
        $this->assertEqual($mail->getContentType(), "text/plain");
        $this->assertEqual($mail->getCharset(), "windows-1252");
    }
    
    function test_mail_parser_multipart_mixed() {
        $rawmail = 'Message-ID: <52A58285.6080804@gmail.com>
Date: Mon, 09 Dec 2013 09:42:45 +0100
From: Rasmus Fuhse <krassmus@gmail.com>
User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:24.0) Gecko/20100101 Thunderbird/24.1.1
MIME-Version: 1.0
To: discussion+3bbb8eae1521c42331f9fec1138e8454@blubber.it
Subject: Fwd: Fwd: Fwd: Fwd: Re: test
References: <52965805.3020503@gmail.com>
In-Reply-To: <52965805.3020503@gmail.com>
X-Forwarded-Message-Id: <52965805.3020503@gmail.com>
Content-Type: multipart/mixed;
 boundary="------------030604080806080609030804"

This is a multi-part message in MIME format.
--------------030604080806080609030804
Content-Type: text/plain; charset=windows-1252
Content-Transfer-Encoding: 7bit

Na bestens!





--------------030604080806080609030804
Content-Type: image/png;
 name="blubbermail.png"
Content-Transfer-Encoding: base64
Content-Disposition: attachment;
 filename="blubbermail.png"

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
--------------030604080806080609030804--
';
        $mail = new BlubberMailParser($rawmail);
        $this->assertEqual($mail->isMultipart(), true);
        $this->assertEqual(trim($mail->getTextBody()), "Na bestens!");
        $attachments = $mail->getAttachments();
        
        $this->assertEqual(count($attachments), 1);
        $this->assertEqual($attachments[0]['filename'], "blubbermail.png");
    }

}


