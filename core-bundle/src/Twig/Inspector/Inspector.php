<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Inspector;

use Psr\Cache\CacheItemPoolInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * @experimental
 */
class Inspector
{
    /**
     * @internal
     */
    public const CACHE_KEY = 'contao.twig.inspector';

    public function __construct(
        private readonly Environment $twig,
        private readonly CacheItemPoolInterface $cachePool,
    ) {
    }

    public function inspectTemplate(string $name): TemplateInformation
    {
        try {
            $blocks = $this->twig->load($name)->getBlockNames();
            $source = $this->twig->getLoader()->getSourceContext($name);
        } catch (LoaderError|RuntimeError|SyntaxError $e) {
            throw new InspectionException($name, $e);
        }

        $currentTemplateName = $name;
        $slots = [];

        // Accumulate data for all set parents as well
        do {
            $data = $this->getData($currentTemplateName);

            $slots = [...$slots, ...$data['slots']];
            $currentTemplateName = $data['parent'] ?? false;
        } while ($currentTemplateName);

        sort($blocks);
        sort($slots);

        return new TemplateInformation(
            $source,
            $blocks,
            $slots,
        );
    }

    private function getData(string $templateName)
    {
        return $this->cachePool->getItem(self::CACHE_KEY)->get()[$templateName] ??
            throw new InspectionException($templateName, reason: 'No recorded information was found. Please clear the Twig template cache to make sure templates are recompiled.');
    }
}
