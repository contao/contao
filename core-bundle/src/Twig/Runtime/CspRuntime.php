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

use Contao\CoreBundle\Csp\WysiwygProcessor;
use Contao\CoreBundle\Routing\ResponseContext\Csp\CspHandler;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Twig\Extension\RuntimeExtensionInterface;

final class CspRuntime implements RuntimeExtensionInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly ResponseContextAccessor $responseContextAccessor,
        private readonly WysiwygProcessor $wysiwygProcessor,
    ) {
    }

    public function wysiwygStyles(string $htmlFragment): string
    {
        $nonce = $this->getNonce('style-src');

        if (null === $nonce) {
            return $htmlFragment;
        }

        return $this->wysiwygProcessor->processStyles($htmlFragment, $nonce);
    }

    public function getNonce(string $directive): string|null
    {
        $responseContext = $this->responseContextAccessor->getResponseContext();

        if (!$responseContext || !$responseContext->has(CspHandler::class)) {
            return '';
        }

        /** @var CspHandler $csp */
        $csp = $responseContext->get(CspHandler::class);

        return $csp->getNonce($directive);
    }

    public function addSource(string $directive, string $source): void
    {
        $responseContext = $this->responseContextAccessor->getResponseContext();

        if (!$responseContext || !$responseContext->has(CspHandler::class)) {
            return;
        }

        /** @var CspHandler $csp */
        $csp = $responseContext->get(CspHandler::class);
        $csp->addSource($directive, $source);
    }
}
