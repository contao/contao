<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

// General settings
$GLOBALS['TL_CONFIG']['adminEmail']     = '';
$GLOBALS['TL_CONFIG']['folderUrl']      = true;

// Date and time
$GLOBALS['TL_CONFIG']['datimFormat'] = 'Y-m-d H:i';
$GLOBALS['TL_CONFIG']['dateFormat']  = 'Y-m-d';
$GLOBALS['TL_CONFIG']['timeFormat']  = 'H:i';
$GLOBALS['TL_CONFIG']['timeZone']    = date_default_timezone_get();

// Input and security
$GLOBALS['TL_CONFIG']['allowedTags']
	= '<a><abbr><acronym><address><area><article><aside><audio>'
	. '<b><bdi><bdo><big><blockquote><br><button>'
	. '<caption><cite><code><col><colgroup>'
	. '<data><datalist><dd><del><details><dfn><div><dl><dt>'
	. '<em>'
	. '<fieldset><figcaption><figure><footer><form>'
	. '<h1><h2><h3><h4><h5><h6><header><hgroup><hr>'
	. '<i><img><input><ins>'
	. '<kbd>'
	. '<label><legend><li>'
	. '<map><mark><menu>'
	. '<nav>'
	. '<ol><optgroup><option><output>'
	. '<p><picture><pre>'
	. '<q>'
	. '<s><samp><section><select><small><source><span><strong><style><sub><summary><sup>'
	. '<table><tbody><td><textarea><tfoot><th><thead><time><tr><tt>'
	. '<u><ul>'
	. '<var><video>'
	. '<wbr>';
$GLOBALS['TL_CONFIG']['allowedAttributes'] = serialize(array(
	array('key' => '*', 'value' => 'data-*,id,class,style,title,dir,lang,aria-*,hidden,translate,itemid,itemprop,itemref,itemscope,itemtype'),
	array('key' => 'a', 'value' => 'href,hreflang,rel,target,download,referrerpolicy'),
	array('key' => 'img', 'value' => 'src,crossorigin,srcset,sizes,width,height,alt,loading,decoding,ismap,usemap,referrerpolicy'),
	array('key' => 'map', 'value' => 'name'),
	array('key' => 'area', 'value' => 'coords,shape,alt,href,hreflang,rel,target,download'),
	array('key' => 'video', 'value' => 'src,crossorigin,width,height,autoplay,controls,controlslist,loop,muted,poster,preload,playsinline'),
	array('key' => 'audio', 'value' => 'src,crossorigin,autoplay,controls,loop,muted,preload'),
	array('key' => 'source', 'value' => 'src,srcset,media,sizes,type'),
	array('key' => 'ol', 'value' => 'reversed,start,type'),
	array('key' => 'table', 'value' => 'border,cellspacing,cellpadding,width,height'),
	array('key' => 'col', 'value' => 'span'),
	array('key' => 'colgroup', 'value' => 'span'),
	array('key' => 'td', 'value' => 'rowspan,colspan,width,height'),
	array('key' => 'th', 'value' => 'rowspan,colspan,width,height'),
	array('key' => 'style', 'value' => 'media'),
	array('key' => 'time', 'value' => 'datetime'),
	array('key' => 'details', 'value' => 'open'),
));

// File uploads
$GLOBALS['TL_CONFIG']['uploadTypes']
	= 'jpg,jpeg,gif,png,ico,svg,svgz,webp,avif,heic,jxl,'
	. 'odt,ods,odp,odg,ott,ots,otp,otg,pdf,csv,'
	. 'doc,docx,dot,dotx,xls,xlsx,xlt,xltx,ppt,pptx,pot,potx,'
	. 'mp3,mp4,m4a,m4v,webm,ogg,ogv,wma,wmv,ram,rm,mov,fla,flv,swf,'
	. 'ttf,ttc,otf,eot,woff,woff2,'
	. 'css,scss,less,js,html,htm,txt,zip,rar,7z,cto,md';
$GLOBALS['TL_CONFIG']['maxFileSize']    = 2048000;
$GLOBALS['TL_CONFIG']['imageWidth']     = 0;
$GLOBALS['TL_CONFIG']['imageHeight']    = 0;

// Timeout values
$GLOBALS['TL_CONFIG']['undoPeriod']     = 2592000;
$GLOBALS['TL_CONFIG']['versionPeriod']  = 7776000;
$GLOBALS['TL_CONFIG']['logPeriod']      = 604800;

// User defaults
$GLOBALS['TL_CONFIG']['showHelp']   = true;
$GLOBALS['TL_CONFIG']['thumbnails'] = true;
$GLOBALS['TL_CONFIG']['useRTE']     = true;
$GLOBALS['TL_CONFIG']['useCE']      = true;

// Miscellaneous
$GLOBALS['TL_CONFIG']['resultsPerPage']       = 30;
$GLOBALS['TL_CONFIG']['maxResultsPerPage']    = 500;
$GLOBALS['TL_CONFIG']['maxImageWidth']        = 0;
$GLOBALS['TL_CONFIG']['defaultUser']          = 0;
$GLOBALS['TL_CONFIG']['defaultGroup']         = 0;
$GLOBALS['TL_CONFIG']['defaultChmod']         = array('u1', 'u2', 'u3', 'u4', 'u5', 'u6', 'g4', 'g5', 'g6');
$GLOBALS['TL_CONFIG']['installPassword']      = '';
$GLOBALS['TL_CONFIG']['backendTheme']         = 'flexible';
$GLOBALS['TL_CONFIG']['minPasswordLength']    = 8;
$GLOBALS['TL_CONFIG']['defaultFileChmod']     = 0644;
$GLOBALS['TL_CONFIG']['defaultFolderChmod']   = 0755;
$GLOBALS['TL_CONFIG']['maxPaginationLinks']   = 7;
