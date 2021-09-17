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
    private ?string $path;
    private ?string $pathRegex;
    private ?string $urlSuffix;
    private array $requirements;
    private array $options;
    private array $defaults;

    /**
     * @var array<string>
     */
    private array $methods;

    /**
     * @param string|array<string> $methods
     */
    public function __construct(string $path = null, string $pathRegex = null, string $urlSuffix = null, array $requirements = [], array $options = [], array $defaults = [], $methods = [])
    {
        $this->path = $path;
        $this->pathRegex = $pathRegex;
        $this->urlSuffix = $urlSuffix;
        $this->requirements = $requirements;
        $this->options = $options;
        $this->defaults = $defaults;
        $this->methods = \is_array($methods) ? $methods : [$methods];
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
