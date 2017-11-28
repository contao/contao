<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CalendarBundle\EventListener;

use Contao\CalendarEventsModel;
use Contao\CalendarFeedModel;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Events;
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
        'event',
        'event_open',
        'event_url',
        'event_title',
        'event_teaser',
    ];

    /**
     * @param ContaoFrameworkInterface $framework
     */
    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    /**
     * Replaces calendar insert tags.
     *
     * @param string $tag
     * @param bool   $useCache
     * @param mixed  $cacheValue
     * @param array  $flags
     *
     * @return string|false
     */
    public function onReplaceInsertTags(string $tag, bool $useCache = false, $cacheValue = null, array $flags = [])
    {
        $elements = explode('::', $tag);
        $key = strtolower($elements[0]);

        if ('calendar_feed' === $key) {
            return $this->replaceFeedInsertTag($elements[1]);
        }

        if (\in_array($key, self::$supportedTags, true)) {
            return $this->replaceEventInsertTag($key, $elements[1], $flags);
        }

        return false;
    }

    /**
     * Replaces the calendar feed insert tag.
     *
     * @param string $feedId
     *
     * @return string
     */
    private function replaceFeedInsertTag(string $feedId): string
    {
        $this->framework->initialize();

        /** @var CalendarFeedModel $adapter */
        $adapter = $this->framework->getAdapter(CalendarFeedModel::class);

        if (null === ($feed = $adapter->findByPk($feedId))) {
            return '';
        }

        return sprintf('%sshare/%s.xml', $feed->feedBase, $feed->alias);
    }

    /**
     * Replaces an event-related insert tag.
     *
     * @param string $insertTag
     * @param string $idOrAlias
     * @param array  $flags
     *
     * @return string
     */
    private function replaceEventInsertTag(string $insertTag, string $idOrAlias, array $flags): string
    {
        $this->framework->initialize();

        /** @var CalendarEventsModel $adapter */
        $adapter = $this->framework->getAdapter(CalendarEventsModel::class);

        if (null === ($event = $adapter->findByIdOrAlias($idOrAlias))) {
            return '';
        }

        return $this->generateReplacement($event, $insertTag, $flags);
    }

    /**
     * Generates the replacement string.
     *
     * @param CalendarEventsModel $event
     * @param string              $insertTag
     * @param array               $flags
     *
     * @return string
     */
    private function generateReplacement(CalendarEventsModel $event, string $insertTag, array $flags): string
    {
        /** @var Events $adapter */
        $adapter = $this->framework->getAdapter(Events::class);

        switch ($insertTag) {
            case 'event':
                return sprintf(
                    '<a href="%s" title="%s">%s</a>',
                    $adapter->generateEventUrl($event, \in_array('absolute', $flags, true)),
                    StringUtil::specialchars($event->title),
                    $event->title
                );

            case 'event_open':
                return sprintf(
                    '<a href="%s" title="%s">',
                    $adapter->generateEventUrl($event, \in_array('absolute', $flags, true)),
                    StringUtil::specialchars($event->title)
                );

            case 'event_url':
                return $adapter->generateEventUrl($event, \in_array('absolute', $flags, true));

            case 'event_title':
                return StringUtil::specialchars($event->title);

            case 'event_teaser':
                return StringUtil::toHtml5($event->teaser);
        }

        return '';
    }
}
