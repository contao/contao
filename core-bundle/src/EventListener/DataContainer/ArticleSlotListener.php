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

use Contao\ArticleModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Twig\Inspector\Inspector;
use Contao\DataContainer;
use Contao\LayoutModel;
use Contao\PageModel;

class ArticleSlotListener
{
    public function __construct(
        private readonly Inspector $inspector,
        private readonly ContaoFramework $framework,
    ) {
    }

    #[AsCallback(table: 'tl_article', target: 'fields.inColumn.options')]
    public function getSlots(DataContainer $dc): array
    {
        $article = $this->framework->getAdapter(ArticleModel::class)->findByPk($dc->id);

        /** @var PageModel $page */
        $page = $article->getRelated('pid');
        $page->loadDetails();

        $layout = $this->framework->getAdapter(LayoutModel::class)->findByPk($page->layout);

        if ('modern' !== $layout->version) {
            return $this->framework->getAdapter(\tl_article::class)->getActiveLayoutSections($dc);
        }

        return $this->inspector->inspectTemplate("@Contao/{$layout->template}.html.twig")->getSlots();
    }
}
