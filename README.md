BlubberMail ![logo](https://raw.github.com/Krassmus/BlubberMail/master/assets/blubbermail.png)
===========

Makes Blubber from [Stud.IP](http://www.studip.de) compatible with the smtp-protocol. This means that users may receive emails for each blubber-comment that they get a notification for. And also (and this is the sweet part) they can hit reply in their mail-client and post another comment that is displayed as a blubber-comment in Stud.IP.

## Requirements

You need a [Stud.IP](http://www.studip.de) (2.4 or higher) installation and a mailserver. Those don't need to be necessarily to be on one server, on one domain or anything like that. But the mailserver needs to be configured in a special way to pipe some incoming messages to this plugin.

## Workflow
We only need the mailserver to receive messages that are meant to be blubber-comments. In this usecase a user has read a blubber in his email-client and answers it to a very special reply-to adress that has the shape discussion+blubber_id@yourdomain.com . So we want from our mailserver to pipe all messages to a php cli script this plugins provides. The cli-script receives the piped email, validates it and inserts it into the blubber-discussion immediately.

## Configuring the mailserver

It's quite simple actually. Add the following line to your alias-table (most of the times at /etc/aliases)

    discussion: |"php /usr/share/studip/public/plugins_packages/RasmusFuhse/BlubberMail/PipeHere.cli.php"

Remember to change */usr/share/studip* to the path of your local studip. If you want to have a different mailaccount than `discussion` to fetch all the incoming blubber-mails, you need to configure that in the global configuration of Stud.IP via the GUI as a root-user.

