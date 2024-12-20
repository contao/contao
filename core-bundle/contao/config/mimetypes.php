<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_MIME'] = array
(
	// Application files
	'xls'   => array('application/excel', 'spreadsheet.svg'),
	'xlsx'  => array('application/excel', 'spreadsheet.svg'),
	'hqx'   => array('application/mac-binhex40', 'archive.svg'),
	'cpt'   => array('application/mac-compactpro', 'image.svg'),
	'bin'   => array('application/macbinary', 'binary.svg'),
	'doc'   => array('application/msword', 'document.svg'),
	'docx'  => array('application/msword', 'document.svg'),
	'cto'   => array('application/octet-stream', 'archive.svg'),
	'exe'   => array('application/octet-stream', 'terminal.svg'),
	'sea'   => array('application/octet-stream', 'archive.svg'),
	'pdf'   => array('application/pdf', 'document.svg'),
	'ai'    => array('application/postscript', 'image.svg'),
	'eps'   => array('application/postscript', 'image.svg'),
	'ppt'   => array('application/powerpoint', 'chart.svg'),
	'pptx'  => array('application/powerpoint', 'chart.svg'),
	'smi'   => array('application/smil', 'code.svg'),
	'smil'  => array('application/smil', 'code.svg'),
	'odc'   => array('application/vnd.oasis.opendocument.chart', 'chart.svg'),
	'odf'   => array('application/vnd.oasis.opendocument.formula', 'archive.svg'),
	'odg'   => array('application/vnd.oasis.opendocument.graphics', 'image.svg'),
	'odi'   => array('application/vnd.oasis.opendocument.image', 'image.svg'),
	'odp'   => array('application/vnd.oasis.opendocument.presentation', 'chart.svg'),
	'ods'   => array('application/vnd.oasis.opendocument.spreadsheet', 'spreadsheet.svg'),
	'odt'   => array('application/vnd.oasis.opendocument.text', 'document.svg'),
	'dmg'   => array('application/x-apple-diskimage', 'archive.svg'),
	'dcr'   => array('application/x-director', 'image.svg'),
	'dir'   => array('application/x-director', 'video.svg'),
	'gtar'  => array('application/x-gtar', 'archive.svg'),
	'inc'   => array('application/x-httpd-php', 'code.svg'),
	'php'   => array('application/x-httpd-php', 'code.svg'),
	'phtml' => array('application/x-httpd-php', 'code.svg'),
	'js'    => array('application/x-javascript', 'code.svg'),
	'json'  => array('application/json', 'json.svg'),
	'ts'    => array('application/typescript', 'code.svg'),
	'psd'   => array('application/x-photoshop', 'image.svg'),
	'rar'   => array('application/x-rar', 'archive.svg'),
	'sit'   => array('application/x-stuffit', 'archive.svg'),
	'tar'   => array('application/x-tar', 'archive.svg'),
	'tgz'   => array('application/x-tar', 'archive.svg'),
	'xhtml' => array('application/xhtml+xml', 'archive.svg'),
	'yml'   => array('application/yaml', 'code.svg'),
	'yaml'  => array('application/yaml', 'code.svg'),
	'zip'   => array('application/zip', 'archive.svg'),

	// Audio files
	'm4a'   => array('audio/x-m4a', 'audio.svg'),
	'mp3'   => array('audio/mpeg', 'audio.svg'),
	'wma'   => array('audio/wma', 'audio.svg'),
	'mpeg'  => array('audio/mpeg', 'audio.svg'),
	'wav'   => array('audio/wav', 'audio.svg'),
	'ogg'   => array('audio/ogg', 'audio.svg'),
	'mid'   => array('audio/midi', 'audio.svg'),
	'midi'  => array('audio/midi', 'audio.svg'),
	'aif'   => array('audio/x-aiff', 'audio.svg'),
	'aiff'  => array('audio/x-aiff', 'audio.svg'),
	'aifc'  => array('audio/x-aiff', 'audio.svg'),
	'ram'   => array('audio/x-pn-realaudio', 'audio.svg'),
	'rm'    => array('audio/x-pn-realaudio', 'audio.svg'),
	'rpm'   => array('audio/x-pn-realaudio-plugin', 'audio.svg'),
	'ra'    => array('audio/x-realaudio', 'audio.svg'),

	// Images
	'bmp'   => array('image/bmp', 'image.svg'),
	'gif'   => array('image/gif', 'image.svg'),
	'jpeg'  => array('image/jpeg', 'image.svg'),
	'jpg'   => array('image/jpeg', 'image.svg'),
	'jpe'   => array('image/jpeg', 'image.svg'),
	'png'   => array('image/png', 'image.svg'),
	'tiff'  => array('image/tiff', 'image.svg'),
	'tif'   => array('image/tiff', 'image.svg'),
	'svg'   => array('image/svg+xml', 'image.svg'),
	'svgz'  => array('image/svg+xml', 'image.svg'),
	'webp'  => array('image/webp', 'image.svg'),
	'avif'  => array('image/avif', 'image.svg'),
	'heic'  => array('image/heic', 'image.svg'),
	'jxl'  => array('image/jxl', 'image.svg'),

	// Text files
	'asp'   => array('text/asp', 'code.svg'),
	'css'   => array('text/css', 'code.svg'),
	'scss'  => array('text/x-scss', 'code.svg'),
	'less'  => array('text/x-less', 'code.svg'),
	'html'  => array('text/html', 'code.svg'),
	'htm'   => array('text/html', 'code.svg'),
	'md'    => array('text/markdown', 'code.svg'),
	'shtml' => array('text/html', 'code.svg'),
	'txt'   => array('text/plain', 'code.svg'),
	'text'  => array('text/plain', 'code.svg'),
	'log'   => array('text/plain', 'code.svg'),
	'rtx'   => array('text/richtext', 'code.svg'),
	'rtf'   => array('text/rtf', 'code.svg'),
	'xml'   => array('text/xml', 'code.svg'),
	'xsl'   => array('text/xml', 'code.svg'),
	'csv'   => array('text/csv', 'code.svg'),

	// Videos
	'mp4'   => array('video/mp4', 'video.svg'),
	'm4v'   => array('video/x-m4v', 'video.svg'),
	'mov'   => array('video/quicktime', 'video.svg'),
	'wmv'   => array('video/wmv', 'video.svg'),
	'webm'  => array('video/webm', 'video.svg'),
	'qt'    => array('video/quicktime', 'video.svg'),
	'rv'    => array('video/vnd.rn-realvideo', 'video.svg'),
	'avi'   => array('video/x-msvideo', 'video.svg'),
	'ogv'   => array('video/ogg', 'video.svg'),
	'movie' => array('video/x-sgi-movie', 'video.svg'),

	// Fonts
	'woff' => array('font/woff', 'font.svg'),
	'woff2' => array('font/woff2', 'font.svg'),
);
