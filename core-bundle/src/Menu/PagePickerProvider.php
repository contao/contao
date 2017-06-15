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

/**
 * Provides the page picker.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PagePickerProvider extends AbstractMenuProvider implements PickerMenuProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports($context)
    {
        return 'page' === $context || 'link' === $context;
    }

    /**
     * {@inheritdoc}
     */
    public function createMenu(ItemInterface $menu, FactoryInterface $factory)
    {
        $user = $this->getUser();

        if ($user->hasAccess('page', 'modules')) {
            $this->addMenuItem($menu, $factory, 'page', 'pagePicker', 'pagemounts');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTable($table)
    {
        return 'tl_page' === $table;
    }

    /**
     * {@inheritdoc}
     */
    public function processSelection($value)
    {
        return sprintf('{{link_url::%s}}', $value);
    }

    /**
     * {@inheritdoc}
     */
    public function canHandle(Request $request)
    {
        return $request->query->has('value') && false !== strpos($request->query->get('value'), '{{link_url::');
    }

    /**
     * {@inheritdoc}
     */
    public function getPickerUrl(Request $request)
    {
        $params = $request->query->all();
        $params['do'] = 'page';
        $params['value'] = str_replace(['{{link_url::', '}}'], '', $params['value']);

        return $this->route('contao_backend', $params);
    }
}
