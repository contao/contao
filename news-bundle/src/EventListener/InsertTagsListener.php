<?php

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

/**
 * Handles insert tags for news.
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
     * @var array
     */
    private $supportedTags = [
        'news',
        'news_open',
        'news_url',
        'news_title',
        'news_teaser',
    ];

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
     * Replaces news insert tags.
     *
     * @param string $tag
     *
     * @return string|false
     */
    public function onReplaceInsertTags($tag)
    {
        $elements = explode('::', $tag);
        $key = strtolower($elements[0]);

        if ('news_feed' === $key) {
            return $this->replaceFeedInsertTag($elements[1]);
        }

        if (\in_array($key, $this->supportedTags, true)) {
            return $this->replaceNewsInsertTags($key, $elements[1]);
        }

        return false;
    }

    /**
     * Replaces the news feed insert tag.
     *
     * @param int $feedId
     *
     * @return string
     */
    private function replaceFeedInsertTag($feedId)
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
     *
     * @return string
     */
    private function replaceNewsInsertTags($insertTag, $idOrAlias)
    {
        $this->framework->initialize();

        /** @var NewsModel $adapter */
        $adapter = $this->framework->getAdapter(NewsModel::class);

        if (null === ($news = $adapter->findByIdOrAlias($idOrAlias))) {
            return '';
        }

        return $this->generateReplacement($news, $insertTag);
    }

    /**
     * Generates the replacement string.
     *
     * @param NewsModel $news
     * @param string    $insertTag
     *
     * @return string
     */
    private function generateReplacement(NewsModel $news, $insertTag)
    {
        /** @var News $adapter */
        $adapter = $this->framework->getAdapter(News::class);

        switch ($insertTag) {
            case 'news':
                return sprintf(
                    '<a href="%s" title="%s">%s</a>',
                    $adapter->generateNewsUrl($news),
                    StringUtil::specialchars($news->headline),
                    $news->headline
                );

            case 'news_open':
                return sprintf(
                    '<a href="%s" title="%s">',
                    $adapter->generateNewsUrl($news),
                    StringUtil::specialchars($news->headline)
                );

            case 'news_url':
                return $adapter->generateNewsUrl($news);

            case 'news_title':
                return StringUtil::specialchars($news->headline);

            case 'news_teaser':
                return StringUtil::toHtml5($news->teaser);
        }

        return '';
    }
}
