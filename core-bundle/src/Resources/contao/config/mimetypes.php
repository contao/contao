<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

// Mime types
$GLOBALS['TL_MIME'] =
[
	// Application files
	'xl'    => ['application/excel', 'iconOFFICE.gif'],
	'xls'   => ['application/excel', 'iconOFFICE.gif'],
	'hqx'   => ['application/mac-binhex40', 'iconPLAIN.svg'],
	'cpt'   => ['application/mac-compactpro', 'iconPLAIN.svg'],
	'bin'   => ['application/macbinary', 'iconPLAIN.svg'],
	'doc'   => ['application/msword', 'iconOFFICE.gif'],
	'word'  => ['application/msword', 'iconOFFICE.gif'],
	'cto'   => ['application/octet-stream', 'iconCTO.gif'],
	'dms'   => ['application/octet-stream', 'iconPLAIN.svg'],
	'lha'   => ['application/octet-stream', 'iconPLAIN.svg'],
	'lzh'   => ['application/octet-stream', 'iconPLAIN.svg'],
	'exe'   => ['application/octet-stream', 'iconPLAIN.svg'],
	'class' => ['application/octet-stream', 'iconPLAIN.svg'],
	'so'    => ['application/octet-stream', 'iconPLAIN.svg'],
	'sea'   => ['application/octet-stream', 'iconPLAIN.svg'],
	'dll'   => ['application/octet-stream', 'iconPLAIN.svg'],
	'oda'   => ['application/oda', 'iconPLAIN.svg'],
	'pdf'   => ['application/pdf', 'iconPDF.gif'],
	'ai'    => ['application/postscript', 'iconPLAIN.svg'],
	'eps'   => ['application/postscript', 'iconPLAIN.svg'],
	'ps'    => ['application/postscript', 'iconPLAIN.svg'],
	'pps'   => ['application/powerpoint', 'iconOFFICE.gif'],
	'ppt'   => ['application/powerpoint', 'iconOFFICE.gif'],
	'smi'   => ['application/smil', 'iconPLAIN.svg'],
	'smil'  => ['application/smil', 'iconPLAIN.svg'],
	'mif'   => ['application/vnd.mif', 'iconPLAIN.svg'],
	'odc'   => ['application/vnd.oasis.opendocument.chart', 'iconOFFICE.gif'],
	'odf'   => ['application/vnd.oasis.opendocument.formula', 'iconOFFICE.gif'],
	'odg'   => ['application/vnd.oasis.opendocument.graphics', 'iconOFFICE.gif'],
	'odi'   => ['application/vnd.oasis.opendocument.image', 'iconOFFICE.gif'],
	'odp'   => ['application/vnd.oasis.opendocument.presentation', 'iconOFFICE.gif'],
	'ods'   => ['application/vnd.oasis.opendocument.spreadsheet', 'iconOFFICE.gif'],
	'odt'   => ['application/vnd.oasis.opendocument.text', 'iconOFFICE.gif'],
	'wbxml' => ['application/wbxml', 'iconPLAIN.svg'],
	'wmlc'  => ['application/wmlc', 'iconPLAIN.svg'],
	'dmg'   => ['application/x-apple-diskimage', 'iconRAR.gif'],
	'dcr'   => ['application/x-director', 'iconPLAIN.svg'],
	'dir'   => ['application/x-director', 'iconPLAIN.svg'],
	'dxr'   => ['application/x-director', 'iconPLAIN.svg'],
	'dvi'   => ['application/x-dvi', 'iconPLAIN.svg'],
	'gtar'  => ['application/x-gtar', 'iconRAR.gif'],
	'inc'   => ['application/x-httpd-php', 'iconPHP.svg'],
	'php'   => ['application/x-httpd-php', 'iconPHP.svg'],
	'php3'  => ['application/x-httpd-php', 'iconPHP.svg'],
	'php4'  => ['application/x-httpd-php', 'iconPHP.svg'],
	'php5'  => ['application/x-httpd-php', 'iconPHP.svg'],
	'phtml' => ['application/x-httpd-php', 'iconPHP.svg'],
	'phps'  => ['application/x-httpd-php-source', 'iconPHP.svg'],
	'js'    => ['application/x-javascript', 'iconJS.gif'],
	'psd'   => ['application/x-photoshop', 'iconPLAIN.svg'],
	'rar'   => ['application/x-rar', 'iconRAR.gif'],
	'fla'   => ['application/x-shockwave-flash', 'iconSWF.svg'],
	'swf'   => ['application/x-shockwave-flash', 'iconSWF.svg'],
	'sit'   => ['application/x-stuffit', 'iconRAR.gif'],
	'tar'   => ['application/x-tar', 'iconRAR.gif'],
	'tgz'   => ['application/x-tar', 'iconRAR.gif'],
	'xhtml' => ['application/xhtml+xml', 'iconPLAIN.svg'],
	'xht'   => ['application/xhtml+xml', 'iconPLAIN.svg'],
	'zip'   => ['application/zip', 'iconRAR.gif'],

	// Audio files
	'm4a'   => ['audio/x-m4a', 'iconAUDIO.svg'],
	'mp3'   => ['audio/mp3', 'iconAUDIO.svg'],
	'wma'   => ['audio/wma', 'iconAUDIO.svg'],
	'mpeg'  => ['audio/mpeg', 'iconAUDIO.svg'],
	'wav'   => ['audio/wav', 'iconAUDIO.svg'],
	'ogg'   => ['audio/ogg','iconAUDIO.svg'],
	'mid'   => ['audio/midi', 'iconAUDIO.svg'],
	'midi'  => ['audio/midi', 'iconAUDIO.svg'],
	'aif'   => ['audio/x-aiff', 'iconAUDIO.svg'],
	'aiff'  => ['audio/x-aiff', 'iconAUDIO.svg'],
	'aifc'  => ['audio/x-aiff', 'iconAUDIO.svg'],
	'ram'   => ['audio/x-pn-realaudio', 'iconAUDIO.svg'],
	'rm'    => ['audio/x-pn-realaudio', 'iconAUDIO.svg'],
	'rpm'   => ['audio/x-pn-realaudio-plugin', 'iconAUDIO.svg'],
	'ra'    => ['audio/x-realaudio', 'iconAUDIO.svg'],

	// Images
	'bmp'   => ['image/bmp', 'iconBMP.svg'],
	'gif'   => ['image/gif', 'iconGIF.svg'],
	'jpeg'  => ['image/jpeg', 'iconJPG.gif'],
	'jpg'   => ['image/jpeg', 'iconJPG.gif'],
	'jpe'   => ['image/jpeg', 'iconJPG.gif'],
	'png'   => ['image/png', 'iconTIF.gif'],
	'tiff'  => ['image/tiff', 'iconTIF.gif'],
	'tif'   => ['image/tiff', 'iconTIF.gif'],
	'svg'   => ['image/svg+xml', 'iconPLAIN.svg'],
	'svgz'  => ['image/svg+xml', 'iconPLAIN.svg'],

	// Mailbox files
	'eml'   => ['message/rfc822', 'iconPLAIN.svg'],

	// Text files
	'asp'   => ['text/asp', 'iconPLAIN.svg'],
	'css'   => ['text/css', 'iconCSS.svg'],
	'scss'  => ['text/x-scss', 'iconCSS.svg'],
	'less'  => ['text/x-less', 'iconCSS.svg'],
	'html'  => ['text/html', 'iconHTML.svg'],
	'htm'   => ['text/html', 'iconHTML.svg'],
	'shtml' => ['text/html', 'iconHTML.svg'],
	'txt'   => ['text/plain', 'iconPLAIN.svg'],
	'text'  => ['text/plain', 'iconPLAIN.svg'],
	'log'   => ['text/plain', 'iconPLAIN.svg'],
	'rtx'   => ['text/richtext', 'iconPLAIN.svg'],
	'rtf'   => ['text/rtf', 'iconPLAIN.svg'],
	'xml'   => ['text/xml', 'iconPLAIN.svg'],
	'xsl'   => ['text/xml', 'iconPLAIN.svg'],

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
