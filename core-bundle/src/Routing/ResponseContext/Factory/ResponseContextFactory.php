<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\ResponseContext\Factory;

use Contao\CoreBundle\Routing\ResponseContext\Factory\Provider\ResponseContextProviderInterface;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextInterface;

class ResponseContextFactory
{
    /**
     * @var array<ResponseContextProviderInterface>
     */
    private $providers = [];

    /**
     * @var ResponseContextAccessor
     */
    private $responseContextAccessor;

    public function __construct(iterable $providers, ResponseContextAccessor $responseContextAccessor)
    {
        foreach ($providers as $provider) {
            $this->addProvider($provider);
        }

        $this->responseContextAccessor = $responseContextAccessor;
    }

    public function addProvider(ResponseContextProviderInterface $provider): self
    {
        $this->providers[] = $provider;

        return $this;
    }

    public function create(string $responseContextClassName): ResponseContextInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($responseContextClassName)) {
                return $provider->create($responseContextClassName);
            }
        }

        throw new \InvalidArgumentException(sprintf('No response context provider for "%s" provided.', $responseContextClassName));
    }

    public function createAndSetCurrent(string $responseContextClassName): ResponseContextInterface
    {
        $responseContext = $this->create($responseContextClassName);
        $this->responseContextAccessor->setResponseContext($responseContext);

        return $responseContext;
    }
}
