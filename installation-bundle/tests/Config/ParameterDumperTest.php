<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\InstallationBundle\Tests\Config;

use Contao\InstallationBundle\Config\ParameterDumper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class ParameterDumperTest extends TestCase
{
    /**
     * @dataProvider provideConfigs
     */
    public function testUsesCorrectConfigFile(bool $hasConfig): void
    {
        $fixtureDir = Path::canonicalize(__DIR__.'/../Fixtures');

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem
            ->method('exists')
            ->willReturn([Path::join($fixtureDir, 'config/parameters.yaml'), $hasConfig])
        ;

        $filesystem
            ->expects($this->once())
            ->method('dumpFile')
            ->with(Path::join($fixtureDir, 'config/parameters.yaml'))
        ;

        $dumper = new ParameterDumper($fixtureDir, $filesystem);
        $dumper->dump();
    }

    public function provideConfigs(): \Generator
    {
        yield 'with config' => [true];
        yield 'without config' => [false];
    }
}
