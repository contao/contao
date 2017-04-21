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
use Knp\Menu\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Provides the file picker.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class FilePickerProvider extends AbstractMenuProvider implements PickerMenuProviderInterface
{
    /**
     * @var string
     */
    private $uploadPath;

    /**
     * Constructor.
     *
     * @param RouterInterface       $router
     * @param TokenStorageInterface $tokenStorage
     * @param RequestStack          $requestStack
     * @param string                $uploadPath
     */
    public function __construct(RouterInterface $router, TokenStorageInterface $tokenStorage, RequestStack $requestStack, $uploadPath)
    {
        parent::__construct($router, $tokenStorage, $requestStack);

        $this->uploadPath = $uploadPath;
    }

    /**
     * {@inheritdoc}
     */
    public function createMenu(ItemInterface $menu, FactoryInterface $factory)
    {
        $user = $this->getUser();

        if ($user->hasAccess('files', 'modules')) {
            $this->addMenuItem($menu, $factory, 'files', 'filePicker', 'filemounts');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports($table)
    {
        return 'tl_files' === $table;
    }

    /**
     * {@inheritdoc}
     */
    public function processSelection($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function canHandle(Request $request)
    {
        return $request->query->has('value') && 0 === strpos($request->query->get('value'), $this->uploadPath.'/');
    }

    /**
     * {@inheritdoc}
     */
    public function getPickerUrl(Request $request)
    {
        $params = $request->query->all();
        $params['do'] = 'files';

        return $this->route('contao_backend', $params);
    }
}
