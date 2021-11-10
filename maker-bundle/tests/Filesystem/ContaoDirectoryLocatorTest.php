<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\MakerBundle\Tests\Filesystem;

use Contao\MakerBundle\Filesystem\ContaoDirectoryLocator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class ContaoDirectoryLocatorTest extends TestCase
{
    public function testReturnsTheConfigDirectory(): void
    {
        $projectDir = '/foo/bar';

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem
            ->expects($this->once())
            ->method('mkdir')
            ->with(Path::join($projectDir, 'contao'))
        ;

        $directoryLocator = new ContaoDirectoryLocator($filesystem, $projectDir);

        $this->assertSame('/foo/bar/contao', $directoryLocator->getConfigDirectory());
    }
}
