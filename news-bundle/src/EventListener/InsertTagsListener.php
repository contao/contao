<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\News;
use Contao\NewsFeedModel;
use Contao\NewsModel;
use Contao\StringUtil;

class InsertTagsListener
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * @return string|false
     */
    public function onReplaceInsertTags(string $tag, bool $useCache, $cacheValue, array $flags)
    {
        $elements = explode('::', $tag);
        $key = strtolower($elements[0]);

        if ('news_feed' === $key) {
            return $this->replaceNewsFeedInsertTag($elements[1]);
        }

        static $supportedTags = [
            'news',
            'news_open',
            'news_url',
            'news_title',
            'news_teaser',
        ];

        if (\in_array($key, $supportedTags, true)) {
            return $this->replaceNewsInsertTags($key, $elements[1], $flags);
        }

        return false;
    }

    private function replaceNewsFeedInsertTag(string $feedId): string
    {
        $this->framework->initialize();

        /** @var NewsFeedModel $adapter */
        $adapter = $this->framework->getAdapter(NewsFeedModel::class);

        if (null === ($feed = $adapter->findByPk($feedId))) {
            return '';
        }

        return sprintf('%sshare/%s.xml', $feed->feedBase, $feed->alias);
    }

    private function replaceNewsInsertTags(string $insertTag, string $idOrAlias, array $flags): string
    {
        $this->framework->initialize();

        /** @var NewsModel $adapter */
        $adapter = $this->framework->getAdapter(NewsModel::class);

        if (null === ($model = $adapter->findByIdOrAlias($idOrAlias))) {
            return '';
        }

        /** @var News $news */
        $news = $this->framework->getAdapter(News::class);

        switch ($insertTag) {
            case 'news':
                return sprintf(
                    '<a href="%s" title="%s">%s</a>',
                    $news->generateNewsUrl($model, false, \in_array('absolute', $flags, true)),
                    StringUtil::specialchars($model->headline),
                    $model->headline
                );

            case 'news_open':
                return sprintf(
                    '<a href="%s" title="%s">',
                    $news->generateNewsUrl($model, false, \in_array('absolute', $flags, true)),
                    StringUtil::specialchars($model->headline)
                );

            case 'news_url':
                return $news->generateNewsUrl($model, false, \in_array('absolute', $flags, true));

            case 'news_title':
                return StringUtil::specialchars($model->headline);

            case 'news_teaser':
                return StringUtil::toHtml5($model->teaser);
        }

        return '';
    }
}
