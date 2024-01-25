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

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\NewsModel;
use Contao\StringUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal
 */
#[AsHook('replaceInsertTags')]
class InsertTagsListener
{
    private const SUPPORTED_TAGS = [
        'news',
        'news_open',
        'news_url',
        'news_title',
        'news_teaser',
    ];

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly ContentUrlGenerator $urlGenerator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(string $tag, bool $useCache, mixed $cacheValue, array $flags): string|false
    {
        $elements = explode('::', $tag);
        $key = strtolower($elements[0]);

        if ('news_feed' === $key) {
            $this->logger->warning('The "news_feed" insert tag has been removed in Contao 5.0. Use "link_url" instead.');

            return false;
        }

        if (\in_array($key, self::SUPPORTED_TAGS, true)) {
            return $this->replaceNewsInsertTags($key, $elements[1], [...$flags, ...\array_slice($elements, 2)]);
        }

        return false;
    }

    private function replaceNewsInsertTags(string $insertTag, string $idOrAlias, array $arguments): string
    {
        $this->framework->initialize();

        $adapter = $this->framework->getAdapter(NewsModel::class);

        if (!$model = $adapter->findByIdOrAlias($idOrAlias)) {
            return '';
        }

        return match ($insertTag) {
            'news' => sprintf(
                '<a href="%s" title="%s"%s>%s</a>',
                $this->urlGenerator->generate($model, [], \in_array('absolute', $arguments, true) ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH),
                StringUtil::specialcharsAttribute($model->headline),
                \in_array('blank', $arguments, true) ? ' target="_blank" rel="noreferrer noopener"' : '',
                $model->headline,
            ),
            'news_open' => sprintf(
                '<a href="%s" title="%s"%s>',
                $this->urlGenerator->generate($model, [], \in_array('absolute', $arguments, true) ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH),
                StringUtil::specialcharsAttribute($model->headline),
                \in_array('blank', $arguments, true) ? ' target="_blank" rel="noreferrer noopener"' : '',
            ),
            'news_url' => $this->urlGenerator->generate($model, [], \in_array('absolute', $arguments, true) ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH),
            'news_title' => StringUtil::specialcharsAttribute($model->headline),
            'news_teaser' => $model->teaser,
            default => '',
        };
    }
}
