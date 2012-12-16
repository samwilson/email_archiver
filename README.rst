==============
Email Archiver
==============

Email Archiver is a simple email archiving database and interface.

Schema
------

The database has the following structure::

    CREATE TABLE IF NOT EXISTS emails (
        id int(10) NOT NULL AUTO_INCREMENT,
        to_id int(11) NOT NULL,
        from_id int(11) NOT NULL,
        date_and_time datetime DEFAULT NULL,
        `subject` varchar(200) NOT NULL,
        message_body text,
        PRIMARY KEY (id),
        KEY to_id (to_id),
        KEY from_id (from_id)
    ) ENGINE=InnoDB;

    CREATE TABLE IF NOT EXISTS people (
        `id` int(5) NOT NULL AUTO_INCREMENT,
        `name` varchar(150) NOT NULL,
        `email_address` varchar(100) NOT NULL,
        `notes` text NULL DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY `name` (`name`)
    ) ENGINE=InnoDB;

    ALTER TABLE `emails`
        ADD CONSTRAINT emails_from FOREIGN KEY (from_id) REFERENCES people (id),
        ADD CONSTRAINT emails_to FOREIGN KEY (to_id) REFERENCES people (id);

Licence
-------

This is Free Software, released under the GNU General Public License (GPL).
