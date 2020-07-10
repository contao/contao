<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\Page;

final class RouteConfig
{
    /**
     * @var string|null
     */
    private $pathParameters;

    /**
     * @var array
     */
    private $requirements;

    /**
     * @var array
     */
    private $options;

    /**
     * @var array
     */
    private $defaults;

    /**
     * @var array
     */
    private $methods;

    public function __construct(string $pathParameters = null, array $requirements = [], array $options = [], array $defaults = [], array $methods = [])
    {
        $this->pathParameters = $pathParameters;
        $this->requirements = $requirements;
        $this->options = $options;
        $this->defaults = $defaults;
        $this->methods = $methods;
    }

    public function getPathParameters(): ?string
    {
        return $this->pathParameters;
    }

    public function getRequirements(): array
    {
        return $this->requirements;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getDefaults(): array
    {
        return $this->defaults;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }
}
