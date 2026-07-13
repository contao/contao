<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration;

use Contao\CoreBundle\Doctrine\Backup\BackupManager;
use Contao\CoreBundle\Doctrine\Backup\Config\CreateConfig;

class DatabaseMigrationRunner
{
    public function __construct(
        private readonly CommandCompiler $commandCompiler,
        private readonly MigrationCollection $migrations,
        private readonly BackupManager $backupManager,
    ) {
    }

    public function hasWorkToDo(bool $skipDropStatements = false): bool
    {
        return $this->migrations->hasPending() || [] !== $this->commandCompiler->compileCommands($skipDropStatements);
    }

    public function createBackupConfig(): CreateConfig
    {
        return $this->backupManager->createCreateConfig();
    }

    /**
     * @return array<string, mixed>
     */
    public function createBackup(CreateConfig $config): array
    {
        $this->backupManager->create($config);

        return $config->getBackup()->toArray();
    }

    /**
     * @return list<string>
     */
    public function getPendingMigrationNames(): array
    {
        return [...$this->migrations->getPendingNames()];
    }

    /**
     * @return iterable<MigrationResult>
     */
    public function runMigrations(array $pendingNames): iterable
    {
        return $this->migrations->run($pendingNames);
    }

    /**
     * @return list<string>
     */
    public function compileSchemaCommands(bool $skipDropStatements = false): array
    {
        return $this->commandCompiler->compileCommands($skipDropStatements);
    }

    public function executeSqlCommand(string $command): void
    {
        $this->commandCompiler->executeSqlCommand($command);
    }
}
