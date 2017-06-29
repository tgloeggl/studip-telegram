<?php
class AddTelegramTables extends Migration
{
    function up()
    {
        DBManager::get()->exec("
            CREATE TABLE IF NOT EXISTS `telegram_bot_config` (
                `botname` varchar(255) NOT NULL,
                `api_key` varchar(64) NOT NULL,
                `owner_id` varchar(32) NOT NULL,
                `webhook` varchar (255) NOT NULL,
                UNIQUE KEY `bot_apikey` (`botname`,`api_key`)
            )
        ");

        DBManager::get()->exec("
            CREATE TABLE IF NOT EXISTS `telegram_log` (
                `id` INT AUTO_INCREMENT,
                `data` TEXT,
                PRIMARY KEY `id` (`id`)
            )
        ");
    }

    function down()
    {
        DBManager::get()->exec("DROPT TABLE telegram_bot_config");
        DBManager::get()->exec("DROPT TABLE telegram_log");
    }
}
