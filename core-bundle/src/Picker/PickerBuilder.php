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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class PickerBuilder implements PickerBuilderInterface
{
    /**
     * @var FactoryInterface
     */
    private $menuFactory;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var PickerProviderInterface[]
     */
    private $providers = [];

    /**
     * @param FactoryInterface $menuFactory
     * @param RouterInterface  $router
     * @param RequestStack     $requestStack
     */
    public function __construct(FactoryInterface $menuFactory, RouterInterface $router, RequestStack $requestStack)
    {
        $this->menuFactory = $menuFactory;
        $this->router = $router;
        $this->requestStack = $requestStack;
    }

    /**
     * Adds a picker provider.
     *
     * @param PickerProviderInterface $provider
     */
    public function addProvider(PickerProviderInterface $provider): void
    {
        $this->providers[$provider->getName()] = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function create(PickerConfig $config): ?Picker
    {
        $providers = $this->providers;

        if (\is_array($allowed = $config->getExtra('providers'))) {
            $providers = array_intersect_key($providers, array_flip($allowed));
        }

        $providers = array_filter(
            $providers,
            function (PickerProviderInterface $provider) use ($config): bool {
                return $provider->supportsContext($config->getContext());
            }
        );

        if (empty($providers)) {
            return null;
        }

        return new Picker($this->menuFactory, $providers, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function createFromData($data): ?Picker
    {
        try {
            $config = PickerConfig::urlDecode($data);
        } catch (\InvalidArgumentException $e) {
            return null;
        }

        return $this->create($config);
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function getUrl($context, array $extras = [], $value = ''): string
    {
        $providers = (isset($extras['providers']) && \is_array($extras['providers'])) ? $extras['providers'] : null;

        if (!$this->supportsContext($context, $providers)) {
            return '';
        }

        return $this->router->generate(
            'contao_backend_picker',
            ['context' => $context, 'extras' => $extras, 'value' => $value]
        );
    }
}
