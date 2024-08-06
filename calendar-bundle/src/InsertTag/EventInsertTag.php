<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\InsertTag;

use Contao\CalendarEventsModel;
use Contao\CalendarFeedModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\Exception\InvalidInsertTagException;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\Resolver\InsertTagResolverNestedResolvedInterface;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\StringUtil;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal
 */
#[AsInsertTag('event')]
#[AsInsertTag('event_open')]
#[AsInsertTag('event_url')]
#[AsInsertTag('event_title')]
#[AsInsertTag('event_teaser')]
#[AsInsertTag('calendar_feed')]
class EventInsertTag implements InsertTagResolverNestedResolvedInterface
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly ContentUrlGenerator $urlGenerator,
    ) {
    }

    public function __invoke(ResolvedInsertTag $insertTag): InsertTagResult
    {
        if ('calendar_feed' === $insertTag->getName()) {
            return $this->replaceCalendarFeedInsertTag($insertTag->getParameters()->get(0));
        }

        return $this->replaceEventInsertTag(
            $insertTag->getName(),
            $insertTag->getParameters()->get(0),
            \array_slice($insertTag->getParameters()->all(), 1),
        );
    }

    private function replaceCalendarFeedInsertTag(string $feedId): InsertTagResult
    {
        $this->framework->initialize();

        $adapter = $this->framework->getAdapter(CalendarFeedModel::class);

        if (!$feed = $adapter->findById($feedId)) {
            return new InsertTagResult('');
        }

        return new InsertTagResult(\sprintf('%sshare/%s.xml', $feed->feedBase, $feed->alias), OutputType::url);
    }

    private function replaceEventInsertTag(string $insertTag, string $idOrAlias, array $arguments): InsertTagResult
    {
        $this->framework->initialize();

        $adapter = $this->framework->getAdapter(CalendarEventsModel::class);

        if (!$model = $adapter->findByIdOrAlias($idOrAlias)) {
            return new InsertTagResult('');
        }

        $generateUrl = fn () => $this->urlGenerator->generate(
            $model,
            [],
            \in_array('absolute', $arguments, true) ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH,
        );

        return match ($insertTag) {
            'event' => new InsertTagResult(
                \sprintf(
                    '<a href="%s" title="%s"%s>%s</a>',
                    StringUtil::specialcharsAttribute($generateUrl()),
                    StringUtil::specialcharsAttribute($model->title),
                    \in_array('blank', $arguments, true) ? ' target="_blank" rel="noreferrer noopener"' : '',
                    $model->title,
                ),
                OutputType::html,
            ),
            'event_open' => new InsertTagResult(
                \sprintf(
                    '<a href="%s" title="%s"%s>',
                    StringUtil::specialcharsAttribute($generateUrl()),
                    StringUtil::specialcharsAttribute($model->title),
                    \in_array('blank', $arguments, true) ? ' target="_blank" rel="noreferrer noopener"' : '',
                ),
                OutputType::html,
            ),
            'event_url' => new InsertTagResult(
                StringUtil::specialcharsAttribute($generateUrl()),
                OutputType::url,
            ),
            'event_title' => new InsertTagResult($model->title),
            'event_teaser' => new InsertTagResult($model->teaser ?? '', OutputType::html),
            default => throw new InvalidInsertTagException(),
        };
    }
}
