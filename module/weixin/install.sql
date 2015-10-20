
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
