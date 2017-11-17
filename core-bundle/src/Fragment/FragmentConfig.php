<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Fragment;

class FragmentConfig
{
    /**
     * @var string
     */
    private $controller;

    /**
     * @var string
     */
    private $renderer;

    /**
     * @var array
     */
    private $options;

    /**
     * @param string $controller
     * @param string $renderer
     * @param array  $options
     *
     * @see \Symfony\Component\HttpKernel\Fragment\FragmentHandler::render()
     */
    public function __construct(string $controller, string $renderer = 'inline', array $options = [])
    {
        $this->controller = $controller;
        $this->renderer = $renderer;
        $this->options = $options;
    }

    /**
     * Returns the controller.
     *
     * @return string
     */
    public function getController(): string
    {
        return $this->controller;
    }

    /**
     * Sets the controller.
     *
     * @param string $controller
     *
     * @return FragmentConfig
     */
    public function setController(string $controller): self
    {
        $this->controller = $controller;

        return $this;
    }

    /**
     * Returns the renderer.
     *
     * @return string
     */
    public function getRenderer(): string
    {
        return $this->renderer;
    }

    /**
     * Sets the renderer.
     *
     * @param string $renderer
     *
     * @return FragmentConfig
     */
    public function setRenderer(string $renderer): self
    {
        $this->renderer = $renderer;

        return $this;
    }

    /**
     * Returns the options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Sets the options.
     *
     * @param array $options
     *
     * @return FragmentConfig
     */
    public function setOptions(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Returns a single option.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getOption(string $name)
    {
        return $this->options[$name] ?? null;
    }

    /**
     * Sets a single option.
     *
     * @param string $name
     * @param mixed  $option
     *
     * @return FragmentConfig
     */
    public function setOption(string $name, $option): self
    {
        $this->options[$name] = $option;

        return $this;
    }
}
