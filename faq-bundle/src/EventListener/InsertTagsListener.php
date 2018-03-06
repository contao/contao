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
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\FaqCategoryModel;
use Contao\FaqModel;
use Contao\PageModel;
use Contao\StringUtil;

class InsertTagsListener
{
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * @var array
     */
    private static $supportedTags = [
        'faq',
        'faq_open',
        'faq_url',
        'faq_title',
    ];

    /**
     * @param ContaoFrameworkInterface $framework
     */
    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    /**
     * Replaces FAQ insert tags.
     *
     * @param string $tag
     * @param bool   $useCache
     * @param mixed  $cacheValue
     * @param array  $flags
     *
     * @return string|false
     */
    public function onReplaceInsertTags(string $tag, bool $useCache = true, $cacheValue = null, array $flags = [])
    {
        $elements = explode('::', $tag);
        $key = strtolower($elements[0]);

        if (!\in_array($key, self::$supportedTags, true)) {
            return false;
        }

        $this->framework->initialize();

        /** @var FaqModel $adapter */
        $adapter = $this->framework->getAdapter(FaqModel::class);

        $faq = $adapter->findByIdOrAlias($elements[1]);

        if (null === $faq || false === ($url = $this->generateUrl($faq, \in_array('absolute', $flags, true)))) {
            return '';
        }

        return $this->generateReplacement($faq, $key, $url);
    }

    /**
     * Generates the URL for an FAQ.
     *
     * @param FaqModel $faq
     * @param bool     $absolute
     *
     * @return string|false
     */
    private function generateUrl(FaqModel $faq, bool $absolute)
    {
        /** @var PageModel $jumpTo */
        if (
            !($category = $faq->getRelated('pid')) instanceof FaqCategoryModel
            || !(($jumpTo = $category->getRelated('jumpTo')) instanceof PageModel)
        ) {
            return false;
        }

        /** @var Config $config */
        $config = $this->framework->getAdapter(Config::class);
        $params = ($config->get('useAutoItem') ? '/' : '/items/').($faq->alias ?: $faq->id);

        return $absolute ? $jumpTo->getAbsoluteUrl($params) : $jumpTo->getFrontendUrl($params);
    }

    /**
     * Generates the replacement string.
     *
     * @param FaqModel $faq
     * @param string   $key
     * @param string   $url
     *
     * @return string|false
     */
    private function generateReplacement(FaqModel $faq, string $key, string $url)
    {
        switch ($key) {
            case 'faq':
                return sprintf(
                    '<a href="%s" title="%s">%s</a>',
                    $url,
                    StringUtil::specialchars($faq->question),
                    $faq->question
                );

            case 'faq_open':
                return sprintf(
                    '<a href="%s" title="%s">',
                    $url,
                    StringUtil::specialchars($faq->question)
                );

            case 'faq_url':
                return $url;

            case 'faq_title':
                return StringUtil::specialchars($faq->question);
        }

        return false;
    }
}
