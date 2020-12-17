<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

set_time_limit(0);
@ini_set('zlib.output_compression', '0');

if (file_exists(__DIR__.'/../autoload.php')) {
    $projectDir = dirname(__DIR__, 2);
} elseif (file_exists(__DIR__.'/../../../../autoload.php')) {
    $projectDir = dirname(__DIR__, 5);
} elseif (false !== ($cwd = getcwd()) && file_exists($cwd.'/vendor/autoload.php')) {
    $projectDir = $cwd;
} else {
    $projectDir = dirname(__DIR__, 4);
}

require $projectDir.'/vendor/autoload.php';

return $projectDir;
