<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version500;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class BasicEntitiesMigration extends AbstractMigration
{
    private static array $targets = [
        'tl_article.title',
        'tl_article.teaser',

        'tl_calendar.title',

        'tl_calendar_events.title',
        'tl_calendar_events.location',
        'tl_calendar_events.teaser',
        'tl_calendar_events.alt',
        'tl_calendar_events.imageurl',
        'tl_calendar_events.caption',
        'tl_calendar_events.url',
        'tl_calendar_events.imagetitle',
        'tl_calendar_events.address',
        'tl_calendar_events.description',
        'tl_calendar_events.pagetitle',

        'tl_calendar_feed.title',
        'tl_calendar_feed.description',

        'tl_comments.comment',

        'tl_content.headline',
        'tl_content.text',
        'tl_content.alt',
        'tl_content.imagetitle',
        'tl_content.imageurl',
        'tl_content.caption',
        'tl_content.html',
        'tl_content.listitems',
        'tl_content.tableitems',
        'tl_content.summary',
        'tl_content.mooheadline',
        'tl_content.code',
        'tl_content.url',
        'tl_content.titletext',
        'tl_content.linktitle',
        'tl_content.embed',
        'tl_content.data',

        'tl_faq.question',
        'tl_faq.answer',
        'tl_faq.alt',
        'tl_faq.imageurl',
        'tl_faq.caption',
        'tl_faq.imagetitle',
        'tl_faq.description',
        'tl_faq.pagetitle',

        'tl_faq_category.title',
        'tl_faq_category.headline',

        'tl_files.meta',

        'tl_form_field.label',
        'tl_form_field.text',
        'tl_form_field.html',
        'tl_form_field.options',
        'tl_form_field.placeholder',
        'tl_form_field.value',
        'tl_form_field.errormsg',

        'tl_layout.titletag',
        'tl_layout.head',

        'tl_member.firstname',
        'tl_member.lastname',
        'tl_member.company',
        'tl_member.street',
        'tl_member.city',
        'tl_member.state',
        'tl_member.website',
        'tl_member.website',

        'tl_module.headline',
        'tl_module.customlabel',
        'tl_module.html',
        'tl_module.data',
        'tl_module.nl_text',

        'tl_news.headline',
        'tl_news.subheadline',
        'tl_news.teaser',
        'tl_news.alt',
        'tl_news.imageurl',
        'tl_news.caption',
        'tl_news.url',
        'tl_news.imagetitle',
        'tl_news.description',
        'tl_news.pagetitle',

        'tl_news_archive.title',

        'tl_page.title',
        'tl_page.pagetitle',
        'tl_page.description',
        'tl_page.url',
    ];

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        // This migration is very intrusive thus we try to run it only if the
        // database schema was not yet updated to Contao 5
        if (
            !$schemaManager->tablesExist(['tl_article'])
            || !isset($schemaManager->listTableColumns('tl_article')['keywords'])
            || !$schemaManager->tablesExist(['tl_theme'])
            || !isset($schemaManager->listTableColumns('tl_theme')['vars'])
        ) {
            return false;
        }

        foreach ($this->getTargets() as [$table, $column]) {
            if (!$schemaManager->tablesExist([$table]) || !isset($schemaManager->listTableColumns($table)[$column])) {
                continue;
            }

            $test = $this->connection->fetchOne(
                "SELECT TRUE FROM $table WHERE `$column` REGEXP '\\\\[(&|&amp;|lt|gt|nbsp|-)\\\\]' LIMIT 1;"
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
                "SELECT id, `$column` FROM $table WHERE `$column` REGEXP '\\\\[(&|&amp;|lt|gt|nbsp|-)\\\\]'"
            );

            foreach ($values as $id => $value) {
                $value = StringUtil::restoreBasicEntities(StringUtil::deserialize($value));

                if (\is_array($value)) {
                    $value = serialize($value);
                }

                $this->connection->update($table, [$column => $value], ['id' => (int) $id]);
            }
        }

        return $this->createResult(true);
    }

    private function getTargets(): array
    {
        return array_map(static fn (string $target) => explode('.', $target), self::$targets);
    }
}
