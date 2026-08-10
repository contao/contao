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

use Contao\CoreBundle\Routing\ResponseContext\Csp\CspHandler;
use Contao\CoreBundle\Routing\ResponseContext\HtmlBodyBag;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\HtmlTag;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\Twig\ResponseContext\DocumentLocation;
use Twig\Extension\RuntimeExtensionInterface;

final class HtmlDocumentRuntime implements RuntimeExtensionInterface
{
    private int $unnamedTagCounter = 0;

    public function __construct(
        private readonly ResponseContextAccessor $responseContextAccessor,
        private readonly bool $debug = false,
    ) {
    }

    public function renderHtmlTag(HtmlTag|string $tag, int|string|null $identifier = null): string
    {
        if (\is_string($tag)) {
            if ($this->debug && null !== $identifier) {
                return $this->addDebugIdentifierComment($tag, $identifier);
            }

            return $tag;
        }

        if ($this->debug && null !== $identifier) {
            $tag = $tag->withAttribute('data-contao-tag', (string) $identifier);
        }

        return $this->addCspNonce($tag)->toHtml();
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

    private function addDebugIdentifierComment(string $markup, int|string $identifier): string
    {
        $identifier = $this->normalizeDebugIdentifier((string) $identifier);
        $identifier = trim(str_replace(['--', "\r", "\n"], ['- -', ' ', ' '], $identifier));

        return "<!-- contao-tag: $identifier -->$markup";
    }

    private function normalizeDebugIdentifier(string $identifier): string
    {
        foreach (['contao.twig.head.', 'contao.twig.stylesheets.'] as $prefix) {
            if (str_starts_with($identifier, $prefix)) {
                return substr($identifier, \strlen($prefix));
            }
        }

        return $identifier;
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

    private function addCspNonce(HtmlTag $tag): HtmlTag
    {
        if (isset($tag->getAttributes()['nonce']) || (!$tag->isInlineScript() && !$tag->isInlineStyle())) {
            return $tag;
        }

        $responseContext = $this->responseContextAccessor->getResponseContext();

        if (!$responseContext?->has(CspHandler::class)) {
            return $tag;
        }

        $directive = $tag->isInlineScript() ? 'script-src' : 'style-src';

        if ($nonce = $responseContext->get(CspHandler::class)->getNonce($directive)) {
            return $tag->withAttribute('nonce', $nonce);
        }

        return $tag;
    }
}
