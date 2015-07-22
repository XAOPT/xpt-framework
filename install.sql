/*Table structure for table `session` */

CREATE TABLE `session` (
  `id` varchar(32) NOT NULL,
  `userid` bigint(20) unsigned NOT NULL,
  `browser` varchar(255) NOT NULL,
  `ip` varchar(16) NOT NULL,
  `start_time` int(10) unsigned NOT NULL,
  `lastupd` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `static_pages` */

CREATE TABLE `static_pages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cat_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `lang` varchar(3) NOT NULL,
  `alias` varchar(64) NOT NULL,
  `published` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `html` longtext NOT NULL,
  `description` varchar(255) NOT NULL,
  `keywords` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `users` */

CREATE TABLE `users` (
  `userid` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `password` char(32) NOT NULL,
  `email` varchar(64) NOT NULL,
  PRIMARY KEY (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;