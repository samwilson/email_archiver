Email Archiver
==============

Email Archiver is a simple email archiving database and web interface.

* **Homepage:** https://samwilson.id.au/email_archiver
* **Source code:** https://github.com/samwilson/email_archiver
* CircleCI: [![CircleCI](https://circleci.com/gh/samwilson/email_archiver.svg)](https://circleci.com/gh/samwilson/email_archiver)

Installation
------------

The database has the following structure:

    CREATE TABLE IF NOT EXISTS `emails` (
        `id` int(10) NOT NULL AUTO_INCREMENT,
        `to_id` int(11) NOT NULL,
        `from_id` int(11) NOT NULL,
        `date_and_time` datetime DEFAULT NULL,
        `subject` varchar(200) CHARACTER SET utf8mb4 NOT NULL,
        `message_body` text CHARACTER SET utf8mb4,
        PRIMARY KEY (`id`),
        KEY to_id (`to_id`),
        KEY from_id (`from_id`)
    ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4;

    CREATE TABLE IF NOT EXISTS `people` (
        `id` int(5) NOT NULL AUTO_INCREMENT,
        `name` varchar(150) CHARACTER SET utf8mb4 NOT NULL,
        `email_address` varchar(100) CHARACTER SET utf8mb4 NOT NULL,
        `notes` text CHARACTER SET utf8mb4 NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `name` (`name`)
    ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4;

    ALTER TABLE `emails`
        ADD CONSTRAINT emails_from FOREIGN KEY (from_id) REFERENCES people (id),
        ADD CONSTRAINT emails_to FOREIGN KEY (to_id) REFERENCES people (id);

Create a password and add it to `config.php`:

    $ php -r "echo password_hash('y0urpwd123!', PASSWORD_DEFAULT).PHP_EOL;"

Make sure the webserver user can write to `var/`.

Licence
-------

This is Free Software, released under the GNU General Public License (GPL).
