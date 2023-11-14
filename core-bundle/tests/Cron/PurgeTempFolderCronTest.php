<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Cron;

use Contao\CoreBundle\Cron\PurgeTempFolderCron;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class PurgeTempFolderCronTest extends ContaoTestCase
{
    public function testPurgesTheTempFolder(): void
    {
        $projectDir = $this->getTempDir();
        $tempDir = Path::join($projectDir, 'system/tmp');
        $testFile = Path::join($tempDir, 'test.txt');

        $fs = new Filesystem();
        $fs->mkdir($tempDir);
        $fs->touch($testFile);

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem
            ->expects($this->once())
            ->method('remove')
            ->with($this->callback(
                static function (\Traversable $files) use ($testFile) {
                    foreach ($files as $file) {
                        if (Path::normalize((string) $file) === $testFile) {
                            return true;
                        }
                    }

                    return false;
                },
            ))
        ;

        (new PurgeTempFolderCron($filesystem, $projectDir, null))();
    }
}
