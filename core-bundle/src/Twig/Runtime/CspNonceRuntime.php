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

use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use ParagonIE\CSPBuilder\CSPBuilder;
use Twig\Extension\RuntimeExtensionInterface;

final class CspNonceRuntime implements RuntimeExtensionInterface
{
    /**
     * @internal
     */
    public function __construct(private readonly ResponseContextAccessor $responseContextAccessor)
    {
    }

    public function getNonce(string $directive): string
    {
        $responseContext = $this->responseContextAccessor->getResponseContext();

        if (!$responseContext || !$responseContext->has(CSPBuilder::class)) {
            return '';
        }

        /** @var CSPBuilder $csp */
        $csp = $responseContext->get(CSPBuilder::class);

        return $csp->nonce($directive);
    }
}
