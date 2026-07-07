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

enum DatabaseMigrationEventType: string
{
    case Problem = 'problem';
    case Warning = 'warning';
    case WarningSummary = 'warning-summary';
    case BackupResult = 'backup-result';
    case MigrationPending = 'migration-pending';
    case MigrationExecuteStart = 'migration-execute-start';
    case MigrationResult = 'migration-result';
    case MigrationSummary = 'migration-summary';
    case SchemaPending = 'schema-pending';
    case SchemaExecuteStart = 'schema-execute-start';
    case SchemaExecute = 'schema-execute';
    case SchemaResult = 'schema-result';
    case SchemaSummary = 'schema-summary';
    case Error = 'error';
    case ConfigurationSummary = 'configuration-summary';
}
