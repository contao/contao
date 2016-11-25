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
use Contao\StringUtil;

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
     * @var array
     */
    private $supportedTags = [
        'event',
        'event_open',
        'event_url',
        'event_title',
        'event_teaser',
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
     * Replaces calendar insert tags.
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
            return $this->replaceFeedInsertTag($elements[1]);
        }

        if (in_array($key, $this->supportedTags, true)) {
            return $this->replaceEventInsertTag($key, $elements[1]);
        }

        return false;
    }

    /**
     * Replaces the calendar feed insert tag.
     *
     * @param int $feedId
     *
     * @return string
     */
    private function replaceFeedInsertTag($feedId)
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
     *
     * @return string
     */
    private function replaceEventInsertTag($insertTag, $idOrAlias)
    {
        $this->framework->initialize();

        /** @var CalendarEventsModel $adapter */
        $adapter = $this->framework->getAdapter(CalendarEventsModel::class);

        if (null === ($event = $adapter->findByIdOrAlias($idOrAlias))) {
            return '';
        }

        return $this->generateReplacement($event, $insertTag);
    }

    /**
     * Generates an event URL.
     *
     * @param CalendarEventsModel $event
     *
     * @return string
     */
    private function generateEventUrl(CalendarEventsModel $event)
    {
        if ('external' === $event->source) {
            return $event->url;
        }

        if ('internal' === $event->source) {
            return $this->generatePageUrl($event);
        }

        if ('article' === $event->source) {
            return $this->generateArticleUrl($event);
        }

        return $this->generateEventReaderUrl($event);
    }

    /**
     * Generates the URL to a page.
     *
     * @param CalendarEventsModel $event
     *
     * @return string
     */
    private function generatePageUrl(CalendarEventsModel $event)
    {
        /** @var PageModel $targetPage */
        if (!(($targetPage = $event->getRelated('jumpTo')) instanceof PageModel)) {
            return '';
        }

        return $targetPage->getFrontendUrl();
    }

    /**
     * Generates the URL to an article.
     *
     * @param CalendarEventsModel $event
     *
     * @return string
     */
    private function generateArticleUrl(CalendarEventsModel $event)
    {
        /** @var PageModel $targetPage */
        if (!(($article = $event->getRelated('articleId')) instanceof ArticleModel)
            || !(($targetPage = $article->getRelated('pid')) instanceof PageModel)
        ) {
            return '';
        }

        return $targetPage->getFrontendUrl('/articles/'.($article->alias ?: $article->id));
    }

    /**
     * Generates URL to an event.
     *
     * @param CalendarEventsModel $event
     *
     * @return string
     */
    private function generateEventReaderUrl(CalendarEventsModel $event)
    {
        /** @var PageModel $targetPage */
        if (!(($calendar = $event->getRelated('pid')) instanceof CalendarModel)
            || !(($targetPage = $calendar->getRelated('jumpTo')) instanceof PageModel)
        ) {
            return '';
        }

        /** @var Config $config */
        $config = $this->framework->getAdapter(Config::class);

        return $targetPage->getFrontendUrl(
            ($config->get('useAutoItem') ? '/' : '/events/').($event->alias ?: $event->id)
        );
    }

    /**
     * Generates the replacement string.
     *
     * @param CalendarEventsModel $event
     * @param string              $insertTag
     *
     * @return string
     */
    private function generateReplacement(CalendarEventsModel $event, $insertTag)
    {
        switch ($insertTag) {
            case 'event':
                return sprintf(
                    '<a href="%s" title="%s">%s</a>',
                    $this->generateEventUrl($event),
                    StringUtil::specialchars($event->title),
                    $event->title
                );

            case 'event_open':
                return sprintf(
                    '<a href="%s" title="%s">',
                    $this->generateEventUrl($event),
                    StringUtil::specialchars($event->title)
                );

            case 'event_url':
                return $this->generateEventUrl($event);

            case 'event_title':
                return StringUtil::specialchars($event->title);

            case 'event_teaser':
                return StringUtil::toHtml5($event->teaser);
        }

        return '';
    }
}
