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

final class MigrationConfiguration
{
    private function __construct(
        private readonly bool $dryRun = false,
        private readonly bool $createBackup = false,
        private readonly bool $schemaOnly = false,
        private readonly bool $migrationsOnly = false,
        private readonly bool $backupSkipDropStatements = false,
        private readonly bool $schemaWarningSkipDropStatements = false,
        private readonly WarningMode $warningMode = WarningMode::Continue,
        private readonly MigrationExecutionMode $migrationExecutionMode = MigrationExecutionMode::Execute,
        private readonly SchemaUpdateMode $schemaUpdateMode = SchemaUpdateMode::WithoutDeletes,
        private readonly string|null $hash = null,
    ) {
    }

    public static function create(): self
    {
        return new self();
    }

    public function withDryRun(bool $dryRun): self
    {
        return $this->copy(['dryRun' => $dryRun]);
    }

    public function withCreateBackup(bool $createBackup): self
    {
        return $this->copy(['createBackup' => $createBackup]);
    }

    public function withSchemaOnly(bool $schemaOnly): self
    {
        return $this->copy(['schemaOnly' => $schemaOnly]);
    }

    public function withMigrationsOnly(bool $migrationsOnly): self
    {
        return $this->copy(['migrationsOnly' => $migrationsOnly]);
    }

    public function withBackupSkipDropStatements(bool $backupSkipDropStatements): self
    {
        return $this->copy(['backupSkipDropStatements' => $backupSkipDropStatements]);
    }

    public function withSchemaWarningSkipDropStatements(bool $schemaWarningSkipDropStatements): self
    {
        return $this->copy(['schemaWarningSkipDropStatements' => $schemaWarningSkipDropStatements]);
    }

    public function withWarningMode(WarningMode $warningMode): self
    {
        return $this->copy(['warningMode' => $warningMode]);
    }

    public function withMigrationExecutionMode(MigrationExecutionMode $migrationExecutionMode): self
    {
        return $this->copy(['migrationExecutionMode' => $migrationExecutionMode]);
    }

    public function withSchemaUpdateMode(SchemaUpdateMode $schemaUpdateMode): self
    {
        return $this->copy(['schemaUpdateMode' => $schemaUpdateMode]);
    }

    public function withHash(string|null $hash): self
    {
        return $this->copy(['hash' => $hash]);
    }

    public function isDryRun(): bool
    {
        return $this->dryRun;
    }

    public function shouldCreateBackup(): bool
    {
        return $this->createBackup;
    }

    public function isSchemaOnly(): bool
    {
        return $this->schemaOnly;
    }

    public function isMigrationsOnly(): bool
    {
        return $this->migrationsOnly;
    }

    public function shouldSkipDropStatementsForBackup(): bool
    {
        return $this->backupSkipDropStatements;
    }

    public function shouldSkipDropStatementsForSchemaWarnings(): bool
    {
        return $this->schemaWarningSkipDropStatements;
    }

    public function getWarningMode(): WarningMode
    {
        return $this->warningMode;
    }

    public function getMigrationExecutionMode(): MigrationExecutionMode
    {
        return $this->migrationExecutionMode;
    }

    public function getSchemaUpdateMode(): SchemaUpdateMode
    {
        return $this->schemaUpdateMode;
    }

    public function getHash(): string|null
    {
        return $this->hash;
    }

    /**
     * @param array{dryRun?: bool, createBackup?: bool, schemaOnly?: bool, migrationsOnly?: bool, backupSkipDropStatements?: bool, schemaWarningSkipDropStatements?: bool, warningMode?: WarningMode, migrationExecutionMode?: MigrationExecutionMode, schemaUpdateMode?: SchemaUpdateMode, hash?: string|null} $overrides
     */
    private function copy(array $overrides): self
    {
        return new self(
            $overrides['dryRun'] ?? $this->dryRun,
            $overrides['createBackup'] ?? $this->createBackup,
            $overrides['schemaOnly'] ?? $this->schemaOnly,
            $overrides['migrationsOnly'] ?? $this->migrationsOnly,
            $overrides['backupSkipDropStatements'] ?? $this->backupSkipDropStatements,
            $overrides['schemaWarningSkipDropStatements'] ?? $this->schemaWarningSkipDropStatements,
            $overrides['warningMode'] ?? $this->warningMode,
            $overrides['migrationExecutionMode'] ?? $this->migrationExecutionMode,
            $overrides['schemaUpdateMode'] ?? $this->schemaUpdateMode,
            \array_key_exists('hash', $overrides) ? $overrides['hash'] : $this->hash,
        );
    }
}
