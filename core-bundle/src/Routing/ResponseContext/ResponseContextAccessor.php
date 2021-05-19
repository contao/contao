<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\ResponseContext;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class ResponseContextAccessor
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * ResponseContextAccessor constructor.
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getResponseContext(): ?ResponseContextInterface
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return null;
        }

        return $request->attributes->get(ResponseContextInterface::REQUEST_ATTRIBUTE_NAME, null);
    }

    public function setResponseContext(?ResponseContextInterface $responseContext): self
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null !== $request) {
            $request->attributes->set(ResponseContextInterface::REQUEST_ATTRIBUTE_NAME, $responseContext);
        }

        return $this;
    }

    public function endCurrentContext(): self
    {
        $this->setResponseContext(null);

        return $this;
    }

    public function finalizeCurrentContext(Response $response): self
    {
        $responseContext = $this->getResponseContext();

        if (null === $responseContext) {
            return $this;
        }

        $responseContext->finalize($response);
        $this->endCurrentContext();

        return $this;
    }
}
