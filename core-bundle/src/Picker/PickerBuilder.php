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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

/**
 * Picker builder.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
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
     * @var PickerProviderInterface[]
     */
    private $providers = [];

    /**
     * Constructor.
     *
     * @param FactoryInterface $menuFactory
     * @param RouterInterface  $router
     */
    public function __construct(FactoryInterface $menuFactory, RouterInterface $router)
    {
        $this->menuFactory = $menuFactory;
        $this->router = $router;
    }

    /**
     * Adds a picker provider.
     *
     * @param PickerProviderInterface $provider
     */
    public function addProvider(PickerProviderInterface $provider)
    {
        $this->providers[$provider->getName()] = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function create(PickerConfig $config)
    {
        $providers = $this->providers;

        if (is_array($allowed = $config->getExtra('providers'))) {
            $providers = array_intersect_key($providers, array_flip($allowed));
        }

        $providers = array_filter(
            $providers,
            function (PickerProviderInterface $provider) use ($config) {
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
    public function createFromData($data)
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
    public function supportsContext($context, array $allowed = null)
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
    public function getUrl($context, array $extras = [], $value = '')
    {
        $providers = (isset($extras['providers']) && is_array($extras['providers'])) ? $extras['providers'] : null;

        if (!$this->supportsContext($context, $providers)) {
            return '';
        }

        return $this->router->generate(
            'contao_backend_picker',
            ['context' => $context, 'extras' => $extras, 'value' => $value]
        );
    }
}
