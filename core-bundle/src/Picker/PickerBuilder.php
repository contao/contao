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
use Symfony\Component\Routing\RouterInterface;

class PickerBuilder implements PickerBuilderInterface
{
    private FactoryInterface $menuFactory;
    private RouterInterface $router;

    /**
     * @var array<PickerProviderInterface>
     */
    private array $providers = [];

    /**
     * @internal
     */
    public function __construct(FactoryInterface $menuFactory, RouterInterface $router)
    {
        $this->menuFactory = $menuFactory;
        $this->router = $router;
    }

    /**
     * Adds a picker provider.
     */
    public function addProvider(PickerProviderInterface $provider): void
    {
        $this->providers[$provider->getName()] = $provider;
    }

    public function create(PickerConfig $config): ?Picker
    {
        $providers = $this->providers;

        if (\is_array($allowed = $config->getExtra('providers'))) {
            $providers = array_intersect_key($providers, array_flip($allowed));
        }

        $providers = array_filter(
            $providers,
            static fn (PickerProviderInterface $provider): bool => $provider->supportsContext($config->getContext())
        );

        if (empty($providers)) {
            return null;
        }

        return new Picker($this->menuFactory, $providers, $config);
    }

    public function createFromData($data): ?Picker
    {
        try {
            $config = PickerConfig::urlDecode($data);
        } catch (\InvalidArgumentException $e) {
            return null;
        }

        return $this->create($config);
    }

    public function supportsContext($context, array $allowed = null): bool
    {
        $providers = $this->providers;

        if (null !== $allowed) {
            $providers = array_intersect_key($providers, array_flip($allowed));
        }

        foreach ($providers as $provider) {
            if ($provider->supportsContext($context)) {
                return true;
            }
        }

        return false;
    }

    public function getUrl($context, array $extras = [], $value = ''): string
    {
        $providers = isset($extras['providers']) && \is_array($extras['providers']) ? $extras['providers'] : null;

        if (!$this->supportsContext($context, $providers)) {
            return '';
        }

        return $this->router->generate('contao_backend_picker', compact('context', 'extras', 'value'));
    }
}
