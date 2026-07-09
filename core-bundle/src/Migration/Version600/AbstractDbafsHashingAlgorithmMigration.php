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

use Contao\CoreBundle\Filesystem\Dbafs\Dbafs;
use Contao\CoreBundle\Filesystem\Dbafs\Hashing\Context;
use Contao\CoreBundle\Filesystem\Dbafs\Hashing\HashGenerator;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

class AbstractDbafsHashingAlgorithmMigration extends AbstractMigration
{
    public function __construct(
        private readonly Connection $connection,
        private readonly Dbafs $dbafs,
        private readonly string $oldHashingAlgorithm,
    ) {
    }

    public function shouldRun(): bool
    {
        $dbafs = new \ReflectionClass(Dbafs::class);

        $allHashesByPath = $dbafs->getMethod('getDatabaseEntries')->invoke($this->dbafs, [''], [])[1];
        $storage = $dbafs->getProperty('filesystem')->getValue($this->dbafs);

        $currentHashGenerator = $dbafs->getProperty('hashGenerator')->getValue($this->dbafs);
        $previousHashGenerator = new HashGenerator($this->oldHashingAlgorithm, false);

        // Detect used hashing algorithm
        foreach ($allHashesByPath as $path => $hash) {
            // Do not run if any of the hashes was cleared before
            if (!$hash) {
                return false;
            }

            if (!$storage->fileExists($path, VirtualFilesystemInterface::BYPASS_DBAFS)) {
                continue;
            }

            $context = new Context();
            $currentHashGenerator->hashFileContent($storage, $path, $context);

            if ($context->getResult() === $hash) {
                return false;
            }

            $previousHashGenerator->hashFileContent($storage, $path, $context);

            if ($context->getResult() === $hash) {
                return true;
            }
        }

        // None of the hashes match: run the migration because the database needs to be
        // synced anyway.
        return \count($allHashesByPath) > 0;
    }

    public function run(): MigrationResult
    {
        // Remove all existing hashes - they will get recreated when syncing the
        // filesystem the next time
        $this->connection->update(
            new \ReflectionProperty(Dbafs::class, 'table')->getValue($this->dbafs),
            ['hash' => ''],
        );

        return $this->createResult(true);
    }
}
