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
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\Twig\ResponseContext\DocumentLocation;
use Twig\Extension\RuntimeExtensionInterface;

final class HtmlDocumentRuntime implements RuntimeExtensionInterface
{
    private int $unnamedTagCounter = 0;

    public function __construct(private readonly ResponseContextAccessor $responseContextAccessor)
    {
    }

    /**
     * @internal
     *
     * @param array{identifier?: string, before?: string, after?: string} $options
     */
    public function add(string $content, DocumentLocation $location, array $options = []): void
    {
        $responseContext = $this->responseContextAccessor->getResponseContext();

        if ($responseContext && DocumentLocation::endOfBody === $location) {
            if ($this->addToBody($responseContext, $content, $options)) {
                return;
            }
        }

        if ($responseContext && DocumentLocation::endOfBody !== $location && $responseContext->has(HtmlHeadBag::class)) {
            $head = $responseContext->get(HtmlHeadBag::class);
            $identifier = $options['identifier'] ?? (string) ++$this->unnamedTagCounter;
            $position = match (true) {
                isset($options['before']) => ['before' => $options['before']],
                isset($options['after']) => ['after' => $options['after']],
                default => [],
            };

            if (DocumentLocation::head === $location) {
                $head->addRawToHead($identifier, $content, $position);
            } else {
                $head->addRawToStylesheets($identifier, $content, $position);
            }

            return;
        }

        $this->addLegacyContent($options['identifier'] ?? null, $content, $location);
    }

    /**
     * @param array{identifier?: string, before?: string, after?: string} $options
     */
    private function addToBody(ResponseContext $responseContext, string $content, array $options): bool
    {
        if (!$responseContext->has(HtmlBodyBag::class)) {
            return false;
        }

        $body = $responseContext->get(HtmlBodyBag::class);
        $identifier = $options['identifier'] ?? null;

        if (isset($options['before'])) {
            $body->addBefore($content, $options['before'], $identifier);
        } elseif (isset($options['after'])) {
            $body->addAfter($content, $options['after'], $identifier);
        } else {
            $body->add($content, $identifier);
        }

        return true;
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

        if (null !== $identifier) {
            $GLOBALS[$global][$identifier] = $content;
        } else {
            $GLOBALS[$global][] = $content;
        }
    }
}
