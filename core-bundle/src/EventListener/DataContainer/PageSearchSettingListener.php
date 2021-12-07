<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer;

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Contao\Search;
use Doctrine\DBAL\Connection;
use Contao\CoreBundle\Framework\ContaoFramework;

/**
 * @Callback(table="tl_page", target="fields.noSearch.save")
 */
class PageSearchSettingListener
{
    private ContaoFramework $framework;
    private Connection $connection;

    public function __construct(ContaoFramework $framework, Connection $connection)
    {
        $this->framework = $framework;
        $this->connection = $connection;
    }


    public function __invoke($value, DataContainer $dc)
    {

        if ($dc->activeRecord->noSearch || $dc->activeRecord->type != 'regular') {
            $this->purgeSearchIndex((int) $dc->activeRecord->id);
        }

        return $value;
    }

    public function purgeSearchIndex(int $pageId): void
    {
        $urls = $this->connection->fetchFirstColumn(
            'SELECT url FROM tl_search WHERE pid=:pageId',
            ['pageId' => $pageId]
        );

        $search = $this->framework->getAdapter(Search::class);

        foreach ($urls as $url) {
            $search->removeEntry($url);
        }
    }
}
