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
use Contao\CoreBundle\Twig\Loader\FilesystemLoader;
use Twig\Error\LoaderError;

class FilesystemLoaderTest extends TestCase
{
    public function testIgnoresInvalidPaths(): void
    {
        $loader = new FilesystemLoader([], '/project/dir');

        // Should be ignored
        $loader->addPath('non/existing/path', 'Namespace');

        $this->expectException(LoaderError::class);

        $loader->addPath('non/existing/path', 'Namespace', false);
    }

    // todo
}
