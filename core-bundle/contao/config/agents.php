<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Core
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

// Operating systems (check Windows CE before Windows and Android before Linux!)
$GLOBALS['TL_CONFIG']['os'] =
[
	'Macintosh'     => ['os'=>'mac',        'mobile'=>false],
	'Windows CE'    => ['os'=>'win-ce',     'mobile'=>true],
	'Windows Phone' => ['os'=>'win-ce',     'mobile'=>true],
	'Windows'       => ['os'=>'win',        'mobile'=>false],
	'iPad'          => ['os'=>'ios',        'mobile'=>false],
	'iPhone'        => ['os'=>'ios',        'mobile'=>true],
	'iPod'          => ['os'=>'ios',        'mobile'=>true],
	'Android'       => ['os'=>'android',    'mobile'=>true],
	'BB10'          => ['os'=>'blackberry', 'mobile'=>true],
	'Blackberry'    => ['os'=>'blackberry', 'mobile'=>true],
	'Symbian'       => ['os'=>'symbian',    'mobile'=>true],
	'WebOS'         => ['os'=>'webos',      'mobile'=>true],
	'Linux'         => ['os'=>'unix',       'mobile'=>false],
	'FreeBSD'       => ['os'=>'unix',       'mobile'=>false],
	'OpenBSD'       => ['os'=>'unix',       'mobile'=>false],
	'NetBSD'        => ['os'=>'unix',       'mobile'=>false],
];

// Browsers (check OmniWeb and Silk before Safari and Opera Mini/Mobi before Opera!)
$GLOBALS['TL_CONFIG']['browser'] =
[
	'MSIE'       => ['browser'=>'ie',           'shorty'=>'ie', 'engine'=>'trident', 'version'=>'/^.*MSIE (\d+(\.\d+)*).*$/'],
	'Trident'    => ['browser'=>'ie',           'shorty'=>'ie', 'engine'=>'trident', 'version'=>'/^.*Trident\/\d+\.\d+; rv:(\d+(\.\d+)*).*$/'],
	'Firefox'    => ['browser'=>'firefox',      'shorty'=>'fx', 'engine'=>'gecko',   'version'=>'/^.*Firefox\/(\d+(\.\d+)*).*$/'],
	'Chrome'     => ['browser'=>'chrome',       'shorty'=>'ch', 'engine'=>'webkit',  'version'=>'/^.*Chrome\/(\d+(\.\d+)*).*$/'],
	'OmniWeb'    => ['browser'=>'omniweb',      'shorty'=>'ow', 'engine'=>'webkit',  'version'=>'/^.*Version\/(\d+(\.\d+)*).*$/'],
	'Silk'       => ['browser'=>'silk',         'shorty'=>'si', 'engine'=>'silk',    'version'=>'/^.*Silk\/(\d+(\.\d+)*).*$/'],
	'Safari'     => ['browser'=>'safari',       'shorty'=>'sf', 'engine'=>'webkit',  'version'=>'/^.*Version\/(\d+(\.\d+)*).*$/'],
	'Opera Mini' => ['browser'=>'opera-mini',   'shorty'=>'oi', 'engine'=>'presto',  'version'=>'/^.*Opera Mini\/(\d+(\.\d+)*).*$/'],
	'Opera Mobi' => ['browser'=>'opera-mobile', 'shorty'=>'om', 'engine'=>'presto',  'version'=>'/^.*Version\/(\d+(\.\d+)*).*$/'],
	'Opera'      => ['browser'=>'opera',        'shorty'=>'op', 'engine'=>'presto',  'version'=>'/^.*Version\/(\d+(\.\d+)*).*$/'],
	'IEMobile'   => ['browser'=>'ie-mobile',    'shorty'=>'im', 'engine'=>'trident', 'version'=>'/^.*IEMobile (\d+(\.\d+)*).*$/'],
	'Camino'     => ['browser'=>'camino',       'shorty'=>'ca', 'engine'=>'gecko',   'version'=>'/^.*Camino\/(\d+(\.\d+)*).*$/'],
	'Konqueror'  => ['browser'=>'konqueror',    'shorty'=>'ko', 'engine'=>'webkit',  'version'=>'/^.*Konqueror\/(\d+(\.\d+)*).*$/']
];
