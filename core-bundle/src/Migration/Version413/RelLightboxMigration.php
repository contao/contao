<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version413;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class RelLightboxMigration extends AbstractMigration
{
    private static array $targets = [
        'tl_article.teaser',
        'tl_calendar_events.teaser',
        'tl_comments.comment',
        'tl_content.text',
        'tl_faq.answer',
        'tl_form_field.text',
        'tl_news.teaser',
    ];

    public function __construct(private Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        foreach ($this->getTargets() as [$table, $column]) {
            if (!$schemaManager->tablesExist([$table]) || !isset($schemaManager->listTableColumns($table)[$column])) {
                continue;
            }

            $test = $this->connection->fetchOne(
                "SELECT TRUE FROM $table WHERE `$column` REGEXP ' rel=\"lightbox(\\\\[([^\\\\]]+)\\\\])?\"' LIMIT 1;"
            );

            if (false !== $test) {
                return true;
            }
        }

        return false;
    }

    public function run(): MigrationResult
    {
        foreach ($this->getTargets() as [$table, $column]) {
            $values = $this->connection->fetchAllKeyValue(
                "SELECT id, `$column` FROM $table WHERE `$column` REGEXP ' rel=\"lightbox(\\\\[([^\\\\]]+)\\\\])?\"'"
            );

            foreach ($values as $id => $value) {
                $value = preg_replace('/ rel="lightbox(\[([^\]]+)\])?"/', ' data-lightbox="$2"', $value, -1, $count);

                if ($count) {
                    $this->connection->update($table, [$column => $value], ['id' => (int) $id]);
                }
            }
        }

        return $this->createResult(true);
    }

    private function getTargets(): array
    {
        return array_map(static fn (string $target) => explode('.', $target), self::$targets);
    }
}
