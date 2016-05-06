<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
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

        if ('news_feed' === $key) {
            return $this->replaceFeedInsertTag((int) $elements[1]);
        }

        if (in_array($key, ['news', 'news_open', 'news_url', 'news_title', 'news_teaser'], true)) {
            $this->replaceNewsInsertTags($key, $elements[1]);
        }

        return false;
    }

    /**
     * Replaces insert tag for news feed.
     *
     * @param int $feedId
     *
     * @return string
     */
    private function replaceFeedInsertTag($feedId)
    {
        /** @var NewsFeedModel $feed */
        $feed = $this->framework
            ->getAdapter('Contao\NewsFeedModel')
            ->findByPk($feedId)
        ;

        if (null === $feed) {
            return '';
        }

        return $feed->feedBase . 'share/' . $feed->alias . '.xml';
    }

    /**
     * Replaces news-related insert tags.
     *
     * @param string $insertTag
     * @param string $idOrAlias
     *
     * @return string
     */
    private function replaceNewsInsertTags($insertTag, $idOrAlias)
    {
        $this->framework->initialize();

        /** @var NewsModel $news */
        $news = $this->framework
            ->getAdapter('Contao\NewsModel')
            ->findByIdOrAlias($idOrAlias)
        ;

        if (null === $news) {
            return '';
        }

        switch ($insertTag) {
            case 'news':
                return sprintf(
                    '<a href="%s" title="%s">%s</a>',
                    $this->generateNewsUrl($news),
                    specialchars($news->headline),
                    $news->headline
                );

            case 'news_open':
                return sprintf(
                    '<a href="%s" title="%s">',
                    $this->generateNewsUrl($news),
                    specialchars($news->headline)
                );

            case 'news_url':
                return $this->generateNewsUrl($news);

            case 'news_title':
                return specialchars($news->headline);

            case 'news_teaser':
                return \StringUtil::toHtml5($news->teaser);
        }

        return '';
    }

    /**
     * Generate URL for an news item.
     *
     * @param NewsModel $news
     *
     * @return string|false
     */
    private function generateNewsUrl(NewsModel $news)
    {
        if ('external' === $news->source) {
            return $news->url;
        }

        if ('internal' === $news->source) {
            return $this->generateNewsPageUrl($news);
        }

        if ('article' === $news->source) {
            return $this->generateNewsArticleUrl($news);
        }

        return $this->generateNewsArchiveUrl($news);
    }

    /**
     * Generates URL to page for given news.
     *
     * @param NewsModel $news
     *
     * @return string
     */
    private function generateNewsPageUrl(NewsModel $news)
    {
        /** @var PageModel $targetPage */
        if (($targetPage = $news->getRelated('jumpTo')) instanceof PageModel) {
            return $targetPage->getFrontendUrl();
        }

        return '';
    }

    /**
     * Generates URL to article for given news item.
     *
     * @param NewsModel $news
     *
     * @return string
     */
    private function generateNewsArticleUrl(NewsModel $news)
    {
        /** @var PageModel $targetPage */
        if (($article = $news->getRelated('articleId')) instanceof ArticleModel
            && ($targetPage = $article->getRelated('pid')) instanceof PageModel
        ) {
            return $targetPage->getFrontendUrl('/articles/' . ($article->alias ?: $article->id));
        }

        return '';
    }

    /**
     * Generates URL for archive of given news item.
     *
     * @param NewsModel $news
     *
     * @return string
     */
    private function generateNewsArchiveUrl(NewsModel $news)
    {
        /** @var PageModel $targetPage */
        if (($archive = $news->getRelated('pid')) instanceof NewsArchiveModel
            && ($targetPage = $archive->getRelated('jumpTo')) instanceof PageModel
        ) {
            /** @var Config $config */
            $config = $this->framework->getAdapter('Contao\Config');

            return $targetPage->getFrontendUrl(
                ($config->get('useAutoItem') ? '/' : '/items/') . ($news->alias ?: $news->id)
            );
        }

        return '';
    }
}
