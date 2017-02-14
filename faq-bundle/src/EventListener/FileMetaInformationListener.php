<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\FaqBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\FaqCategoryModel;
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
        if ('tl_faq_category' === $table) {
            return $this->getPageForFaq($id);
        }

        return false;
    }

    /**
     * Returns the page model for an FAQ.
     *
     * @param int $id
     *
     * @return PageModel|false|null
     */
    private function getPageForFaq($id)
    {
        $this->framework->initialize();

        /** @var FaqCategoryModel $categoryAdapter */
        $categoryAdapter = $this->framework->getAdapter(FaqCategoryModel::class);

        if (null === ($categoryModel = $categoryAdapter->findByPk($id))) {
            return false;
        }

        /** @var PageModel $pageModel */
        $pageModel = $categoryModel->getRelated('jumpTo');

        return $pageModel;
    }
}
