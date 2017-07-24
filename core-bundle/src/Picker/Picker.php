<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Picker;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;

/**
 * DCA picker.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class Picker implements PickerInterface
{
    /**
     * @var FactoryInterface
     */
    private $menuFactory;

    /**
     * @var PickerProviderInterface[]
     */
    private $providers;

    /**
     * @var PickerConfig
     */
    private $config;

    /**
     * @var ItemInterface
     */
    private $menu;

    /**
     * Constructor.
     *
     * @param FactoryInterface          $menuFactory
     * @param PickerProviderInterface[] $providers
     * @param PickerConfig              $config
     */
    public function __construct(FactoryInterface $menuFactory, array $providers, PickerConfig $config)
    {
        $this->menuFactory = $menuFactory;
        $this->providers = $providers;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * {@inheritdoc}
     */
    public function getMenu()
    {
        if (null !== $this->menu) {
            return $this->menu;
        }

        $this->menu = $this->menuFactory->createItem('picker');

        foreach ($this->providers as $provider) {
            $item = $provider->createMenuItem($this->config);
            $item->setExtra('provider', $provider);

            $this->menu->addChild($item);
        }

        return $this->menu;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentProvider()
    {
        foreach ($this->providers as $provider) {
            if ($provider->isCurrent($this->config)) {
                return $provider;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentUrl()
    {
        $menu = $this->getMenu();

        if (!$menu->count()) {
            throw new \RuntimeException('No picker menu items found.');
        }

        /** @var ItemInterface[] $menu */
        foreach ($menu as $item) {
            $picker = $item->getExtra('provider');

            if ($picker instanceof PickerProviderInterface && $picker->supportsValue($this->config)) {
                return $item->getUri();
            }
        }

        return $menu->getFirstChild()->getUri();
    }
}
