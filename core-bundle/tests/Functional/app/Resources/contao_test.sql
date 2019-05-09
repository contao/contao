-- MySQL dump 10.17  Distrib 10.3.12-MariaDB, for osx10.14 (x86_64)
--
-- Host: localhost    Database: contao_test
-- ------------------------------------------------------
-- Server version	10.3.12-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `tl_article`
--

DROP TABLE IF EXISTS `tl_article`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_article` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `sorting` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `alias` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `author` int(10) unsigned NOT NULL DEFAULT 0,
  `inColumn` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `keywords` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `showTeaser` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `teaserCssID` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `teaser` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `printable` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customTpl` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `protected` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `groups` blob DEFAULT NULL,
  `guests` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cssID` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `published` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `start` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `stop` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `alias` (`alias`),
  KEY `pid_start_stop_published_sorting` (`pid`,`start`,`stop`,`published`,`sorting`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_article`
--

LOCK TABLES `tl_article` WRITE;
/*!40000 ALTER TABLE `tl_article` DISABLE KEYS */;
INSERT INTO `tl_article` VALUES (4,4,128,1539686711,'Home','',1,'main',NULL,'','a:2:{i:0;s:0:\"\";i:1;s:0:\"\";}',NULL,'','','',NULL,'','a:2:{i:0;s:0:\"\";i:1;s:0:\"\";}','1','',''),(13,8,128,1539686843,'Home','',1,'main',NULL,'','a:2:{i:0;s:0:\"\";i:1;s:0:\"\";}',NULL,'','','',NULL,'','a:2:{i:0;s:0:\"\";i:1;s:0:\"\";}','1','','');
/*!40000 ALTER TABLE `tl_article` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_calendar`
--

DROP TABLE IF EXISTS `tl_calendar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_calendar` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `jumpTo` int(10) unsigned NOT NULL DEFAULT 0,
  `protected` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `groups` blob DEFAULT NULL,
  `allowComments` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `notify` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sortOrder` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `perPage` smallint(5) unsigned NOT NULL DEFAULT 0,
  `moderate` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `bbcode` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `requireLogin` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `disableCaptcha` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_calendar`
--

LOCK TABLES `tl_calendar` WRITE;
/*!40000 ALTER TABLE `tl_calendar` DISABLE KEYS */;
/*!40000 ALTER TABLE `tl_calendar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_calendar_events`
--

DROP TABLE IF EXISTS `tl_calendar_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_calendar_events` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `alias` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `author` int(10) unsigned NOT NULL DEFAULT 0,
  `addTime` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `startTime` int(10) unsigned DEFAULT NULL,
  `endTime` int(10) unsigned DEFAULT NULL,
  `startDate` int(10) unsigned DEFAULT NULL,
  `endDate` int(10) unsigned DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `teaser` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `addImage` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `singleSRC` binary(16) DEFAULT NULL,
  `alt` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `size` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `imagemargin` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `imageUrl` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `fullsize` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `caption` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `floating` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `recurring` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `repeatEach` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `repeatEnd` int(10) unsigned NOT NULL DEFAULT 0,
  `recurrences` smallint(5) unsigned NOT NULL DEFAULT 0,
  `addEnclosure` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `enclosure` blob DEFAULT NULL,
  `source` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `jumpTo` int(10) unsigned NOT NULL DEFAULT 0,
  `articleId` int(10) unsigned NOT NULL DEFAULT 0,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `target` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cssClass` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `noComments` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `published` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `start` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `stop` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `overwriteMeta` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `orderEnclosure` blob DEFAULT NULL,
  `imageTitle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pageTitle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `alias` (`alias`),
  KEY `pid_start_stop_published` (`pid`,`start`,`stop`,`published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_calendar_events`
--

LOCK TABLES `tl_calendar_events` WRITE;
/*!40000 ALTER TABLE `tl_calendar_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `tl_calendar_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_calendar_feed`
--

DROP TABLE IF EXISTS `tl_calendar_feed`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_calendar_feed` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `alias` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `language` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `calendars` blob DEFAULT NULL,
  `format` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `source` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `maxItems` smallint(5) unsigned NOT NULL DEFAULT 0,
  `feedBase` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `alias` (`alias`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_calendar_feed`
--

LOCK TABLES `tl_calendar_feed` WRITE;
/*!40000 ALTER TABLE `tl_calendar_feed` DISABLE KEYS */;
/*!40000 ALTER TABLE `tl_calendar_feed` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_comments`
--

DROP TABLE IF EXISTS `tl_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_comments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `source` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `parent` int(10) unsigned NOT NULL DEFAULT 0,
  `date` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `website` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `comment` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `addReply` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `author` int(10) unsigned NOT NULL DEFAULT 0,
  `reply` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `published` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `ip` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `notified` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `notifiedReply` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `member` int(10) unsigned DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `published` (`published`),
  KEY `source_parent_published` (`source`,`parent`,`published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_comments`
--

LOCK TABLES `tl_comments` WRITE;
/*!40000 ALTER TABLE `tl_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `tl_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_comments_notify`
--

DROP TABLE IF EXISTS `tl_comments_notify`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_comments_notify` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `source` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `parent` int(10) unsigned NOT NULL DEFAULT 0,
  `name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `addedOn` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tokenRemove` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `active` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `tokenRemove` (`tokenRemove`),
  KEY `source_parent_active` (`source`,`parent`,`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_comments_notify`
--

LOCK TABLES `tl_comments_notify` WRITE;
/*!40000 ALTER TABLE `tl_comments_notify` DISABLE KEYS */;
/*!40000 ALTER TABLE `tl_comments_notify` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_content`
--

DROP TABLE IF EXISTS `tl_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_content` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `ptable` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sorting` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `headline` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `text` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `addImage` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `singleSRC` binary(16) DEFAULT NULL,
  `alt` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `imageTitle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `size` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `imagemargin` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `imageUrl` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `fullsize` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `caption` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `floating` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `html` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `listtype` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `listitems` blob DEFAULT NULL,
  `tableitems` mediumblob DEFAULT NULL,
  `summary` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `thead` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tfoot` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tleft` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sortable` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sortIndex` smallint(5) unsigned NOT NULL DEFAULT 0,
  `sortOrder` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mooHeadline` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mooStyle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mooClasses` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `highlight` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `code` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `target` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `titleText` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `linkTitle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `embed` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `rel` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `useImage` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `multiSRC` blob DEFAULT NULL,
  `orderSRC` blob DEFAULT NULL,
  `useHomeDir` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `perRow` smallint(5) unsigned NOT NULL DEFAULT 0,
  `perPage` smallint(5) unsigned NOT NULL DEFAULT 0,
  `numberOfItems` smallint(5) unsigned NOT NULL DEFAULT 0,
  `sortBy` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `metaIgnore` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `galleryTpl` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customTpl` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `playerSRC` blob DEFAULT NULL,
  `youtube` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `posterSRC` binary(16) DEFAULT NULL,
  `playerSize` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sliderDelay` int(10) unsigned NOT NULL DEFAULT 0,
  `sliderSpeed` int(10) unsigned NOT NULL DEFAULT 300,
  `sliderStartSlide` smallint(5) unsigned NOT NULL DEFAULT 0,
  `sliderContinuous` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cteAlias` int(10) unsigned NOT NULL DEFAULT 0,
  `articleAlias` int(10) unsigned NOT NULL DEFAULT 0,
  `article` int(10) unsigned NOT NULL DEFAULT 0,
  `form` int(10) unsigned NOT NULL DEFAULT 0,
  `module` int(10) unsigned NOT NULL DEFAULT 0,
  `protected` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `groups` blob DEFAULT NULL,
  `guests` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cssID` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `invisible` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `start` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `stop` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `com_order` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `com_perPage` smallint(5) unsigned NOT NULL DEFAULT 0,
  `com_moderate` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `com_bbcode` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `com_disableCaptcha` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `com_requireLogin` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `com_template` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `overwriteMeta` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `youtubeOptions` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vimeo` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `playerOptions` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vimeoOptions` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `playerStart` int(10) unsigned NOT NULL DEFAULT 0,
  `playerStop` int(10) unsigned NOT NULL DEFAULT 0,
  `playerColor` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `playerPreload` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `playerAspect` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `playerCaption` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `overwriteLink` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `inline` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `pid_ptable_invisible_sorting` (`pid`,`ptable`,`invisible`,`sorting`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_content`
--

LOCK TABLES `tl_content` WRITE;
/*!40000 ALTER TABLE `tl_content` DISABLE KEYS */;
INSERT INTO `tl_content` VALUES (9,4,'tl_article',128,1539686688,'module','a:2:{s:5:\"value\";s:0:\"\";s:4:\"unit\";s:2:\"h2\";}',NULL,'',NULL,'','','','','','','','above',NULL,'',NULL,NULL,'','','','','',0,'ascending','','','','',NULL,'','','','','','','',NULL,NULL,'',4,0,0,'','','','',NULL,'',NULL,'',0,300,0,'',0,0,0,0,1,'',NULL,'','a:2:{i:0;s:0:\"\";i:1;s:0:\"\";}','','','','ascending',0,'','','','','com_default','',NULL,'',NULL,NULL,0,0,'','','none','','',''),(10,13,'tl_article',128,1539686730,'module','a:2:{s:5:\"value\";s:0:\"\";s:4:\"unit\";s:2:\"h2\";}',NULL,'',NULL,'','','','','','','','above',NULL,'',NULL,NULL,'','','','','',0,'ascending','','','','',NULL,'','','','','','','',NULL,NULL,'',4,0,0,'','','','',NULL,'',NULL,'',0,300,0,'',0,0,0,0,1,'',NULL,'','a:2:{i:0;s:0:\"\";i:1;s:0:\"\";}','','','','ascending',0,'','','','','com_default','',NULL,'',NULL,NULL,0,0,'','','none','','','');
/*!40000 ALTER TABLE `tl_content` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_cron`
--

DROP TABLE IF EXISTS `tl_cron`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_cron` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_cron`
--

LOCK TABLES `tl_cron` WRITE;
/*!40000 ALTER TABLE `tl_cron` DISABLE KEYS */;
INSERT INTO `tl_cron` VALUES (1,'lastrun','4102444799'),(2,'monthly','0'),(3,'weekly','0'),(4,'daily','20993112'),(5,'hourly','0'),(6,'minutely','0');
/*!40000 ALTER TABLE `tl_cron` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_faq`
--

DROP TABLE IF EXISTS `tl_faq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_faq` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `sorting` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `question` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `alias` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `author` int(10) unsigned NOT NULL DEFAULT 0,
  `answer` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `addImage` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `singleSRC` binary(16) DEFAULT NULL,
  `alt` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `size` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `imagemargin` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `imageUrl` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `fullsize` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `caption` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `floating` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `addEnclosure` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `enclosure` blob DEFAULT NULL,
  `noComments` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `published` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `overwriteMeta` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `orderEnclosure` blob DEFAULT NULL,
  `imageTitle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `pid_published_sorting` (`pid`,`published`,`sorting`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_faq`
--

LOCK TABLES `tl_faq` WRITE;
/*!40000 ALTER TABLE `tl_faq` DISABLE KEYS */;
/*!40000 ALTER TABLE `tl_faq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_faq_category`
--

DROP TABLE IF EXISTS `tl_faq_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_faq_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `headline` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `jumpTo` int(10) unsigned NOT NULL DEFAULT 0,
  `allowComments` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `notify` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sortOrder` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `perPage` smallint(5) unsigned NOT NULL DEFAULT 0,
  `moderate` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `bbcode` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `requireLogin` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `disableCaptcha` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_faq_category`
--

LOCK TABLES `tl_faq_category` WRITE;
/*!40000 ALTER TABLE `tl_faq_category` DISABLE KEYS */;
/*!40000 ALTER TABLE `tl_faq_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_files`
--

DROP TABLE IF EXISTS `tl_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_files` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` binary(16) DEFAULT NULL,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `uuid` binary(16) DEFAULT NULL,
  `type` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `path` varchar(1022) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `extension` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `hash` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `found` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `importantPartX` int(10) NOT NULL DEFAULT 0,
  `importantPartY` int(10) NOT NULL DEFAULT 0,
  `importantPartWidth` int(10) NOT NULL DEFAULT 0,
  `importantPartHeight` int(10) NOT NULL DEFAULT 0,
  `meta` blob DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `pid` (`pid`),
  KEY `extension` (`extension`),
  KEY `path` (`path`(768))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_files`
--

LOCK TABLES `tl_files` WRITE;
/*!40000 ALTER TABLE `tl_files` DISABLE KEYS */;
/*!40000 ALTER TABLE `tl_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_form`
--

DROP TABLE IF EXISTS `tl_form`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_form` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `alias` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `jumpTo` int(10) unsigned NOT NULL DEFAULT 0,
  `sendViaEmail` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `recipient` varchar(1022) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `format` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `skipEmpty` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `storeValues` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `targetTable` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `method` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `novalidate` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `attributes` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `formID` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `allowTags` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customTpl` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `alias` (`alias`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_form`
--

LOCK TABLES `tl_form` WRITE;
/*!40000 ALTER TABLE `tl_form` DISABLE KEYS */;
/*!40000 ALTER TABLE `tl_form` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_form_field`
--

DROP TABLE IF EXISTS `tl_form_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_form_field` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `sorting` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `invisible` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `text` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `html` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `options` blob DEFAULT NULL,
  `mandatory` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `rgxp` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `placeholder` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `minlength` int(10) unsigned NOT NULL DEFAULT 0,
  `maxlength` int(10) unsigned NOT NULL DEFAULT 0,
  `size` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `multiple` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mSize` smallint(5) unsigned NOT NULL DEFAULT 0,
  `extensions` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `storeFile` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `uploadFolder` binary(16) DEFAULT NULL,
  `useHomeDir` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `doNotOverwrite` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `class` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `accesskey` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tabindex` smallint(5) unsigned NOT NULL DEFAULT 0,
  `fSize` smallint(5) unsigned NOT NULL DEFAULT 0,
  `customTpl` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `slabel` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `imageSubmit` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `singleSRC` binary(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_form_field`
--

LOCK TABLES `tl_form_field` WRITE;
/*!40000 ALTER TABLE `tl_form_field` DISABLE KEYS */;
/*!40000 ALTER TABLE `tl_form_field` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_image_size`
--

DROP TABLE IF EXISTS `tl_image_size`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_image_size` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `name` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sizes` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `densities` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `width` int(10) DEFAULT NULL,
  `height` int(10) DEFAULT NULL,
  `resizeMode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `zoom` int(10) DEFAULT NULL,
  `cssClass` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_image_size`
--

LOCK TABLES `tl_image_size` WRITE;
/*!40000 ALTER TABLE `tl_image_size` DISABLE KEYS */;
/*!40000 ALTER TABLE `tl_image_size` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_image_size_item`
--

DROP TABLE IF EXISTS `tl_image_size_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_image_size_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `sorting` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `media` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sizes` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `densities` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `width` int(10) DEFAULT NULL,
  `height` int(10) DEFAULT NULL,
  `resizeMode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `zoom` int(10) DEFAULT NULL,
  `invisible` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_image_size_item`
--

LOCK TABLES `tl_image_size_item` WRITE;
/*!40000 ALTER TABLE `tl_image_size_item` DISABLE KEYS */;
/*!40000 ALTER TABLE `tl_image_size_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_layout`
--

DROP TABLE IF EXISTS `tl_layout`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_layout` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `rows` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `headerHeight` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `footerHeight` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cols` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `widthLeft` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `widthRight` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sections` blob DEFAULT NULL,
  `framework` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `stylesheet` blob DEFAULT NULL,
  `external` blob DEFAULT NULL,
  `orderExt` blob DEFAULT NULL,
  `loadingOrder` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `newsfeeds` blob DEFAULT NULL,
  `calendarfeeds` blob DEFAULT NULL,
  `modules` blob DEFAULT NULL,
  `template` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `webfonts` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `picturefill` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `viewport` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `titleTag` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cssClass` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `onload` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `head` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `addJQuery` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `jSource` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `jquery` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `addMooTools` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mooSource` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mootools` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `analytics` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `script` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `static` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `width` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `align` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `scripts` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `combineScripts` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `externalJs` blob DEFAULT NULL,
  `orderExtJs` blob DEFAULT NULL,
  `minifyMarkup` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_layout`
--

LOCK TABLES `tl_layout` WRITE;
/*!40000 ALTER TABLE `tl_layout` DISABLE KEYS */;
INSERT INTO `tl_layout` VALUES (1,1,1539617638,'Default','1rw','a:2:{s:4:\"unit\";s:0:\"\";s:5:\"value\";s:0:\"\";}','','1cl','a:2:{s:4:\"unit\";s:0:\"\";s:5:\"value\";s:0:\"\";}','','a:1:{i:0;a:4:{s:5:\"title\";s:0:\"\";s:2:\"id\";s:0:\"\";s:8:\"template\";s:13:\"block_section\";s:8:\"position\";s:3:\"top\";}}','',NULL,NULL,NULL,'external_first',NULL,NULL,'a:1:{i:0;a:3:{s:3:\"mod\";s:1:\"0\";s:3:\"col\";s:4:\"main\";s:6:\"enable\";s:1:\"1\";}}','fe_page','','','','','','',NULL,'','',NULL,'','moo_local',NULL,NULL,NULL,'','','center',NULL,'1',NULL,NULL,'');
/*!40000 ALTER TABLE `tl_layout` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_log`
--

DROP TABLE IF EXISTS `tl_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `source` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `action` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `username` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `text` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `func` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `browser` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_log`
--

LOCK TABLES `tl_log` WRITE;
/*!40000 ALTER TABLE `tl_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `tl_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_member`
--

DROP TABLE IF EXISTS `tl_member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_member` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `firstname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `lastname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateOfBirth` varchar(11) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `gender` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `company` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `street` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `postal` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `city` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `state` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `country` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `phone` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mobile` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `fax` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `website` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `language` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `groups` blob DEFAULT NULL,
  `login` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `username` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `assignDir` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `homeDir` binary(16) DEFAULT NULL,
  `disable` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `start` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `stop` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateAdded` int(10) unsigned NOT NULL DEFAULT 0,
  `lastLogin` int(10) unsigned NOT NULL DEFAULT 0,
  `currentLogin` int(10) unsigned NOT NULL DEFAULT 0,
  `loginCount` smallint(5) unsigned NOT NULL DEFAULT 3,
  `locked` int(10) unsigned NOT NULL DEFAULT 0,
  `session` blob DEFAULT NULL,
  `newsletter` blob DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_member`
--

LOCK TABLES `tl_member` WRITE;
/*!40000 ALTER TABLE `tl_member` DISABLE KEYS */;
/*!40000 ALTER TABLE `tl_member` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_member_group`
--

DROP TABLE IF EXISTS `tl_member_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_member_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `redirect` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `jumpTo` int(10) unsigned NOT NULL DEFAULT 0,
  `disable` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `start` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `stop` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_member_group`
--

LOCK TABLES `tl_member_group` WRITE;
/*!40000 ALTER TABLE `tl_member_group` DISABLE KEYS */;
/*!40000 ALTER TABLE `tl_member_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_module`
--

DROP TABLE IF EXISTS `tl_module`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_module` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `headline` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `levelOffset` smallint(5) unsigned NOT NULL DEFAULT 0,
  `showLevel` smallint(5) unsigned NOT NULL DEFAULT 0,
  `hardLimit` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `showProtected` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `defineRoot` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `rootPage` int(10) unsigned NOT NULL DEFAULT 0,
  `navigationTpl` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customTpl` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `pages` blob DEFAULT NULL,
  `orderPages` blob DEFAULT NULL,
  `showHidden` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customLabel` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `autologin` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `jumpTo` int(10) unsigned NOT NULL DEFAULT 0,
  `redirectBack` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `editable` blob DEFAULT NULL,
  `memberTpl` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cal_hideRunning` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `form` int(10) unsigned NOT NULL DEFAULT 0,
  `queryType` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `fuzzy` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `contextLength` smallint(5) unsigned NOT NULL DEFAULT 0,
  `totalLength` smallint(5) unsigned NOT NULL DEFAULT 0,
  `perPage` smallint(5) unsigned NOT NULL DEFAULT 0,
  `searchType` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `searchTpl` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `inColumn` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `skipFirst` smallint(5) unsigned NOT NULL DEFAULT 0,
  `loadFirst` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `singleSRC` binary(16) DEFAULT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `imgSize` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `useCaption` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `fullsize` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `multiSRC` blob DEFAULT NULL,
  `orderSRC` blob DEFAULT NULL,
  `html` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rss_cache` int(10) unsigned NOT NULL DEFAULT 0,
  `rss_feed` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rss_template` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `numberOfItems` smallint(5) unsigned NOT NULL DEFAULT 0,
  `disableCaptcha` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `reg_groups` blob DEFAULT NULL,
  `reg_allowLogin` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `reg_skipName` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `reg_close` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `reg_assignDir` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `reg_homeDir` binary(16) DEFAULT NULL,
  `reg_activate` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `reg_jumpTo` int(10) unsigned NOT NULL DEFAULT 0,
  `reg_text` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reg_password` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `protected` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `groups` blob DEFAULT NULL,
  `guests` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cssID` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cal_calendar` blob DEFAULT NULL,
  `cal_noSpan` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cal_startDay` smallint(5) unsigned NOT NULL DEFAULT 1,
  `cal_format` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cal_ignoreDynamic` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cal_order` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cal_readerModule` int(10) unsigned NOT NULL DEFAULT 0,
  `cal_limit` smallint(5) unsigned NOT NULL DEFAULT 0,
  `cal_template` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cal_ctemplate` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cal_showQuantity` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `com_order` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `com_moderate` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `com_bbcode` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `com_requireLogin` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `com_disableCaptcha` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `com_template` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `list_table` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `list_fields` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `list_where` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `list_search` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `list_sort` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `list_info` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `list_info_where` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `list_layout` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `list_info_layout` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `news_archives` blob DEFAULT NULL,
  `news_featured` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `news_jumpToCurrent` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `news_readerModule` int(10) unsigned NOT NULL DEFAULT 0,
  `news_metaFields` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `news_template` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `news_format` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `news_startDay` smallint(5) unsigned NOT NULL DEFAULT 0,
  `news_order` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `news_showQuantity` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `newsletters` blob DEFAULT NULL,
  `nl_channels` blob DEFAULT NULL,
  `nl_hideChannels` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `nl_subscribe` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nl_unsubscribe` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nl_template` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `faq_categories` blob DEFAULT NULL,
  `faq_readerModule` int(10) unsigned NOT NULL DEFAULT 0,
  `nl_text` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_module`
--

LOCK TABLES `tl_module` WRITE;
/*!40000 ALTER TABLE `tl_module` DISABLE KEYS */;
INSERT INTO `tl_module` VALUES (1,1,1539616752,'News archive','a:2:{s:4:\"unit\";s:2:\"h2\";s:5:\"value\";s:0:\"\";}','newsarchive',0,0,'','','',0,'','',NULL,NULL,'','','',0,'',NULL,'','',0,'and','',48,1000,0,'simple','','main',0,'',NULL,'','a:3:{i:0;s:0:\"\";i:1;s:0:\"\";i:2;s:0:\"\";}','','',NULL,NULL,NULL,3600,NULL,'rss_default',3,'',NULL,'','','','',NULL,'',0,NULL,NULL,'',NULL,'','a:2:{i:0;s:0:\"\";i:1;s:0:\"\";}',NULL,'',1,'cal_month','','ascending',0,0,'event_full','cal_default','','ascending','','','','','com_default','','','','','','','','list_default','info_default','a:1:{i:0;s:1:\"1\";}','all_items','hide_module',2,'a:2:{i:0;s:4:\"date\";i:1;s:6:\"author\";}','news_latest','news_month',0,'order_date_desc','',NULL,NULL,'',NULL,NULL,'nl_simple',NULL,0,NULL),(2,1,1539616572,'News reader','a:2:{s:4:\"unit\";s:2:\"h2\";s:5:\"value\";s:0:\"\";}','newsreader',0,0,'','','',0,'','',NULL,NULL,'','','',0,'',NULL,'','',0,'and','',48,1000,0,'simple','','main',0,'',NULL,'','a:3:{i:0;s:0:\"\";i:1;s:0:\"\";i:2;s:0:\"\";}','','',NULL,NULL,NULL,3600,NULL,'rss_default',3,'',NULL,'','','','',NULL,'',0,NULL,NULL,'',NULL,'','a:2:{i:0;s:0:\"\";i:1;s:0:\"\";}',NULL,'',1,'cal_month','','ascending',0,0,'event_full','cal_default','','ascending','','','','','com_default','','','','','','','','list_default','info_default','a:1:{i:0;s:1:\"1\";}','all_items','',0,'a:2:{i:0;s:4:\"date\";i:1;s:6:\"author\";}','news_latest','news_month',0,'order_date_desc','',NULL,NULL,'',NULL,NULL,'nl_simple',NULL,0,NULL);
/*!40000 ALTER TABLE `tl_module` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_news`
--

DROP TABLE IF EXISTS `tl_news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_news` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `headline` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `alias` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `author` int(10) unsigned NOT NULL DEFAULT 0,
  `date` int(10) unsigned NOT NULL DEFAULT 0,
  `time` int(10) unsigned NOT NULL DEFAULT 0,
  `subheadline` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `teaser` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `addImage` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `singleSRC` binary(16) DEFAULT NULL,
  `alt` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `size` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `imagemargin` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `imageUrl` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `fullsize` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `caption` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `floating` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `addEnclosure` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `enclosure` blob DEFAULT NULL,
  `source` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `jumpTo` int(10) unsigned NOT NULL DEFAULT 0,
  `articleId` int(10) unsigned NOT NULL DEFAULT 0,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `target` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cssClass` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `noComments` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `featured` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `published` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `start` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `stop` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `overwriteMeta` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `orderEnclosure` blob DEFAULT NULL,
  `imageTitle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pageTitle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `alias` (`alias`),
  KEY `pid_start_stop_published` (`pid`,`start`,`stop`,`published`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_news`
--

LOCK TABLES `tl_news` WRITE;
/*!40000 ALTER TABLE `tl_news` DISABLE KEYS */;
INSERT INTO `tl_news` VALUES (1,1,1539616623,'Foobar','foobar',1,1539616560,1539616560,'',NULL,'',NULL,'','','','','','','above','',NULL,'default',0,0,'','','','','','1','','','',NULL,'',NULL,'');
/*!40000 ALTER TABLE `tl_news` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_news_archive`
--

DROP TABLE IF EXISTS `tl_news_archive`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_news_archive` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `jumpTo` int(10) unsigned NOT NULL DEFAULT 0,
  `protected` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `groups` blob DEFAULT NULL,
  `allowComments` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `notify` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sortOrder` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `perPage` smallint(5) unsigned NOT NULL DEFAULT 0,
  `moderate` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `bbcode` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `requireLogin` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `disableCaptcha` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_news_archive`
--

LOCK TABLES `tl_news_archive` WRITE;
/*!40000 ALTER TABLE `tl_news_archive` DISABLE KEYS */;
INSERT INTO `tl_news_archive` VALUES (1,1539616536,'Test',2,'',NULL,'','notify_admin','ascending',0,'','','','');
/*!40000 ALTER TABLE `tl_news_archive` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_news_feed`
--

DROP TABLE IF EXISTS `tl_news_feed`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_news_feed` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `alias` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `language` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `archives` blob DEFAULT NULL,
  `format` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `source` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `maxItems` smallint(5) unsigned NOT NULL DEFAULT 0,
  `feedBase` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `alias` (`alias`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_news_feed`
--

LOCK TABLES `tl_news_feed` WRITE;
/*!40000 ALTER TABLE `tl_news_feed` DISABLE KEYS */;
/*!40000 ALTER TABLE `tl_news_feed` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_newsletter`
--

DROP TABLE IF EXISTS `tl_newsletter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_newsletter` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `alias` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `content` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `addFile` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `files` blob DEFAULT NULL,
  `template` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sendText` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `externalImages` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sender` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `senderName` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sent` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `date` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_newsletter`
--

LOCK TABLES `tl_newsletter` WRITE;
/*!40000 ALTER TABLE `tl_newsletter` DISABLE KEYS */;
/*!40000 ALTER TABLE `tl_newsletter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_newsletter_blacklist`
--

DROP TABLE IF EXISTS `tl_newsletter_blacklist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_newsletter_blacklist` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `hash` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pid_hash` (`pid`,`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_newsletter_blacklist`
--

LOCK TABLES `tl_newsletter_blacklist` WRITE;
/*!40000 ALTER TABLE `tl_newsletter_blacklist` DISABLE KEYS */;
/*!40000 ALTER TABLE `tl_newsletter_blacklist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_newsletter_channel`
--

DROP TABLE IF EXISTS `tl_newsletter_channel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_newsletter_channel` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `jumpTo` int(10) unsigned NOT NULL DEFAULT 0,
  `senderName` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sender` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `template` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_newsletter_channel`
--

LOCK TABLES `tl_newsletter_channel` WRITE;
/*!40000 ALTER TABLE `tl_newsletter_channel` DISABLE KEYS */;
/*!40000 ALTER TABLE `tl_newsletter_channel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_newsletter_recipients`
--

DROP TABLE IF EXISTS `tl_newsletter_recipients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_newsletter_recipients` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `active` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `addedOn` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `pid_email` (`pid`,`email`),
  KEY `active` (`active`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_newsletter_recipients`
--

LOCK TABLES `tl_newsletter_recipients` WRITE;
/*!40000 ALTER TABLE `tl_newsletter_recipients` DISABLE KEYS */;
/*!40000 ALTER TABLE `tl_newsletter_recipients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_opt_in`
--

DROP TABLE IF EXISTS `tl_opt_in`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_opt_in` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `token` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `createdOn` int(10) unsigned NOT NULL DEFAULT 0,
  `confirmedOn` int(10) unsigned NOT NULL DEFAULT 0,
  `removeOn` int(10) unsigned NOT NULL DEFAULT 0,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `emailSubject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `emailText` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `removeon` (`removeOn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_opt_in`
--

LOCK TABLES `tl_opt_in` WRITE;
/*!40000 ALTER TABLE `tl_opt_in` DISABLE KEYS */;
/*!40000 ALTER TABLE `tl_opt_in` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_opt_in_related`
--

DROP TABLE IF EXISTS `tl_opt_in_related`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_opt_in_related` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `relTable` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `relId` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `reltable_relid` (`relTable`,`relId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_opt_in_related`
--

LOCK TABLES `tl_opt_in_related` WRITE;
/*!40000 ALTER TABLE `tl_opt_in_related` DISABLE KEYS */;
/*!40000 ALTER TABLE `tl_opt_in_related` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_page`
--

DROP TABLE IF EXISTS `tl_page`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_page` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `sorting` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `alias` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `pageTitle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `language` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `robots` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `redirect` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `jumpTo` int(10) unsigned NOT NULL DEFAULT 0,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `target` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dns` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `staticFiles` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `staticPlugins` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `fallback` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `adminEmail` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateFormat` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `timeFormat` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `datimFormat` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `createSitemap` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sitemapName` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `useSSL` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `autoforward` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `protected` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `groups` blob DEFAULT NULL,
  `includeLayout` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `layout` int(10) unsigned NOT NULL DEFAULT 0,
  `includeCache` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cache` int(10) unsigned NOT NULL DEFAULT 0,
  `includeChmod` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cuser` int(10) unsigned NOT NULL DEFAULT 0,
  `cgroup` int(10) unsigned NOT NULL DEFAULT 0,
  `chmod` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `noSearch` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cssClass` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sitemap` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `hide` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `guests` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tabindex` smallint(5) unsigned NOT NULL DEFAULT 0,
  `accesskey` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `published` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `start` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `stop` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `requireItem` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `clientCache` int(10) unsigned NOT NULL DEFAULT 0,
  `validAliasCharacters` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `redirectBack` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `alias` (`alias`),
  KEY `pid_type_start_stop_published` (`pid`,`type`,`start`,`stop`,`published`),
  KEY `type_dns` (`type`,`dns`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_page`
--

LOCK TABLES `tl_page` WRITE;
/*!40000 ALTER TABLE `tl_page` DISABLE KEYS */;
INSERT INTO `tl_page` VALUES (1,0,128,1539679763,'Root with index page','root-with-index-page','root','','en','',NULL,'permanent',0,'','','root-with-index.local','','','1','','','','','','','','','',NULL,'1',1,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','','','','','',0,'','1','','','',0,'',''),(2,1,128,1539698035,'Index','index','regular','','','index,follow',NULL,'permanent',0,'','','','','','','','','','','','','','','',NULL,'',0,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','1','','map_default','','',0,'','1','','','',0,'',''),(3,0,256,1539679767,'Root with home page','root-with-home-page','root','','en','',NULL,'permanent',0,'','','root-with-home.local','','','1','','','','','','','','','',NULL,'1',1,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','','','','','',0,'','1','','','',0,'',''),(4,3,128,1539698035,'Home','home','regular','','','index,follow',NULL,'permanent',0,'','','','','','','','','','','','','','','',NULL,'',0,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','1','','map_default','','',0,'','1','','','',0,'',''),(5,0,384,1539680233,'Root with special chars','root-with-special-chars','root','','en','',NULL,'permanent',0,'','','root-with-special-chars.local','','','1','','','','','','','','','',NULL,'1',1,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','','','','','',0,'','1','','','',0,'',''),(6,5,128,1539698035,'Höme','höme','regular','','','index,follow',NULL,'permanent',0,'','','','','','','','','','','','','','','',NULL,'',0,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','1','','map_default','','',0,'','1','','','',0,'',''),(7,0,512,1539680468,'Root with folder URLs','root-with-folder-urls','root','','en','',NULL,'permanent',0,'','','root-with-folder-urls.local','','','1','','','','','','','','','',NULL,'1',1,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','','','','','',0,'','1','','','',0,'',''),(8,7,128,1539698035,'Home','folder/url/home','regular','','','index,follow',NULL,'permanent',0,'','','','','','','','','','','','','','','',NULL,'',0,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','1','','map_default','','',0,'','1','','','',0,'',''),(9,0,640,1539694676,'Root without fallback language','root-without-fallback-language','root','','en','',NULL,'permanent',0,'','','root-without-fallback-language.local','','','','','','','','','','','','',NULL,'1',1,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','','','','','',0,'','1','','','',0,'',''),(10,9,128,1539698035,'English','index','regular','','','index,follow',NULL,'permanent',0,'','','','','','','','','','','','','','','',NULL,'',0,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','1','','map_default','','',0,'','1','','','',0,'',''),(11,0,768,1550250980,'Same domain root English','same-domain-root-english','root','','en','',NULL,'permanent',0,'','','same-domain-root.local','','','1','','','','','','','','','',NULL,'1',1,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','','','','','',0,'','1','','','',0,'',''),(12,11,128,1539698035,'English site','english-site','regular','','','index,follow',NULL,'permanent',0,'','','','','','','','','','','','','','','',NULL,'',0,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','1','','map_default','','',0,'','1','','','',0,'',''),(13,0,896,1550250980,'Same domain root German','same-domain-root-german','root','','de','',NULL,'permanent',0,'','','same-domain-root.local','','','','','','','','','','','','',NULL,'1',1,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','','','','','',0,'','1','','','',0,'',''),(14,13,128,1539698035,'German site','german-site','regular','','','index,follow',NULL,'permanent',0,'','','','','','','','','','','','','','','',NULL,'',0,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','1','','map_default','','',0,'','1','','','',0,'',''),(15,0,960,1550250933,'Same domain root English with index','same-domain-root-english-with-index','root','','en','',NULL,'permanent',0,'','','same-domain-root-with-index.local','','','1','','','','','','','','','',NULL,'1',1,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','','','','','',0,'','1','','','',0,'',''),(16,15,128,1539698035,'English site','index','regular','','','index,follow',NULL,'permanent',0,'','','','','','','','','','','','','','','',NULL,'',0,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','1','','map_default','','',0,'','1','','','',0,'',''),(17,0,896,1539696285,'Same domain root German with index','same-domain-root-german-with-index','root','','de','',NULL,'permanent',0,'','','same-domain-root-with-index.local','','','','','','','','','','','','',NULL,'1',1,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','','','','','',0,'','1','','','',0,'',''),(18,17,128,1539698035,'German site','index','regular','','','index,follow',NULL,'permanent',0,'','','','','','','','','','','','','','','',NULL,'',0,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','1','','map_default','','',0,'','1','','','',0,'',''),(19,0,1024,1539696285,'Domain without hostname','domain-without-hostname','root','','en','',NULL,'permanent',0,'','','','','','1','','','','','','','','','',NULL,'1',1,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','','','','','',0,'','1','','','',0,'',''),(20,19,128,1539698035,'Home','index','regular','','','index,follow',NULL,'permanent',0,'','','','','','','','','','','','','','','',NULL,'',0,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','1','','map_default','','',0,'','1','','','',0,'',''),(21,0,1024,1539696285,'Domain with port','domain-with-port','root','','en','',NULL,'permanent',0,'','','domain-with-port.local:8080','','','1','','','','','','','','','',NULL,'1',1,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','','','','','',0,'','1','','','',0,'',''),(22,21,128,1539698035,'Home','index','regular','','','index,follow',NULL,'permanent',0,'','','','','','','','','','','','','','','',NULL,'',0,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','1','','map_default','','',0,'','1','','','',0,'','');
/*!40000 ALTER TABLE `tl_page` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_remember_me`
--

DROP TABLE IF EXISTS `tl_remember_me`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_remember_me` (
  `series` char(88) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` char(88) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lastUsed` datetime DEFAULT NULL,
  `class` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`series`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_remember_me`
--

LOCK TABLES `tl_remember_me` WRITE;
/*!40000 ALTER TABLE `tl_remember_me` DISABLE KEYS */;
/*!40000 ALTER TABLE `tl_remember_me` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_search`
--

DROP TABLE IF EXISTS `tl_search`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_search` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `text` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `filesize` double NOT NULL DEFAULT 0,
  `checksum` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `protected` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `groups` blob DEFAULT NULL,
  `language` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `checksum_pid` (`checksum`,`pid`),
  UNIQUE KEY `url` (`url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_search`
--

LOCK TABLES `tl_search` WRITE;
/*!40000 ALTER TABLE `tl_search` DISABLE KEYS */;
/*!40000 ALTER TABLE `tl_search` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_search_index`
--

DROP TABLE IF EXISTS `tl_search_index`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_search_index` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `word` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `relevance` smallint(5) unsigned NOT NULL DEFAULT 0,
  `language` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `word` (`word`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_search_index`
--

LOCK TABLES `tl_search_index` WRITE;
/*!40000 ALTER TABLE `tl_search_index` DISABLE KEYS */;
/*!40000 ALTER TABLE `tl_search_index` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_style`
--

DROP TABLE IF EXISTS `tl_style`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_style` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `sorting` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `selector` varchar(1022) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `category` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `comment` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `size` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `width` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `height` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `minwidth` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `minheight` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `maxwidth` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `maxheight` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `positioning` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `trbl` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `position` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `floating` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `clear` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `overflow` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `display` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `alignment` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `margin` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `padding` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `align` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `verticalalign` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `textalign` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `whitespace` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `background` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `bgcolor` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `bgimage` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `bgposition` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `bgrepeat` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `shadowsize` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `shadowcolor` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `gradientAngle` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `gradientColors` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `border` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `borderwidth` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `borderstyle` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `bordercolor` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `borderradius` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `bordercollapse` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `borderspacing` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `font` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `fontfamily` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `fontsize` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `fontcolor` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `lineheight` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `fontstyle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `texttransform` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `textindent` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `letterspacing` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `wordspacing` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `list` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `liststyletype` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `liststyleimage` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `own` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invisible` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_style`
--

LOCK TABLES `tl_style` WRITE;
/*!40000 ALTER TABLE `tl_style` DISABLE KEYS */;
/*!40000 ALTER TABLE `tl_style` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_style_sheet`
--

DROP TABLE IF EXISTS `tl_style_sheet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_style_sheet` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `name` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `embedImages` int(10) unsigned NOT NULL DEFAULT 0,
  `cc` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `media` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mediaQuery` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vars` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_style_sheet`
--

LOCK TABLES `tl_style_sheet` WRITE;
/*!40000 ALTER TABLE `tl_style_sheet` DISABLE KEYS */;
/*!40000 ALTER TABLE `tl_style_sheet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_theme`
--

DROP TABLE IF EXISTS `tl_theme`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_theme` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `author` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `folders` blob DEFAULT NULL,
  `screenshot` binary(16) DEFAULT NULL,
  `templates` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `vars` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `defaultImageDensities` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_theme`
--

LOCK TABLES `tl_theme` WRITE;
/*!40000 ALTER TABLE `tl_theme` DISABLE KEYS */;
INSERT INTO `tl_theme` VALUES (1,1539598899,'Default','Leo Feyer',NULL,NULL,'','a:0:{}','');
/*!40000 ALTER TABLE `tl_theme` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_undo`
--

DROP TABLE IF EXISTS `tl_undo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_undo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `fromTable` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `query` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `affectedRows` smallint(5) unsigned NOT NULL DEFAULT 0,
  `data` mediumblob DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_undo`
--

LOCK TABLES `tl_undo` WRITE;
/*!40000 ALTER TABLE `tl_undo` DISABLE KEYS */;
/*!40000 ALTER TABLE `tl_undo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_user`
--

DROP TABLE IF EXISTS `tl_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `username` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `language` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `backendTheme` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `uploader` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `showHelp` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `thumbnails` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `useRTE` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `useCE` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `pwChange` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `admin` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `groups` blob DEFAULT NULL,
  `inherit` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `modules` blob DEFAULT NULL,
  `themes` blob DEFAULT NULL,
  `pagemounts` blob DEFAULT NULL,
  `alpty` blob DEFAULT NULL,
  `filemounts` blob DEFAULT NULL,
  `fop` blob DEFAULT NULL,
  `forms` blob DEFAULT NULL,
  `formp` blob DEFAULT NULL,
  `disable` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `start` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `stop` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `session` blob DEFAULT NULL,
  `dateAdded` int(10) unsigned NOT NULL DEFAULT 0,
  `lastLogin` int(10) unsigned NOT NULL DEFAULT 0,
  `currentLogin` int(10) unsigned NOT NULL DEFAULT 0,
  `loginCount` smallint(5) unsigned NOT NULL DEFAULT 3,
  `locked` int(10) unsigned NOT NULL DEFAULT 0,
  `calendars` blob DEFAULT NULL,
  `calendarp` blob DEFAULT NULL,
  `calendarfeeds` blob DEFAULT NULL,
  `calendarfeedp` blob DEFAULT NULL,
  `news` blob DEFAULT NULL,
  `newp` blob DEFAULT NULL,
  `newsfeeds` blob DEFAULT NULL,
  `newsfeedp` blob DEFAULT NULL,
  `newsletters` blob DEFAULT NULL,
  `newsletterp` blob DEFAULT NULL,
  `faqs` blob DEFAULT NULL,
  `faqp` blob DEFAULT NULL,
  `imageSizes` blob DEFAULT NULL,
  `amg` blob DEFAULT NULL,
  `fullscreen` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `useTwoFactor` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `secret` binary(128) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_user`
--

LOCK TABLES `tl_user` WRITE;
/*!40000 ALTER TABLE `tl_user` DISABLE KEYS */;
INSERT INTO `tl_user` VALUES (1,1539598726,'k.jones','Kevin Jones','k.jones@example.com','de','flexible','','1','1','1','1','$2y$10$i41Nwj6cjYGqdVh2sDJ0b.XueJpD4kEu20EfNULFw2d/0C8bJr9/.','','1',NULL,'',NULL,NULL,'a:0:{}',NULL,'a:0:{}',NULL,NULL,NULL,'','','',NULL,1539598726,1539686648,1539694637,3,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'','',NULL);
/*!40000 ALTER TABLE `tl_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_user_group`
--

DROP TABLE IF EXISTS `tl_user_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_user_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `modules` blob DEFAULT NULL,
  `themes` blob DEFAULT NULL,
  `pagemounts` blob DEFAULT NULL,
  `alpty` blob DEFAULT NULL,
  `filemounts` blob DEFAULT NULL,
  `fop` blob DEFAULT NULL,
  `forms` blob DEFAULT NULL,
  `formp` blob DEFAULT NULL,
  `alexf` blob DEFAULT NULL,
  `disable` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `start` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `stop` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `calendars` blob DEFAULT NULL,
  `calendarp` blob DEFAULT NULL,
  `calendarfeeds` blob DEFAULT NULL,
  `calendarfeedp` blob DEFAULT NULL,
  `news` blob DEFAULT NULL,
  `newp` blob DEFAULT NULL,
  `newsfeeds` blob DEFAULT NULL,
  `newsfeedp` blob DEFAULT NULL,
  `newsletters` blob DEFAULT NULL,
  `newsletterp` blob DEFAULT NULL,
  `faqs` blob DEFAULT NULL,
  `faqp` blob DEFAULT NULL,
  `imageSizes` blob DEFAULT NULL,
  `amg` blob DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_user_group`
--

LOCK TABLES `tl_user_group` WRITE;
/*!40000 ALTER TABLE `tl_user_group` DISABLE KEYS */;
/*!40000 ALTER TABLE `tl_user_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tl_version`
--

DROP TABLE IF EXISTS `tl_version`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_version` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `version` smallint(5) unsigned NOT NULL DEFAULT 1,
  `fromTable` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `userid` int(10) unsigned NOT NULL DEFAULT 0,
  `username` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `editUrl` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `data` mediumblob DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `fromtable` (`fromTable`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_version`
--

LOCK TABLES `tl_version` WRITE;
/*!40000 ALTER TABLE `tl_version` DISABLE KEYS */;
/*!40000 ALTER TABLE `tl_version` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2019-02-19 10:44:51
