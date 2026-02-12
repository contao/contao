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
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Twig\Inspector\InspectionException;
use Contao\CoreBundle\Twig\Inspector\Inspector;
use Contao\DataContainer;
use Contao\LayoutModel;
use Contao\PageModel;

class ArticleColumnListener
{
    public function __construct(
        private readonly Inspector $inspector,
        private readonly ContaoFramework $framework,
        private readonly PageRegistry $pageRegistry,
    ) {
    }

    #[AsCallback(table: 'tl_article', target: 'fields.inColumn.load')]
    public function setSlotOptions(string $value, DataContainer $dc): string
    {
        if (!$article = $this->framework->getAdapter(ArticleModel::class)->findById($dc->id)) {
            return $value;
        }

        $page = $article->getRelated('pid');

        if (!$page instanceof PageModel) {
            return $value;
        }

        if (!$template = $this->pageRegistry->getRoute($page)->getDefault('_template')) {
            if (!$layout = $this->framework->getAdapter(LayoutModel::class)->findById($page->loadDetails()->layout)) {
                return $value;
            }

            if ('modern' !== $layout->type) {
                return $value;
            }

            $template = $layout->template;
        }

        try {
            $slots = $this->inspector
                ->inspectTemplate("@Contao/$template.html.twig")
                ->getSlots()
            ;
        } catch (InspectionException) {
            $slots = [];
        }

        $options = [];

        foreach ($slots as $slot) {
            $options[$slot] = "{% slot $slot %}";
        }

        $GLOBALS['TL_DCA']['tl_article']['fields']['inColumn']['options'] = $options;
        unset($GLOBALS['TL_DCA']['tl_article']['fields']['inColumn']['options_callback']);

        return $value;
    }
}
