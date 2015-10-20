
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
