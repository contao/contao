<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DataContainer;

use Doctrine\DBAL\Connection;

trait DynamicPtableTrait
{
    /**
     * Recursively finds the ptable and pid of a nested record in the current table.
     * - for articles, it returns `tl_article` and the ID of the current article.
     * - for news, it returns `tl_news` and the ID of the current news.
     * - for calendars, it returns `tl_calendar_events` and the ID of the current event.
     *
     * @return array{0: string, 1: int}
     *
     * @throws \RuntimeException if a parent record is not found
     */
    private function getParentTableAndId(Connection $connection, string $table, int $id): array
    {
        // Limit to a nesting level of 10
        $records = $connection->fetchAllAssociative(
            "SELECT id, @pid:=pid AS pid, ptable FROM $table WHERE id=:id".str_repeat(" UNION SELECT id, @pid:=pid AS pid, ptable FROM $table WHERE id=@pid AND ptable=:ptable", 9),
            ['id' => $id, 'ptable' => $table],
        );

        if (!$records) {
            throw new \RuntimeException(\sprintf('Parent record of %s.%s not found', $table, $id));
        }

        // The current record has a different ptable than $table, we already have a result.
        if ($records[0]['ptable'] !== $table) {
            return [$records[0]['ptable'], (int) $records[0]['pid']];
        }

        $record = end($records);

        // If the given $id is the child of a record where ptable!=$table (e.g. only one
        // element nested), $records will only have one result, and we can directly use it.
        if ($record['ptable'] !== $table) {
            return [$record['ptable'], (int) $record['pid']];
        }

        // Trigger recursion in case our query returned exactly 10 records in which case
        // we might have higher parent records
        if (10 === \count($records)) {
            return $this->getParentTableAndId($connection, $table, (int) $record['pid']);
        }

        // If we have more than 1 but less than 10 results, the last result in our array
        // must be the first nested element, and its parent is what we are looking for.
        $record = $connection->fetchAssociative(
            "SELECT id, pid, ptable FROM $table WHERE id=?",
            [$record['pid']],
        );

        if (!$record) {
            throw new \RuntimeException(\sprintf('Parent record of %s.%s not found', $table, $id));
        }

        return [$record['ptable'], (int) $record['pid']];
    }
}
