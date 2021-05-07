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

use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\ServiceProviderInterface;

class ResponseContext implements ResponseContextInterface
{
    /**
     * @var bool
     */
    private $terminated = false;

    /**
     * @var ServiceProviderInterface|null
     */
    private $serviceLocator;

    /**
     * @var PartialResponseHeaderBag|null
     */
    private $headerBag;

    public function __construct(ServiceProviderInterface $serviceLocator = null)
    {
        $this->serviceLocator = $serviceLocator;
    }

    public function getHeaderBag(): PartialResponseHeaderBag
    {
        if (null === $this->headerBag) {
            $this->headerBag = new PartialResponseHeaderBag();
        }

        return $this->headerBag;
    }

    public function terminate(Response $response): void
    {
        if (null !== $this->serviceLocator && $this->serviceLocator->has('event_dispatcher')) {
            $this->serviceLocator->get('event_dispatcher')->dispatch(new TerminateResponseContextEvent($this));
        }

        foreach ($this->getHeaderBag()->all() as $name => $values) {
            $response->headers->set($name, $values, false); // Do not replace but add
        }

        $this->terminated = true;
    }

    public function isTerminated(): bool
    {
        return $this->terminated;
    }
}
