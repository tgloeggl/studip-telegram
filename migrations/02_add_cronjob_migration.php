<?php
class AddCronjobMigration extends Migration
{
    function up()
    {
        $new_job = array(
            'filename'    => 'public/plugins_packages/RasmusFuhse/BlubberMail/SendMailThreads.cronjob.php',
            'class'       => 'SendMailTreads',
            'priority'    => 'normal',
            'minute'      => '-1'
        );

        $query = "INSERT IGNORE INTO `cronjobs_tasks`
                    (`task_id`, `filename`, `class`, `active`)
                  VALUES (:task_id, :filename, :class, 1)";
        $task_statement = DBManager::get()->prepare($query);

        $query = "INSERT IGNORE INTO `cronjobs_schedules`
                    (`schedule_id`, `task_id`, `parameters`, `priority`,
                     `type`, `minute`, `mkdate`, `chdate`,
                     `last_result`)
                  VALUES (:schedule_id, :task_id, '[]', :priority, 'periodic',
                          :minute, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(),
                          NULL)";
        $schedule_statement = DBManager::get()->prepare($query);


        $task_id = md5(uniqid('blubbermailtask', true));

        $task_statement->execute(array(
            ':task_id'  => $task_id,
            ':filename' => $new_job['filename'],
            ':class'    => $new_job['class'],
        ));

        $schedule_id = md5(uniqid('schedule', true));
        $schedule_statement->execute(array(
            ':schedule_id' => $schedule_id,
            ':task_id'     => $task_id,
            ':priority'    => $new_job['priority'],
            ':minute'      => $new_job['minute'],
        ));

        //Neue Tabelle:
        $create_table = DBManager::get()->prepare("
            CREATE TABLE IF NOT EXISTS `blubbermail_abos` (
                `stream_id` varchar(32) NOT NULL,
                `user_id` varchar(32) NOT NULL,
                `last_update` bigint(20) NOT NULL,
                UNIQUE KEY `unique_user_streams` (`stream_id`,`user_id`)
            ) ENGINE=MyISAM
        ");
        $create_table->execute();
    }
}
