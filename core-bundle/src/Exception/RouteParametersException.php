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

use Contao\CoreBundle\Routing\Page\PageRoute;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Route;

class RouteParametersException extends InvalidParameterException
{
    public function __construct(private Route $route, private array $parameters, private int $referenceType, ExceptionInterface $previous)
    {
        $message = $previous->getMessage();

        if ($route instanceof PageRoute) {
            $pageModel = $route->getPageModel();
            $message = 'Unable to generate route for page ID '.$pageModel->id.'.';

            if ($pageModel->requireItem && empty($parameters['parameters'])) {
                $message .= ' The page requires an item but none was given.';
            }
        }

        parent::__construct($message, $previous->getCode(), $previous);
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
