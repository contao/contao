<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\InsertTag\Resolver;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsInsertTag('trans')]
class TranslationInsertTag implements InsertTagResolverNestedResolvedInterface
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function __invoke(ResolvedInsertTag $insertTag): InsertTagResult
    {
        $parameters = \array_slice($insertTag->getParameters()->all(), 2);

        if (1 === \count($parameters) && str_contains($parameters[0], ':')) {
            trigger_deprecation('contao/core-bundle', '5.3', 'Passing parameters to the trans insert tag separated by a single colon has has been deprecated and will no longer work in Contao 6. Use double colons instead.');
            $parameters = explode(':', $parameters[0]);
        }

        return new InsertTagResult(
            $this->translator->trans($insertTag->getParameters()->get(0), $parameters, $insertTag->getParameters()->get(1)),
        );
    }
}
