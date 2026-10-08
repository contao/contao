<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Studio;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Studio\CacheInvalidator;
use Contao\CoreBundle\Twig\Studio\TemplateSnapshots;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class TemplateSnapshotsTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/contao-template-snapshots-'.bin2hex(random_bytes(8));
        new Filesystem()->mkdir($this->directory.'/templates');
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->directory);

        parent::tearDown();
    }

    public function testRestoresWholeTree(): void
    {
        new Filesystem()->dumpFile($this->directory.'/templates/first.twig', 'first');

        $cacheInvalidator = $this->createMock(CacheInvalidator::class);
        $cacheInvalidator
            ->expects($this->exactly(2))
            ->method('invalidateCache')
            ->with('')
        ;

        $loader = $this->createMock(ContaoFilesystemLoader::class);
        $loader
            ->expects($this->once())
            ->method('warmUp')
            ->with(true)
        ;

        $snapshots = new TemplateSnapshots($this->directory, $this->directory.'/var/cache/test', $cacheInvalidator, $loader);

        if (!$snapshots->isAvailable()) {
            $this->markTestSkipped('Git is unavailable.');
        }

        $initial = $snapshots->snapshot();
        $current = $snapshots->latestSnapshot();
        $this->assertNotNull($current);
        $this->assertSame($initial, $current['hash']);
        $this->assertNotEmpty($current['date']);

        $filesystem = new Filesystem();
        $filesystem->dumpFile($this->directory.'/templates/first.twig', 'changed');
        $filesystem->dumpFile($this->directory.'/templates/second.twig', 'added');

        $this->assertStringContainsString('+changed', $snapshots->diff());
        $this->assertStringContainsString('second.twig', $snapshots->diff());

        $result = $snapshots->rollback();

        $this->assertSame($initial, $result['restored']);
        $this->assertSame('first', file_get_contents($this->directory.'/templates/first.twig'));
        $this->assertFileDoesNotExist($this->directory.'/templates/second.twig');
        $this->assertSame('', $snapshots->diff());
        $this->assertSame($initial, $snapshots->latestSnapshot()['hash'] ?? null);
    }

    public function testProjectRepositoryIsIndependent(): void
    {
        $git = new Process(['git', 'init', $this->directory]);
        $git->run();

        if (!$git->isSuccessful()) {
            $this->markTestSkipped('Git is unavailable.');
        }

        $filesystem = new Filesystem();
        $filesystem->dumpFile($this->directory.'/templates/page.twig', 'original');

        $snapshots = $this->createSnapshots();
        $snapshots->snapshot();

        $filesystem->dumpFile($this->directory.'/templates/page.twig', 'edited');
        $snapshots->rollback();

        $this->assertSame('original', file_get_contents($this->directory.'/templates/page.twig'));
        $this->assertFileDoesNotExist($this->directory.'/templates/.git');
        $this->assertFileExists($this->directory.'/.git');
    }

    #[DataProvider('getCacheRemovalOperations')]
    public function testCacheRemovalResetsHistory(bool $createDiff): void
    {
        $snapshots = $this->createSnapshots();

        if (!$snapshots->isAvailable()) {
            $this->markTestSkipped('Git is unavailable.');
        }

        $snapshots->snapshot();

        $filesystem = new Filesystem();

        if ($createDiff) {
            $filesystem->dumpFile($this->directory.'/templates/page.twig', 'added');
            $snapshots->diff();
        }

        $filesystem->remove($this->directory.'/var/cache/test');

        $this->assertSame([], $snapshots->listSnapshots());
        $this->assertNull($snapshots->latestSnapshot());
        $snapshots->snapshot();
        $this->assertCount(1, $snapshots->listSnapshots());
    }

    public static function getCacheRemovalOperations(): iterable
    {
        yield 'snapshot' => [false];
        yield 'diff' => [true];
    }

    private function createSnapshots(): TemplateSnapshots
    {
        return new TemplateSnapshots(
            $this->directory,
            $this->directory.'/var/cache/test',
            $this->createStub(CacheInvalidator::class),
            $this->createStub(ContaoFilesystemLoader::class),
        );
    }
}
