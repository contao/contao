<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

$projectDir ??= __DIR__;
$console = in_array(basename($_SERVER['SCRIPT_FILENAME']), ['console', 'contao-console'], true);

// Suppress error messages (see #1422)
@ini_set('display_errors', '0');

// Disable the phar stream wrapper for security reasons (see #105)
if (!$console && in_array('phar', stream_get_wrappers(), true)) {
    stream_wrapper_unregister('phar');
}

// System maintenance mode comes first as it has to work even if the vendor
// directory does not exist
if ($console && 'contao:cron' === ($_SERVER['argv'][1] ?? null) && is_file($projectDir.'/var/maintenance.html')) {
    exit;
}

if (!$console && is_file($projectDir.'/var/maintenance.html')) {
    $contents = (string) file_get_contents($projectDir.'/var/maintenance.html');

    http_response_code(503);
    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Length: '.strlen($contents));
    header('Cache-Control: no-store');

    echo $contents;

    exit;
}

if ($console) {
    set_time_limit(0);
    @ini_set('zlib.output_compression', '0');
}

// Symfony's Composer plugin compiles Contao's defaults and project runtime options.
require_once $projectDir.'/vendor/autoload_runtime.php';
