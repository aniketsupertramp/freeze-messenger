-- phpMyAdmin SQL Dump
-- version 3.3.7deb5build0.10.10.1
-- http://www.phpmyadmin.net
--
-- Host: 10.10.10.1
-- Generation Time: May 07, 2011 at 01:55 PM
-- Server version: 5.1.41
-- PHP Version: 5.3.3-1ubuntu9.5

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `vbulletin`
--

-- --------------------------------------------------------

--
-- Table structure for table `vrc_censorBlackWhiteLists`
--

CREATE TABLE IF NOT EXISTS `vrc_censorBlackWhiteLists` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `listid` int(10) NOT NULL,
  `roomid` int(10) NOT NULL,
  `status` enum('block','unblock') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Unique2` (`listid`,`roomid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;
