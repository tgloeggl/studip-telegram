# TelegramConnect

Follow the instructions at

https://github.com/php-telegram-bot/core#create-your-first-bot

to create your own telegram bot.


You need to run _composer install_ as well. See https://getcomposer.org/

The used third party library to interface with Telegram is located at
https://github.com/php-telegram-bot/core

## Current API

Call the sethook-action in the plugin with the API-key as the first url param and the botname as the second to register the webhook

## TODO

* The server logs state that the webhook has been called, but nothing seems to happen
* Implement sending of messages to the bot instead of only receiving
