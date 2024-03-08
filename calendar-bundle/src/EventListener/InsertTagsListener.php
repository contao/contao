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
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\StringUtil;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal
 */
#[AsHook('replaceInsertTags')]
class InsertTagsListener
{
    private const SUPPORTED_TAGS = [
        'event',
        'event_open',
        'event_url',
        'event_title',
        'event_teaser',
    ];

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly ContentUrlGenerator $urlGenerator,
    ) {
    }

    public function __invoke(string $tag, bool $useCache, mixed $cacheValue, array $flags): string|false
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

        if (!$feed = $adapter->findById($feedId)) {
            return '';
        }

        return sprintf('%sshare/%s.xml', $feed->feedBase, $feed->alias);
    }

    private function replaceEventInsertTag(string $insertTag, string $idOrAlias, array $arguments): string
    {
        $this->framework->initialize();

        $adapter = $this->framework->getAdapter(CalendarEventsModel::class);

        if (!$model = $adapter->findByIdOrAlias($idOrAlias)) {
            return '';
        }

        return match ($insertTag) {
            'event' => sprintf(
                '<a href="%s" title="%s"%s>%s</a>',
                $this->urlGenerator->generate($model, [], \in_array('absolute', $arguments, true) ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH),
                StringUtil::specialcharsAttribute($model->title),
                \in_array('blank', $arguments, true) ? ' target="_blank" rel="noreferrer noopener"' : '',
                $model->title,
            ),
            'event_open' => sprintf(
                '<a href="%s" title="%s"%s>',
                $this->urlGenerator->generate($model, [], \in_array('absolute', $arguments, true) ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH),
                StringUtil::specialcharsAttribute($model->title),
                \in_array('blank', $arguments, true) ? ' target="_blank" rel="noreferrer noopener"' : '',
            ),
            'event_url' => $this->urlGenerator->generate($model, [], \in_array('absolute', $arguments, true) ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH),
            'event_title' => StringUtil::specialcharsAttribute($model->title),
            'event_teaser' => $model->teaser,
            default => '',
        };
    }
}
