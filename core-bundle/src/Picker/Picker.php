<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Picker;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;

class Picker implements PickerInterface
{
    private ItemInterface|null $menu = null;

    /**
     * @param array<PickerProviderInterface> $providers
     */
    public function __construct(
        private readonly FactoryInterface $menuFactory,
        private readonly array $providers,
        private readonly PickerConfig $config,
    ) {
    }

    public function getConfig(): PickerConfig
    {
        return $this->config;
    }

    public function getMenu(): ItemInterface
    {
        if (null !== $this->menu) {
            return $this->menu;
        }

        $this->menu = $this->menuFactory->createItem('picker');

        foreach ($this->providers as $provider) {
            if ($provider instanceof PickerMenuInterface) {
                $provider->addMenuItems($this->menu, $this->config);
            } else {
                $this->menu->addChild($provider->createMenuItem($this->config));
            }
        }

        return $this->menu;
    }

    public function getCurrentProvider(): PickerProviderInterface|null
    {
        foreach ($this->providers as $provider) {
            if ($provider->isCurrent($this->config)) {
                return $provider;
            }
        }

        return null;
    }

    public function getCurrentUrl(): string|null
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
