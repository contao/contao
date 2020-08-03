<?php

namespace Contao\CoreBundle\Exception;

use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Route;

class RouteParametersException extends \InvalidArgumentException implements ExceptionInterface
{
    /**
     * @var Route
     */
    private $route;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @var int
     */
    private $referenceType;

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
