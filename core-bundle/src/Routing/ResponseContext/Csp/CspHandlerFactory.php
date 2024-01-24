<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Routing\ResponseContext\Csp;

use Contao\CoreBundle\Csp\CspParser;
use Psr\Log\LoggerInterface;

class CspHandlerFactory
{
    public function __construct(
        private readonly CspParser $cspParser,
        private readonly int $maxHeaderLength = 3072,
        private readonly LoggerInterface|null $logger = null,
    ) {
    }

    /**
     * @param string|null $csp Existing CSP header if any
     */
    public function create(string|null $csp = null): CspHandler
    {
        return new CspHandler($this->cspParser->parseHeader($csp), $this->maxHeaderLength, $this->logger);
    }
}
