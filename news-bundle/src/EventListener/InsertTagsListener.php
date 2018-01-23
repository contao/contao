<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\NewsBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\News;
use Contao\NewsFeedModel;
use Contao\NewsModel;
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
        'news',
        'news_open',
        'news_url',
        'news_title',
        'news_teaser',
    ];

    /**
     * @param ContaoFrameworkInterface $framework
     */
    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    /**
     * Replaces news insert tags.
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

        if ('news_feed' === $key) {
            return $this->replaceFeedInsertTag($elements[1]);
        }

        if (\in_array($key, self::$supportedTags, true)) {
            return $this->replaceNewsInsertTags($key, $elements[1], $flags);
        }

        return false;
    }

    /**
     * Replaces the news feed insert tag.
     *
     * @param string $feedId
     *
     * @return string
     */
    private function replaceFeedInsertTag(string $feedId): string
    {
        $this->framework->initialize();

        /** @var NewsFeedModel $adapter */
        $adapter = $this->framework->getAdapter(NewsFeedModel::class);

        if (null === ($feed = $adapter->findByPk($feedId))) {
            return '';
        }

        return sprintf('%sshare/%s.xml', $feed->feedBase, $feed->alias);
    }

    /**
     * Replaces a news-related insert tag.
     *
     * @param string $insertTag
     * @param string $idOrAlias
     * @param array  $flags
     *
     * @return string
     */
    private function replaceNewsInsertTags(string $insertTag, string $idOrAlias, array $flags): string
    {
        $this->framework->initialize();

        /** @var NewsModel $adapter */
        $adapter = $this->framework->getAdapter(NewsModel::class);

        if (null === ($news = $adapter->findByIdOrAlias($idOrAlias))) {
            return '';
        }

        return $this->generateReplacement($news, $insertTag, $flags);
    }

    /**
     * Generates the replacement string.
     *
     * @param NewsModel $news
     * @param string    $insertTag
     * @param array     $flags
     *
     * @return string
     */
    private function generateReplacement(NewsModel $news, string $insertTag, array $flags): string
    {
        /** @var News $adapter */
        $adapter = $this->framework->getAdapter(News::class);

        switch ($insertTag) {
            case 'news':
                return sprintf(
                    '<a href="%s" title="%s">%s</a>',
                    $adapter->generateNewsUrl($news, false, \in_array('absolute', $flags, true)),
                    StringUtil::specialchars($news->headline),
                    $news->headline
                );

            case 'news_open':
                return sprintf(
                    '<a href="%s" title="%s">',
                    $adapter->generateNewsUrl($news, false, \in_array('absolute', $flags, true)),
                    StringUtil::specialchars($news->headline)
                );

            case 'news_url':
                return $adapter->generateNewsUrl($news, false, \in_array('absolute', $flags, true));

            case 'news_title':
                return StringUtil::specialchars($news->headline);

            case 'news_teaser':
                return StringUtil::toHtml5($news->teaser);
        }

        return '';
    }
}
