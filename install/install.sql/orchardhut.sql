-- phpMyAdmin SQL Dump
-- version 3.5.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 08, 2013 at 01:53 PM
-- Server version: 5.5.24-log
-- PHP Version: 5.3.13

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `orchardhut`
--

-- --------------------------------------------------------

--
-- Table structure for table `hut_addresscomponent`
--

CREATE TABLE IF NOT EXISTS `hut_addresscomponent` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `formatid` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `hut_addresscomponent`
--

INSERT INTO `hut_addresscomponent` (`id`, `name`, `formatid`) VALUES
(1, '江安校区', 1),
(2, '望江校区', 1);

-- --------------------------------------------------------

--
-- Table structure for table `hut_addressformat`
--

CREATE TABLE IF NOT EXISTS `hut_addressformat` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `displayorder` tinyint(3) NOT NULL,
  `name` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `hut_addressformat`
--

INSERT INTO `hut_addressformat` (`id`, `displayorder`, `name`) VALUES
(1, 0, '校区'),
(2, 1, '宿舍'),
(3, 2, '单元');

-- --------------------------------------------------------

--
-- Table structure for table `hut_administrator`
--

CREATE TABLE IF NOT EXISTS `hut_administrator` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `hut_announcement`
--

CREATE TABLE IF NOT EXISTS `hut_announcement` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `hut_deliveryaddress`
--

CREATE TABLE IF NOT EXISTS `hut_deliveryaddress` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` mediumint(8) unsigned NOT NULL,
  `extaddress` varchar(50) NOT NULL,
  `addressee` varchar(50) NOT NULL,
  `mobile` varchar(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `hut_deliveryaddresscomponent`
--

CREATE TABLE IF NOT EXISTS `hut_deliveryaddresscomponent` (
  `addressid` mediumint(8) unsigned NOT NULL,
  `formatid` mediumint(8) unsigned NOT NULL,
  `componentid` mediumint(8) unsigned NOT NULL,
  KEY `deliveryaddressid` (`addressid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `hut_order`
--

CREATE TABLE IF NOT EXISTS `hut_order` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(8) unsigned NOT NULL,
  `dateline` int(11) unsigned NOT NULL,
  `status` tinyint(1) unsigned NOT NULL,
  `extaddress` varchar(50) NOT NULL,
  `addressee` varchar(50) NOT NULL,
  `totalprice` decimal(9,2) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `hut_orderaddresscomponent`
--

CREATE TABLE IF NOT EXISTS `hut_orderaddresscomponent` (
  `orderid` mediumint(8) unsigned NOT NULL,
  `formatid` mediumint(8) unsigned NOT NULL,
  `componentid` mediumint(8) unsigned NOT NULL,
  KEY `orderid` (`orderid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `hut_orderdetail`
--

CREATE TABLE IF NOT EXISTS `hut_orderdetail` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `orderid` mediumint(8) unsigned NOT NULL,
  `productid` mediumint(8) unsigned NOT NULL,
  `productamount` int(11) unsigned NOT NULL,
  `productunit` varchar(30) NOT NULL,
  `price` decimal(9,2) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `orderid` (`orderid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `hut_product`
--

CREATE TABLE IF NOT EXISTS `hut_product` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `type` tinyint(1) unsigned NOT NULL,
  `introdution` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `hut_productunit`
--

CREATE TABLE IF NOT EXISTS `hut_productunit` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `hut_user`
--

CREATE TABLE IF NOT EXISTS `hut_user` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `account` varchar(50) NOT NULL,
  `email` varchar(50) DEFAULT NULL,
  `mobile` varchar(11) DEFAULT NULL,
  `pwmd5` varchar(32) NOT NULL,
  `nickname` varchar(50) NOT NULL,
  `realname` varchar(50) NOT NULL,
  `regtime` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `account` (`account`),
  UNIQUE KEY `mobile` (`mobile`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `hut_user`
--

INSERT INTO `hut_user` (`id`, `account`, `email`, `mobile`, `pwmd5`, `nickname`, `realname`, `regtime`) VALUES
(1, 'takashiro', '', '', 'e954d7b11096bd96d699b61528300bef', '', '', 1383916854);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
