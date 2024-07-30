<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\FaqBundle\InsertTag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\Exception\InvalidInsertTagException;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\Resolver\InsertTagResolverNestedResolvedInterface;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\FaqModel;
use Contao\StringUtil;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsInsertTag('faq')]
#[AsInsertTag('faq_open')]
#[AsInsertTag('faq_url')]
#[AsInsertTag('faq_title')]
class FaqInsertTag implements InsertTagResolverNestedResolvedInterface
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly ContentUrlGenerator $urlGenerator,
    ) {
    }

    public function __invoke(ResolvedInsertTag $insertTag): InsertTagResult
    {
        $this->framework->initialize();

        if (!$faq = $this->framework->getAdapter(FaqModel::class)->findByIdOrAlias($insertTag->getParameters()->get(0))) {
            return new InsertTagResult('');
        }

        $params = \array_slice($insertTag->getParameters()->all(), 1);
        $url = '';

        if ('faq_title' !== $insertTag->getName()) {
            $absolute = \in_array('absolute', $params, true);

            try {
                $url = $this->urlGenerator->generate($faq, [], $absolute ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH);
            } catch (ExceptionInterface) {
                return new InsertTagResult('');
            }
        }

        return $this->generateReplacement($faq, $insertTag->getName(), $url, \in_array('blank', $params, true));
    }

    private function generateReplacement(FaqModel $faq, string $key, string $url, bool $blank): InsertTagResult
    {
        return match ($key) {
            'faq' => new InsertTagResult(
                \sprintf(
                    '<a href="%s" title="%s"%s>%s</a>',
                    StringUtil::specialcharsAttribute($url ?: './'),
                    StringUtil::specialcharsAttribute($faq->question),
                    $blank ? ' target="_blank" rel="noreferrer noopener"' : '',
                    $faq->question,
                ),
                OutputType::html,
            ),
            'faq_open' => new InsertTagResult(
                \sprintf(
                    '<a href="%s" title="%s"%s>',
                    StringUtil::specialcharsAttribute($url ?: './'),
                    StringUtil::specialcharsAttribute($faq->question),
                    $blank ? ' target="_blank" rel="noreferrer noopener"' : '',
                ),
                OutputType::html,
            ),
            'faq_url' => new InsertTagResult($url ?: './', OutputType::url),
            'faq_title' => new InsertTagResult($faq->question, OutputType::text),
            default => throw new InvalidInsertTagException(),
        };
    }
}
