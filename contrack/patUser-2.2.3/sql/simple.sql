# phpMyAdmin MySQL-Dump
# version 2.3.2
# http://www.phpmyadmin.net/ (download page)
#
# Host: localhost:3306
# Generation Time: Jul 26, 2003 at 04:17 PM
# Server version: 3.23.52
# PHP Version: 4.2.2
# Database : `pat`
# --------------------------------------------------------

#
# Table structure for table `groups`
#

DROP TABLE IF EXISTS groups;
CREATE TABLE groups (
  gid int(11) NOT NULL default '0',
  name varchar(50) default NULL,
  UNIQUE KEY gid (gid)
); #TYPE=MyISAM;

#
# Dumping data for table `groups`
#

# --------------------------------------------------------

#
# Table structure for table `permissions`
#

DROP TABLE IF EXISTS permissions;
CREATE TABLE permissions (
  id int(11) NOT NULL default '0',
  id_type enum('group','user') NOT NULL default 'group',
  application varchar(100) default NULL,
  part varchar(50) default NULL,
  detail varchar(100) default NULL,
  perms set('read','delete','modify','add') default NULL
); # TYPE=MyISAM;

#
# Dumping data for table `permissions`
#

# --------------------------------------------------------

#
# Table structure for table `usergroups`
#

DROP TABLE IF EXISTS usergroups;
CREATE TABLE usergroups (
  id int(10) NOT NULL auto_increment,
  uid int(11) NOT NULL default '0',
  gid int(11) NOT NULL default '0',
  PRIMARY KEY  (id)
);# TYPE=MyISAM;

#
# Dumping data for table `usergroups`
#

# --------------------------------------------------------

#
# Table structure for table `users`
#

DROP TABLE IF EXISTS users;
CREATE TABLE users (
  uid int(10) unsigned NOT NULL default '0',
  username varchar(20) NOT NULL default '',
  passwd varchar(20) NOT NULL default '',
  forename varchar(100) default NULL,
  lastname varchar(100) default NULL,
  email varchar(200) default NULL,
  nologin tinyint(1) NOT NULL default '0',
  first_login datetime default NULL,
  last_login datetime default NULL,
  count_logins int(10) unsigned NOT NULL default '0',
  count_pages int(10) unsigned NOT NULL default '0',
  time_online int(11) NOT NULL default '0',
  PRIMARY KEY  (uid),
  KEY username (username)
);# TYPE=MyISAM;

#
# Dumping data for table `users`
#

INSERT INTO users (uid, username, passwd, forename, lastname, email, nologin, first_login, last_login, count_logins, count_pages, time_online) VALUES (501, 'gerd', 'gerd123', 'Gerd', 'Schaufelberger', 'gerd@exit0.net', 0, '2002-12-27 10:33:55', '2003-07-26 16:03:12', 21, 56, 12912),
(1002, 'heiko', 'heiko123', 'Heiko', 'Hund', 'heiko@exit0.net', 0, '2002-12-27 10:34:42', '2002-12-27 10:34:42', 0, 0, 0),
(502, 'tom', 'tom123', 'Thomas', 'Hunzelmann', 'tom@exit0.net', 0, '2002-12-27 10:35:01', '2002-12-27 10:35:01', 0, 0, 0),
(1001, 'mathias', 'mathias123', 'Mathias', 'Fischer', 'mfi@exit0.net', 0, '2002-12-27 10:35:53', '2002-12-27 10:35:53', 0, 0, 0);

