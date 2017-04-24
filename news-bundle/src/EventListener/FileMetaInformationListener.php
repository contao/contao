<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\NewsBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\NewsArchiveModel;
use Contao\NewsModel;
use Contao\PageModel;

/**
 * Provides file meta information for the request.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 * @author Leo Feyer <https://github.com/leofeyer>
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
     * Returns the page model related to the given table and ID.
     *
     * @param string $table
     * @param int    $id
     *
     * @return PageModel|false|null
     */
    public function onAddFileMetaInformationToRequest($table, $id)
    {
        switch ($table) {
            case 'tl_news_archive':
                return $this->getPageForNewsArchive($id);

            case 'tl_news':
                return $this->getPageForNews($id);
        }

        return false;
    }

    /**
     * Returns the page model for a news archive.
     *
     * @param int $id
     *
     * @return PageModel|false|null
     */
    private function getPageForNewsArchive($id)
    {
        $this->framework->initialize();

        /** @var NewsArchiveModel $archiveAdapter */
        $archiveAdapter = $this->framework->getAdapter(NewsArchiveModel::class);

        if (null === ($archiveModel = $archiveAdapter->findByPk($id))) {
            return false;
        }

        /** @var PageModel $pageModel */
        $pageModel = $archiveModel->getRelated('jumpTo');

        return $pageModel;
    }

    /**
     * Returns the page model for a news item.
     *
     * @param int $id
     *
     * @return PageModel|false|null
     */
    private function getPageForNews($id)
    {
        $this->framework->initialize();

        /** @var NewsModel $newsAdapter */
        $newsAdapter = $this->framework->getAdapter(NewsModel::class);

        if (null === ($newsModel = $newsAdapter->findByPk($id))) {
            return false;
        }

        /** @var NewsArchiveModel $archiveModel */
        if (null === ($archiveModel = $newsModel->getRelated('pid'))) {
            return false;
        }

        /** @var PageModel $pageModel */
        $pageModel = $archiveModel->getRelated('jumpTo');

        return $pageModel;
    }
}
