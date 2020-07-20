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
    private $path;

    /**
     * @var string|null
     */
    private $pathRegex;

    /**
     * @var string|null
     */
    private $urlSuffix;

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

    public function __construct(string $path = null, string $pathRegex = null, string $urlSuffix = null, array $requirements = [], array $options = [], array $defaults = [], array $methods = [])
    {
        $this->path = $path;
        $this->pathRegex = $pathRegex;
        $this->urlSuffix = $urlSuffix;
        $this->requirements = $requirements;
        $this->options = $options;
        $this->defaults = $defaults;
        $this->methods = $methods;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getPathRegex(): ?string
    {
        return $this->pathRegex;
    }

    public function getUrlSuffix(): ?string
    {
        return $this->urlSuffix;
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
