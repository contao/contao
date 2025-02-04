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

class ArticleColumnListener
{
    public function __construct(
        private readonly Inspector $inspector,
        private readonly ContaoFramework $framework,
    ) {
    }

    #[AsCallback(table: 'tl_article', target: 'fields.inColumn.options')]
    public function getOptions(DataContainer $dc): array
    {
        if (!$article = $this->framework->getAdapter(ArticleModel::class)->findById($dc->id)) {
            return [];
        }

        $page = $article->getRelated('pid');

        if (!$page instanceof PageModel) {
            return [];
        }

        if (!$layout = $this->framework->getAdapter(LayoutModel::class)->findById($page->loadDetails()->layout)) {
            return [];
        }

        if ('modern' !== $layout->type) {
            return (new \tl_article())->getActiveLayoutSections($dc);
        }

        $slots = $this->inspector
            ->inspectTemplate("@Contao/$layout->template.html.twig")
            ->getSlots()
        ;

        $options = [];

        foreach ($slots as $slot) {
            $options[$slot] = "{% slot $slot %}";
        }

        return $options;
    }
}
