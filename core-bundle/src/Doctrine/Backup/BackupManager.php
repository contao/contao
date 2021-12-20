<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Doctrine\Backup;

use Contao\CoreBundle\Doctrine\Backup\Config\CreateConfig;
use Contao\CoreBundle\Doctrine\Backup\Config\RestoreConfig;
use Contao\CoreBundle\Doctrine\Backup\Config\RetentionPolicy;
use Doctrine\DBAL\Connection;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * @internal
 */
class BackupManager
{
    public const DUMP_HEADER = '-- Generated by the Contao Open Source CMS Backup Manager (version: v1).';

    private Connection $connection;
    private DumperInterface $dumper;
    private string $backupDir;
    private array $tablesToIgnore;
    private RetentionPolicy $retentionPolicy;

    public function __construct(Connection $connection, DumperInterface $dumper, string $backupDir, array $tablesToIgnore, RetentionPolicy $retentionPolicy)
    {
        $this->connection = $connection;
        $this->dumper = $dumper;
        $this->backupDir = $backupDir;
        $this->tablesToIgnore = $tablesToIgnore;
        $this->retentionPolicy = $retentionPolicy;
    }

    public function createCreateConfig(): CreateConfig
    {
        $config = new CreateConfig(Backup::createNewAtPath($this->backupDir));

        return $config->withTablesToIgnore($this->tablesToIgnore);
    }

    public function createRestoreConfig(): RestoreConfig
    {
        $latestBackup = $this->getLatestBackup();

        if (null === $latestBackup) {
            throw new BackupManagerException('No backups found.');
        }

        $config = new RestoreConfig($latestBackup);

        return $config->withTablesToIgnore($this->tablesToIgnore);
    }

    public function getLatestBackup(): ?Backup
    {
        return $this->listBackups()[0] ?? null;
    }

    /**
     * Lists all backups (the latest one first).
     *
     * @return array<Backup>
     */
    public function listBackups(): array
    {
        // Ensure that the directory exists
        (new Filesystem())->mkdir($this->backupDir);

        $files = Finder::create()
            ->files()
            ->in($this->backupDir)
            ->depth('== 0')
            ->name(Backup::VALID_BACKUP_NAME_REGEX)
        ;

        $backups = [];

        foreach ($files as $file) {
            $backups[] = new Backup($file->getPathname());
        }

        usort($backups, static fn (Backup $a, Backup $b) => $b->getCreatedAt() <=> $a->getCreatedAt());

        return $backups;
    }

    /**
     * @throws BackupManagerException
     */
    public function create(CreateConfig $config): void
    {
        $this->executeTransactional(fn () => $this->doCreate($config));
    }

    /**
     * @throws BackupManagerException
     */
    public function restore(RestoreConfig $config): void
    {
        $this->executeTransactional(fn () => $this->doRestore($config));
    }

    private function executeTransactional(\Closure $func): void
    {
        $isAutoCommit = $this->connection->isAutoCommit();

        if ($isAutoCommit) {
            $this->connection->setAutoCommit(false);
        }

        try {
            $this->connection->transactional($func);
        } catch (BackupManagerException $e) {
            throw $e;
        } catch (\Throwable $t) {
            throw new BackupManagerException($t->getMessage(), 0, $t);
        } finally {
            if ($isAutoCommit) {
                $this->connection->setAutoCommit(true);
            }
        }
    }

    private function doCreate(CreateConfig $config): void
    {
        $backup = $config->getBackup();

        // Ensure the target file exists and is empty
        (new Filesystem())->dumpFile($backup->getFilepath(), '');

        $fileHandle = fopen($backup->getFilepath(), 'w');
        $deflateContext = $config->isGzCompressionEnabled() ? deflate_init(ZLIB_ENCODING_GZIP, ['level' => 9]) : null;

        // Header lines
        $this->writeLine(self::DUMP_HEADER, $fileHandle, $deflateContext);
        $this->writeLine('-- Generated at '.$config->getBackup()->getCreatedAt()->format(\DateTimeInterface::ATOM), $fileHandle, $deflateContext);

        try {
            foreach ($this->dumper->dump($this->connection, $config) as $data) {
                $this->writeLine($data, $fileHandle, $deflateContext);
            }

            $this->finishWriting($fileHandle, $deflateContext);
            $this->tidyDirectory();
        } catch (BackupManagerException $exception) {
            (new Filesystem())->remove($backup->getFilepath());

            throw $exception;
        }
    }

    /**
     * @param resource                 $fileHandle
     * @param \DeflateContext|resource $deflateContext
     */
    private function writeLine(string $data, $fileHandle, $deflateContext): void
    {
        $data .= PHP_EOL;

        if ($deflateContext) {
            $data = deflate_add($deflateContext, $data, ZLIB_NO_FLUSH);
        }

        @fwrite($fileHandle, $data);
        fflush($fileHandle);
    }

    /**
     * @param resource                 $fileHandle
     * @param \DeflateContext|resource $deflateContext
     */
    private function finishWriting($fileHandle, $deflateContext): void
    {
        if ($deflateContext) {
            fwrite($fileHandle, deflate_add($deflateContext, '', ZLIB_FINISH));
        }

        fclose($fileHandle);
    }

    private function doRestore(RestoreConfig $config): void
    {
        $backup = $config->getBackup();

        if (!(new Filesystem())->exists($backup->getFilepath())) {
            throw new BackupManagerException(sprintf('Dump does not exist at "%s".', $backup->getFilepath()));
        }

        $currentQuery = '';
        $checkedForHeader = $config->ignoreOriginCheck();
        $handle = gzopen($backup->getFilepath(), 'r');

        while ($line = gzgets($handle)) {
            $line = trim($line);

            if (!$checkedForHeader) {
                if (self::DUMP_HEADER !== $line) {
                    throw new BackupManagerException('The Contao database importer only supports dumps generated by Contao.');
                }

                $checkedForHeader = true;
                continue;
            }

            // Ignore comments
            if (0 === strpos($line, '--')) {
                continue;
            }

            $currentQuery .= $line;

            // Current query ends
            if (';' === substr($currentQuery, -1)) {
                $this->executeWrappedQuery($currentQuery);
                $currentQuery = '';
            }
        }

        if ('' !== $currentQuery) {
            $this->executeWrappedQuery($currentQuery);
        }
    }

    private function tidyDirectory(): void
    {
        $i = 0;
        $keepMax = $this->retentionPolicy->getKeepMax();

        foreach ($this->listBackups() as $backup) {
            if ($i >= $keepMax) {
                (new Filesystem())->remove($backup->getFilepath());
            }

            ++$i;
        }
    }

    private function executeWrappedQuery(string $query): void
    {
        try {
            $this->connection->executeQuery($query);
        } catch (\Exception $e) {
            throw new BackupManagerException($e->getMessage(), 0, $e);
        }
    }
}
