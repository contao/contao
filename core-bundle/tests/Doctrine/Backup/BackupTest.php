<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Doctrine\Backup;

use Contao\CoreBundle\Doctrine\Backup\Backup;
use Contao\CoreBundle\Doctrine\Backup\BackupManagerException;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class BackupTest extends ContaoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        (new Filesystem())->dumpFile($this->getValidBackupPath(), 'foobar');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        (new Filesystem())->remove($this->getValidBackupPath());
    }

    public function testGetters(): void
    {
        $backup = new Backup($this->getValidBackupPath());

        $this->assertSame($this->getValidBackupPath(), $backup->getFilepath());
        $this->assertSame('2021-11-01T14:12:54+0000', $backup->getCreatedAt()->format(\DateTimeInterface::ISO8601));
        $this->assertSame('6 B', $backup->getHumanReadableSize());
        $this->assertSame(6, $backup->getSize());
        $this->assertSame([
            'createdAt' => '2021-11-01T14:12:54+0000',
            'size' => 6,
            'humanReadableSize' => '6 B',
            'path' => $this->getValidBackupPath(),
        ], $backup->toArray());
    }

    public function testCreateNewAtPath(): void
    {
        $backup = Backup::createNewAtPath($this->getTempDir());

        $this->assertInstanceOf(\DateTimeInterface::class, $backup->getCreatedAt());
        $this->assertSame(0, $backup->getSize());

        (new Filesystem())->remove($backup->getFilepath());
    }

    /**
     * @dataProvider invalidFileNameProvider
     */
    public function testInvalidFileName(string $filepath): void
    {
        $this->expectException(BackupManagerException::class);
        $this->expectExceptionMessage(sprintf(
            'The filepath "%s" does not match "%s"',
            $filepath,
            Backup::VALID_BACKUP_NAME_REGEX
        ));

        new Backup($filepath);
    }

    public function invalidFileNameProvider(): \Generator
    {
        yield 'Invalid file extension' => ['foobar__20211101141254.gif'];
        yield 'Missing __' => ['foobar20211101141254.sql.gz'];
        yield 'Error in datetime' => ['foobar__2021110114125.sql.gz'];
    }

    private function getValidBackupPath(): string
    {
        return Path::join($this->getTempDir(), 'valid_backup_filename__20211101141254.sql');
    }
}
