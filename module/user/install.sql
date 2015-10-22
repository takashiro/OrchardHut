
ALTER TABLE `pre_user` ADD `groupid` mediumint(8) unsigned NOT NULL;

DROP TABLE IF EXISTS `pre_usergroup`;
CREATE TABLE IF NOT EXISTS `pre_usergroup` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `type` tinyint(4) NOT NULL,
  `name` varchar(50) NOT NULL,
  `minordernum` mediumint(8) unsigned NOT NULL,
  `maxordernum` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
