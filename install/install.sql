SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


DROP TABLE IF EXISTS `hut_addresscomponent`;
CREATE TABLE IF NOT EXISTS `hut_addresscomponent` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `formatid` mediumint(8) unsigned NOT NULL,
  `parentid` mediumint(8) unsigned NOT NULL,
  `displayorder` tinyint(3) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `hut_addressformat`;
CREATE TABLE IF NOT EXISTS `hut_addressformat` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `displayorder` tinyint(3) NOT NULL,
  `name` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `hut_administrator`;
CREATE TABLE IF NOT EXISTS `hut_administrator` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `account` varchar(15) NOT NULL,
  `pwmd5` varchar(32) NOT NULL,
  `nickname` varchar(50) NOT NULL,
  `permission` int(11) NOT NULL,
  `limitation` text NOT NULL,
  `logintime` int(11) unsigned NOT NULL,
  `realname` varchar(50) NOT NULL,
  `mobile` varchar(11) NOT NULL,
  PRIMARY KEY (`id`)
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
  `expiry` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `hut_autoreply`;
CREATE TABLE IF NOT EXISTS `hut_autoreply` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `keyword` text NOT NULL,
  `reply` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `hut_deliveryaddress`;
CREATE TABLE IF NOT EXISTS `hut_deliveryaddress` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` mediumint(8) unsigned NOT NULL,
  `extaddress` varchar(50) NOT NULL,
  `addressee` varchar(50) NOT NULL,
  `mobile` varchar(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `hut_deliveryaddresscomponent`;
CREATE TABLE IF NOT EXISTS `hut_deliveryaddresscomponent` (
  `addressid` mediumint(8) unsigned NOT NULL,
  `formatid` mediumint(8) unsigned NOT NULL,
  `componentid` mediumint(8) unsigned NOT NULL,
  KEY `deliveryaddressid` (`addressid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `hut_order`;
CREATE TABLE IF NOT EXISTS `hut_order` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(8) unsigned NOT NULL,
  `dateline` int(11) unsigned NOT NULL,
  `status` tinyint(1) unsigned NOT NULL,
  `totalprice` decimal(9,2) unsigned NOT NULL,
  `priceunit` mediumint(8) unsigned NOT NULL,
  `extaddress` varchar(50) NOT NULL,
  `addressee` varchar(50) NOT NULL,
  `mobile` varchar(11) NOT NULL,
  `message` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `dateline` (`dateline`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `hut_orderaddresscomponent`;
CREATE TABLE IF NOT EXISTS `hut_orderaddresscomponent` (
  `orderid` mediumint(8) unsigned NOT NULL,
  `formatid` mediumint(8) unsigned NOT NULL,
  `componentid` mediumint(8) unsigned NOT NULL,
  KEY `orderid` (`orderid`)
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

DROP TABLE IF EXISTS `hut_product`;
CREATE TABLE IF NOT EXISTS `hut_product` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `type` tinyint(1) unsigned NOT NULL,
  `introduction` text NOT NULL,
  `icon` tinyint(1) NOT NULL,
  `photo` tinyint(1) NOT NULL,
  `soldout` int(11) unsigned NOT NULL,
  `text_color` mediumint(8) unsigned NOT NULL DEFAULT '16777215',
  `background_color` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `icon_background` mediumint(8) unsigned NOT NULL DEFAULT '13164714',
  `displayorder` tinyint(4) NOT NULL,
  `hide` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `hut_productcountdown`;
CREATE TABLE IF NOT EXISTS `hut_productcountdown` (
  `id` int(11) unsigned NOT NULL,
  `masked_priceid` int(11) unsigned DEFAULT NULL,
  `start_time` int(11) unsigned NOT NULL,
  `end_time` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `hut_productprice`;
CREATE TABLE IF NOT EXISTS `hut_productprice` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `productid` mediumint(8) unsigned NOT NULL,
  `subtype` varchar(100) DEFAULT NULL,
  `price` decimal(9,2) NOT NULL,
  `priceunit` mediumint(8) unsigned NOT NULL,
  `amount` int(11) unsigned NOT NULL,
  `amountunit` mediumint(8) unsigned NOT NULL,
  `displayorder` tinyint(4) NOT NULL,
  `storageid` mediumint(8) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `productid` (`productid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `hut_productstorage`;
CREATE TABLE IF NOT EXISTS `hut_productstorage` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `productid` mediumint(8) unsigned NOT NULL,
  `remark` varchar(15) NOT NULL,
  `num` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `productid` (`productid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `hut_productunit`;
CREATE TABLE IF NOT EXISTS `hut_productunit` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `type` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `hut_user`;
CREATE TABLE IF NOT EXISTS `hut_user` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `account` varchar(50) NOT NULL,
  `email` varchar(50) DEFAULT NULL,
  `mobile` varchar(11) DEFAULT NULL,
  `pwmd5` varchar(32) NOT NULL,
  `nickname` varchar(50) NOT NULL,
  `realname` varchar(50) NOT NULL,
  `regtime` int(11) unsigned NOT NULL,
  `qqopenid` varchar(32) DEFAULT NULL,
  `wxopenid` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `account` (`account`),
  UNIQUE KEY `qqopenid` (`qqopenid`),
  UNIQUE KEY `mobile` (`mobile`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `wxopenid` (`wxopenid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
