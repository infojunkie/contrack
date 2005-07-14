# phpMyAdmin MySQL-Dump
# version 2.5.1
# http://www.phpmyadmin.net/ (download page)
# --------------------------------------------------------

#
# Table structure for table `users`
#
# Creation: Jul 22, 2003 at 09:08 PM
# Last update: Jul 22, 2003 at 09:08 PM
# Last check: Jul 22, 2003 at 09:08 PM
#

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `uid` int(10) unsigned NOT NULL,
  `username` varchar(20) NOT NULL default '',
  `passwd` varchar(20) NOT NULL default '',
  `forename` varchar(100) default NULL,
  `lastname` varchar(100) default NULL,
  `email` varchar(200) default NULL,
  `nologin` tinyint(1) NOT NULL default '0',
  `first_login` datetime default NULL,
  `last_login` datetime default NULL,
  `count_logins` int(10) unsigned NOT NULL default '0',
  `count_pages` int(10) unsigned NOT NULL default '0',
  `time_online` int(11) NOT NULL default '0',
  PRIMARY KEY  (`uid`),
  KEY `username` (`username`)
);
#TYPE=MyISAM;



#
# Table structure for table `groups`
#
# Creation: Jul 22, 2003 at 09:08 PM
# Last update: Jul 22, 2003 at 09:08 PM
#

DROP TABLE IF EXISTS `groups`;
CREATE TABLE `groups` (
  `gid` int(11) NOT NULL,
  `name` varchar(50) default NULL,
  UNIQUE KEY `gid` (`gid`)
);
# TYPE=MyISAM;

#
# Table structure for table `permissions`
#
# Creation: Jul 22, 2003 at 08:14 PM
# Last update: Jul 22, 2003 at 08:38 PM
#

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id` int(11) NOT NULL default '0',
  `id_type` enum('group','user') NOT NULL default 'group',
  `application` varchar(100) default NULL,
  `part` varchar(50) default NULL,
  `detail` varchar(100) default NULL,
  `perms` set('read','delete','modify','add') default NULL
);
# TYPE=MyISAM;


#
# Table structure for table `usergroups`
#
# Creation: Jul 22, 2003 at 08:14 PM
# Last update: Jul 22, 2003 at 08:38 PM
#

DROP TABLE IF EXISTS `usergroups`;
CREATE TABLE `usergroups` (
  `id` int(10) NOT NULL,
  `uid` int(11) NOT NULL default '0',
  `gid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
);
# TYPE=MyISAM;

