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
use Symfony\Component\Filesystem\Path;

class MtimeVersionStrategyTest extends TestCase
{
    #[DataProvider('getPaths')]
    public function testGetVersion(string $path, string $expectedVersion): void
    {
        $projectDir = $this->getFixturesDir();
        $webDir = Path::join($projectDir, 'public');
        $strategy = new MtimeVersionStrategy($projectDir, $webDir);

        $this->assertSame($expectedVersion, $strategy->getVersion($path));
    }

    public function testGetVersionFromAbsolutePath(): void
    {
        $projectDir = $this->getFixturesDir();
        $webDir = Path::join($projectDir, 'public');
        $strategy = new MtimeVersionStrategy($projectDir, $webDir);

        $this->assertSame('1762613044', $strategy->getVersion(Path::join($projectDir, 'public/images/dummy_public.jpg')));
    }

    public static function getPaths(): iterable
    {
        return [
            ['files/data/data.csv', '1762613044'],
            ['images/dummy_public.jpg', '1762613044'],
            ['does_not_exist', ''],
        ];
    }
}
