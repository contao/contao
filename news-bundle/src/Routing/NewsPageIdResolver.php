<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Routing;

use Contao\CoreBundle\Routing\PageIdResolverInterface;
use Doctrine\DBAL\Connection;
use PDO;
use function array_map;

class NewsPageIdResolver implements PageIdResolverInterface
{
    /** @var Connection */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @inheritDoc
     */
    public function resolvePageIds(?array $names) : array
    {
        $newsIds = $this->determineNewsIds($names);
        if (empty($newsIds)) {
            return [];
        }

        $sql = <<<'SQL'
SELECT p.id
      FROM tl_page p
INNER JOIN tl_news_archive a ON a.jumpTo = p.id
INNER JOIN tl_news n ON n.pid = a.id
WHERE n.id IN (?)
GROUP BY p.id
SQL;

        $statement = $this->connection->executeQuery($sql, [$newsIds], [Connection::PARAM_INT_ARRAY]);

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    private function determineNewsIds(?array $names) : array
    {
        $newsIds = [];

        if (null === $names) {
            return $newsIds;
        }

        foreach ($names as $name) {
            if (0 !== strncmp($name, 'tl_news.', 8)) {
                continue;
            }

            [, $id] = explode('.', $name);

            if (!is_numeric($id)) {
                continue;
            }

            $newsIds[] = (int) $id;
        }

        return $newsIds;
    }
}
