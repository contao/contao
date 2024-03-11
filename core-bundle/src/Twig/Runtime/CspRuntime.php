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

use Contao\CoreBundle\Csp\WysiwygStyleProcessor;
use Contao\CoreBundle\Routing\ResponseContext\Csp\CspHandler;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Nelmio\SecurityBundle\Twig\CSPRuntime as NelmioCSPRuntime;
use Twig\Extension\RuntimeExtensionInterface;

final class CspRuntime implements RuntimeExtensionInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly ResponseContextAccessor $responseContextAccessor,
        private readonly WysiwygStyleProcessor $wysiwygProcessor,
        private readonly NelmioCSPRuntime|null $nelmioCSPRuntime = null,
    ) {
    }

    public function inlineStyles(string $htmlFragment): string
    {
        $responseContext = $this->responseContextAccessor->getResponseContext();

        if (!$responseContext?->has(CspHandler::class)) {
            return $htmlFragment;
        }

        if (!$styles = $this->wysiwygProcessor->extractStyles($htmlFragment)) {
            return $htmlFragment;
        }

        $csp = $responseContext->get(CspHandler::class);

        foreach ($styles as $style) {
            $csp->addHash('style-src', $style);
        }

        $csp->addSource('style-src', 'unsafe-hashes');

        return $htmlFragment;
    }

    public function getNonce(string $directive): string|null
    {
        $responseContext = $this->responseContextAccessor->getResponseContext();

        if (!$responseContext?->has(CspHandler::class)) {
            // Forward to Nelmio's CSPRuntime method
            if ($this->nelmioCSPRuntime) {
                return $this->nelmioCSPRuntime->getCSPNonce(preg_replace('/^(script|style)-src/', '$1', $directive));
            }

            return '';
        }

        return $responseContext->get(CspHandler::class)->getNonce($directive);
    }

    public function addSource(array|string $directives, string $source): void
    {
        $responseContext = $this->responseContextAccessor->getResponseContext();

        if (!$responseContext?->has(CspHandler::class)) {
            return;
        }

        $csp = $responseContext->get(CspHandler::class);

        foreach ((array) $directives as $directive) {
            $csp->addSource($directive, $source);
        }
    }

    public function addHash(string $directive, string $source, string $algorithm = 'sha256'): void
    {
        $responseContext = $this->responseContextAccessor->getResponseContext();

        if (!$responseContext?->has(CspHandler::class)) {
            return;
        }

        $responseContext->get(CspHandler::class)->addHash($directive, $source, $algorithm);
    }
}
