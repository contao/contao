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
use Webmozart\PathUtil\Path;

class ParameterDumperTest extends TestCase
{
    /**
     * @dataProvider provideConfigs
     */
    public function testUsesCorrectConfigFile(bool $hasConfig, bool $hasLegacyConfig, string $expectedConfigFile): void
    {
        $fixtureDir = Path::canonicalize(__DIR__.'/../Fixtures');

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem
            ->method('exists')
            ->willReturnMap([
                [Path::join($fixtureDir, 'config/parameters.yml'), $hasConfig],
                [Path::join($fixtureDir, 'app/config/parameters.yml'), $hasLegacyConfig],
            ])
        ;

        $filesystem
            ->expects($this->once())
            ->method('dumpFile')
            ->with(Path::join($fixtureDir, $expectedConfigFile))
        ;

        $dumper = new ParameterDumper($fixtureDir, $filesystem);
        $dumper->dump();
    }

    public function provideConfigs(): \Generator
    {
        yield 'just new' => [true, false, 'config/parameters.yml'];
        yield 'both new and old' => [true, true, 'config/parameters.yml'];
        yield 'just old' => [false, true, 'app/config/parameters.yml'];
        yield 'neither new nor old' => [false, false, 'config/parameters.yml'];
    }
}
