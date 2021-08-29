<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Loader;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Loader\FailTolerantFilesystemLoader;

class FailTolerantFilesystemLoaderTest extends TestCase
{
    public function testToleratesInvalidPaths(): void
    {
        $loader = new FailTolerantFilesystemLoader([], '/project/dir');

        $loader->addPath('non/existing/path');
        $loader->prependPath('non/existing/path');

        $this->assertEmpty($loader->getPaths());
    }
}
