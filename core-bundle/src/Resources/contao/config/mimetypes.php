<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

// Mime types
$GLOBALS['TL_MIME'] =
[
	// Application files
	'xl'    => ['application/excel', 'iconOFFICE.gif'],
	'xls'   => ['application/excel', 'iconOFFICE.gif'],
	'hqx'   => ['application/mac-binhex40', 'iconPLAIN.gif'],
	'cpt'   => ['application/mac-compactpro', 'iconPLAIN.gif'],
	'bin'   => ['application/macbinary', 'iconPLAIN.gif'],
	'doc'   => ['application/msword', 'iconOFFICE.gif'],
	'word'  => ['application/msword', 'iconOFFICE.gif'],
	'cto'   => ['application/octet-stream', 'iconCTO.gif'],
	'dms'   => ['application/octet-stream', 'iconPLAIN.gif'],
	'lha'   => ['application/octet-stream', 'iconPLAIN.gif'],
	'lzh'   => ['application/octet-stream', 'iconPLAIN.gif'],
	'exe'   => ['application/octet-stream', 'iconPLAIN.gif'],
	'class' => ['application/octet-stream', 'iconPLAIN.gif'],
	'so'    => ['application/octet-stream', 'iconPLAIN.gif'],
	'sea'   => ['application/octet-stream', 'iconPLAIN.gif'],
	'dll'   => ['application/octet-stream', 'iconPLAIN.gif'],
	'oda'   => ['application/oda', 'iconPLAIN.gif'],
	'pdf'   => ['application/pdf', 'iconPDF.gif'],
	'ai'    => ['application/postscript', 'iconPLAIN.gif'],
	'eps'   => ['application/postscript', 'iconPLAIN.gif'],
	'ps'    => ['application/postscript', 'iconPLAIN.gif'],
	'pps'   => ['application/powerpoint', 'iconOFFICE.gif'],
	'ppt'   => ['application/powerpoint', 'iconOFFICE.gif'],
	'smi'   => ['application/smil', 'iconPLAIN.gif'],
	'smil'  => ['application/smil', 'iconPLAIN.gif'],
	'mif'   => ['application/vnd.mif', 'iconPLAIN.gif'],
	'odc'   => ['application/vnd.oasis.opendocument.chart', 'iconOFFICE.gif'],
	'odf'   => ['application/vnd.oasis.opendocument.formula', 'iconOFFICE.gif'],
	'odg'   => ['application/vnd.oasis.opendocument.graphics', 'iconOFFICE.gif'],
	'odi'   => ['application/vnd.oasis.opendocument.image', 'iconOFFICE.gif'],
	'odp'   => ['application/vnd.oasis.opendocument.presentation', 'iconOFFICE.gif'],
	'ods'   => ['application/vnd.oasis.opendocument.spreadsheet', 'iconOFFICE.gif'],
	'odt'   => ['application/vnd.oasis.opendocument.text', 'iconOFFICE.gif'],
	'wbxml' => ['application/wbxml', 'iconPLAIN.gif'],
	'wmlc'  => ['application/wmlc', 'iconPLAIN.gif'],
	'dmg'   => ['application/x-apple-diskimage', 'iconRAR.gif'],
	'dcr'   => ['application/x-director', 'iconPLAIN.gif'],
	'dir'   => ['application/x-director', 'iconPLAIN.gif'],
	'dxr'   => ['application/x-director', 'iconPLAIN.gif'],
	'dvi'   => ['application/x-dvi', 'iconPLAIN.gif'],
	'gtar'  => ['application/x-gtar', 'iconRAR.gif'],
	'inc'   => ['application/x-httpd-php', 'iconPHP.gif'],
	'php'   => ['application/x-httpd-php', 'iconPHP.gif'],
	'php3'  => ['application/x-httpd-php', 'iconPHP.gif'],
	'php4'  => ['application/x-httpd-php', 'iconPHP.gif'],
	'php5'  => ['application/x-httpd-php', 'iconPHP.gif'],
	'phtml' => ['application/x-httpd-php', 'iconPHP.gif'],
	'phps'  => ['application/x-httpd-php-source', 'iconPHP.gif'],
	'js'    => ['application/x-javascript', 'iconJS.gif'],
	'psd'   => ['application/x-photoshop', 'iconPLAIN.gif'],
	'rar'   => ['application/x-rar', 'iconRAR.gif'],
	'fla'   => ['application/x-shockwave-flash', 'iconSWF.gif'],
	'swf'   => ['application/x-shockwave-flash', 'iconSWF.gif'],
	'sit'   => ['application/x-stuffit', 'iconRAR.gif'],
	'tar'   => ['application/x-tar', 'iconRAR.gif'],
	'tgz'   => ['application/x-tar', 'iconRAR.gif'],
	'xhtml' => ['application/xhtml+xml', 'iconPLAIN.gif'],
	'xht'   => ['application/xhtml+xml', 'iconPLAIN.gif'],
	'zip'   => ['application/zip', 'iconRAR.gif'],

	// Audio files
	'm4a'   => ['audio/x-m4a', 'iconAUDIO.gif'],
	'mp3'   => ['audio/mp3', 'iconAUDIO.gif'],
	'wma'   => ['audio/wma', 'iconAUDIO.gif'],
	'mpeg'  => ['audio/mpeg', 'iconAUDIO.gif'],
	'wav'   => ['audio/wav', 'iconAUDIO.gif'],
	'ogg'   => ['audio/ogg','iconAUDIO.gif'],
	'mid'   => ['audio/midi', 'iconAUDIO.gif'],
	'midi'  => ['audio/midi', 'iconAUDIO.gif'],
	'aif'   => ['audio/x-aiff', 'iconAUDIO.gif'],
	'aiff'  => ['audio/x-aiff', 'iconAUDIO.gif'],
	'aifc'  => ['audio/x-aiff', 'iconAUDIO.gif'],
	'ram'   => ['audio/x-pn-realaudio', 'iconAUDIO.gif'],
	'rm'    => ['audio/x-pn-realaudio', 'iconAUDIO.gif'],
	'rpm'   => ['audio/x-pn-realaudio-plugin', 'iconAUDIO.gif'],
	'ra'    => ['audio/x-realaudio', 'iconAUDIO.gif'],

	// Images
	'bmp'   => ['image/bmp', 'iconBMP.gif'],
	'gif'   => ['image/gif', 'iconGIF.gif'],
	'jpeg'  => ['image/jpeg', 'iconJPG.gif'],
	'jpg'   => ['image/jpeg', 'iconJPG.gif'],
	'jpe'   => ['image/jpeg', 'iconJPG.gif'],
	'png'   => ['image/png', 'iconTIF.gif'],
	'tiff'  => ['image/tiff', 'iconTIF.gif'],
	'tif'   => ['image/tiff', 'iconTIF.gif'],
	'svg'   => ['image/svg+xml', 'iconPLAIN.gif'],
	'svgz'  => ['image/svg+xml', 'iconPLAIN.gif'],

	// Mailbox files
	'eml'   => ['message/rfc822', 'iconPLAIN.gif'],

	// Text files
	'asp'   => ['text/asp', 'iconPLAIN.gif'],
	'css'   => ['text/css', 'iconCSS.gif'],
	'scss'  => ['text/x-scss', 'iconCSS.gif'],
	'less'  => ['text/x-less', 'iconCSS.gif'],
	'html'  => ['text/html', 'iconHTML.gif'],
	'htm'   => ['text/html', 'iconHTML.gif'],
	'shtml' => ['text/html', 'iconHTML.gif'],
	'txt'   => ['text/plain', 'iconPLAIN.gif'],
	'text'  => ['text/plain', 'iconPLAIN.gif'],
	'log'   => ['text/plain', 'iconPLAIN.gif'],
	'rtx'   => ['text/richtext', 'iconPLAIN.gif'],
	'rtf'   => ['text/rtf', 'iconPLAIN.gif'],
	'xml'   => ['text/xml', 'iconPLAIN.gif'],
	'xsl'   => ['text/xml', 'iconPLAIN.gif'],

	// Videos
	'mp4'   => ['video/mp4', 'iconVIDEO.gif'],
	'm4v'   => ['video/x-m4v', 'iconVIDEO.gif'],
	'mov'   => ['video/mov', 'iconVIDEO.gif'],
	'wmv'   => ['video/wmv', 'iconVIDEO.gif'],
	'webm'  => ['video/webm', 'iconVIDEO.gif'],
	'qt'    => ['video/quicktime', 'iconVIDEO.gif'],
	'rv'    => ['video/vnd.rn-realvideo', 'iconVIDEO.gif'],
	'avi'   => ['video/x-msvideo', 'iconVIDEO.gif'],
	'ogv'   => ['video/ogg', 'iconVIDEO.gif'],
	'movie' => ['video/x-sgi-movie', 'iconVIDEO.gif']
];
