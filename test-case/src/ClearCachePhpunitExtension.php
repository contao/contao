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

use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\BeforeFirstTestHook;
use Symfony\Component\Filesystem\Filesystem;

class ClearCachePhpunitExtension implements BeforeFirstTestHook, AfterLastTestHook
{
    public function executeBeforeFirstTest(): void
    {
        (new Filesystem())->remove([
            __DIR__.'/../var/cache',
            __DIR__.'/../../var/cache',
            __DIR__.'/../../core-bundle/var/cache',
        ]);
    }

    public function executeAfterLastTest(): void
    {
        $this->executeBeforeFirstTest();
    }
}
