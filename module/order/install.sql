
DROP TABLE IF EXISTS `pre_order`;
CREATE TABLE IF NOT EXISTS `pre_order` (
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
  `packcode` tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `tradetime` (`tradetime`),
  KEY `dateline` (`dateline`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_ordercomment`;
CREATE TABLE IF NOT EXISTS `pre_ordercomment` (
  `orderid` mediumint(8) unsigned NOT NULL,
  `dateline` int(11) unsigned NOT NULL,
  `level1` tinyint(3) unsigned NOT NULL,
  `level2` tinyint(3) unsigned NOT NULL,
  `level3` tinyint(3) unsigned NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`orderid`),
  KEY `level` (`level1`,`level2`,`level3`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_orderdetail`;
CREATE TABLE IF NOT EXISTS `pre_orderdetail` (
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

DROP TABLE IF EXISTS `pre_orderlog`;
CREATE TABLE IF NOT EXISTS `pre_orderlog` (
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
