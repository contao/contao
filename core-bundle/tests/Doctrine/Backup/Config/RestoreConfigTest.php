<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Doctrine\Backup\Config;

use Contao\CoreBundle\Doctrine\Backup\Backup;
use Contao\CoreBundle\Doctrine\Backup\Config\RestoreConfig;
use PHPUnit\Framework\TestCase;

class RestoreConfigTest extends TestCase
{
    public function testDefaults(): void
    {
        $config = new RestoreConfig(new Backup('valid_backup_filename__20211101141254.sql'));

        $this->assertInstanceOf(Backup::class, $config->getBackup());
        $this->assertSame(RestoreConfig::DEFAULT_DUMP_HEADER, $config->getDumpHeader());
        $this->assertFalse($config->ignoreOriginCheck());
        $this->assertSame([], $config->getTablesToIgnore());
        $this->assertFalse($config->isGzCompressionEnabled());
    }

    public function testAutomatedGzipCompression(): void
    {
        $config = new RestoreConfig(new Backup('valid_backup_filename__20211101141254.sql.gz'));
        $this->assertTrue($config->isGzCompressionEnabled());
    }

    public function testWithers(): void
    {
        $config = new RestoreConfig(new Backup('valid_backup_filename__20211101141254.sql'));

        $config = $config->withFilePath('other_name__20211101141254.sql.gz');
        $config = $config->withTablesToIgnore(['foobar']);
        $config = $config->withIgnoreOriginCheck(true);

        $this->assertSame('other_name__20211101141254.sql.gz', $config->getBackup()->getFilepath());
        $this->assertTrue($config->ignoreOriginCheck());
        $this->assertSame(['foobar'], $config->getTablesToIgnore());

        // Important, this should not automatically be enabled when changing the path!
        $this->assertFalse($config->isGzCompressionEnabled());

        $config = $config->withGzCompression(true);
        $this->assertTrue($config->isGzCompressionEnabled());
    }
}
