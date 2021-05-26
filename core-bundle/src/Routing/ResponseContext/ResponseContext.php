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

final class ResponseContext
{
    public const REQUEST_ATTRIBUTE_NAME = '_contao_response_context';

    /**
     * @var array
     */
    private $services = [];

    /**
     * @var PartialResponseHeaderBag|null
     */
    private $headerBag;

    public function add(object $service): self
    {
        $this->services[\get_class($service)] = $service;

        return $this;
    }

    public function has(string $service): bool
    {
        return null !== $this->get($service);
    }

    /**
     * @return mixed|null
     */
    public function get(string $service)
    {
        return $this->services[$service] ?? null;
    }

    public function getHeaderBag(): PartialResponseHeaderBag
    {
        if (null === $this->headerBag) {
            $this->headerBag = new PartialResponseHeaderBag();
        }

        return $this->headerBag;
    }
}
