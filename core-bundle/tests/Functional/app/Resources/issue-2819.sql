-- MySQL dump 10.13  Distrib 5.7.30, for osx10.12 (x86_64)
--
-- Host: 127.0.0.1    Database: contao_test
-- ------------------------------------------------------
-- Server version	5.7.30

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
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
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `sorting` int(10) unsigned NOT NULL DEFAULT '0',
  `tstamp` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `alias` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `author` int(10) unsigned NOT NULL DEFAULT '0',
  `inColumn` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'main',
  `keywords` mediumtext COLLATE utf8mb4_unicode_ci,
  `showTeaser` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `teaserCssID` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `teaser` mediumtext COLLATE utf8mb4_unicode_ci,
  `printable` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customTpl` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `protected` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `groups` blob,
  `guests` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cssID` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `published` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `start` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `stop` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `languageMain` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `alias` (`alias`),
  KEY `pid_start_stop_published_sorting` (`pid`,`start`,`stop`,`published`,`sorting`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_article`
--


--
-- Table structure for table `tl_content`
--

DROP TABLE IF EXISTS `tl_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_content` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `ptable` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sorting` int(10) unsigned NOT NULL DEFAULT '0',
  `tstamp` int(10) unsigned NOT NULL DEFAULT '0',
  `type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `headline` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'a:2:{s:5:"value";s:0:"";s:4:"unit";s:2:"h2";}',
  `text` longtext COLLATE utf8mb4_unicode_ci,
  `addImage` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `overwriteMeta` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `singleSRC` binary(16) DEFAULT NULL,
  `alt` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `imageTitle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `size` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `imagemargin` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `imageUrl` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `fullsize` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `caption` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `floating` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'above',
  `html` longtext COLLATE utf8mb4_unicode_ci,
  `listtype` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `listitems` blob,
  `tableitems` mediumblob,
  `summary` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `thead` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tfoot` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tleft` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sortable` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sortIndex` smallint(5) unsigned NOT NULL DEFAULT '0',
  `sortOrder` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ascending',
  `mooHeadline` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mooStyle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mooClasses` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `highlight` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `code` mediumtext COLLATE utf8mb4_unicode_ci,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `target` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `titleText` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `linkTitle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `embed` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `rel` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `useImage` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `multiSRC` blob,
  `orderSRC` blob,
  `useHomeDir` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `perRow` smallint(5) unsigned NOT NULL DEFAULT '4',
  `perPage` smallint(5) unsigned NOT NULL DEFAULT '0',
  `numberOfItems` smallint(5) unsigned NOT NULL DEFAULT '0',
  `sortBy` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `metaIgnore` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `galleryTpl` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customTpl` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `playerSRC` blob,
  `youtube` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `vimeo` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `posterSRC` binary(16) DEFAULT NULL,
  `playerSize` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `youtubeOptions` mediumtext COLLATE utf8mb4_unicode_ci,
  `sliderDelay` int(10) unsigned NOT NULL DEFAULT '0',
  `sliderSpeed` int(10) unsigned NOT NULL DEFAULT '300',
  `sliderStartSlide` smallint(5) unsigned NOT NULL DEFAULT '0',
  `sliderContinuous` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cteAlias` int(10) unsigned NOT NULL DEFAULT '0',
  `articleAlias` int(10) unsigned NOT NULL DEFAULT '0',
  `article` int(10) unsigned NOT NULL DEFAULT '0',
  `form` int(10) unsigned NOT NULL DEFAULT '0',
  `module` int(10) unsigned NOT NULL DEFAULT '0',
  `protected` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `groups` blob,
  `guests` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cssID` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `invisible` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `start` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `stop` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `playerOptions` mediumtext COLLATE utf8mb4_unicode_ci,
  `vimeoOptions` mediumtext COLLATE utf8mb4_unicode_ci,
  `playerStart` int(10) unsigned NOT NULL DEFAULT '0',
  `playerStop` int(10) unsigned NOT NULL DEFAULT '0',
  `playerColor` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `playerPreload` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `playerAspect` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `playerCaption` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `overwriteLink` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `splashImage` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `inline` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `pid_ptable_invisible_sorting` (`pid`,`ptable`,`invisible`,`sorting`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_content`
--


--
-- Table structure for table `tl_crawl_queue`
--

DROP TABLE IF EXISTS `tl_crawl_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_crawl_queue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` char(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uri` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `uri_hash` char(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `found_on` longtext COLLATE utf8mb4_unicode_ci,
  `level` smallint(6) NOT NULL,
  `processed` tinyint(1) NOT NULL,
  `tags` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`),
  KEY `uri_hash` (`uri_hash`),
  KEY `processed` (`processed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_crawl_queue`
--


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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_cron`
--


--
-- Table structure for table `tl_cron_job`
--

DROP TABLE IF EXISTS `tl_cron_job`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_cron_job` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lastRun` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_cron_job`
--


--
-- Table structure for table `tl_files`
--

DROP TABLE IF EXISTS `tl_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_files` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` binary(16) DEFAULT NULL,
  `tstamp` int(10) unsigned NOT NULL DEFAULT '0',
  `uuid` binary(16) DEFAULT NULL,
  `type` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `path` varchar(1022) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `extension` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `hash` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `found` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `importantPartX` double unsigned NOT NULL DEFAULT '0',
  `importantPartY` double unsigned NOT NULL DEFAULT '0',
  `importantPartWidth` double unsigned NOT NULL DEFAULT '0',
  `importantPartHeight` double unsigned NOT NULL DEFAULT '0',
  `meta` blob,
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


--
-- Table structure for table `tl_form`
--

DROP TABLE IF EXISTS `tl_form`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_form` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tstamp` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `alias` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `jumpTo` int(10) unsigned NOT NULL DEFAULT '0',
  `sendViaEmail` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `recipient` varchar(1022) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `format` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'raw',
  `skipEmpty` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `storeValues` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `targetTable` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customTpl` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `method` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'POST',
  `novalidate` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `attributes` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `formID` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `allowTags` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `alias` (`alias`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_form`
--


--
-- Table structure for table `tl_form_field`
--

DROP TABLE IF EXISTS `tl_form_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_form_field` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `sorting` int(10) unsigned NOT NULL DEFAULT '0',
  `tstamp` int(10) unsigned NOT NULL DEFAULT '0',
  `type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `text` mediumtext COLLATE utf8mb4_unicode_ci,
  `html` mediumtext COLLATE utf8mb4_unicode_ci,
  `options` blob,
  `mandatory` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `rgxp` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `placeholder` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `minlength` int(10) unsigned NOT NULL DEFAULT '0',
  `maxlength` int(10) unsigned NOT NULL DEFAULT '0',
  `size` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'a:2:{i:0;i:4;i:1;i:40;}',
  `multiple` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mSize` smallint(5) unsigned NOT NULL DEFAULT '0',
  `extensions` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'jpg,jpeg,gif,png,pdf,doc,docx,xls,xlsx,ppt,pptx',
  `storeFile` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `uploadFolder` binary(16) DEFAULT NULL,
  `useHomeDir` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `doNotOverwrite` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `class` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `accesskey` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tabindex` smallint(5) unsigned NOT NULL DEFAULT '0',
  `fSize` smallint(5) unsigned NOT NULL DEFAULT '0',
  `customTpl` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `slabel` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `imageSubmit` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `singleSRC` binary(16) DEFAULT NULL,
  `invisible` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `step` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `maxval` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `minval` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `pid_invisible_sorting` (`pid`,`invisible`,`sorting`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_form_field`
--


--
-- Table structure for table `tl_image_size`
--

DROP TABLE IF EXISTS `tl_image_size`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_image_size` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `tstamp` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cssClass` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sizes` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `densities` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `resizeMode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `zoom` int(11) DEFAULT NULL,
  `skipIfDimensionsMatch` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `lazyLoading` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `formats` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_image_size`
--


--
-- Table structure for table `tl_image_size_item`
--

DROP TABLE IF EXISTS `tl_image_size_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_image_size_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `sorting` int(10) unsigned NOT NULL DEFAULT '0',
  `tstamp` int(10) unsigned NOT NULL DEFAULT '0',
  `media` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sizes` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `densities` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `resizeMode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `zoom` int(11) DEFAULT NULL,
  `invisible` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_image_size_item`
--


--
-- Table structure for table `tl_layout`
--

DROP TABLE IF EXISTS `tl_layout`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_layout` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `tstamp` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `rows` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '2rwh',
  `headerHeight` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `footerHeight` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cols` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '2cll',
  `widthLeft` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `widthRight` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sections` blob,
  `framework` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'a:2:{i:0;s:10:"layout.css";i:1;s:14:"responsive.css";}',
  `stylesheet` blob,
  `external` blob,
  `orderExt` blob,
  `loadingOrder` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'external_first',
  `combineScripts` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `modules` blob,
  `template` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `webfonts` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `viewport` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `titleTag` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cssClass` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `onload` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `head` mediumtext COLLATE utf8mb4_unicode_ci,
  `addJQuery` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `jSource` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `jquery` mediumtext COLLATE utf8mb4_unicode_ci,
  `addMooTools` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mooSource` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'moo_local',
  `mootools` mediumtext COLLATE utf8mb4_unicode_ci,
  `analytics` mediumtext COLLATE utf8mb4_unicode_ci,
  `externalJs` blob,
  `orderExtJs` blob,
  `scripts` mediumtext COLLATE utf8mb4_unicode_ci,
  `script` mediumtext COLLATE utf8mb4_unicode_ci,
  `static` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `width` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `align` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'center',
  `newsfeeds` blob,
  `calendarfeeds` blob,
  `minifyMarkup` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `defaultImageDensities` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `lightboxSize` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_layout`
--

INSERT INTO `tl_layout` (`id`, `pid`, `tstamp`, `name`, `rows`, `headerHeight`, `footerHeight`, `cols`, `widthLeft`, `widthRight`, `sections`, `framework`, `stylesheet`, `external`, `orderExt`, `loadingOrder`, `combineScripts`, `modules`, `template`, `webfonts`, `viewport`, `titleTag`, `cssClass`, `onload`, `head`, `addJQuery`, `jSource`, `jquery`, `addMooTools`, `mooSource`, `mootools`, `analytics`, `externalJs`, `orderExtJs`, `scripts`, `script`, `static`, `width`, `align`, `newsfeeds`, `calendarfeeds`, `minifyMarkup`, `defaultImageDensities`, `lightboxSize`) VALUES (1,1,1584453722,'Contao','3rw','a:2:{s:4:\"unit\";s:0:\"\";s:5:\"value\";s:0:\"\";}','a:2:{s:4:\"unit\";s:0:\"\";s:5:\"value\";s:0:\"\";}','1cl','a:2:{s:4:\"unit\";s:0:\"\";s:5:\"value\";s:0:\"\";}','','','',NULL,NULL,NULL,'external_first','1',_binary 'a:23:{i:0;a:3:{s:3:\"mod\";s:1:\"5\";s:3:\"col\";s:6:\"header\";s:6:\"enable\";s:1:\"1\";}i:1;a:3:{s:3:\"mod\";s:1:\"7\";s:3:\"col\";s:6:\"header\";s:6:\"enable\";s:1:\"1\";}i:2;a:3:{s:3:\"mod\";s:2:\"43\";s:3:\"col\";s:6:\"header\";s:6:\"enable\";s:1:\"1\";}i:3;a:3:{s:3:\"mod\";s:2:\"44\";s:3:\"col\";s:6:\"header\";s:6:\"enable\";s:1:\"1\";}i:4;a:3:{s:3:\"mod\";s:1:\"6\";s:3:\"col\";s:6:\"header\";s:6:\"enable\";s:1:\"1\";}i:5;a:3:{s:3:\"mod\";s:2:\"39\";s:3:\"col\";s:5:\"intro\";s:6:\"enable\";s:1:\"1\";}i:6;a:3:{s:3:\"mod\";s:2:\"17\";s:3:\"col\";s:5:\"intro\";s:6:\"enable\";s:1:\"1\";}i:7;a:3:{s:3:\"mod\";s:2:\"66\";s:3:\"col\";s:5:\"intro\";s:6:\"enable\";s:1:\"1\";}i:8;a:3:{s:3:\"mod\";s:2:\"67\";s:3:\"col\";s:5:\"intro\";s:6:\"enable\";s:1:\"1\";}i:9;a:3:{s:3:\"mod\";s:2:\"21\";s:3:\"col\";s:5:\"intro\";s:6:\"enable\";s:1:\"1\";}i:10;a:3:{s:3:\"mod\";s:1:\"0\";s:3:\"col\";s:4:\"main\";s:6:\"enable\";s:1:\"1\";}i:11;a:3:{s:3:\"mod\";s:2:\"26\";s:3:\"col\";s:10:\"newsletter\";s:6:\"enable\";s:1:\"1\";}i:12;a:3:{s:3:\"mod\";s:3:\"163\";s:3:\"col\";s:10:\"newsletter\";s:6:\"enable\";s:1:\"1\";}i:13;a:3:{s:3:\"mod\";s:3:\"164\";s:3:\"col\";s:10:\"newsletter\";s:6:\"enable\";s:1:\"1\";}i:14;a:3:{s:3:\"mod\";s:2:\"25\";s:3:\"col\";s:10:\"newsletter\";s:6:\"enable\";s:1:\"1\";}i:15;a:3:{s:3:\"mod\";s:1:\"0\";s:3:\"col\";s:7:\"service\";s:6:\"enable\";s:1:\"1\";}i:16;a:3:{s:3:\"mod\";s:2:\"58\";s:3:\"col\";s:9:\"prefooter\";s:6:\"enable\";s:1:\"1\";}i:17;a:3:{s:3:\"mod\";s:1:\"4\";s:3:\"col\";s:9:\"prefooter\";s:6:\"enable\";s:1:\"1\";}i:18;a:3:{s:3:\"mod\";s:1:\"3\";s:3:\"col\";s:6:\"footer\";s:6:\"enable\";s:1:\"1\";}i:19;a:3:{s:3:\"mod\";s:2:\"62\";s:3:\"col\";s:6:\"footer\";s:6:\"enable\";s:1:\"1\";}i:20;a:3:{s:3:\"mod\";s:2:\"60\";s:3:\"col\";s:6:\"footer\";s:6:\"enable\";s:1:\"1\";}i:21;a:3:{s:3:\"mod\";s:2:\"61\";s:3:\"col\";s:6:\"footer\";s:6:\"enable\";s:1:\"1\";}i:22;a:3:{s:3:\"mod\";s:1:\"0\";s:3:\"col\";s:17:\"newsletter-module\";s:6:\"enable\";s:1:\"1\";}}','fe_page','','','','layout--home','','','','',NULL,'','moo_local',NULL,NULL,NULL,NULL,NULL,'','','','center',NULL,NULL,'','','');

--
-- Table structure for table `tl_log`
--

DROP TABLE IF EXISTS `tl_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tstamp` int(10) unsigned NOT NULL DEFAULT '0',
  `source` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `action` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `username` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `text` mediumtext COLLATE utf8mb4_unicode_ci,
  `func` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `browser` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_log`
--


--
-- Table structure for table `tl_member`
--

DROP TABLE IF EXISTS `tl_member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_member` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tstamp` int(10) unsigned NOT NULL DEFAULT '0',
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
  `groups` blob,
  `login` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `username` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `assignDir` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `homeDir` binary(16) DEFAULT NULL,
  `disable` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `start` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `stop` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateAdded` int(10) unsigned NOT NULL DEFAULT '0',
  `lastLogin` int(10) unsigned NOT NULL DEFAULT '0',
  `currentLogin` int(10) unsigned NOT NULL DEFAULT '0',
  `locked` int(10) unsigned NOT NULL DEFAULT '0',
  `session` blob,
  `newsletter` blob,
  `backupCodes` mediumtext COLLATE utf8mb4_unicode_ci,
  `useTwoFactor` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `secret` binary(128) DEFAULT NULL,
  `loginAttempts` smallint(5) unsigned NOT NULL DEFAULT '0',
  `trustedTokenVersion` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_member`
--


--
-- Table structure for table `tl_member_group`
--

DROP TABLE IF EXISTS `tl_member_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_member_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tstamp` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `redirect` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `jumpTo` int(10) unsigned NOT NULL DEFAULT '0',
  `disable` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `start` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `stop` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_member_group`
--


--
-- Table structure for table `tl_module`
--

DROP TABLE IF EXISTS `tl_module`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_module` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `tstamp` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `headline` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'a:2:{s:5:"value";s:0:"";s:4:"unit";s:2:"h2";}',
  `type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'navigation',
  `levelOffset` smallint(5) unsigned NOT NULL DEFAULT '0',
  `showLevel` smallint(5) unsigned NOT NULL DEFAULT '0',
  `hardLimit` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `showProtected` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `defineRoot` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `rootPage` int(10) unsigned NOT NULL DEFAULT '0',
  `navigationTpl` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customTpl` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `pages` blob,
  `orderPages` blob,
  `showHidden` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customLabel` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `autologin` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `jumpTo` int(10) unsigned NOT NULL DEFAULT '0',
  `redirectBack` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `editable` blob,
  `memberTpl` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `form` int(10) unsigned NOT NULL DEFAULT '0',
  `queryType` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'and',
  `fuzzy` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `contextLength` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `minKeywordLength` smallint(5) unsigned NOT NULL DEFAULT '4',
  `perPage` smallint(5) unsigned NOT NULL DEFAULT '0',
  `searchType` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'simple',
  `searchTpl` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `inColumn` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'main',
  `skipFirst` smallint(5) unsigned NOT NULL DEFAULT '0',
  `loadFirst` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `singleSRC` binary(16) DEFAULT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `imgSize` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `useCaption` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `fullsize` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `multiSRC` blob,
  `orderSRC` blob,
  `html` mediumtext COLLATE utf8mb4_unicode_ci,
  `rss_cache` int(10) unsigned NOT NULL DEFAULT '3600',
  `rss_feed` mediumtext COLLATE utf8mb4_unicode_ci,
  `rss_template` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `numberOfItems` smallint(5) unsigned NOT NULL DEFAULT '3',
  `disableCaptcha` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `reg_groups` blob,
  `reg_allowLogin` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `reg_skipName` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `reg_close` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `reg_assignDir` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `reg_homeDir` binary(16) DEFAULT NULL,
  `reg_activate` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `reg_jumpTo` int(10) unsigned NOT NULL DEFAULT '0',
  `reg_text` mediumtext COLLATE utf8mb4_unicode_ci,
  `reg_password` mediumtext COLLATE utf8mb4_unicode_ci,
  `protected` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `groups` blob,
  `guests` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cssID` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_module`
--


--
-- Table structure for table `tl_opt_in`
--

DROP TABLE IF EXISTS `tl_opt_in`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_opt_in` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tstamp` int(10) unsigned NOT NULL DEFAULT '0',
  `token` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `createdOn` int(10) unsigned NOT NULL DEFAULT '0',
  `confirmedOn` int(10) unsigned NOT NULL DEFAULT '0',
  `removeOn` int(10) unsigned NOT NULL DEFAULT '0',
  `invalidatedThrough` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `emailSubject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `emailText` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `removeon` (`removeOn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_opt_in`
--


--
-- Table structure for table `tl_opt_in_related`
--

DROP TABLE IF EXISTS `tl_opt_in_related`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_opt_in_related` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `relTable` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `relId` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `reltable_relid` (`relTable`,`relId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_opt_in_related`
--


--
-- Table structure for table `tl_page`
--

DROP TABLE IF EXISTS `tl_page`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_page` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`pid` int(10) unsigned NOT NULL DEFAULT '0',
`sorting` int(10) unsigned NOT NULL DEFAULT '0',
`tstamp` int(10) unsigned NOT NULL DEFAULT '0',
`title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`alias` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
`type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'regular',
`pageTitle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`language` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`robots` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`description` text COLLATE utf8mb4_unicode_ci,
`redirect` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'permanent',
`jumpTo` int(10) unsigned NOT NULL DEFAULT '0',
`redirectBack` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`target` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`dns` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`staticFiles` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`staticPlugins` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`fallback` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`favicon` binary(16) DEFAULT NULL,
`robotsTxt` text COLLATE utf8mb4_unicode_ci,
`adminEmail` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`dateFormat` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`timeFormat` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`datimFormat` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`validAliasCharacters` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`createSitemap` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`sitemapName` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`useSSL` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`autoforward` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`protected` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`groups` blob,
`includeLayout` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`layout` int(10) unsigned NOT NULL DEFAULT '0',
`includeCache` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`cache` int(10) unsigned NOT NULL DEFAULT '0',
`alwaysLoadFromCache` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`clientCache` int(10) unsigned NOT NULL DEFAULT '0',
`includeChmod` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`cuser` int(10) unsigned NOT NULL DEFAULT '0',
`cgroup` int(10) unsigned NOT NULL DEFAULT '0',
`chmod` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`noSearch` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`requireItem` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`cssClass` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`sitemap` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`hide` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`guests` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`tabindex` smallint(5) unsigned NOT NULL DEFAULT '0',
`accesskey` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`published` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`start` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`stop` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`enforceTwoFactor` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
`twoFactorJumpTo` int(10) unsigned NOT NULL DEFAULT '0',
PRIMARY KEY (`id`),
KEY `alias` (`alias`),
KEY `type_dns` (`type`,`dns`),
KEY `pid_type_start_stop_published` (`pid`,`type`,`start`,`stop`,`published`)
) ENGINE=InnoDB AUTO_INCREMENT=259 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_page`
--

INSERT INTO `tl_page` (`id`, `pid`, `sorting`, `tstamp`, `title`, `alias`, `type`, `pageTitle`, `language`, `robots`, `description`, `redirect`, `jumpTo`, `redirectBack`, `url`, `target`, `dns`, `staticFiles`, `staticPlugins`, `fallback`, `favicon`, `robotsTxt`, `adminEmail`, `dateFormat`, `timeFormat`, `datimFormat`, `validAliasCharacters`, `createSitemap`, `sitemapName`, `useSSL`, `autoforward`, `protected`, `groups`, `includeLayout`, `layout`, `includeCache`, `cache`, `alwaysLoadFromCache`, `clientCache`, `includeChmod`, `cuser`, `cgroup`, `chmod`, `noSearch`, `requireItem`, `cssClass`, `sitemap`, `hide`, `guests`, `tabindex`, `accesskey`, `published`, `start`, `stop`, `enforceTwoFactor`, `twoFactorJumpTo`) VALUES (1,0,608,1608212430,'Domain3','domain3','root','','de','',NULL,'permanent',0,'','','','domain3.local','','','',NULL,NULL,'','','','','0-9a-z','1','','1','','',NULL,'1',1,'',0,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','','','','','','',0,'','','','','',0),(2,0,276,1614619939,'Domain1','domain1_de','root','','de','',NULL,'permanent',0,'','','','domain1.local','','','1',NULL,NULL,'','','','','0-9a-z','1','','1','','',NULL,'1',1,'',0,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','','','','','','',0,'','1','','','',0),(3,0,288,1610312264,'Domain1','domain1_fr-CH','root','','fr-CH','',NULL,'permanent',0,'','','','domain1.local','','','',NULL,NULL,'','','','','0-9a-z','1','','1','','',NULL,'1',1,'',0,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','','','','','','',0,'','','','','',0),(6,0,272,1614580078,'Domain7','domain7_de','root','','de','',NULL,'permanent',0,'','','','domain7.local','','','1',NULL,NULL,'','','','','0-9a-z','1','','1','','',NULL,'1',1,'',0,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','','','','','','',0,'','1','','','',0),(7,0,400,1614527080,'Domain7','domain7_fr-CH','root','','fr-CH','',NULL,'permanent',0,'','','','domain7.local','','','',NULL,NULL,'','','','','0-9a-z','1','','1','','',NULL,'',0,'',0,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','','','','','','',0,'','','','','',0),(22,1,512,1608212260,'Index3','index','regular','','','index,follow',NULL,'permanent',0,'','','','','','','',NULL,NULL,'','','','','','','','','','',NULL,'',0,'',0,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','','','','map_default','1','',0,'','','','','',0),(23,2,512,1614618691,'Index1 - de','index','regular','','','index,follow',NULL,'permanent',0,'','','','','','','',NULL,NULL,'','','','','','','','','','',NULL,'',0,'',0,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','','','','map_default','','',0,'','1','','','',0),(25,6,512,1610742212,'Index7','index','regular','','','index,follow',NULL,'permanent',0,'','','','','','','',NULL,NULL,'','','','','','','','','','',NULL,'',0,'',0,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','','','','map_default','1','',0,'','1','','','',0),(29,3,512,1614618531,'Index1 - fr-CH','index','regular','','','index,follow',NULL,'permanent',0,'','','','','','','',NULL,NULL,'','','','','','','','','','',NULL,'',0,'',0,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','','','','map_default','','',0,'','','','','',0),(33,7,512,1588427868,'Index7','index','regular','','','index,follow',NULL,'permanent',0,'','','','','','','',NULL,NULL,'','','','','','','','','','',NULL,'',0,'',0,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','','','','map_default','1','',0,'','1','','','',0),(129,0,736,1608212074,'Domain8','domain8','root','','de','',NULL,'permanent',0,'','','','domain8.local','','','1',NULL,NULL,'','','','','0-9a-z','','','1','','',NULL,'',0,'',0,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','','','','','','',0,'','','','','',0),(137,0,280,1610312261,'Domain4','domain4','root','','de','',NULL,'permanent',0,'','','','domain4.local','','','1',NULL,NULL,'','','','','0-9a-z','1','','1','','',NULL,'1',1,'',0,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','','','','','','',0,'','','','','',0),(142,0,284,1614527086,'Domain10','domain10_de','root','','de','',NULL,'permanent',0,'','','','domain10.local','','','',NULL,NULL,'','','','','0-9a-z','1','','1','','',NULL,'1',1,'',0,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','','','','','','',0,'','','','','',0),(170,142,60,1607088778,'Index10','index','regular','','','index,follow',NULL,'permanent',0,'','','','','','','',NULL,NULL,'','','','','','','','','','',NULL,'',0,'',0,'',0,'',0,0,'a:9:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";}','','','','map_default','1','',0,'','1','','','',0),(190,0,128,1614527460,'Domain9','domain9','root','','de','',NULL,'permanent',0,'','','','domain9.local','','','1',NULL,NULL,'','','','','0-9a-z','','','1','','',NULL,'1',1,'',0,'',0,'',0,0,'a:15:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";i:9;s:2:\"w1\";i:10;s:2:\"w2\";i:11;s:2:\"w3\";i:12;s:2:\"w4\";i:13;s:2:\"w5\";i:14;s:2:\"w6\";}','','','','','','',0,'','1','','','',0),(193,190,192,1608219830,'Index9','index','regular','','','index,follow',NULL,'permanent',0,'','','','','','','',NULL,NULL,'','','','','','','','','','',NULL,'',0,'',0,'',0,'',0,0,'a:15:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";i:9;s:2:\"w1\";i:10;s:2:\"w2\";i:11;s:2:\"w3\";i:12;s:2:\"w4\";i:13;s:2:\"w5\";i:14;s:2:\"w6\";}','','','','map_default','','',0,'','1','','','',0),(249,0,504,1614527455,'Domain6','domain6','root','','de','',NULL,'permanent',0,'','','','domain6.local','','','1',NULL,NULL,'','','','','','','','1','','',NULL,'',0,'',0,'',0,'',0,0,'a:15:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";i:9;s:2:\"w1\";i:10;s:2:\"w2\";i:11;s:2:\"w3\";i:12;s:2:\"w4\";i:13;s:2:\"w5\";i:14;s:2:\"w6\";}','','','','','','',0,'','1','','','',0),(251,0,556,1614527454,'Domain10','domain10_de','root','','de','',NULL,'permanent',0,'','','','domain10.local','','','1',NULL,NULL,'','','','','','','','1','','',NULL,'',0,'',0,'',0,'',0,0,'a:15:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";i:9;s:2:\"w1\";i:10;s:2:\"w2\";i:11;s:2:\"w3\";i:12;s:2:\"w4\";i:13;s:2:\"w5\";i:14;s:2:\"w6\";}','','','','','','',0,'','1','','','',0),(254,0,582,1614527452,'Domain2','domain2','root','','de','',NULL,'permanent',0,'','','','domain2.local','','','1',NULL,NULL,'','','','','','','','1','','',NULL,'',0,'',0,'',0,'',0,0,'a:15:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";i:9;s:2:\"w1\";i:10;s:2:\"w2\";i:11;s:2:\"w3\";i:12;s:2:\"w4\";i:13;s:2:\"w5\";i:14;s:2:\"w6\";}','','','','','','',0,'','1','','','',0),(257,0,595,1614618459,'Domain5','domain5','root','','de','',NULL,'permanent',0,'','','','domain5.local','','','1',NULL,NULL,'','','','','','','','1','','',NULL,'',0,'',0,'',0,'',0,0,'a:15:{i:0;s:2:\"u1\";i:1;s:2:\"u2\";i:2;s:2:\"u3\";i:3;s:2:\"u4\";i:4;s:2:\"u5\";i:5;s:2:\"u6\";i:6;s:2:\"g4\";i:7;s:2:\"g5\";i:8;s:2:\"g6\";i:9;s:2:\"w1\";i:10;s:2:\"w2\";i:11;s:2:\"w3\";i:12;s:2:\"w4\";i:13;s:2:\"w5\";i:14;s:2:\"w6\";}','','','','','','',0,'','1','','','',0);

--
-- Table structure for table `tl_remember_me`
--

DROP TABLE IF EXISTS `tl_remember_me`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_remember_me` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `series` binary(32) NOT NULL COMMENT '(DC2Type:binary_string)',
  `value` binary(64) NOT NULL COMMENT '(DC2Type:binary_string)',
  `lastUsed` datetime NOT NULL,
  `expires` datetime DEFAULT NULL,
  `class` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `value` (`value`),
  KEY `series` (`series`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_remember_me`
--


--
-- Table structure for table `tl_search`
--

DROP TABLE IF EXISTS `tl_search`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_search` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `tstamp` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `text` longtext COLLATE utf8mb4_unicode_ci,
  `filesize` double NOT NULL DEFAULT '0',
  `checksum` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `protected` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `groups` blob,
  `language` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`url`),
  UNIQUE KEY `checksum_pid` (`checksum`,`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_search`
--


--
-- Table structure for table `tl_search_index`
--

DROP TABLE IF EXISTS `tl_search_index`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_search_index` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `word` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `relevance` smallint(5) unsigned NOT NULL DEFAULT '0',
  `language` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `word` (`word`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_search_index`
--


--
-- Table structure for table `tl_search_term`
--

DROP TABLE IF EXISTS `tl_search_term`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_search_term` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `term` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `documentFrequency` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `term` (`term`),
  KEY `documentfrequency` (`documentFrequency`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_search_term`
--


--
-- Table structure for table `tl_style`
--

DROP TABLE IF EXISTS `tl_style`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_style` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `sorting` int(10) unsigned NOT NULL DEFAULT '0',
  `tstamp` int(10) unsigned NOT NULL DEFAULT '0',
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
  `own` mediumtext COLLATE utf8mb4_unicode_ci,
  `invisible` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_style`
--


--
-- Table structure for table `tl_style_sheet`
--

DROP TABLE IF EXISTS `tl_style_sheet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_style_sheet` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `tstamp` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `embedImages` int(10) unsigned NOT NULL DEFAULT '0',
  `cc` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `media` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'a:1:{i:0;s:3:"all";}',
  `mediaQuery` mediumtext COLLATE utf8mb4_unicode_ci,
  `vars` mediumtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_style_sheet`
--


--
-- Table structure for table `tl_theme`
--

DROP TABLE IF EXISTS `tl_theme`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_theme` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tstamp` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `author` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `folders` blob,
  `screenshot` binary(16) DEFAULT NULL,
  `templates` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_theme`
--

INSERT INTO `tl_theme` (`id`, `tstamp`, `name`, `author`, `folders`, `screenshot`, `templates`) VALUES (1,1575316433,'Contao','',NULL,NULL,'');

--
-- Table structure for table `tl_trusted_device`
--

DROP TABLE IF EXISTS `tl_trusted_device`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_trusted_device` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` datetime NOT NULL,
  `userClass` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  `user_agent` longtext COLLATE utf8mb4_unicode_ci,
  `ua_family` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `os_family` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_family` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_trusted_device`
--


--
-- Table structure for table `tl_undo`
--

DROP TABLE IF EXISTS `tl_undo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_undo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `tstamp` int(10) unsigned NOT NULL DEFAULT '0',
  `fromTable` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `query` mediumtext COLLATE utf8mb4_unicode_ci,
  `affectedRows` smallint(5) unsigned NOT NULL DEFAULT '0',
  `data` mediumblob,
  `haste_data` blob,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_undo`
--


--
-- Table structure for table `tl_user`
--

DROP TABLE IF EXISTS `tl_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tstamp` int(10) unsigned NOT NULL DEFAULT '0',
  `username` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `language` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `backendTheme` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `fullscreen` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `uploader` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `showHelp` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `thumbnails` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `useRTE` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `useCE` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `pwChange` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `admin` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `groups` blob,
  `inherit` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'group',
  `modules` blob,
  `themes` blob,
  `pagemounts` blob,
  `alpty` blob,
  `filemounts` blob,
  `fop` blob,
  `imageSizes` blob,
  `forms` blob,
  `formp` blob,
  `amg` blob,
  `disable` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `start` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `stop` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `session` blob,
  `dateAdded` int(10) unsigned NOT NULL DEFAULT '0',
  `lastLogin` int(10) unsigned NOT NULL DEFAULT '0',
  `currentLogin` int(10) unsigned NOT NULL DEFAULT '0',
  `locked` int(10) unsigned NOT NULL DEFAULT '0',
  `useTwoFactor` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `secret` binary(128) DEFAULT NULL,
  `trustedTokenVersion` int(10) unsigned NOT NULL DEFAULT '0',
  `backupCodes` mediumtext COLLATE utf8mb4_unicode_ci,
  `loginAttempts` smallint(5) unsigned NOT NULL DEFAULT '0',
  `fields` blob,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_user`
--


--
-- Table structure for table `tl_user_group`
--

DROP TABLE IF EXISTS `tl_user_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_user_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tstamp` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `modules` blob,
  `themes` blob,
  `pagemounts` blob,
  `alpty` blob,
  `filemounts` blob,
  `fop` blob,
  `imageSizes` blob,
  `forms` blob,
  `formp` blob,
  `amg` blob,
  `alexf` blob,
  `disable` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `start` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `stop` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `elements` blob,
  `fields` blob,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_user_group`
--


--
-- Table structure for table `tl_version`
--

DROP TABLE IF EXISTS `tl_version`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tl_version` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `tstamp` int(10) unsigned NOT NULL DEFAULT '0',
  `version` smallint(5) unsigned NOT NULL DEFAULT '1',
  `fromTable` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `username` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `editUrl` mediumtext COLLATE utf8mb4_unicode_ci,
  `active` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `data` mediumblob,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pid_fromtable_version` (`pid`,`fromTable`,`version`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tl_version`
--

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2020-10-27 15:40:32
