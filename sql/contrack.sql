-- phpMyAdmin SQL Dump
-- version 2.6.1-pl2
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generation Time: Mar 30, 2005 at 05:26 AM
-- Server version: 4.0.24
-- PHP Version: 4.3.10
-- 
-- Database: `contrack`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `additional_cost`
-- 

CREATE TABLE `additional_cost` (
  `ADDITIONAL_COST_ID` int(10) unsigned NOT NULL auto_increment,
  `DESCRIPTION` varchar(100) default '',
  `AMOUNT` decimal(10,2) NOT NULL default '0.00',
  `SINGLE_ORDER_ID` int(10) unsigned NOT NULL default '0',
  `GEN_ORDER_ID` int(10) unsigned NOT NULL default '0',
  `PAYOR_ID` int(10) unsigned NOT NULL default '0',
  `BELONGS_TO` int(11) NOT NULL default '0',
  `PAYEE_ID` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`ADDITIONAL_COST_ID`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `additional_cost`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `attachment`
-- 

CREATE TABLE `attachment` (
  `ATTACHMENT_ID` int(10) unsigned NOT NULL auto_increment,
  `STATE` varchar(100) NOT NULL default '',
  `TABLE_NAME` varchar(100) NOT NULL default '',
  `ROW_ID` varchar(100) NOT NULL default '',
  `FILENAME` varchar(100) NOT NULL default '',
  `DESCRIPTION` varchar(100) default '',
  `TYPE` varchar(100) default '',
  `SIZE` bigint(20) default '0',
  `DATE_CREATED` datetime default '0000-00-00 00:00:00',
  `DATA` mediumblob,
  PRIMARY KEY  (`ATTACHMENT_ID`)
) TYPE=MyISAM AUTO_INCREMENT=22 ;

-- 
-- Dumping data for table `attachment`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `buyer_rel_agent`
-- 

CREATE TABLE `buyer_rel_agent` (
  `BUYER_ID` int(10) unsigned NOT NULL default '0',
  `AGENT_ID` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`AGENT_ID`,`BUYER_ID`)
) TYPE=MyISAM;

-- 
-- Dumping data for table `buyer_rel_agent`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `country`
-- 

CREATE TABLE `country` (
  `COUNTRY_ID` int(10) unsigned NOT NULL auto_increment,
  `NAME` varchar(100) NOT NULL default '',
  `CODE` varchar(100) default '',
  PRIMARY KEY  (`COUNTRY_ID`)
) TYPE=MyISAM AUTO_INCREMENT=3 ;

-- 
-- Dumping data for table `country`
-- 

INSERT INTO `country` (`COUNTRY_ID`, `NAME`, `CODE`) VALUES (1, 'Egypt', 'EGY');

-- --------------------------------------------------------

-- 
-- Table structure for table `currency`
-- 

CREATE TABLE `currency` (
  `CURRENCY_ID` int(10) unsigned NOT NULL auto_increment,
  `CODE` varchar(100) NOT NULL default '',
  `NAME` varchar(100) default '',
  PRIMARY KEY  (`CURRENCY_ID`)
) TYPE=MyISAM AUTO_INCREMENT=3 ;

-- 
-- Dumping data for table `currency`
-- 

INSERT INTO `currency` (`CURRENCY_ID`, `CODE`, `NAME`) VALUES (1, 'EGP', 'Egyptian Pound');
INSERT INTO `currency` (`CURRENCY_ID`, `CODE`, `NAME`) VALUES (2, 'EUR', 'Euro');

-- --------------------------------------------------------

-- 
-- Table structure for table `gen_order`
-- 

CREATE TABLE `gen_order` (
  `GEN_ORDER_ID` int(10) unsigned NOT NULL auto_increment,
  `STATE` enum('ACTIVE','INACTIVE') NOT NULL default 'ACTIVE',
  `BUYER_ID` int(11) NOT NULL default '0',
  `AGENT_ID` int(11) NOT NULL default '0',
  `CODE` varchar(100) NOT NULL default '0',
  `STATUS` enum('Closed','Open') NOT NULL default 'Open',
  `CLIENT_ORDER_ID` varchar(100) default '',
  `RECEIVE_DATE` date default '0000-00-00',
  `PLANNED_DELIVERY_DATE` date default '0000-00-00',
  `CURRENCY_ID` int(11) default '1',
  `FRIENDLY_NAME` varchar(100) default '',
  `PO_DATE` date default '0000-00-00',
  `PO_NUMBER` varchar(100) default '',
  `COMMENTS` text,
  `DATE_CREATED` date NOT NULL default '0000-00-00',
  PRIMARY KEY  (`GEN_ORDER_ID`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `gen_order`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `gen_order_history`
-- 

CREATE TABLE `gen_order_history` (
  `GEN_ORDER_HISTORY_ID` int(10) unsigned NOT NULL auto_increment,
  `GEN_ORDER_ID` int(10) unsigned NOT NULL default '0',
  `MODIFIED_BY` int(10) unsigned NOT NULL default '0',
  `MODIFIED_DATE` date NOT NULL default '0000-00-00',
  `STATE` varchar(100) default '',
  `CHANGE_DESCRIPTION` varchar(100) default '',
  PRIMARY KEY  (`GEN_ORDER_HISTORY_ID`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `gen_order_history`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `gen_order_rel_party`
-- 

CREATE TABLE `gen_order_rel_party` (
  `GEN_ORDER_ID` int(11) NOT NULL default '0',
  `PARTY_ID` int(11) NOT NULL default '0',
  PRIMARY KEY  (`GEN_ORDER_ID`,`PARTY_ID`)
) TYPE=MyISAM;

-- 
-- Dumping data for table `gen_order_rel_party`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `globals`
-- 

CREATE TABLE `globals` (
  `BUYER_PREFIX` varchar(10) NOT NULL default '',
  `SUPPLIER_PREFIX` varchar(10) NOT NULL default '',
  `AGENT_PREFIX` varchar(10) NOT NULL default '',
  `INVOICE_PREFIX` varchar(10) NOT NULL default '',
  `GEN_ORDER_PREFIX` varchar(10) NOT NULL default '',
  `SINGLE_ORDER_PREFIX` varchar(10) NOT NULL default '',
  `PRODUCT_PREFIX` varchar(10) NOT NULL default '',
  `EXT_PARTY_PREFIX` varchar(10) NOT NULL default '',
  `EXT_INVOICE_PREFIX` varchar(100) NOT NULL default '',
  `BUYER_LAST_ID` int(11) NOT NULL default '0',
  `SUPPLIER_LAST_ID` int(11) NOT NULL default '0',
  `AGENT_LAST_ID` int(11) NOT NULL default '0',
  `INVOICE_LAST_ID` int(11) NOT NULL default '0',
  `GEN_ORDER_LAST_ID` int(11) NOT NULL default '0',
  `SINGLE_ORDER_LAST_ID` int(11) NOT NULL default '0',
  `PRODUCT_LAST_ID` int(11) NOT NULL default '0',
  `EXT_PARTY_LAST_ID` int(11) NOT NULL default '0',
  `EXT_INVOICE_LAST_ID` int(11) NOT NULL default '0'
) TYPE=MyISAM;

-- 
-- Dumping data for table `globals`
-- 
INSERT INTO `globals` ( `BUYER_PREFIX` , `SUPPLIER_PREFIX` , `AGENT_PREFIX` , `INVOICE_PREFIX` , `GEN_ORDER_PREFIX` , `SINGLE_ORDER_PREFIX` , `PRODUCT_PREFIX` , `EXT_PARTY_PREFIX` , `EXT_INVOICE_PREFIX` , `BUYER_LAST_ID` , `SUPPLIER_LAST_ID` , `AGENT_LAST_ID` , `INVOICE_LAST_ID` , `GEN_ORDER_LAST_ID` , `SINGLE_ORDER_LAST_ID` , `PRODUCT_LAST_ID` , `EXT_PARTY_LAST_ID` , `EXT_INVOICE_LAST_ID` )
VALUES (
'BUY', 'SUP', 'AGT', 'INV', 'GO', 'SO', 'PC', 'XPTY', 'XINV', '0', '0', '0', '0', '0', '0', '0', '0', '0');

-- --------------------------------------------------------

-- 
-- Table structure for table `groups`
-- 

CREATE TABLE `groups` (
  `gid` int(11) NOT NULL auto_increment,
  `employer_type` enum('Buyer','Supplier','Agent','External','Internal','UNDEFINED') NOT NULL default 'UNDEFINED',
  `name` varchar(50) default NULL,
  UNIQUE KEY `gid` (`gid`)
) TYPE=MyISAM AUTO_INCREMENT=8 ;

-- 
-- Dumping data for table `groups`
-- 

INSERT INTO `groups` (`gid`, `employer_type`, `name`) VALUES (1, 'Internal', 'Management-Internal');
INSERT INTO `groups` (`gid`, `employer_type`, `name`) VALUES (2, 'Internal', 'Staff-Internal');
INSERT INTO `groups` (`gid`, `employer_type`, `name`) VALUES (3, 'Buyer', 'Buyers');
INSERT INTO `groups` (`gid`, `employer_type`, `name`) VALUES (4, 'Supplier', 'Suppliers');
INSERT INTO `groups` (`gid`, `employer_type`, `name`) VALUES (5, 'Agent', 'Agents');
INSERT INTO `groups` (`gid`, `employer_type`, `name`) VALUES (6, 'Internal', 'System Administrators');
INSERT INTO `groups` (`gid`, `employer_type`, `name`) VALUES (7, 'External', 'External');

-- --------------------------------------------------------

-- 
-- Table structure for table `invoice`
-- 

CREATE TABLE `invoice` (
  `INVOICE_ID` int(10) unsigned NOT NULL auto_increment,
  `STATE` enum('ACTIVE','INACTIVE') NOT NULL default 'ACTIVE',
  `NUMBER` varchar(100) default '',
  `STATUS` enum('Closed','Credited','Draft','Pending') NOT NULL default 'Draft',
  `DATE` date default '0000-00-00',
  `MESSAGE` text,
  `DESCRIPTION` varchar(100) default '',
  `PAYOR_PARTY_ID` int(10) unsigned default '0',
  `PAYOR_CONTACT_ID` int(10) unsigned default '0',
  `PAYEE_CONTACT_ID` int(10) unsigned default '0',
  `PAYMENT_TERMS` varchar(100) default '',
  `CURRENCY_ID` int(10) unsigned default '0',
  `BILLED_AMOUNT` decimal(10,2) default '0.00',
  `PAID_AMOUNT` decimal(10,2) default '0.00',
  `DATE_CREATED` date NOT NULL default '0000-00-00',
  PRIMARY KEY  (`INVOICE_ID`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `invoice`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `invoice_history`
-- 

CREATE TABLE `invoice_history` (
  `INVOICE_HISTORY_ID` int(10) unsigned NOT NULL auto_increment,
  `INVOICE_ID` int(10) unsigned NOT NULL default '0',
  `MODIFIED_BY` int(10) unsigned NOT NULL default '0',
  `MODIFIED_DATE` date NOT NULL default '0000-00-00',
  `STATE` varchar(100) default '',
  `CHANGE_DESCRIPTION` varchar(100) default '',
  PRIMARY KEY  (`INVOICE_HISTORY_ID`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `invoice_history`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `invoice_line_item`
-- 

CREATE TABLE `invoice_line_item` (
  `INVOICE_LINE_ITEM_ID` int(10) unsigned NOT NULL auto_increment,
  `STATE` enum('ACTIVE','INACTIVE') NOT NULL default 'ACTIVE',
  `INVOICE_ID` int(10) unsigned NOT NULL default '0',
  `MILESTONE_ID` int(11) default '0',
  `TYPE` enum('Milestone','Free-Form','Write-Off') default 'Milestone',
  `DESCRIPTION` varchar(100) default '',
  `AMOUNT` decimal(10,2) default '0.00',
  `DATE` date default '0000-00-00',
  PRIMARY KEY  (`INVOICE_LINE_ITEM_ID`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `invoice_line_item`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `invoice_rel_order`
-- 

CREATE TABLE `invoice_rel_order` (
  `INVOICE_ID` int(10) unsigned NOT NULL default '0',
  `SINGLE_ORDER_ID` int(10) unsigned NOT NULL default '0',
  `GEN_ORDER_ID` int(10) unsigned NOT NULL default '0',
  `INVOICE_LINE_ITEM_ID` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`INVOICE_ID`,`SINGLE_ORDER_ID`,`GEN_ORDER_ID`,`INVOICE_LINE_ITEM_ID`)
) TYPE=MyISAM;

-- 
-- Dumping data for table `invoice_rel_order`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `invoice_term`
-- 

CREATE TABLE `invoice_term` (
  `INVOICE_TERM_ID` int(10) unsigned NOT NULL auto_increment,
  `STATE` enum('ACTIVE','INACTIVE') NOT NULL default 'ACTIVE',
  `INVOICE_ID` int(10) unsigned NOT NULL default '0',
  `DESCRIPTION` varchar(100) default '',
  PRIMARY KEY  (`INVOICE_TERM_ID`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `invoice_term`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `milestone`
-- 

CREATE TABLE `milestone` (
  `MILESTONE_ID` int(10) unsigned NOT NULL auto_increment,
  `STATE` enum('ACTIVE','INACTIVE') NOT NULL default 'ACTIVE',
  `SINGLE_ORDER_ID` int(11) default '0',
  `AMOUNT` decimal(10,2) default '0.00',
  `NAME` varchar(100) default '',
  `DESCRIPTION` varchar(100) default '',
  `RECIPIENT_ID` int(11) default '0',
  `MILESTONE_TYPE` enum('Incoming Payment','Agent Commission','B2S Payment') default 'Incoming Payment',
  `MILESTONE_STATUS` enum('Future','Due','Invoiced','Closed') default 'Future',
  `DATE` date default '0000-00-00',
  PRIMARY KEY  (`MILESTONE_ID`)
) TYPE=MyISAM COMMENT='MILESTONE_TYPE' AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `milestone`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `milestone_history`
-- 

CREATE TABLE `milestone_history` (
  `MILESTONE_HISTORY_ID` int(10) unsigned NOT NULL auto_increment,
  `MILESTONE_ID` int(10) unsigned NOT NULL default '0',
  `MODIFIED_BY` int(10) unsigned NOT NULL default '0',
  `MODIFIED_DATE` date NOT NULL default '0000-00-00',
  `STATE` varchar(100) default '',
  `CHANGE_DESCRIPTION` varchar(100) default '',
  PRIMARY KEY  (`MILESTONE_HISTORY_ID`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `milestone_history`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `party`
-- 

CREATE TABLE `party` (
  `PARTY_ID` int(10) unsigned NOT NULL auto_increment,
  `STATE` enum('ACTIVE','INACTIVE') default 'ACTIVE',
  `CODE` varchar(100) default '',
  `NAME` varchar(100) default '',
  `TYPE` enum('Buyer','Supplier','Agent','External','Internal','UNDEFINED') default 'UNDEFINED',
  `ADDRESS1` varchar(100) default '',
  `ADDRESS2` varchar(100) default '',
  `CITY` varchar(100) default '',
  `PROVINCE` varchar(100) default '',
  `POSTAL_CODE` varchar(100) default '',
  `COUNTRY_ID` int(10) default '0',
  `TEL` varchar(100) default '',
  `MOBILE` varchar(100) default '',
  `FAX` varchar(100) default '',
  `EMAIL` varchar(100) default '',
  `WEBSITE` varchar(100) default '',
  `EMPLOYER_ID` int(10) default '0',
  `POSITION` varchar(100) default '',
  `NOTES` text,
  `DATE_CREATED` datetime default '0000-00-00 00:00:00',
  PRIMARY KEY  (`PARTY_ID`)
) TYPE=MyISAM AUTO_INCREMENT=5 ;

-- 
-- Dumping data for table `party`
-- 

INSERT INTO `party` (`PARTY_ID`, `STATE`, `CODE`, `NAME`, `TYPE`, `ADDRESS1`, `ADDRESS2`, `CITY`, `PROVINCE`, `POSTAL_CODE`, `COUNTRY_ID`, `TEL`, `MOBILE`, `FAX`, `EMAIL`, `WEBSITE`, `EMPLOYER_ID`, `POSITION`, `NOTES`, `DATE_CREATED`) VALUES (1, 'ACTIVE', NULL, 'Company Name', 'Internal', 'Address Line 1', 'Address Line 2', 'City Name', '', '', 1, '', '', '', '', '', 0, '', '', '0000-00-00 00:00:00');

-- --------------------------------------------------------

-- 
-- Table structure for table `payment`
-- 

CREATE TABLE `payment` (
  `PAYMENT_ID` int(10) unsigned NOT NULL auto_increment,
  `STATE` enum('ACTIVE','INACTIVE') NOT NULL default 'ACTIVE',
  `INVOICE_ID` int(10) unsigned NOT NULL default '0',
  `DATE` date NOT NULL default '0000-00-00',
  `AMOUNT` decimal(10,2) NOT NULL default '0.00',
  `DOCUMENT_REF` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`PAYMENT_ID`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `payment`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `payment_b2s`
-- 

CREATE TABLE `payment_b2s` (
  `PAYMENT_B2S_ID` int(10) unsigned NOT NULL auto_increment,
  `STATE` enum('ACTIVE','INACTIVE') NOT NULL default 'ACTIVE',
  `DATE` date default '0000-00-00',
  `AMOUNT` decimal(10,2) default '0.00',
  `INVOICE_REF` varchar(100) default '',
  `MILESTONE_ID` int(10) unsigned default '0',
  PRIMARY KEY  (`PAYMENT_B2S_ID`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `payment_b2s`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `permissions`
-- 

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL default '0',
  `id_type` enum('group','user') NOT NULL default 'group',
  `application` varchar(100) default NULL,
  `part` varchar(50) default NULL,
  `detail` varchar(100) default NULL,
  `perms` set('View_All','View_Own','Modify_All','Modify_Own','All') default NULL
) TYPE=MyISAM;

-- 
-- Dumping data for table `permissions`
-- 

INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (1, 'group', 'contrack', '', '', 'All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (3, 'group', 'contrack', 'gen_order', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (2, 'group', 'contrack', 'agent_commissions', '', '');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (3, 'group', 'contrack', 'buyer_supplier_milestones', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (3, 'group', 'contrack', 'buyer_orders', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (3, 'group', 'contrack', 'buyer_invoices', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (3, 'group', 'contrack', 'buyer_to_organization_milestones', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (3, 'group', 'contrack', 'buyer', '', 'Modify_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (3, 'group', 'contrack', 'agent_orders', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (3, 'group', 'contrack', 'agent_commissions', '', '');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (2, 'group', 'contrack', 'agent', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (3, 'group', 'contrack', 'agent', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (3, 'group', 'contrack', 'gen_order_single_orders', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (3, 'group', 'contrack', 'gen_order_invoices', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (3, 'group', 'contrack', 'invoice', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (3, 'group', 'contrack', 'invoice_payments', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (3, 'group', 'contrack', 'single_order', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (3, 'group', 'contrack', 'single_order_milestones', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (3, 'group', 'contrack', 'single_order_product', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (3, 'group', 'contrack', 'single_order_production', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (3, 'group', 'contrack', 'supplier', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (3, 'group', 'contrack', 'supplier_to_organization_milestones', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (3, 'group', 'contrack', 'supplier_invoices', '', '');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (3, 'group', 'contrack', 'supplier_orders', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (3, 'group', 'contrack', 'supplier_buyer_milestones', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (3, 'group', 'contrack', 'audit_trail', '', '');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (2, 'group', 'contrack', 'admin', '', '');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (3, 'group', 'contrack', 'admin', '', '');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (5, 'group', 'contrack', 'agent', '', 'Modify_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (5, 'group', 'contrack', 'agent_commissions', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (5, 'group', 'contrack', 'agent_orders', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (5, 'group', 'contrack', 'buyer', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (5, 'group', 'contrack', 'buyer_to_organization_milestones', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (5, 'group', 'contrack', 'buyer_invoices', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (5, 'group', 'contrack', 'buyer_orders', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (5, 'group', 'contrack', 'buyer_supplier_milestones', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (5, 'group', 'contrack', 'gen_order', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (5, 'group', 'contrack', 'gen_order_single_orders', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (5, 'group', 'contrack', 'gen_order_invoices', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (5, 'group', 'contrack', 'invoice', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (5, 'group', 'contrack', 'invoice_payments', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (5, 'group', 'contrack', 'single_order', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (5, 'group', 'contrack', 'single_order_milestones', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (5, 'group', 'contrack', 'single_order_product', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (5, 'group', 'contrack', 'single_order_production', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (5, 'group', 'contrack', 'supplier', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (5, 'group', 'contrack', 'supplier_to_organization_milestones', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (5, 'group', 'contrack', 'supplier_invoices', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (5, 'group', 'contrack', 'supplier_orders', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (5, 'group', 'contrack', 'supplier_buyer_milestones', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (5, 'group', 'contrack', 'audit_trail', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (5, 'group', 'contrack', 'admin', '', '');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (4, 'group', 'contrack', 'agent_commissions', '', '');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (4, 'group', 'contrack', 'agent_orders', '', '');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (4, 'group', 'contrack', 'buyer', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (4, 'group', 'contrack', 'buyer_to_organization_milestones', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (4, 'group', 'contrack', 'buyer_invoices', '', '');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (4, 'group', 'contrack', 'buyer_orders', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (4, 'group', 'contrack', 'buyer_supplier_milestones', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (4, 'group', 'contrack', 'gen_order', '', '');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (4, 'group', 'contrack', 'gen_order_single_orders', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (4, 'group', 'contrack', 'gen_order_invoices', '', '');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (4, 'group', 'contrack', 'invoice', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (4, 'group', 'contrack', 'invoice_payments', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (4, 'group', 'contrack', 'single_order', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (4, 'group', 'contrack', 'single_order_milestones', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (4, 'group', 'contrack', 'single_order_product', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (4, 'group', 'contrack', 'single_order_production', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (4, 'group', 'contrack', 'supplier', '', 'Modify_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (4, 'group', 'contrack', 'supplier_to_organization_milestones', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (4, 'group', 'contrack', 'supplier_invoices', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (4, 'group', 'contrack', 'supplier_orders', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (4, 'group', 'contrack', 'supplier_buyer_milestones', '', 'View_Own');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (4, 'group', 'contrack', 'audit_trail', '', '');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (2, 'group', 'contrack', 'external', '', 'Modify_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (4, 'group', 'contrack', 'admin', '', '');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (2, 'group', 'contrack', 'agent_orders', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (2, 'group', 'contrack', 'buyer', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (2, 'group', 'contrack', 'buyer_to_organization_milestones', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (2, 'group', 'contrack', 'buyer_invoices', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (2, 'group', 'contrack', 'buyer_orders', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (2, 'group', 'contrack', 'buyer_supplier_milestones', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (2, 'group', 'contrack', 'gen_order', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (2, 'group', 'contrack', 'gen_order_single_orders', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (2, 'group', 'contrack', 'gen_order_invoices', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (2, 'group', 'contrack', 'invoice', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (2, 'group', 'contrack', 'invoice_payments', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (2, 'group', 'contrack', 'single_order', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (2, 'group', 'contrack', 'single_order_milestones', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (2, 'group', 'contrack', 'single_order_product', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (2, 'group', 'contrack', 'single_order_production', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (2, 'group', 'contrack', 'supplier', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (2, 'group', 'contrack', 'supplier_to_organization_milestones', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (2, 'group', 'contrack', 'supplier_invoices', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (2, 'group', 'contrack', 'supplier_orders', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (2, 'group', 'contrack', 'supplier_buyer_milestones', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (2, 'group', 'contrack', 'audit_trail', '', 'View_All');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (3, 'group', 'contrack', 'external', '', '');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (4, 'group', 'contrack', 'external', '', '');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (5, 'group', 'contrack', 'external', '', '');
INSERT INTO `permissions` (`id`, `id_type`, `application`, `part`, `detail`, `perms`) VALUES (4, 'group', 'contrack', 'agent', '', 'View_Own');

-- --------------------------------------------------------

-- 
-- Table structure for table `product`
-- 

CREATE TABLE `product` (
  `PRODUCT_ID` int(10) unsigned NOT NULL auto_increment,
  `STATE` enum('ACTIVE','INACTIVE') NOT NULL default 'ACTIVE',
  `PRODUCT_TYPE_ID` int(10) unsigned NOT NULL default '0',
  `PRODUCT_CODE` varchar(100) default '',
  `NAME` varchar(100) NOT NULL default '',
  `LABEL_HANG_TAGS` varchar(100) default '',
  `PACKING_INSTRUCTIONS` text,
  `SHIPPING_INSTRUCTIONS` text,
  `COMMENTS` text,
  `PIC_ATTACH_ID` int(11) default '0',
  `FABRIC_WEIGHT` varchar(100) default '',
  `FW_UNIT_ID` int(11) default '0',
  `DIMENSIONS` varchar(100) default '',
  `DIM_UNIT_ID` int(11) default '0',
  `PRODUCT_WEIGHT` varchar(100) default '',
  `PW_UNIT_ID` int(11) default '0',
  `COLOURS` varchar(100) default '',
  `PRINT_EMBROIDERY` varchar(100) default '',
  `CUSTOM_1` varchar(100) default '',
  `CUSTOM_2` varchar(100) default '',
  `CUSTOM_3` varchar(100) default '',
  `CUSTOM_4` varchar(100) default '',
  `CUSTOM_5` varchar(100) default '',
  `CUSTOM_6` varchar(100) default '',
  PRIMARY KEY  (`PRODUCT_ID`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `product`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `product_attribute`
-- 

CREATE TABLE `product_attribute` (
  `PRODUCT_ATTRIBUTE_ID` int(10) unsigned NOT NULL auto_increment,
  `PRODUCT_TYPE_ID` int(11) NOT NULL default '0',
  `NAME` varchar(100) NOT NULL default '',
  `DESCRIPTION` varchar(100) default '',
  PRIMARY KEY  (`PRODUCT_ATTRIBUTE_ID`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `product_attribute`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `product_type`
-- 

CREATE TABLE `product_type` (
  `PRODUCT_TYPE_ID` int(10) unsigned NOT NULL auto_increment,
  `NAME` varchar(100) NOT NULL default '',
  `DESCRIPTION` varchar(100) default '',
  PRIMARY KEY  (`PRODUCT_TYPE_ID`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `product_type`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `production_step`
-- 

CREATE TABLE `production_step` (
  `PRODUCTION_STEP_ID` int(10) unsigned NOT NULL auto_increment,
  `STATUS` enum('Draft','Open','Closed') NOT NULL default 'Draft',
  `PARENT_ID` int(10) NOT NULL default '0',
  `SEQ_ID` int(10) NOT NULL default '0',
  `SINGLE_ORDER_ID` int(10) unsigned NOT NULL default '0',
  `NAME` varchar(100) NOT NULL default '',
  `DESCRIPTION` varchar(100) default '',
  `PLANNED_START_DATE` date default '0000-00-00',
  `PLANNED_START_QTY` int(11) unsigned default '0',
  `ACTUAL_START_DATE` date default '0000-00-00',
  `ACTUAL_START_QTY` int(11) unsigned default '0',
  `PLANNED_END_DATE` date default '0000-00-00',
  `PLANNED_END_QTY` int(11) unsigned default '0',
  `ACTUAL_END_DATE` date default '0000-00-00',
  `ACTUAL_END_QTY` int(11) unsigned default '0',
  `START_UNIT_ID` int(11) unsigned default '0',
  `END_UNIT_ID` int(11) unsigned default '0',
  `COMMENTS` text,
  PRIMARY KEY  (`PRODUCTION_STEP_ID`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `production_step`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `production_step_history`
-- 

CREATE TABLE `production_step_history` (
  `PRODUCTION_STEP_HISTORY_ID` int(10) unsigned NOT NULL auto_increment,
  `PRODUCTION_STEP_ID` int(10) unsigned NOT NULL default '0',
  `MODIFIED_BY` int(10) unsigned NOT NULL default '0',
  `MODIFIED_DATE` date NOT NULL default '0000-00-00',
  `STATE` varchar(100) default '',
  `CHANGE_DESCRIPTION` varchar(100) default '',
  PRIMARY KEY  (`PRODUCTION_STEP_HISTORY_ID`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `production_step_history`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `single_order`
-- 

CREATE TABLE `single_order` (
  `SINGLE_ORDER_ID` int(10) unsigned NOT NULL auto_increment,
  `STATE` enum('ACTIVE','INACTIVE') NOT NULL default 'ACTIVE',
  `CODE` varchar(100) NOT NULL default '',
  `STATUS` enum('Closed','Open') NOT NULL default 'Open',
  `FRIENDLY_NAME` varchar(100) NOT NULL default '',
  `GEN_ORDER_ID` int(10) unsigned default '0',
  `SUPPLIER_ID` int(10) unsigned default '0',
  `PRODUCT_ID` int(10) unsigned default '0',
  `CLIENT_ORDER_ID` varchar(100) default '',
  `CLIENT_PRODUCT_CODE` varchar(100) default '',
  `UNIT_PRICE` decimal(10,2) default '0.00',
  `PO_DATE` date default '0000-00-00',
  `PO_NUMBER` varchar(100) default '',
  `COMMISSION` int(11) default '0',
  `IS_COMMISSION_VALUE` tinyint(1) default '0',
  `IS_BUYER_COMMISSIONER` tinyint(1) default '0',
  `PAYMENT_INSTRUCTIONS` text,
  `COMMENTS` text,
  `AGENT_COMMISSION` int(11) default '0',
  `IS_AGENT_COMMISSION_VALUE` tinyint(1) default '0',
  `DATE_CREATED` date default '0000-00-00',
  PRIMARY KEY  (`SINGLE_ORDER_ID`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `single_order`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `single_order_history`
-- 

CREATE TABLE `single_order_history` (
  `SINGLE_ORDER_HISTORY_ID` int(10) unsigned NOT NULL auto_increment,
  `SINGLE_ORDER_ID` int(10) unsigned NOT NULL default '0',
  `MODIFIED_BY` int(10) unsigned NOT NULL default '0',
  `MODIFIED_DATE` date NOT NULL default '0000-00-00',
  `STATE` varchar(100) default '',
  `CHANGE_DESCRIPTION` varchar(100) default '',
  PRIMARY KEY  (`SINGLE_ORDER_HISTORY_ID`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `single_order_history`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `single_order_rel_party`
-- 

CREATE TABLE `single_order_rel_party` (
  `SINGLE_ORDER_ID` int(11) NOT NULL default '0',
  `PARTY_ID` int(11) NOT NULL default '0',
  PRIMARY KEY  (`SINGLE_ORDER_ID`,`PARTY_ID`)
) TYPE=MyISAM;

-- 
-- Dumping data for table `single_order_rel_party`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `unit`
-- 

CREATE TABLE `unit` (
  `UNIT_ID` int(10) unsigned NOT NULL auto_increment,
  `ABBREV` varchar(100) NOT NULL default '',
  `NAME` varchar(100) default '',
  PRIMARY KEY  (`UNIT_ID`)
) TYPE=MyISAM AUTO_INCREMENT=3 ;

-- 
-- Dumping data for table `unit`
-- 

INSERT INTO `unit` (`UNIT_ID`, `ABBREV`, `NAME`) VALUES (1, 'm', 'Meter');
INSERT INTO `unit` (`UNIT_ID`, `ABBREV`, `NAME`) VALUES (2, 'Kg', 'Kilogram');

-- --------------------------------------------------------

-- 
-- Table structure for table `user`
-- 

CREATE TABLE `user` (
  `uid` int(10) unsigned NOT NULL auto_increment,
  `party_id` int(10) unsigned NOT NULL default '0',
  `username` varchar(20) NOT NULL default '',
  `passwd` varchar(20) NOT NULL default '',
  `nologin` tinyint(1) NOT NULL default '0',
  `first_login` datetime default NULL,
  `last_login` datetime default NULL,
  `count_logins` int(10) unsigned NOT NULL default '0',
  `count_pages` int(10) unsigned NOT NULL default '0',
  `time_online` int(11) NOT NULL default '0',
  PRIMARY KEY  (`uid`),
  KEY `username` (`username`)
) TYPE=MyISAM AUTO_INCREMENT=3 ;

-- 
-- Dumping data for table `user`
-- 

INSERT INTO `user` (`uid`, `party_id`, `username`, `passwd`, `nologin`, `first_login`, `last_login`, `count_logins`, `count_pages`, `time_online`) VALUES (1, 1, 'admin', 'admin', 0, NULL, '2005-03-17 05:09:02', 25, 0, 0);

-- --------------------------------------------------------

-- 
-- Table structure for table `user_rel_group`
-- 

CREATE TABLE `user_rel_group` (
  `id` int(10) NOT NULL auto_increment,
  `uid` int(11) NOT NULL default '0',
  `gid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM AUTO_INCREMENT=3 ;

-- 
-- Dumping data for table `user_rel_group`
-- 

INSERT INTO `user_rel_group` (`id`, `uid`, `gid`) VALUES (1, 1, 1);
INSERT INTO `user_rel_group` (`id`, `uid`, `gid`) VALUES (2, 2, 1);
