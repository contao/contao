<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Asset\VersionStrategy;

use Contao\CoreBundle\Asset\VersionStrategy\MtimeVersionStrategy;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class MtimeVersionStrategyTest extends TestCase
{
    private const MTIME = '2026-01-01 00:00:00';

    public static function setUpBeforeClass(): void
    {
        // Set a predictable mtime on the tested files
        $affectedFiles = [
            Path::join(__DIR__, '../../Fixtures/files/data/data.csv'),
            Path::join(__DIR__, '../../Fixtures/public/images/dummy_public.jpg'),
        ];

        $fs = new Filesystem();

        foreach ($affectedFiles as $file) {
            $fs->touch($file, strtotime(self::MTIME));
        }
    }

    #[DataProvider('getPaths')]
    public function testGetVersion(string $path, string $expectedVersion): void
    {
        $projectDir = $this->getFixturesDir();
        $webDir = Path::join($projectDir, 'public');
        $strategy = new MtimeVersionStrategy($projectDir, $webDir);

        $this->assertSame($expectedVersion, $strategy->getVersion($path));
        $this->assertSame($expectedVersion ? \sprintf('%s?v=%s', $path, $expectedVersion) : $path, $strategy->applyVersion($path));
    }

    public static function getPaths(): iterable
    {
        $expectedVersion = (string) strtotime(self::MTIME);

        return [
            ['files/data/data.csv', $expectedVersion],
            ['images/dummy_public.jpg', $expectedVersion],
            [Path::join(__DIR__, '../../Fixtures/public/images/dummy_public.jpg'), $expectedVersion],
            ['does_not_exist', ''],
        ];
    }
}
