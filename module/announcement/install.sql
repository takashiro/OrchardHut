
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
