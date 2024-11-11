-- Adminer 4.8.1 MySQL 5.6.51 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP DATABASE IF EXISTS `mvcapp`;
CREATE DATABASE `mvcapp` /*!40100 DEFAULT CHARACTER SET utf8 */;
USE `mvcapp`;

DROP TABLE IF EXISTS `posts`;
CREATE TABLE `posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `posts` (`id`, `title`) VALUES
(1,	'Post One'),
(2,	'Post Two');

-- 2024-11-11 19:47:46
