<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Runtime;

use Contao\CoreBundle\Routing\ResponseContext\HtmlBodyBag;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\Twig\ResponseContext\DocumentLocation;
use Twig\Extension\RuntimeExtensionInterface;

final class HtmlDocumentRuntime implements RuntimeExtensionInterface
{
    public function __construct(private readonly ResponseContextAccessor $responseContextAccessor)
    {
    }

    /**
     * @internal
     */
    public function add(string $content, DocumentLocation $location, string|null $identifier = null): void
    {
        $responseContext = $this->responseContextAccessor->getResponseContext();

        if ($responseContext && DocumentLocation::endOfBody === $location && $responseContext->has(HtmlBodyBag::class)) {
            $responseContext->get(HtmlBodyBag::class)->add($content, $identifier);

            return;
        }

        if ($responseContext && DocumentLocation::endOfBody !== $location && $responseContext->has(HtmlHeadBag::class)) {
            $head = $responseContext->get(HtmlHeadBag::class);

            if (DocumentLocation::head === $location) {
                $head->addRawToHead($content, $identifier);
            } else {
                $head->addRawToStylesheets($content, $identifier);
            }

            return;
        }

        $this->addLegacyContent($identifier, $content, $location);
    }

    private function addLegacyContent(string|null $identifier, string $content, DocumentLocation $location): void
    {
        trigger_deprecation(
            'contao/core-bundle',
            '6.1',
            'Using the Twig "add" tag without the corresponding response context bag is deprecated and will no longer work in Contao 7.',
        );

        $global = match ($location) {
            DocumentLocation::head => 'TL_HEAD',
            DocumentLocation::stylesheets => 'TL_STYLE_SHEETS',
            DocumentLocation::endOfBody => 'TL_BODY',
        };

        if (null === $identifier) {
            $GLOBALS[$global][] = $content;
        } else {
            $GLOBALS[$global][$identifier] = $content;
        }
    }
}
