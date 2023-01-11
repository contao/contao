<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Fragment;

use Symfony\Component\HttpKernel\Fragment\FragmentHandler;

class FragmentConfig
{
    /**
     * @see FragmentHandler::render()
     */
    public function __construct(private string $controller, private string $renderer = 'forward', private array $options = [])
    {
    }

    public function getController(): string
    {
        return $this->controller;
    }

    public function setController(string $controller): self
    {
        $this->controller = $controller;

        return $this;
    }

    public function getRenderer(): string
    {
        return $this->renderer;
    }

    public function setRenderer(string $renderer): self
    {
        $this->renderer = $renderer;

        return $this;
    }

    /**
     * @return array<string,mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function getOption(string $name): mixed
    {
        return $this->options[$name] ?? null;
    }

    public function setOption(string $name, mixed $option): self
    {
        $this->options[$name] = $option;

        return $this;
    }
}
