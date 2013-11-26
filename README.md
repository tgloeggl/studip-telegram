BlubberMail ![logo](https://raw.github.com/Krassmus/BlubberMail/master/assets/blubbermail.png)
===========

Makes Blubber compatible with the smtp-protocol, so that users can send emails to blubber discussions.

## Requirements

You need a [Stud.IP](http://www.studip.de) installation and a mailserver. Those don't need to be necessarily to be on one server, on one domain or anything like that. But the mailserver needs to be configured in a special way.

## Workflow
We only need the mailserver to receive messages that are meant to be blubber-comments. In this usecase a user has read a blubber in his email-client and answers it to a very special reply-to adress that has the shape discussion_([0-9a-z]+)@<yourdomain.com> . So we want from our mailserver to pipe all messages to a php cli script this plugins provides. The cli-script receives the piped email, validates it and inserts it into the blubber-discussion immediately.

## Configuring the mailserver

Add the following line to your alias-table (most of the times at /etc/aliases)

    discussion: |"php /usr/share/studip/public/plugins_packages/RasmusFuhse/BlubberMail/PipeHere.cli.php"

Remember to change */usr/share/studip* to the path of your local studip.