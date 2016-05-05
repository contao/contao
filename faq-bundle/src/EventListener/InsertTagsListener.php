<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\FaqBundle\EventListener;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\FaqCategoryModel;
use Contao\FaqModel;
use Contao\PageModel;

/**
 * Handles insert tags for FAQ.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class InsertTagsListener
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
     * Replaces insert tags known to this bundle.
     *
     * @param string $tag
     *
     * @return string|false
     */
    public function onReplaceInsertTags($tag)
    {
        $elements = explode('::', $tag);
        $key      = strtolower($elements[0]);

        if (!in_array($key, ['faq', 'faq_open', 'faq_url', 'faq_title'], true)) {
            return false;
        }

        $this->framework->initialize();

        /** @var FaqModel $faq */
        $faq = $this->framework
            ->getAdapter('Contao\FaqModel')
            ->findByIdOrAlias($elements[1])
        ;

        if (null === $faq || ($url = $this->generateUrl($faq)) === false) {
            return '';
        }

        switch ($key) {
            case 'faq':
                return sprintf(
                    '<a href="%s" title="%s">%s</a>',
                    $url,
                    specialchars($faq->question),
                    $faq->question
                );

            case 'faq_open':
                return sprintf('<a href="%s" title="%s">', $url, specialchars($faq->question));

            case 'faq_url':
                return $url;

            case 'faq_title':
                return specialchars($faq->question);
        }

        return false;
    }

    /**
     * Generate URL for an FAQ item.
     *
     * @param FaqModel $faq
     *
     * @return string|false
     */
    private function generateUrl(FaqModel $faq)
    {
        /** @var PageModel $jumpTo */
        if (!(($category = $faq->getRelated('pid')) instanceof FaqCategoryModel)
            || !(($jumpTo = $category->getRelated('jumpTo')) instanceof PageModel)
        ) {
            return false;
        }

        /** @var Config $config */
        $config = $this->framework->getAdapter('Contao\Config');

        return $jumpTo->getFrontendUrl(($config->get('useAutoItem') ?  '/' : '/items/') . ($faq->alias ?: $faq->id));
    }
}
