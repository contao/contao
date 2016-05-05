<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CalendarBundle\EventListener;

use Contao\ArticleModel;
use Contao\CalendarEventsModel;
use Contao\CalendarFeedModel;
use Contao\CalendarModel;
use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\PageModel;

/**
 * Handles insert tags for calendars.
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
        $key = strtolower($elements[0]);

        if ('calendar_feed' === $key) {
            return $this->replaceFeedInsertTag((int) $elements[1]);
        }

        if (in_array($key, ['event', 'event_open', 'event_url', 'event_title', 'event_teaser'], true)) {
            $this->replaceEventInsertTags($key, $elements[1]);
        }

        return false;
    }

    /**
     * Replaces insert tag for calendar feed.
     *
     * @param int $feedId
     *
     * @return string
     */
    private function replaceFeedInsertTag($feedId)
    {
        /** @var CalendarFeedModel $feed */
        $feed = $this->framework
            ->getAdapter('Contao\CalendarFeedModel')
            ->findByPk($feedId)
        ;

        if (null === $feed) {
            return '';
        }

        return $feed->feedBase.'share/'.$feed->alias.'.xml';
    }

    /**
     * Replaces event-related insert tags.
     *
     * @param string $insertTag
     * @param string $idOrAlias
     *
     * @return string
     */
    private function replaceEventInsertTags($insertTag, $idOrAlias)
    {
        $this->framework->initialize();

        /** @var CalendarEventsModel $event */
        $event = $this->framework
            ->getAdapter('Contao\CalendarEventsModel')
            ->findByIdOrAlias($idOrAlias)
        ;

        if (null === $event) {
            return '';
        }

        switch ($insertTag) {
            case 'event':
                return sprintf(
                    '<a href="%s" title="%s">%s</a>',
                    $this->generateEventUrl($event),
                    specialchars($event->title),
                    $event->title
                );

            case 'event_open':
                return sprintf(
                    '<a href="%s" title="%s">',
                    $this->generateEventUrl($event),
                    specialchars($event->title)
                );

            case 'event_url':
                return $this->generateEventUrl($event);

            case 'event_title':
                return specialchars($event->title);

            case 'event_teaser':
                return \StringUtil::toHtml5($event->teaser);
        }

        return '';
    }

    /**
     * Generate URL for an calendar event.
     *
     * @param CalendarEventsModel $event
     *
     * @return string|false
     */
    private function generateEventUrl(CalendarEventsModel $event)
    {
        if ('external' === $event->source) {
            return $event->url;
        }

        if ('internal' === $event->source) {
            return $this->generateEventPageUrl($event);
        }

        if ('article' === $event->source) {
            return $this->generateEventArticleUrl($event);
        }

        return $this->generateEventCalendarUrl($event);
    }

    /**
     * Generates URL to page for given event.
     *
     * @param CalendarEventsModel $event
     *
     * @return string
     */
    private function generateEventPageUrl(CalendarEventsModel $event)
    {
        /** @var PageModel $targetPage */
        if (($targetPage = $event->getRelated('jumpTo')) instanceof PageModel) {
            return $targetPage->getFrontendUrl();
        }

        return '';
    }

    /**
     * Generates URL to article for given event.
     *
     * @param CalendarEventsModel $event
     *
     * @return string
     */
    private function generateEventArticleUrl(CalendarEventsModel $event)
    {
        /** @var PageModel $targetPage */
        if (($article = $event->getRelated('articleId')) instanceof ArticleModel
            && ($targetPage = $article->getRelated('pid')) instanceof PageModel
        ) {
            return $targetPage->getFrontendUrl('/articles/'.($article->alias ?: $article->id));
        }

        return '';
    }

    /**
     * Generates URL for calendar of given event.
     *
     * @param CalendarEventsModel $event
     *
     * @return string
     */
    private function generateEventCalendarUrl(CalendarEventsModel $event)
    {
        /** @var PageModel $targetPage */
        if (($calendar = $event->getRelated('pid')) instanceof CalendarModel
            && ($targetPage = $calendar->getRelated('jumpTo')) instanceof PageModel
        ) {
            /** @var Config $config */
            $config = $this->framework->getAdapter('Contao\Config');

            return $targetPage->getFrontendUrl(
                ($config->get('useAutoItem') ? '/' : '/events/').($event->alias ?: $event->id)
            );
        }

        return '';
    }
}
