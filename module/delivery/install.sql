
ALTER TABLE `pre_user` ADD `addressid` mediumint(8) unsigned NULL;
ALTER TABLE `pre_user` ADD KEY `addressid` (`addressid`);

ALTER TABLE `pre_administrator` ADD `limitation` text NOT NULL;

DROP TABLE IF EXISTS `pre_addresscomponent`;
CREATE TABLE IF NOT EXISTS `pre_addresscomponent` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `parentid` mediumint(8) unsigned NOT NULL,
  `displayorder` tinyint(3) NOT NULL,
  `hidden` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_deliveryaddress`;
CREATE TABLE IF NOT EXISTS `pre_deliveryaddress` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` mediumint(8) unsigned NOT NULL,
  `addressid` mediumint(8) unsigned NOT NULL,
  `extaddress` varchar(50) NOT NULL,
  `addressee` varchar(50) NOT NULL,
  `mobile` varchar(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_deliverytime`;
CREATE TABLE IF NOT EXISTS `pre_deliverytime` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `hidden` tinyint(1) NOT NULL,
  `time_from` mediumint(8) unsigned NOT NULL,
  `time_to` mediumint(8) unsigned NOT NULL,
  `deadline` mediumint(8) unsigned NOT NULL,
  `effective_time` int(11) unsigned NOT NULL,
  `expiry_time` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_station`;
CREATE TABLE IF NOT EXISTS `pre_station` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `address` varchar(255) NULL,
  `orderrange` mediumint(8) unsigned NULL,
  `pauseprinting` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `packqrcode` smallint(5) unsigned NOT NULL DEFAULT '0',
  `packqrcodeexpiry` int(11) unsigned NULL,
  PRIMARY KEY (`id`)
) ENGINE=MEMORY  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_stationorder`;
CREATE TABLE IF NOT EXISTS `pre_stationorder` (
  `stationid` mediumint(8) unsigned NOT NULL,
  `orderid` mediumint(8) unsigned NOT NULL,
  `dateline` int(11) unsigned NOT NULL,
  KEY `stationid` (`stationid`),
  UNIQUE KEY `orderid` (`orderid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
