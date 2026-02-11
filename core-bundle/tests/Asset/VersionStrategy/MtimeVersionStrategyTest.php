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
        // Set a predictable mtime on the tested file
        (new Filesystem())->touch(Path::join(__DIR__, '../../Fixtures/public/images/dummy_public.jpg'), strtotime(self::MTIME));
    }

    #[DataProvider('getPaths')]
    public function testGetVersion(string $path, string $expectedVersion): void
    {
        $strategy = new MtimeVersionStrategy(Path::join($this->getFixturesDir(), 'public'));

        $this->assertSame($expectedVersion, $strategy->getVersion($path));
        $this->assertSame($expectedVersion ? \sprintf('%s?v=%s', $path, $expectedVersion) : $path, $strategy->applyVersion($path));
    }

    public static function getPaths(): iterable
    {
        return [
            ['images/dummy_public.jpg', (string) strtotime(self::MTIME)],
            ['/images/dummy_public.jpg', (string) strtotime(self::MTIME)],
            ['does_not_exist', ''],
        ];
    }
}
