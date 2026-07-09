<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version600;

use Contao\CoreBundle\Filesystem\Dbafs\Hashing\Context;
use Contao\CoreBundle\Filesystem\Dbafs\Hashing\HashGenerator;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Symfony\Component\Filesystem\Path;

class DbafsHashingAlgorithmMigration extends AbstractMigration
{
    public function __construct(
        private readonly Connection $connection,
        private readonly VirtualFilesystemInterface $filesStorage,
    ) {
    }

    public function shouldRun(): bool
    {
        $md5HashGenerator = new HashGenerator('md5', false);
        $xxh128HashGenerator = new HashGenerator('xxh128', false);

        $entries = $this->connection
            ->executeQuery("SELECT path, hash FROM tl_files WHERE type='file'")
            ->fetchAllKeyValue()
        ;

        // Detect used hashing algorithm
        foreach ($entries as $path => $hash) {
            // Do not run if any of the hashes was cleared before
            if (!$hash) {
                return false;
            }

            $path = Path::makeRelative($path, 'files');

            if (!$this->filesStorage->fileExists($path, VirtualFilesystemInterface::BYPASS_DBAFS)) {
                continue;
            }

            $context = new Context();
            $xxh128HashGenerator->hashFileContent($this->filesStorage, $path, $context);

            if ($context->getResult() === $hash) {
                return false;
            }

            $md5HashGenerator->hashFileContent($this->filesStorage, $path, $context);

            if ($context->getResult() === $hash) {
                return true;
            }
        }

        // None of the hashes match: run the migration because the database needs to be
        // synced anyway.
        return \count($entries) > 0;
    }

    public function run(): MigrationResult
    {
        // Remove all existing hashes - they will get recreated when syncing the
        // filesystem the next time
        $this->connection->update('tl_files', ['hash' => '']);

        return $this->createResult(true);
    }
}
