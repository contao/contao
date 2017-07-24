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
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * Constructor.
     *
     * @param FactoryInterface $menuFactory
     */
    public function __construct(FactoryInterface $menuFactory)
    {
        $this->menuFactory = $menuFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function createMenuItem(PickerConfig $config)
    {
        $name = $this->getName();

        $params = array_merge(
            ['popup' => '1'],
            $this->getRouteParameters($config),
            ['picker' => $config->cloneForCurrent($name)->urlEncode()]
        );

        return $this->menuFactory->createItem(
            $name,
            [
                'label' => $GLOBALS['TL_LANG']['MSC'][$name] ?: $name,
                'linkAttributes' => ['class' => $this->getLinkClass()],
                'current' => $this->isCurrent($config),
                'route' => 'contao_backend',
                'routeParameters' => $params,
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
     * Returns the link class for the picker menu item.
     *
     * @return string
     */
    abstract protected function getLinkClass();

    /**
     * Returns the routing parameters for the backend picker.
     *
     * @param PickerConfig $config
     *
     * @return array
     */
    abstract protected function getRouteParameters(PickerConfig $config);
}
