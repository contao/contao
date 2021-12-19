<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Exception;

use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Route;

class RouteParametersException extends InvalidParameterException
{
    private Route $route;
    private array $parameters;
    private int $referenceType;

    public function __construct(Route $route, array $parameters, int $referenceType, ExceptionInterface $previous)
    {
        parent::__construct($previous->getMessage(), $previous->getCode(), $previous);

        $this->route = $route;
        $this->parameters = $parameters;
        $this->referenceType = $referenceType;
    }

    public function getRoute(): Route
    {
        return $this->route;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getReferenceType(): int
    {
        return $this->referenceType;
    }
}
