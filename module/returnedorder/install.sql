
DROP TABLE IF EXISTS `pre_returnedorder`;
CREATE TABLE IF NOT EXISTS `pre_returnedorder` (
  `id` mediumint(8) unsigned NOT NULL,
  `dateline` int(11) unsigned NOT NULL,
  `reason` text NOT NULL,
  `state` tinyint(4) NOT NULL,
  `returnedfee` decimal(9,2) NOT NULL,
  `adminreply` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_returnedorderdetail`;
CREATE TABLE IF NOT EXISTS `pre_returnedorderdetail` (
  `id` int(11) unsigned NOT NULL,
  `orderid` mediumint(8) unsigned NOT NULL,
  `number` int(11) unsigned NOT NULL,
  `state` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `orderid` (`orderid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
