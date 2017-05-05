<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\NewsBundle\EventListener;

use Contao\ArticleModel;
use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\NewsArchiveModel;
use Contao\NewsFeedModel;
use Contao\NewsModel;
use Contao\PageModel;
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

        if (in_array($key, $this->supportedTags, true)) {
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
     * Generates a news URL.
     *
     * @param NewsModel $news
     *
     * @return string
     */
    private function generateNewsUrl(NewsModel $news)
    {
        if ('external' === $news->source) {
            return $news->url;
        }

        if ('internal' === $news->source) {
            return $this->generatePageUrl($news);
        }

        if ('article' === $news->source) {
            return $this->generateArticleUrl($news);
        }

        return $this->generateNewsReaderUrl($news);
    }

    /**
     * Generates the URL to a page.
     *
     * @param NewsModel $news
     *
     * @return string
     */
    private function generatePageUrl(NewsModel $news)
    {
        /** @var PageModel $targetPage */
        if (!(($targetPage = $news->getRelated('jumpTo')) instanceof PageModel)) {
            return '';
        }

        return $targetPage->getFrontendUrl();
    }

    /**
     * Generates the URL to an article.
     *
     * @param NewsModel $news
     *
     * @return string
     */
    private function generateArticleUrl(NewsModel $news)
    {
        /** @var PageModel $targetPage */
        if (!(($article = $news->getRelated('articleId')) instanceof ArticleModel)
            || !(($targetPage = $article->getRelated('pid')) instanceof PageModel)
        ) {
            return '';
        }

        return $targetPage->getFrontendUrl('/articles/'.($article->alias ?: $article->id));
    }

    /**
     * Generates URL to a news item.
     *
     * @param NewsModel $news
     *
     * @return string
     */
    private function generateNewsReaderUrl(NewsModel $news)
    {
        /** @var PageModel $targetPage */
        if (!(($archive = $news->getRelated('pid')) instanceof NewsArchiveModel)
            || !(($targetPage = $archive->getRelated('jumpTo')) instanceof PageModel)
        ) {
            return '';
        }

        /** @var Config $config */
        $config = $this->framework->getAdapter(Config::class);

        return $targetPage->getFrontendUrl(
            ($config->get('useAutoItem') ? '/' : '/items/').($news->alias ?: $news->id)
        );
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
        switch ($insertTag) {
            case 'news':
                return sprintf(
                    '<a href="%s" title="%s">%s</a>',
                    $this->generateNewsUrl($news),
                    StringUtil::specialchars($news->headline),
                    $news->headline
                );

            case 'news_open':
                return sprintf(
                    '<a href="%s" title="%s">',
                    $this->generateNewsUrl($news),
                    StringUtil::specialchars($news->headline)
                );

            case 'news_url':
                return $this->generateNewsUrl($news);

            case 'news_title':
                return StringUtil::specialchars($news->headline);

            case 'news_teaser':
                return StringUtil::toHtml5($news->teaser);
        }

        return '';
    }
}
