<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Picker;

use Contao\BackendUser;
use Knp\Menu\FactoryInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Abstract class for picker providers.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
abstract class AbstractPickerProvider implements PickerProviderInterface
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
     * @var TokenStorageInterface
     */
    private $tokenStorage;

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
     * {@inheritdoc}
     */
    public function getUrl(PickerConfig $config)
    {
        return $this->generateUrl($config, false);
    }

    /**
     * {@inheritdoc}
     */
    public function createMenuItem(PickerConfig $config)
    {
        $name = $this->getName();

        return $this->menuFactory->createItem(
            $name,
            [
                'label' => $GLOBALS['TL_LANG']['MSC'][$name] ?: $name,
                'linkAttributes' => ['class' => $name],
                'current' => $this->isCurrent($config),
                'uri' => $this->generateUrl($config, true),
            ]
        );
    }

    /**
     * Sets the security token storage.
     *
     * @param TokenStorageInterface $tokenStorage
     */
    public function setTokenStorage(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function isCurrent(PickerConfig $config)
    {
        return $config->getCurrent() === $this->getName();
    }

    /**
     * Returns the back end user object.
     *
     * @throws \RuntimeException
     *
     * @return BackendUser
     */
    protected function getUser()
    {
        if (null === $this->tokenStorage) {
            throw new \RuntimeException('No token storage provided');
        }

        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            throw new \RuntimeException('No token provided');
        }

        $user = $token->getUser();

        if (!($user instanceof BackendUser)) {
            throw new \RuntimeException('The token does not contain a back end user object');
        }

        return $user;
    }

    /**
     * Returns the routing parameters for the backend picker.
     *
     * @param PickerConfig|null $config
     *
     * @return array
     */
    abstract protected function getRouteParameters(PickerConfig $config = null);

    /**
     * Generates the URL for the picker.
     *
     * @param PickerConfig $config
     * @param bool         $ignoreValue
     *
     * @return string
     */
    private function generateUrl(PickerConfig $config, $ignoreValue)
    {
        $params = array_merge(
            $this->getRouteParameters($ignoreValue ? null : $config),
            ['popup' => '1', 'picker' => $config->cloneForCurrent($this->getName())->urlEncode()]
        );

        return $this->router->generate('contao_backend', $params);
    }
}
