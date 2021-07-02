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
    public function __invoke(string $tag, bool $useCache, $cacheValue, array $flags)
    {
        $elements = explode('::', $tag);
        $key = strtolower($elements[0]);

        if ('calendar_feed' === $key) {
            return $this->replaceCalendarFeedInsertTag($elements[1]);
        }

        if (\in_array($key, self::SUPPORTED_TAGS, true)) {
            return $this->replaceEventInsertTag($key, $elements[1], array_merge($flags, \array_slice($elements, 2)));
        }

        return false;
    }

    private function replaceCalendarFeedInsertTag(string $feedId): string
    {
        $this->framework->initialize();

        /** @var CalendarFeedModel $adapter */
        $adapter = $this->framework->getAdapter(CalendarFeedModel::class);

        if (null === ($feed = $adapter->findByPk($feedId))) {
            return '';
        }

        return sprintf('%sshare/%s.xml', $feed->feedBase, $feed->alias);
    }

    private function replaceEventInsertTag(string $insertTag, string $idOrAlias, array $arguments): string
    {
        $this->framework->initialize();

        /** @var CalendarEventsModel $adapter */
        $adapter = $this->framework->getAdapter(CalendarEventsModel::class);

        if (null === ($model = $adapter->findByIdOrAlias($idOrAlias))) {
            return '';
        }

        /** @var Events $events */
        $events = $this->framework->getAdapter(Events::class);

        switch ($insertTag) {
            case 'event':
                return sprintf(
                    '<a href="%s" title="%s">%s</a>',
                    $events->generateEventUrl($model, \in_array('absolute', $arguments, true)) ?: './',
                    StringUtil::specialchars($model->title),
                    $model->title
                );

            case 'event_open':
                return sprintf(
                    '<a href="%s" title="%s">',
                    $events->generateEventUrl($model, \in_array('absolute', $arguments, true)) ?: './',
                    StringUtil::specialchars($model->title)
                );

            case 'event_url':
                return $events->generateEventUrl($model, \in_array('absolute', $arguments, true)) ?: './';

            case 'event_title':
                return StringUtil::specialchars($model->title);

            case 'event_teaser':
                return StringUtil::toHtml5($model->teaser);
        }

        return '';
    }
}
