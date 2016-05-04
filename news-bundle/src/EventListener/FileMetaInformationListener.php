<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\NewsBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Database;
use Contao\Database\Result;

/**
 * Provides file meta information for the request.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class FileMetaInformationListener
{
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * Constructor.
     *
     * @param ContaoFrameworkInterface $framework
     */
    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    /**
     * Returns the page record related to the given table and ID.
     *
     * @param string $table
     * @param int    $id
     *
     * @return Result|false
     */
    public function onAddFileMetaInformationToRequest($table, $id)
    {
        if ('tl_news' === $table) {
            return $this->getResult(
                'SELECT * FROM tl_page WHERE id=(
                    SELECT jumpTo FROM tl_news_archive WHERE id=(SELECT pid FROM tl_news WHERE id=?)
                )',
                $id
            );
        }

        if ('tl_news_archive' === $table) {
            return $this->getResult(
                'SELECT * FROM tl_page WHERE id=(SELECT jumpTo FROM tl_news_archive WHERE id=?)',
                $id
            );
        }

        return false;
    }

    /**
     * Fetches result from database.
     *
     * @param string $query
     * @param mixed  $params
     *
     * @return Result|object
     */
    private function getResult($query, $params)
    {
        $this->framework->initialize();

        /** @var Database $database */
        $database = $this->framework->getAdapter('Contao\Database')->getInstance();

        return $database->prepare($query)->execute($params);
    }
}
