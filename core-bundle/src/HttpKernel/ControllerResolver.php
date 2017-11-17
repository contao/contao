<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\HttpKernel;

use Contao\CoreBundle\Fragment\FragmentRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

class ControllerResolver implements ControllerResolverInterface
{
    /**
     * @var ControllerResolverInterface
     */
    private $resolver;

    /**
     * @var FragmentRegistry
     */
    private $registry;

    /**
     * @param ControllerResolverInterface $resolver
     * @param FragmentRegistry            $registry
     */
    public function __construct(ControllerResolverInterface $resolver, FragmentRegistry $registry)
    {
        $this->resolver = $resolver;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getController(Request $request)
    {
        if ($request->attributes->has('_controller')) {
            $fragmentConfig = $this->registry->get($request->attributes->get('_controller'));

            if (null !== $fragmentConfig) {
                $request->attributes->set('_controller', $fragmentConfig->getController());
            }
        }

        return $this->resolver->getController($request);
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(Request $request, $controller)
    {
        return $this->resolver->getArguments($request, $controller);
    }
}
