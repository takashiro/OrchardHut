
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

ALTER TABLE `pre_bankaccountlog`
  ADD CONSTRAINT `accountid` FOREIGN KEY (`accountid`) REFERENCES `pre_bankaccount` (`id`);
