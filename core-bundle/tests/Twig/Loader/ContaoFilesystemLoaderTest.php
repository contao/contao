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
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Loader\TemplateLocator;
use Symfony\Component\Cache\Adapter\NullAdapter;

class ContaoFilesystemLoaderTest extends TestCase
{
    public function testIgnoresInvalidPaths(): void
    {
        $loader = new ContaoFilesystemLoader(
            new NullAdapter(),
            $this->createMock(TemplateLocator::class),
            '/project/dir'
        );

        // Should be ignored
        $loader->addPath('non/existing/path', 'Namespace');
        $loader->prependPath('non/existing/path', 'Namespace');

        $this->assertFalse($loader->exists('non/existing/path'));
    }

    // todo
}
