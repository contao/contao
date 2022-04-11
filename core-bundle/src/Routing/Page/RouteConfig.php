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
     * @var array<string>
     */
    private array $methods;

    /**
     * @param string|array<string> $methods
     */
    public function __construct(
        private bool|string|null $path = null,
        private string|null $pathRegex = null,
        private string|null $urlSuffix = null,
        private array $requirements = [],
        private array $options = [],
        private array $defaults = [],
        array|string $methods = [],
    ) {
        $this->methods = \is_array($methods) ? $methods : [$methods];
    }

    public function getPath(): bool|string|null
    {
        return $this->path;
    }

    public function getPathRegex(): string|null
    {
        return $this->pathRegex;
    }

    public function getUrlSuffix(): string|null
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
