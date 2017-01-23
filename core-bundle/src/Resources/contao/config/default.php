<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

// General settings
$GLOBALS['TL_CONFIG']['websiteTitle']   = 'Contao Open Source CMS';
$GLOBALS['TL_CONFIG']['characterSet']   = 'utf-8';
$GLOBALS['TL_CONFIG']['adminEmail']     = '';
$GLOBALS['TL_CONFIG']['enableSearch']   = true;
$GLOBALS['TL_CONFIG']['indexProtected'] = false;
$GLOBALS['TL_CONFIG']['folderUrl']      = false;
$GLOBALS['TL_CONFIG']['minifyMarkup']   = false;
$GLOBALS['TL_CONFIG']['gzipScripts']    = false;

// Date and time
$GLOBALS['TL_CONFIG']['datimFormat'] = 'Y-m-d H:i';
$GLOBALS['TL_CONFIG']['dateFormat']  = 'Y-m-d';
$GLOBALS['TL_CONFIG']['timeFormat']  = 'H:i';
$GLOBALS['TL_CONFIG']['timeZone']    = ini_get('date.timezone') ?: 'GMT';

// Input and security
$GLOBALS['TL_CONFIG']['allowedTags']
	= '<a><abbr><acronym><address><area><article><aside><audio>'
	. '<b><bdi><bdo><big><blockquote><br><base><button>'
	. '<canvas><caption><cite><code><col><colgroup>'
	. '<data><datalist><dataset><dd><del><dfn><div><dl><dt>'
	. '<em>'
	. '<fieldset><figcaption><figure><footer><form>'
	. '<h1><h2><h3><h4><h5><h6><header><hgroup><hr>'
	. '<i><img><input><ins>'
	. '<kbd><keygen>'
	. '<label><legend><li><link>'
	. '<map><mark><menu>'
	. '<nav>'
	. '<object><ol><optgroup><option><output>'
	. '<p><param><picture><pre>'
	. '<q>'
	. '<s><samp><section><select><small><source><span><strong><style><sub><sup>'
	. '<table><tbody><td><textarea><tfoot><th><thead><time><tr><tt>'
	. '<u><ul>'
	. '<var><video>'
	. '<wbr>';
$GLOBALS['TL_CONFIG']['disableRefererCheck']   = false;
$GLOBALS['TL_CONFIG']['requestTokenWhitelist'] = [];

// Database
$GLOBALS['TL_CONFIG']['dbCharset']   = 'utf8';
$GLOBALS['TL_CONFIG']['dbCollation'] = 'utf8_general_ci';

// Encryption
$GLOBALS['TL_CONFIG']['encryptionMode']   = 'cfb';
$GLOBALS['TL_CONFIG']['encryptionCipher'] = 'rijndael-256';
$GLOBALS['TL_CONFIG']['bcryptCost']       = 10;

// File uploads
$GLOBALS['TL_CONFIG']['uploadTypes']
	= 'jpg,jpeg,gif,png,ico,svg,svgz,'
	. 'odt,ods,odp,odg,ott,ots,otp,otg,pdf,csv,'
	. 'doc,docx,dot,dotx,xls,xlsx,xlt,xltx,ppt,pptx,pot,potx,'
	. 'mp3,mp4,m4a,m4v,webm,ogg,ogv,wma,wmv,ram,rm,mov,fla,flv,swf,'
	. 'ttf,ttc,otf,eot,woff,woff2,'
	. 'css,scss,less,js,html,htm,txt,zip,rar,7z,cto';
$GLOBALS['TL_CONFIG']['maxFileSize']    = 2048000;
$GLOBALS['TL_CONFIG']['imageWidth']     = 800;
$GLOBALS['TL_CONFIG']['imageHeight']    = 600;
$GLOBALS['TL_CONFIG']['gdMaxImgWidth']  = 3000;
$GLOBALS['TL_CONFIG']['gdMaxImgHeight'] = 3000;

// Timeout values
$GLOBALS['TL_CONFIG']['undoPeriod']     = 86400;
$GLOBALS['TL_CONFIG']['versionPeriod']  = 7776000;
$GLOBALS['TL_CONFIG']['logPeriod']      = 1209600;
$GLOBALS['TL_CONFIG']['sessionTimeout'] = 3600;
$GLOBALS['TL_CONFIG']['lockPeriod']     = 300;

// User defaults
$GLOBALS['TL_CONFIG']['showHelp']   = true;
$GLOBALS['TL_CONFIG']['thumbnails'] = true;
$GLOBALS['TL_CONFIG']['useRTE']     = true;
$GLOBALS['TL_CONFIG']['useCE']      = true;

// Miscellaneous
$GLOBALS['TL_CONFIG']['loginCount']           = 3;
$GLOBALS['TL_CONFIG']['resultsPerPage']       = 30;
$GLOBALS['TL_CONFIG']['maxResultsPerPage']    = 500;
$GLOBALS['TL_CONFIG']['maxImageWidth']        = '';
$GLOBALS['TL_CONFIG']['defaultUser']          = 0;
$GLOBALS['TL_CONFIG']['defaultGroup']         = 0;
$GLOBALS['TL_CONFIG']['defaultChmod']         = ['u1', 'u2', 'u3', 'u4', 'u5', 'u6', 'g4', 'g5', 'g6'];
$GLOBALS['TL_CONFIG']['editableFiles']        = 'htm,html,css,scss,less,js,txt,log,xml,svg,svgz';
$GLOBALS['TL_CONFIG']['templateFiles']        = 'tpl,html5,xhtml';
$GLOBALS['TL_CONFIG']['allowedDownload']
	= 'jpg,jpeg,gif,png,svg,svgz,'
	. 'odt,ods,odp,odg,ott,ots,otp,otg,pdf,'
	. 'doc,docx,dot,dotx,xls,xlsx,xlt,xltx,ppt,pptx,pot,potx,'
	. 'mp3,mp4,m4a,m4v,webm,ogg,ogv,wma,wmv,ram,rm,mov,'
	. 'zip,rar,7z';
$GLOBALS['TL_CONFIG']['installPassword']      = '';
$GLOBALS['TL_CONFIG']['liveUpdateBase']       = 'https://update.contao.org/service/';
$GLOBALS['TL_CONFIG']['repository_wsdl']      = 'https://contao.org/services/repository.wsdl';
$GLOBALS['TL_CONFIG']['repository_languages'] = 'en,de';
$GLOBALS['TL_CONFIG']['repository_listsize']  = 10;
$GLOBALS['TL_CONFIG']['backendTheme']         = 'flexible';
$GLOBALS['TL_CONFIG']['disableInsertTags']    = false;
$GLOBALS['TL_CONFIG']['rootFiles']            = [];
$GLOBALS['TL_CONFIG']['fileSyncExclude']      = '';
$GLOBALS['TL_CONFIG']['doNotCollapse']        = false;
$GLOBALS['TL_CONFIG']['exampleWebsite']       = '';
$GLOBALS['TL_CONFIG']['minPasswordLength']    = 8;
$GLOBALS['TL_CONFIG']['autologin']            = 7776000;
$GLOBALS['TL_CONFIG']['staticFiles']          = '';
$GLOBALS['TL_CONFIG']['staticPlugins']        = '';
$GLOBALS['TL_CONFIG']['disableCron']          = false;
$GLOBALS['TL_CONFIG']['coreOnlyMode']         = false;
$GLOBALS['TL_CONFIG']['doNotRedirectEmpty']   = false;
$GLOBALS['TL_CONFIG']['useAutoItem']          = true;
$GLOBALS['TL_CONFIG']['privacyAnonymizeIp']   = true;
$GLOBALS['TL_CONFIG']['privacyAnonymizeGA']   = true;
$GLOBALS['TL_CONFIG']['bypassCache']          = false;
$GLOBALS['TL_CONFIG']['defaultFileChmod']     = 0644;
$GLOBALS['TL_CONFIG']['defaultFolderChmod']   = 0755;
$GLOBALS['TL_CONFIG']['maxPaginationLinks']   = 7;
$GLOBALS['TL_CONFIG']['sslProxyDomain']       = '';
