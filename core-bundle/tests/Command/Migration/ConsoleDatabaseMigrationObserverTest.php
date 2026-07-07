<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Command\Migration;

use Contao\CoreBundle\Command\Migration\ConsoleDatabaseMigrationObserver;
use Contao\CoreBundle\Migration\DatabaseMigrationEvent;
use Contao\CoreBundle\Migration\DatabaseMigrationEventType;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConsoleDatabaseMigrationObserverTest extends TestCase
{
    public function testWritesNdjsonEventsWithTheExpectedShapes(): void
    {
        $output = new BufferedOutput();
        $observer = new ConsoleDatabaseMigrationObserver(new SymfonyStyle(new ArrayInput([]), $output), true, false);

        $observer->notify(new DatabaseMigrationEvent(DatabaseMigrationEventType::Problem, ['message' => 'problem', 'severity' => 'warning']));
        $observer->notify(new DatabaseMigrationEvent(DatabaseMigrationEventType::Warning, ['message' => 'warning']));
        $observer->notify(new DatabaseMigrationEvent(DatabaseMigrationEventType::WarningSummary, ['warnings' => ['warning'], 'prompt' => true]));
        $observer->notify(new DatabaseMigrationEvent(DatabaseMigrationEventType::BackupResult, ['started' => true, 'name' => 'valid_backup_filename__20211101141254.sql']));
        $observer->notify(new DatabaseMigrationEvent(DatabaseMigrationEventType::BackupResult, ['createdAt' => '2021-11-01T14:12:54+00:00', 'size' => 0, 'name' => 'valid_backup_filename__20211101141254.sql']));
        $observer->notify(new DatabaseMigrationEvent(DatabaseMigrationEventType::MigrationPending, ['names' => ['Migration 1'], 'hash' => 'abc']));
        $observer->notify(new DatabaseMigrationEvent(DatabaseMigrationEventType::MigrationExecuteStart));
        $observer->notify(new DatabaseMigrationEvent(DatabaseMigrationEventType::MigrationResult, ['message' => 'Result 1', 'isSuccessful' => true]));
        $observer->notify(new DatabaseMigrationEvent(DatabaseMigrationEventType::MigrationResult, ['message' => 'Expected "Foo" got "Bar".', 'isSuccessful' => false, 'unexpectedPending' => true]));
        $observer->notify(new DatabaseMigrationEvent(DatabaseMigrationEventType::SchemaPending, ['commands' => ['ALTER TABLE tl_test ADD foo INT NULL'], 'hash' => 'def']));
        $observer->notify(new DatabaseMigrationEvent(DatabaseMigrationEventType::SchemaExecuteStart));
        $observer->notify(new DatabaseMigrationEvent(DatabaseMigrationEventType::SchemaExecute, ['command' => 'ALTER TABLE tl_test ADD foo INT NULL']));
        $observer->notify(new DatabaseMigrationEvent(DatabaseMigrationEventType::SchemaResult, ['command' => 'ALTER TABLE tl_test ADD foo INT NULL', 'isSuccessful' => true]));
        $observer->notify(new DatabaseMigrationEvent(DatabaseMigrationEventType::MigrationSummary, ['count' => 1, 'exception' => null, 'restart' => false]));
        $observer->notify(new DatabaseMigrationEvent(DatabaseMigrationEventType::SchemaSummary, ['count' => 1, 'exceptions' => []]));
        $observer->notify(new DatabaseMigrationEvent(DatabaseMigrationEventType::ConfigurationSummary));
        $observer->notify(new DatabaseMigrationEvent(DatabaseMigrationEventType::Error, ['message' => 'Boom', 'code' => 0, 'file' => 'file.php', 'line' => 1, 'trace' => 'trace']));

        $ndjson = $this->jsonArrayFromNdjson($output->fetch());

        $this->assertSame(
            [
                ['type' => 'problem', 'message' => 'problem'],
                ['type' => 'warning', 'message' => 'warning'],
                ['type' => 'backup-result', 'createdAt' => '2021-11-01T14:12:54+00:00', 'size' => 0, 'name' => 'valid_backup_filename__20211101141254.sql'],
                ['type' => 'migration-pending', 'names' => ['Migration 1'], 'hash' => 'abc'],
                ['type' => 'migration-result', 'message' => 'Result 1', 'isSuccessful' => true],
                ['type' => 'migration-result', 'message' => 'Expected "Foo" got "Bar".', 'isSuccessful' => false],
                ['type' => 'schema-pending', 'commands' => ['ALTER TABLE tl_test ADD foo INT NULL'], 'hash' => 'def'],
                ['type' => 'schema-execute', 'command' => 'ALTER TABLE tl_test ADD foo INT NULL'],
                ['type' => 'schema-result', 'command' => 'ALTER TABLE tl_test ADD foo INT NULL', 'isSuccessful' => true],
                ['type' => 'error', 'message' => 'Boom', 'code' => 0, 'file' => 'file.php', 'line' => 1, 'trace' => 'trace'],
            ],
            $ndjson,
        );

        $this->assertArrayNotHasKey('severity', $ndjson[0]);
    }

    public function testWritesTxtSummaryMessages(): void
    {
        $output = new BufferedOutput();
        $observer = new ConsoleDatabaseMigrationObserver(new SymfonyStyle(new ArrayInput([]), $output), false, false);

        $observer->notify(new DatabaseMigrationEvent(DatabaseMigrationEventType::Problem, ['message' => 'Problem one', 'severity' => 'warning']));
        $observer->notify(new DatabaseMigrationEvent(DatabaseMigrationEventType::WarningSummary, ['warnings' => ['Problem one'], 'prompt' => true]));
        $observer->notify(new DatabaseMigrationEvent(DatabaseMigrationEventType::BackupResult, ['started' => true, 'name' => 'valid_backup_filename__20211101141254.sql']));
        $observer->notify(new DatabaseMigrationEvent(DatabaseMigrationEventType::BackupResult, ['createdAt' => '2021-11-01T14:12:54+00:00', 'size' => 0, 'name' => 'valid_backup_filename__20211101141254.sql']));
        $observer->notify(new DatabaseMigrationEvent(DatabaseMigrationEventType::MigrationPending, ['names' => ['Migration 1'], 'hash' => 'abc']));
        $observer->notify(new DatabaseMigrationEvent(DatabaseMigrationEventType::MigrationExecuteStart));
        $observer->notify(new DatabaseMigrationEvent(DatabaseMigrationEventType::MigrationResult, ['message' => 'Result 1', 'isSuccessful' => true]));
        $observer->notify(new DatabaseMigrationEvent(DatabaseMigrationEventType::ConfigurationSummary));
        $observer->notify(new DatabaseMigrationEvent(DatabaseMigrationEventType::MigrationSummary, ['count' => 2, 'exception' => 'Expected "Foo" got "Bar".', 'restart' => true]));
        $observer->notify(new DatabaseMigrationEvent(DatabaseMigrationEventType::SchemaPending, ['commands' => ['ALTER TABLE tl_test ADD foo INT NULL'], 'hash' => 'def']));
        $observer->notify(new DatabaseMigrationEvent(DatabaseMigrationEventType::SchemaExecuteStart));
        $observer->notify(new DatabaseMigrationEvent(DatabaseMigrationEventType::SchemaExecute, ['command' => 'ALTER TABLE tl_test ADD foo INT NULL']));
        $observer->notify(new DatabaseMigrationEvent(DatabaseMigrationEventType::SchemaResult, ['command' => 'ALTER TABLE tl_test ADD foo INT NULL', 'isSuccessful' => true]));
        $observer->notify(new DatabaseMigrationEvent(DatabaseMigrationEventType::SchemaSummary, ['count' => 3, 'exceptions' => ['SQL failed']]));

        $display = preg_replace('/\s+/', ' ', $output->fetch());

        $this->assertStringContainsString('Problem one', $display);
        $this->assertStringContainsString('Creating a database dump to "valid_backup_filename__20211101141254.sql" with the default options. Use --no-backup to disable this feature.', $display);
        $this->assertStringContainsString('Pending migrations', $display);
        $this->assertStringContainsString('Execute migrations', $display);
        $this->assertStringContainsString('Pending database migrations (def)', $display);
        $this->assertStringContainsString('Execute database migrations', $display);
        $this->assertStringContainsString('Executed 2 migrations.', $display);
        $this->assertStringContainsString('Expected "Foo" got "Bar".', $display);
        $this->assertStringContainsString('Restarting migration process...', $display);
        $this->assertStringContainsString('Executed 3 SQL queries.', $display);
        $this->assertStringContainsString('SQL failed', $display);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function jsonArrayFromNdjson(string $ndjson): array
    {
        return array_map(static fn (string $line): array => json_decode($line, true, 512, JSON_THROW_ON_ERROR), explode("\n", trim($ndjson)));
    }
}
