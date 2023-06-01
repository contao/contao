<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\EventListener;

use Contao\CalendarEventsModel;
use Contao\CalendarFeedModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Events;
use Contao\StringUtil;

/**
 * @internal
 */
class InsertTagsListener
{
    private const SUPPORTED_TAGS = [
        'event',
        'event_open',
        'event_url',
        'event_title',
        'event_teaser',
    ];

    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function __invoke(string $tag, bool $useCache, $cacheValue, array $flags): string|false
    {
        $elements = explode('::', $tag);
        $key = strtolower($elements[0]);

        if ('calendar_feed' === $key) {
            return $this->replaceCalendarFeedInsertTag($elements[1]);
        }

        if (\in_array($key, self::SUPPORTED_TAGS, true)) {
            return $this->replaceEventInsertTag($key, $elements[1], [...$flags, ...\array_slice($elements, 2)]);
        }

        return false;
    }

    private function replaceCalendarFeedInsertTag(string $feedId): string
    {
        $this->framework->initialize();

        $adapter = $this->framework->getAdapter(CalendarFeedModel::class);

        if (null === ($feed = $adapter->findByPk($feedId))) {
            return '';
        }

        return sprintf('%sshare/%s.xml', $feed->feedBase, $feed->alias);
    }

    private function replaceEventInsertTag(string $insertTag, string $idOrAlias, array $arguments): string
    {
        $this->framework->initialize();

        $adapter = $this->framework->getAdapter(CalendarEventsModel::class);

        if (null === ($model = $adapter->findByIdOrAlias($idOrAlias))) {
            return '';
        }

        $events = $this->framework->getAdapter(Events::class);

        return match ($insertTag) {
            'event' => sprintf(
                '<a href="%s" title="%s"%s>%s</a>',
                $events->generateEventUrl($model, \in_array('absolute', $arguments, true)) ?: './',
                StringUtil::specialcharsAttribute($model->title),
                \in_array('blank', $arguments, true) ? ' target="_blank" rel="noreferrer noopener"' : '',
                $model->title
            ),
            'event_open' => sprintf(
                '<a href="%s" title="%s"%s>',
                $events->generateEventUrl($model, \in_array('absolute', $arguments, true)) ?: './',
                StringUtil::specialcharsAttribute($model->title),
                \in_array('blank', $arguments, true) ? ' target="_blank" rel="noreferrer noopener"' : ''
            ),
            'event_url' => $events->generateEventUrl($model, \in_array('absolute', $arguments, true)) ?: './',
            'event_title' => StringUtil::specialcharsAttribute($model->title),
            'event_teaser' => $model->teaser,
            default => '',
        };
    }
}
