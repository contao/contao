<?php

declare(strict_types=1);

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
    public function getConfig(): PickerConfig
    {
        return $this->config;
    }

    /**
     * {@inheritdoc}
     */
    public function getMenu(): ItemInterface
    {
        if (null !== $this->menu) {
            return $this->menu;
        }

        $this->menu = $this->menuFactory->createItem('picker');

        foreach ($this->providers as $provider) {
            $this->menu->addChild($provider->createMenuItem($this->config));
        }

        return $this->menu;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentProvider(): ?PickerProviderInterface
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
    public function getCurrentUrl(): ?string
    {
        foreach ($this->providers as $provider) {
            if ($provider->supportsValue($this->config)) {
                return $provider->getUrl($this->config);
            }
        }

        $menu = $this->getMenu();

        if (!$menu->count()) {
            throw new \RuntimeException('No picker menu items found');
        }

        return $menu->getFirstChild()->getUri();
    }
}
