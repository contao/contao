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
use Contao\NewsModel;
use Contao\StringUtil;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
class InsertTagsListener
{
    private const SUPPORTED_TAGS = [
        'news',
        'news_open',
        'news_url',
        'news_title',
        'news_teaser',
    ];

    public function __construct(private ContaoFramework $framework, private LoggerInterface $logger)
    {
    }

    public function __invoke(string $tag, bool $useCache, $cacheValue, array $flags): string|false
    {
        $elements = explode('::', $tag);
        $key = strtolower($elements[0]);

        if ('news_feed' === $key) {
            $this->logger->warning('The "news_feed" insert tag has been removed in Contao 5.0. Use "link_url" instead.');

            return false;
        }

        if (\in_array($key, self::SUPPORTED_TAGS, true)) {
            return $this->replaceNewsInsertTags($key, $elements[1], array_merge($flags, \array_slice($elements, 2)));
        }

        return false;
    }

    private function replaceNewsInsertTags(string $insertTag, string $idOrAlias, array $arguments): string
    {
        $this->framework->initialize();

        $adapter = $this->framework->getAdapter(NewsModel::class);

        if (null === ($model = $adapter->findByIdOrAlias($idOrAlias))) {
            return '';
        }

        $news = $this->framework->getAdapter(News::class);

        switch ($insertTag) {
            case 'news':
                return sprintf(
                    '<a href="%s" title="%s"%s>%s</a>',
                    $news->generateNewsUrl($model, false, \in_array('absolute', $arguments, true)) ?: './',
                    StringUtil::specialcharsAttribute($model->headline),
                    \in_array('blank', $arguments, true) ? ' target="_blank" rel="noreferrer noopener"' : '',
                    $model->headline
                );

            case 'news_open':
                return sprintf(
                    '<a href="%s" title="%s"%s>',
                    $news->generateNewsUrl($model, false, \in_array('absolute', $arguments, true)) ?: './',
                    StringUtil::specialcharsAttribute($model->headline),
                    \in_array('blank', $arguments, true) ? ' target="_blank" rel="noreferrer noopener"' : ''
                );

            case 'news_url':
                return $news->generateNewsUrl($model, false, \in_array('absolute', $arguments, true)) ?: './';

            case 'news_title':
                return StringUtil::specialcharsAttribute($model->headline);

            case 'news_teaser':
                return $model->teaser;
        }

        return '';
    }
}
