SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

DROP TABLE IF EXISTS `hut_administrator`;
CREATE TABLE IF NOT EXISTS `hut_administrator` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `account` varchar(15) NOT NULL,
  `pwmd5` varchar(32) NOT NULL,
  `nickname` varchar(50) NOT NULL,
  `permissions` text NOT NULL,
  `limitation` text NOT NULL,
  `logintime` int(11) unsigned NOT NULL,
  `realname` varchar(50) NOT NULL,
  `mobile` varchar(11) NOT NULL,
  `loginkey` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `account` (`account`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `hut_announcement`;
CREATE TABLE IF NOT EXISTS `hut_announcement` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `time_start` int(11) unsigned NOT NULL,
  `time_end` int(11) unsigned NOT NULL,
  `displayorder` tinyint(3) NOT NULL,
  `dateline` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `hut_authkey`;
CREATE TABLE IF NOT EXISTS `hut_authkey` (
  `user` varchar(32) NOT NULL,
  `key` varchar(32) NOT NULL,
  `expiry` int(11) unsigned NOT NULL,
  PRIMARY KEY (`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `hut_autoreply`;
CREATE TABLE IF NOT EXISTS `hut_autoreply` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `keyword` text NOT NULL,
  `reply` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `hut_bankaccount`;
CREATE TABLE IF NOT EXISTS `hut_bankaccount` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `remark` varchar(50) NOT NULL,
  `amount` decimal(9,2) NOT NULL,
  `addressrange` mediumint(8) unsigned NOT NULL,
  `handleorder` tinyint(1) NOT NULL,
  `orderpaymentmethod` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `addressrange` (`addressrange`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `hut_bankaccountlog`;
CREATE TABLE IF NOT EXISTS `hut_bankaccountlog` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `accountid` mediumint(8) unsigned NOT NULL,
  `delta` decimal(9,2) NOT NULL,
  `reason` varchar(100) NOT NULL,
  `operatorid` mediumint(8) NOT NULL,
  `operation` tinyint(3) unsigned NOT NULL,
  `targetid` mediumint(8) NOT NULL,
  `dateline` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `accountid` (`accountid`),
  KEY `dateline` (`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `hut_order`;
CREATE TABLE IF NOT EXISTS `hut_order` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `userid` mediumint(8) unsigned NOT NULL,
  `dateline` int(11) unsigned NOT NULL,
  `status` tinyint(1) unsigned NOT NULL,
  `totalprice` decimal(9,2) unsigned NOT NULL,
  `addressid` mediumint(8) unsigned NOT NULL,
  `extaddress` varchar(50) NOT NULL,
  `addressee` varchar(50) NOT NULL,
  `mobile` varchar(11) NOT NULL,
  `message` text NOT NULL,
  `deliverymethod` tinyint(4) NOT NULL,
  `dtime_from` int(11) unsigned NOT NULL,
  `dtime_to` int(11) unsigned NOT NULL,
  `deliveryfee` decimal(5,2) NOT NULL DEFAULT '0.00',
  `paymentmethod` tinyint(4) NOT NULL,
  `customlabel` varchar(32) NOT NULL,
  `tradeid` varchar(255) NOT NULL,
  `tradestate` tinyint(4) NOT NULL,
  `tradetime` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `tradetime` (`tradetime`),
  KEY `dateline` (`dateline`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `hut_ordercomment`;
CREATE TABLE IF NOT EXISTS `hut_ordercomment` (
  `orderid` mediumint(8) unsigned NOT NULL,
  `dateline` int(11) unsigned NOT NULL,
  `level1` tinyint(3) unsigned NOT NULL,
  `level2` tinyint(3) unsigned NOT NULL,
  `level3` tinyint(3) unsigned NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`orderid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `hut_orderdetail`;
CREATE TABLE IF NOT EXISTS `hut_orderdetail` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `orderid` mediumint(8) unsigned NOT NULL,
  `productid` mediumint(8) unsigned NOT NULL,
  `storageid` mediumint(8) unsigned DEFAULT NULL,
  `productname` varchar(50) NOT NULL,
  `subtype` varchar(50) NOT NULL,
  `amount` int(11) unsigned NOT NULL,
  `amountunit` varchar(30) NOT NULL,
  `number` int(11) unsigned NOT NULL,
  `subtotal` decimal(9,2) unsigned NOT NULL,
  `state` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `orderid` (`orderid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `hut_orderlog`;
CREATE TABLE IF NOT EXISTS `hut_orderlog` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `orderid` mediumint(8) unsigned NOT NULL,
  `operator` mediumint(8) unsigned DEFAULT NULL,
  `operatorgroup` tinyint(4) unsigned NOT NULL,
  `operation` smallint(5) unsigned NOT NULL,
  `extra` varchar(255) DEFAULT NULL,
  `dateline` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `orderid` (`orderid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `hut_prepaidreward`;
CREATE TABLE IF NOT EXISTS `hut_prepaidreward` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `minamount` decimal(9,2) NOT NULL,
  `maxamount` decimal(9,2) NOT NULL,
  `reward` decimal(9,2) NOT NULL,
  `etime_start` int(11) unsigned NOT NULL,
  `etime_end` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `hut_returnedorder`;
CREATE TABLE IF NOT EXISTS `hut_returnedorder` (
  `id` mediumint(8) unsigned NOT NULL,
  `dateline` int(11) unsigned NOT NULL,
  `reason` text NOT NULL,
  `state` tinyint(4) NOT NULL,
  `returnedfee` decimal(9,2) NOT NULL,
  `adminreply` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `hut_returnedorderdetail`;
CREATE TABLE IF NOT EXISTS `hut_returnedorderdetail` (
  `id` int(11) unsigned NOT NULL,
  `orderid` mediumint(8) unsigned NOT NULL,
  `number` int(11) unsigned NOT NULL,
  `state` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `orderid` (`orderid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `hut_user`;
CREATE TABLE IF NOT EXISTS `hut_user` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `account` varchar(50) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `mobile` varchar(11) DEFAULT NULL,
  `pwmd5` varchar(32) NOT NULL,
  `nickname` varchar(50) NOT NULL,
  `realname` varchar(50) NOT NULL,
  `regtime` int(11) unsigned NOT NULL,
  `qqopenid` varchar(32) DEFAULT NULL,
  `wxopenid` varchar(32) DEFAULT NULL,
  `wxunionid` varchar(32) DEFAULT NULL,
  `wallet` decimal(9,2) NOT NULL,
  `formkey` tinyint(4) unsigned NOT NULL,
  `logintime` int(11) unsigned NOT NULL,
  `loginkey` smallint(5) unsigned NOT NULL,
  `trickflag` int(11) unsigned NOT NULL,
  `referrerid` mediumint(8) unsigned NOT NULL,
  `getuiclientid` varchar(50) DEFAULT NULL,
  `groupid` mediumint(8) unsigned NOT NULL,
  `addressid` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `account` (`account`),
  UNIQUE KEY `qqopenid` (`qqopenid`),
  UNIQUE KEY `mobile` (`mobile`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `wxopenid` (`wxopenid`),
  UNIQUE KEY `wxunionid` (`wxunionid`),
  KEY `addressid` (`addressid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `hut_usergroup`;
CREATE TABLE IF NOT EXISTS `hut_usergroup` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `type` tinyint(4) NOT NULL,
  `name` varchar(50) NOT NULL,
  `minordernum` mediumint(8) unsigned NOT NULL,
  `maxordernum` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `hut_userwalletlog`;
CREATE TABLE IF NOT EXISTS `hut_userwalletlog` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint(8) unsigned NOT NULL,
  `dateline` int(11) unsigned NOT NULL,
  `type` tinyint(4) unsigned NOT NULL,
  `delta` decimal(9,2) NOT NULL,
  `cost` decimal(9,2) NOT NULL,
  `recharged` tinyint(1) NOT NULL,
  `orderid` mediumint(8) unsigned NOT NULL,
  `paymentmethod` tinyint(4) NOT NULL,
  `tradeid` varchar(255) NOT NULL,
  `tradestate` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


ALTER TABLE `hut_bankaccountlog`
  ADD CONSTRAINT `accountid` FOREIGN KEY (`accountid`) REFERENCES `hut_bankaccount` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
