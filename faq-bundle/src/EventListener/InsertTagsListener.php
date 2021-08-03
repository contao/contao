<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\FaqBundle\EventListener;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FaqCategoryModel;
use Contao\FaqModel;
use Contao\PageModel;
use Contao\StringUtil;

/**
 * @internal
 */
class InsertTagsListener
{
    private const SUPPORTED_TAGS = [
        'faq',
        'faq_open',
        'faq_url',
        'faq_title',
    ];

    /**
     * @var ContaoFramework
     */
    private $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * Replaces the FAQ insert tags.
     *
     * @return string|false
     */
    public function onReplaceInsertTags(string $tag, bool $useCache, $cacheValue, array $flags)
    {
        $elements = explode('::', $tag);
        $key = strtolower($elements[0]);

        if (!\in_array($key, self::SUPPORTED_TAGS, true)) {
            return false;
        }

        $this->framework->initialize();

        /** @var FaqModel $adapter */
        $adapter = $this->framework->getAdapter(FaqModel::class);

        $faq = $adapter->findByIdOrAlias($elements[1]);

        if (null === $faq || false === ($url = $this->generateUrl($faq, \in_array('absolute', \array_slice($elements, 2), true) || \in_array('absolute', $flags, true)))) {
            return '';
        }

        return $this->generateReplacement($faq, $key, $url);
    }

    /**
     * @return string|false
     */
    private function generateUrl(FaqModel $faq, bool $absolute)
    {
        /** @var PageModel $jumpTo */
        if (
            !($category = $faq->getRelated('pid')) instanceof FaqCategoryModel
            || !($jumpTo = $category->getRelated('jumpTo')) instanceof PageModel
        ) {
            return false;
        }

        /** @var Config $config */
        $config = $this->framework->getAdapter(Config::class);
        $params = ($config->get('useAutoItem') ? '/' : '/items/').($faq->alias ?: $faq->id);

        return $absolute ? $jumpTo->getAbsoluteUrl($params) : $jumpTo->getFrontendUrl($params);
    }

    /**
     * @return string|false
     */
    private function generateReplacement(FaqModel $faq, string $key, string $url)
    {
        switch ($key) {
            case 'faq':
                return sprintf(
                    '<a href="%s" title="%s">%s</a>',
                    $url ?: './',
                    StringUtil::specialchars($faq->question),
                    $faq->question
                );

            case 'faq_open':
                return sprintf(
                    '<a href="%s" title="%s">',
                    $url ?: './',
                    StringUtil::specialchars($faq->question)
                );

            case 'faq_url':
                return $url ?: './';

            case 'faq_title':
                return StringUtil::specialchars($faq->question);
        }

        return false;
    }
}
