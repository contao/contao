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

/*
 * Elements based on the safe default configuration from the HTML specification
 * with the following modifications:
 * - removed: <html><head><title><body><main><search>
 * - added: <img><picture><source><map><area><audio><video><details><summary><style>
 *
 * @see https://html.spec.whatwg.org/multipage/dynamic-markup-insertion.html#built-in-safe-default-configuration
 */
$GLOBALS['TL_CONFIG']['allowedTags']
	= '<a><abbr><address><area><article><aside><audio>'
	. '<b><bdi><bdo><blockquote><br>'
	. '<caption><cite><code><col><colgroup>'
	. '<data><dd><del><details><dfn><div><dl><dt>'
	. '<em>'
	. '<figcaption><figure><footer>'
	. '<h1><h2><h3><h4><h5><h6><header><hgroup><hr>'
	. '<i><img><ins>'
	. '<kbd>'
	. '<li>'
	. '<map><mark><menu>'
	. '<nav>'
	. '<ol>'
	. '<p><picture><pre>'
	. '<q>'
	. '<rp><rt><ruby>'
	. '<s><samp><section><small><source><span><strong><style><sub><summary><sup>'
	. '<table><tbody><td><tfoot><th><thead><time><tr>'
	. '<u><ul>'
	. '<var><video>'
	. '<wbr>';

/*
 * Attributes based on the safe default configuration from the HTML
 * specification with the following modifications:
 * - added for all elements: data-*,id,class,style,aria-*,hidden,translate,itemid,itemprop,itemref,itemscope,itemtype
 * - added for the <a> element: rel,target,download,referrerpolicy
 * - added for the <table> element: border,cellspacing,cellpadding,width,height
 * - added for the <td> and <th> element: width,height
 * - added elements: img, map, area, video, audio, source, style, details
 *
 * @see https://html.spec.whatwg.org/multipage/dynamic-markup-insertion.html#built-in-safe-default-configuration
 */
$GLOBALS['TL_CONFIG']['allowedAttributes'] = serialize(array(
	array('key' => '*', 'value' => 'data-*,id,class,style,title,dir,lang,aria-*,hidden,translate,itemid,itemprop,itemref,itemscope,itemtype'),
	array('key' => 'a', 'value' => 'href,hreflang,type,rel,target,download,referrerpolicy'),
	array('key' => 'img', 'value' => 'src,crossorigin,srcset,sizes,width,height,alt,loading,decoding,ismap,usemap,referrerpolicy'),
	array('key' => 'map', 'value' => 'name'),
	array('key' => 'area', 'value' => 'coords,shape,alt,href,hreflang,rel,target,download'),
	array('key' => 'video', 'value' => 'src,crossorigin,width,height,autoplay,controls,controlslist,loop,muted,poster,preload,playsinline'),
	array('key' => 'audio', 'value' => 'src,crossorigin,autoplay,controls,loop,muted,preload'),
	array('key' => 'source', 'value' => 'src,srcset,media,sizes,type'),
	array('key' => 'ol', 'value' => 'reversed,start,type'),
	array('key' => 'li', 'value' => 'value'),
	array('key' => 'blockquote', 'value' => 'cite'),
	array('key' => 'data', 'value' => 'value'),
	array('key' => 'ins', 'value' => 'cite,datetime'),
	array('key' => 'del', 'value' => 'cite,datetime'),
	array('key' => 'table', 'value' => 'border,cellspacing,cellpadding,width,height'),
	array('key' => 'col', 'value' => 'span'),
	array('key' => 'colgroup', 'value' => 'span'),
	array('key' => 'td', 'value' => 'rowspan,colspan,headers,width,height'),
	array('key' => 'th', 'value' => 'rowspan,colspan,headers,abbr,scope,width,height'),
	array('key' => 'style', 'value' => 'media'),
	array('key' => 'time', 'value' => 'datetime'),
	array('key' => 'details', 'value' => 'open,name'),
));

// File uploads
$GLOBALS['TL_CONFIG']['uploadTypes']
	= 'jpg,jpeg,gif,png,ico,svg,svgz,webp,avif,heic,jxl,'
	. 'odt,ods,odp,odg,ott,ots,otp,otg,pdf,csv,'
	. 'doc,docx,dot,dotx,xls,xlsx,xlt,xltx,ppt,pptx,pot,potx,vtt,'
	. 'mp3,mp4,m4a,m4v,webm,ogg,ogv,wma,wmv,ram,rm,mov,fla,flv,swf,'
	. 'ttf,ttc,otf,eot,woff,woff2,'
	. 'css,scss,less,txt,zip,rar,7z,cto,md,ics';
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
$GLOBALS['TL_CONFIG']['maxResultsPerPage']    = 300;
$GLOBALS['TL_CONFIG']['maxImageWidth']        = 0;
$GLOBALS['TL_CONFIG']['defaultUser']          = 0;
$GLOBALS['TL_CONFIG']['defaultGroup']         = 0;
$GLOBALS['TL_CONFIG']['defaultChmod']         = array('u1', 'u2', 'u3', 'u4', 'u5', 'u6', 'g4', 'g5', 'g6');
$GLOBALS['TL_CONFIG']['allowedDownload']
	= 'jpg,jpeg,gif,png,svg,svgz,webp,avif,heic,jxl,'
	. 'odt,ods,odp,odg,ott,ots,otp,otg,pdf,'
	. 'doc,docx,dot,dotx,xls,xlsx,xlt,xltx,ppt,pptx,pot,potx,'
	. 'mp3,mp4,m4a,m4v,webm,ogg,ogv,wma,wmv,ram,rm,mov,'
	. 'zip,rar,7z,md,ics';
$GLOBALS['TL_CONFIG']['installPassword']      = '';
$GLOBALS['TL_CONFIG']['minPasswordLength']    = 8;
$GLOBALS['TL_CONFIG']['defaultFileChmod']     = 0644;
$GLOBALS['TL_CONFIG']['defaultFolderChmod']   = 0755;
$GLOBALS['TL_CONFIG']['maxPaginationLinks']   = 7;
