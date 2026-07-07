<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Command\Migration;

use Contao\CoreBundle\Migration\DatabaseMigrationDecision;
use Contao\CoreBundle\Migration\DatabaseMigrationEvent;
use Contao\CoreBundle\Migration\DatabaseMigrationEventType;
use Contao\CoreBundle\Migration\DatabaseMigrationObserverInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ConsoleDatabaseMigrationObserver implements DatabaseMigrationObserverInterface
{
    public function __construct(
        private readonly SymfonyStyle $io,
        private readonly bool $asJson,
        private readonly bool $withDeletesOption,
    ) {
    }

    public function notify(DatabaseMigrationEvent $event): DatabaseMigrationDecision|null
    {
        return match ($event->getType()) {
            DatabaseMigrationEventType::Problem => $this->renderProblem($event),
            DatabaseMigrationEventType::ConfigurationSummary => $this->renderConfigurationSummary(),
            DatabaseMigrationEventType::Warning => $this->renderWarning($event),
            DatabaseMigrationEventType::WarningSummary => $this->renderWarningSummary($event),
            DatabaseMigrationEventType::BackupResult => $this->renderBackupResult($event),
            DatabaseMigrationEventType::MigrationPending => $this->renderMigrationPending($event),
            DatabaseMigrationEventType::MigrationExecuteStart => $this->renderMigrationExecuteStart(),
            DatabaseMigrationEventType::MigrationResult => $this->renderMigrationResult($event),
            DatabaseMigrationEventType::MigrationSummary => $this->renderMigrationSummary($event),
            DatabaseMigrationEventType::SchemaPending => $this->renderSchemaPending($event),
            DatabaseMigrationEventType::SchemaExecuteStart => $this->renderSchemaExecuteStart(),
            DatabaseMigrationEventType::SchemaExecute => $this->renderSchemaExecute($event),
            DatabaseMigrationEventType::SchemaResult => $this->renderSchemaResult($event),
            DatabaseMigrationEventType::SchemaSummary => $this->renderSchemaSummary($event),
            DatabaseMigrationEventType::Error => $this->renderError($event),
        };
    }

    private function renderProblem(DatabaseMigrationEvent $event): null
    {
        $this->writeNdjson($event);

        if (!$this->asJson && 'warning' === ($event->getPayload()['severity'] ?? 'error')) {
            $this->io->block((string) $event->getPayload()['message'], '!', 'fg=yellow', ' ', true);
        } elseif (!$this->asJson) {
            $this->io->error((string) $event->getPayload()['message']);
        }

        return null;
    }

    private function renderConfigurationSummary(): null
    {
        if (!$this->asJson) {
            $this->io->error('The database server is not configured properly. Please resolve the above issue(s) and rerun the command.');
        }

        return null;
    }

    private function renderWarning(DatabaseMigrationEvent $event): null
    {
        $this->writeNdjson($event);

        return null;
    }

    private function renderWarningSummary(DatabaseMigrationEvent $event): DatabaseMigrationDecision|null
    {
        if ($this->asJson) {
            return null;
        }

        $this->io->warning(implode("\n\n", $event->getPayload()['warnings']));

        if (!($event->getPayload()['prompt'] ?? false)) {
            return null;
        }

        return $this->io->confirm('Continue regardless of the warnings?')
            ? DatabaseMigrationDecision::Continue
            : DatabaseMigrationDecision::Abort;
    }

    private function renderBackupResult(DatabaseMigrationEvent $event): null
    {
        if (isset($event->getPayload()['started'])) {
            if (!$this->asJson) {
                $this->io->info(\sprintf(
                    'Creating a database dump to "%s" with the default options. Use --no-backup to disable this feature.',
                    $event->getPayload()['name'],
                ));
            }

            return null;
        }

        if (isset($event->getPayload()['skipped'])) {
            if (!$this->asJson) {
                $this->io->info((string) $event->getPayload()['message']);
            }

            return null;
        }

        $this->writeNdjson($event);

        return null;
    }

    private function renderMigrationPending(DatabaseMigrationEvent $event): DatabaseMigrationDecision|null
    {
        $this->writeNdjson($event);

        if ($this->asJson) {
            return null;
        }

        $names = $event->getPayload()['names'];

        if ([] === $names) {
            return null;
        }

        $this->io->section('Pending migrations');

        foreach ($names as $name) {
            $this->io->writeln(' * '.$name);
        }

        if (!$event->getDecision()) {
            return null;
        }

        return $this->io->confirm('Execute the listed migrations?')
            ? DatabaseMigrationDecision::Execute
            : DatabaseMigrationDecision::Skip;
    }

    private function renderMigrationExecuteStart(): null
    {
        if (!$this->asJson) {
            $this->io->section('Execute migrations');
        }

        return null;
    }

    private function renderMigrationResult(DatabaseMigrationEvent $event): null
    {
        $this->writeNdjson($event);

        if ($this->asJson) {
            return null;
        }

        if (!empty($event->getPayload()['unexpectedPending'])) {
            return null;
        }

        $this->io->writeln(' * '.$event->getPayload()['message']);

        if (!$event->getPayload()['isSuccessful']) {
            $this->io->error('Migration failed');
        }

        return null;
    }

    private function renderMigrationSummary(DatabaseMigrationEvent $event): null
    {
        if ($this->asJson) {
            return null;
        }

        $count = (int) $event->getPayload()['count'];

        if (0 === $count && null === $event->getPayload()['exception']) {
            return null;
        }

        $this->io->success(\sprintf('Executed %d migrations.', $count));

        if (null !== ($message = $event->getPayload()['exception'])) {
            $text = (string) $message;

            if (!empty($event->getPayload()['restart'])) {
                $text .= "\n\nRestarting migration process...";
            }

            $this->io->error($text);
        }

        return null;
    }

    private function renderSchemaPending(DatabaseMigrationEvent $event): DatabaseMigrationDecision|null
    {
        $this->writeNdjson($event);

        if ($this->asJson) {
            return null;
        }

        $commands = $event->getPayload()['commands'];

        if ([] === $commands) {
            return null;
        }

        $this->io->section('Pending database migrations ('.$event->getPayload()['hash'].')');
        $this->io->listing($commands);

        if (!$event->getDecision()) {
            return null;
        }

        $choices = $this->withDeletesOption
            ? ['yes, with deletes', 'no']
            : ['yes', 'yes, with deletes', 'no'];

        $choice = $this->io->choice('Execute the listed database updates?', $choices, $choices[0]);

        return 'no' === $choice
            ? DatabaseMigrationDecision::Skip
            : ('yes, with deletes' === $choice ? DatabaseMigrationDecision::WithDeletes : DatabaseMigrationDecision::WithoutDeletes);
    }

    private function renderSchemaExecuteStart(): null
    {
        if (!$this->asJson) {
            $this->io->section('Execute database migrations');
        }

        return null;
    }

    private function renderSchemaExecute(DatabaseMigrationEvent $event): null
    {
        $this->writeNdjson($event);

        if (!$this->asJson) {
            $this->io->write(' * '.$event->getPayload()['command']);
        }

        return null;
    }

    private function renderSchemaResult(DatabaseMigrationEvent $event): null
    {
        $this->writeNdjson($event);

        if ($this->asJson) {
            return null;
        }

        if ($event->getPayload()['isSuccessful']) {
            $this->io->writeln('');

            return null;
        }

        $this->io->writeln('......FAILED');

        return null;
    }

    private function renderSchemaSummary(DatabaseMigrationEvent $event): null
    {
        if ($this->asJson) {
            return null;
        }

        $count = (int) $event->getPayload()['count'];
        $exceptions = $event->getPayload()['exceptions'];

        $this->io->success(\sprintf('Executed %d SQL queries.', $count));

        foreach ($exceptions as $exception) {
            $this->io->error((string) $exception);
        }

        return null;
    }

    private function renderError(DatabaseMigrationEvent $event): null
    {
        $this->writeNdjson($event);

        if (!$this->asJson) {
            $this->io->error((string) $event->getPayload()['message']);
        }

        return null;
    }

    private function writeNdjson(DatabaseMigrationEvent $event): void
    {
        if (
            !$this->asJson
            || \in_array(
                $event->getType(),
                [
                    DatabaseMigrationEventType::WarningSummary,
                    DatabaseMigrationEventType::MigrationExecuteStart,
                    DatabaseMigrationEventType::SchemaExecuteStart,
                    DatabaseMigrationEventType::ConfigurationSummary,
                ],
                true,
            )
        ) {
            return;
        }

        $payload = ['type' => $event->getType()->value] + array_diff_key($event->getPayload(), [
            'severity' => true,
            'started' => true,
            'prompt' => true,
            'unexpectedPending' => true,
            'warnings' => true,
        ]);
        $this->io->writeln(json_encode($payload, JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR));
    }
}
