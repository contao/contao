<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\TestCase;

use PHPUnit\Runner\BeforeFirstTestHook;

class WarnXdebugPhpunitExtension implements BeforeFirstTestHook
{
    public function executeBeforeFirstTest(): void
    {
        if (\is_callable('xdebug_info') && [] !== xdebug_info('mode') && ['off'] !== xdebug_info('mode')) {
            fwrite(STDERR, "XDebug is enabled, consider disabling it to speed up the unit tests.\n\n");
        }
    }
}
