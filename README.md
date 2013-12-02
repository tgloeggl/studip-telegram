BlubberMail ![logo](https://raw.github.com/Krassmus/BlubberMail/master/assets/blubbermail.png)
===========

Makes Blubber from [Stud.IP](http://www.studip.de) compatible with the smtp-protocol. This means that users may receive emails for each blubber-comment that they get a notification for. And also (and this is the sweet part) they can hit reply in their mail-client and post another comment that is displayed as a blubber-comment in Stud.IP. So you can completely discuss in blubber with your email client - may it be on a computer oder smartphone.

## Installation

You need a [Stud.IP](http://www.studip.de) (2.4 or higher) installation and a mailserver. Those don't need to be necessarily to be on one server, on one domain or anything like that. But the mailserver needs to be configured in a special way to pipe some incoming messages to this plugin. Install this plugin as a casual Stud.IP plugin (upload it) and then configure your mailserver.

In Stud.IP 3.0 you also should activate the cronjob for sending new blubber-threads. You can do that as a root-user via the admin-GUI.

### Configuring the mailserver

It's quite simple actually. Add the following line to your alias-table (most of the times at /etc/aliases)

    blubb: |"php /usr/share/studip/public/plugins_packages/RasmusFuhse/BlubberMail/PipeHere.cli.php"

Remember to change */usr/share/studip* to the path of your local studip. If you want to have a different mailaccount than `blubb` to fetch all the incoming blubber-mails, you need to configure that in the global configuration of Stud.IP via the GUI as a root-user.

## Using blubber with email

Write a blubber in Stud.IP as normal. If someone is writing a comment on this blubber, you should receive an email from Stud.IP which has the comment as the email-message and your original blubber as a quote at the end of the mail. Also there is a short automatic message that tells you to configure your blubbermail settings in Stud.IP if you don't want to receive any more mails. We think that is good business.

Now that you have received the mail you can either go to the webbrowser, type your Stud.IP URL, login with username and password, go to blubber and write the comment. Or much more convenient and intuitively you could hit reply directly in your mailclient, write something and send your comment as an email. The mailadress you are writing to should look like `Rasmus Fuhse <blubb+abcdef0123456789@blubber.it>`. But don't let this bother you. When you send your mail, it is sent to the mailserver of your Stud.IP host which pipes (sends) it directly to Stud.IP, where the message body is now inserted as a comment by you in blubber. And all within a second! 

Note that you are identified by your email-adress. When you send an email by hitting reply make sure that you will send it with the correct email adress and not with one of your other 20 ones. Otherwise your mail will most likely be sent back to you so you can try it again with the correct email adress.

## Prevent "spam"

To prevent yourself from getting too many emails you (as a user in the system) can configure BlubberMail and set if you want to receive BlubberMail, if you don't want to or if the number of Mails should be limited. If you declare it should be limited to 3 comments, you receive not more than three BlubberMails per thread in which you participated unless you participate once more. So you won't be spammed by BlubberMail but still get some mail-notifications that the discussions goes on.

## Getting new threads per mail

In Stud.IP 3.0 BlubberMail can be configured by each user to send to that user new blubber-threads. The user can choose one or more blubber-streams to be checked for updates every minute. If there are new blubbers - no matter if public or private or course-blubber - the user gets a BlubberMail and can answer it by hitting reply just as usual.

## Writing new blubbers by email

If it is not disabled by the admin (via admin-GUI as root-user) you can also create a public or private blubber by writing an email to a special email-adress. You can see this adress in the personal settings page. And most of the times it is `blubb+public@yourserver.com`. So just instead of a blubber-ID you just write `public` for creating a public blubber or `private` for a private blubber. And once you sent your email the blubber is created. This is a nice gimmick for those who want to use their smartphone-mail-app as a blubber-app.

