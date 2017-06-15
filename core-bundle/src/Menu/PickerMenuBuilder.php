<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\Renderer\RendererInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

/**
 * Creates the picker menu.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PickerMenuBuilder implements PickerMenuBuilderInterface
{
    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var RendererInterface
     */
    private $renderer;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var PickerMenuProviderInterface[]
     */
    private $providers = [];

    /**
     * Constructor.
     *
     * @param FactoryInterface  $factory
     * @param RendererInterface $renderer
     * @param RouterInterface   $router
     */
    public function __construct(FactoryInterface $factory, RendererInterface $renderer, RouterInterface $router)
    {
        $this->factory = $factory;
        $this->renderer = $renderer;
        $this->router = $router;
    }

    /**
     * Adds a picker menu provider.
     *
     * @param PickerMenuProviderInterface $provider
     */
    public function addProvider(PickerMenuProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function createMenu($context)
    {
        $menu = $this->factory->createItem('picker');

        foreach ($this->providers as $provider) {
            if ($provider->supports($context)) {
                $provider->createMenu($menu, $this->factory);
            }
        }

        if ($menu->count() > 1) {
            return $this->renderer->render($menu);
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTable($table)
    {
        foreach ($this->providers as $provider) {
            if ($provider->supportsTable($table)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function processSelection($table, $value)
    {
        foreach ($this->providers as $provider) {
            if ($provider->supportsTable($table)) {
                return $provider->processSelection($value);
            }
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getPickerUrl(Request $request)
    {
        foreach ($this->providers as $provider) {
            if ($provider->canHandle($request)) {
                return $provider->getPickerUrl($request);
            }
        }

        return $this->router->generate('contao_backend', array_merge(['do' => 'page'], $request->query->all()));
    }
}
