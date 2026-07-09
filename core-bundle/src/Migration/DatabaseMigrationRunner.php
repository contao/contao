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
use Doctrine\DBAL\Connection;

class DatabaseMigrationRunner
{
    public function __construct(
        private readonly CommandCompiler $commandCompiler,
        private readonly Connection $connection,
        private readonly MigrationCollection $migrations,
        private readonly BackupManager $backupManager,
        private readonly DatabaseMigrationChecks $checks,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function compileConfigurationErrors(): array
    {
        return $this->checks->compileConfigurationErrors($this->connection);
    }

    /**
     * @return array<int, string>
     */
    public function compileConfigurationWarnings(): array
    {
        return $this->checks->compileConfigurationWarnings($this->connection);
    }

    /**
     * @return array<int, string>
     */
    public function compileSchemaWarnings(bool $skipDropStatements): array
    {
        return $this->checks->compileSchemaWarnings($this->connection, $skipDropStatements);
    }

    public function validateDatabaseVersion(): string|null
    {
        return $this->checks->validateDatabaseVersion($this->connection);
    }

    public function hasWorkToDo(bool $skipDropStatements): bool
    {
        if ($this->migrations->hasPending()) {
            return true;
        }

        return [] !== $this->commandCompiler->compileCommands($skipDropStatements);
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

    /**
     * @return array<string, mixed>
     */
    public function createBackup(): array
    {
        $config = $this->backupManager->createCreateConfig();
        $this->backupManager->create($config);

        return $config->getBackup()->toArray();
    }

    public function getBackupFilename(): string
    {
        return $this->backupManager->createCreateConfig()->getBackup()->getFilename();
    }
}
