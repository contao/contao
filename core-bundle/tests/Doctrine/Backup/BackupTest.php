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

class BackupTest extends ContaoTestCase
{
    public function testGetters(): void
    {
        $backup = new Backup('valid_backup_filename__20211101141254.sql');
        $backup->setSize(6);

        $this->assertSame('valid_backup_filename__20211101141254.sql', $backup->getFilename());
        $this->assertSame('2021-11-01T14:12:54+00:00', $backup->getCreatedAt()->format(\DateTimeInterface::ATOM));
        $this->assertSame(6, $backup->getSize());

        $this->assertSame(
            [
                'createdAt' => '2021-11-01T14:12:54+00:00',
                'size' => 6,
                'name' => 'valid_backup_filename__20211101141254.sql',
            ],
            $backup->toArray()
        );
    }

    public function testCreateNew(): void
    {
        $this->assertSame(0, Backup::createNew()->getSize());
    }

    /**
     * @dataProvider invalidFileNameProvider
     */
    public function testInvalidFileName(string $filename): void
    {
        $this->expectException(BackupManagerException::class);

        $this->expectExceptionMessage(sprintf(
            'The filename "%s" does not match "%s"',
            $filename,
            Backup::VALID_BACKUP_NAME_REGEX
        ));

        new Backup($filename);
    }

    public function invalidFileNameProvider(): \Generator
    {
        yield 'Invalid file extension' => ['foobar__20211101141254.gif'];
        yield 'Missing __' => ['foobar20211101141254.sql.gz'];
        yield 'Error in datetime' => ['foobar__2021110114125.sql.gz'];
        yield 'Path' => ['directory/valid_backup_filename__20211101141254.sql'];
    }
}
