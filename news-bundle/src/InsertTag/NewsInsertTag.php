<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\InsertTag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\Exception\ForwardPageNotFoundException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\Resolver\InsertTagResolverNestedResolvedInterface;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\NewsModel;
use Contao\StringUtil;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsInsertTag('news')]
#[AsInsertTag('news_open')]
#[AsInsertTag('news_url')]
#[AsInsertTag('news_title')]
#[AsInsertTag('news_teaser')]
class NewsInsertTag implements InsertTagResolverNestedResolvedInterface
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly ContentUrlGenerator $urlGenerator,
    ) {
    }

    public function __invoke(ResolvedInsertTag $insertTag): InsertTagResult
    {
        $this->framework->initialize();

        $arguments = \array_slice($insertTag->getParameters()->all(), 1);
        $adapter = $this->framework->getAdapter(NewsModel::class);

        if (!$model = $adapter->findByIdOrAlias($insertTag->getParameters()->get(0))) {
            return new InsertTagResult('');
        }

        return match ($insertTag->getName()) {
            'news' => new InsertTagResult(
                \sprintf(
                    '<a href="%s" title="%s"%s>%s</a>',
                    StringUtil::specialcharsAttribute($this->generateNewsUrl($model, $arguments)),
                    StringUtil::specialcharsAttribute($model->headline),
                    \in_array('blank', $arguments, true) ? ' target="_blank" rel="noreferrer noopener"' : '',
                    $model->headline,
                ),
                OutputType::html,
            ),
            'news_open' => new InsertTagResult(
                \sprintf(
                    '<a href="%s" title="%s"%s>',
                    StringUtil::specialcharsAttribute($this->generateNewsUrl($model, $arguments)),
                    StringUtil::specialcharsAttribute($model->headline),
                    \in_array('blank', $arguments, true) ? ' target="_blank" rel="noreferrer noopener"' : '',
                ),
                OutputType::html,
            ),
            'news_url' => new InsertTagResult($this->generateNewsUrl($model, $arguments), OutputType::url),
            'news_title' => new InsertTagResult($model->headline),
            'news_teaser' => new InsertTagResult($model->teaser ?? '', OutputType::html),
            default => new InsertTagResult(''),
        };
    }

    private function generateNewsUrl(NewsModel $model, array $arguments): string
    {
        try {
            return $this->urlGenerator->generate($model, [], \in_array('absolute', $arguments, true) ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH);
        } catch (ForwardPageNotFoundException) {
            return '';
        }
    }
}
