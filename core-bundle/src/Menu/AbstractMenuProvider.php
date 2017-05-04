<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Menu;

use Contao\BackendUser;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Abstract class for menu providers.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
abstract class AbstractMenuProvider
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var array
     */
    private $keys = ['do', 'context', 'target', 'value', 'popup'];

    /**
     * Constructor.
     *
     * @param RouterInterface            $router
     * @param RequestStack               $requestStack
     * @param TokenStorageInterface|null $tokenStorage
     */
    public function __construct(RouterInterface $router, RequestStack $requestStack, TokenStorageInterface $tokenStorage = null)
    {
        $this->router = $router;
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Returns the back end user object.
     *
     * @return BackendUser
     *
     * @throws \RuntimeException
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

        if (null === $user) {
            throw new \RuntimeException('The token does not contain a user');
        }

        return $user;
    }

    /**
     * Generates a route.
     *
     * @param string $name
     * @param array  $params
     *
     * @return bool|string
     */
    protected function route($name, array $params = [])
    {
        return $this->router->generate($name, $params);
    }

    /**
     * Adds a menu item.
     *
     * @param ItemInterface    $menu
     * @param FactoryInterface $factory
     * @param string           $do
     * @param string           $key
     * @param string           $class
     */
    protected function addMenuItem(ItemInterface $menu, FactoryInterface $factory, $do, $key, $class)
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return;
        }

        $params = $this->getParametersFromRequest($request);

        $item = $factory->createItem(
            $key,
            ['uri' => $this->route('contao_backend', array_merge($params, ['do' => $do]))]
        );

        $item->setLabel($this->getLabel($key));
        $item->setLinkAttribute('class', $class);
        $item->setCurrent(isset($params['do']) && $do === $params['do']);

        $menu->addChild($item);
    }

    /**
     * Returns the filtered request parameters.
     *
     * @param Request $request
     *
     * @return array
     */
    protected function getParametersFromRequest(Request $request)
    {
        $params = [];

        foreach ($this->keys as $key) {
            if ($request->query->has($key)) {
                $params[$key] = $request->query->get($key);
            }
        }

        return $params;
    }

    /**
     * Returns a label.
     *
     * @param $key
     *
     * @return string
     */
    protected function getLabel($key)
    {
        if (isset($GLOBALS['TL_LANG']['MSC'][$key])) {
            return $GLOBALS['TL_LANG']['MSC'][$key];
        }

        return $key;
    }
}
