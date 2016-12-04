ALTER TABLE `pre_administrator` ADD `producttypes` text NOT NULL;

DROP TABLE IF EXISTS `pre_product`;
CREATE TABLE IF NOT EXISTS `pre_product` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `type` tinyint(1) unsigned NOT NULL,
  `briefintro` varchar(255) NULL,
  `introduction` text NULL,
  `icon` tinyint(1) NULL,
  `photo` tinyint(1) NULL,
  `soldout` int(11) unsigned NOT NULL DEFAULT '0',
  `text_color` mediumint(8) unsigned NOT NULL DEFAULT '16777215',
  `background_color` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `icon_background` mediumint(8) unsigned NOT NULL DEFAULT '13164714',
  `displayorder` tinyint(4) NOT NULL DEFAULT '0',
  `hide` tinyint(1) NOT NULL DEFAULT '0',
  `flowid` mediumint(8) NOT NULL DEFAULT '0',
  `is_ribbon` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_productacronym`;
CREATE TABLE IF NOT EXISTS `pre_productacronym` (
	`id` mediumint(8) unsigned NOT NULL,
	`name` varchar(50) NOT NULL,
	KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_productcountdown`;
CREATE TABLE IF NOT EXISTS `pre_productcountdown` (
  `id` int(11) unsigned NOT NULL,
  `masked_priceid` int(11) unsigned DEFAULT NULL,
  `start_time` int(11) unsigned NOT NULL,
  `end_time` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_productprice`;
CREATE TABLE IF NOT EXISTS `pre_productprice` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `productid` mediumint(8) unsigned NOT NULL,
  `subtype` varchar(100) DEFAULT NULL,
  `briefintro` varchar(255) NOT NULL,
  `price` decimal(9,2) NOT NULL,
  `amount` int(11) unsigned NOT NULL,
  `amountunit` mediumint(8) unsigned NOT NULL,
  `displayorder` tinyint(4) NOT NULL,
  `storageid` mediumint(8) unsigned DEFAULT NULL,
  `quantitylimit` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `productid` (`productid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_productpricelimit`;
CREATE TABLE IF NOT EXISTS `pre_productpricelimit` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `productid` mediumint(8) unsigned NOT NULL,
  `priceid` int(11) unsigned NOT NULL,
  `usergroupid` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `productid` (`productid`),
  KEY `priceid` (`priceid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_productquantitylimit`;
CREATE TABLE IF NOT EXISTS `pre_productquantitylimit` (
  `priceid` int(11) unsigned NOT NULL,
  `userid` mediumint(8) unsigned NOT NULL,
  `amount` smallint(5) unsigned NOT NULL,
  UNIQUE KEY `priceid` (`priceid`,`userid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_productstorage`;
CREATE TABLE IF NOT EXISTS `pre_productstorage` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `productid` mediumint(8) unsigned NOT NULL,
  `remark` varchar(15) NOT NULL,
  `num` int(11) NOT NULL,
  `mode` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `productid` (`productid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_productstoragelog`;
CREATE TABLE IF NOT EXISTS `pre_productstoragelog` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `storageid` mediumint(8) unsigned NOT NULL,
  `dateline` int(10) unsigned NOT NULL,
  `amount` int(11) NOT NULL,
  `totalcosts` decimal(9,2) NOT NULL,
  `adminid` mediumint(8) unsigned NOT NULL,
  `bankaccountid` mediumint(8) unsigned NOT NULL,
  `productname` varchar(50) NOT NULL,
  `storageremark` varchar(15) NOT NULL,
  `importamount` decimal(9,2) NOT NULL,
  `importamountunit` varchar(15) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `dateline` (`dateline`),
  KEY `adminid` (`adminid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_producttype`;
CREATE TABLE IF NOT EXISTS `pre_producttype` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(33) NOT NULL,
  `displayorder` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_productunit`;
CREATE TABLE IF NOT EXISTS `pre_productunit` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `type` tinyint(1) NOT NULL,
  `hidden` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_productflow`;
CREATE TABLE IF NOT EXISTS `pre_productflow` (
	`id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
	`name` varchar(32) NOT NULL,
	`hidden` tinyint(1) NOT NULL DEFAULT '0',
	`displayorder` tinyint(4) NOT NULL DEFAULT '0',
	PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
