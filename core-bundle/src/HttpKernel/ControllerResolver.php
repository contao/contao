<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\HttpKernel;

use Contao\CoreBundle\Fragment\FragmentRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

class ControllerResolver implements ControllerResolverInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly ControllerResolverInterface $resolver,
        private readonly FragmentRegistry $registry,
    ) {
    }

    public function getController(Request $request): callable|false
    {
        if (
            $request->attributes->has('_controller')
            && \is_string($controller = $request->attributes->get('_controller'))
            && ($fragmentConfig = $this->registry->get($controller))
        ) {
            $request->attributes->set('_controller', $fragmentConfig->getController());
        }

        return $this->resolver->getController($request);
    }

    public function getArguments(Request $request, callable $controller): array
    {
        if (!method_exists($this->resolver, 'getArguments')) {
            return [];
        }

        return $this->resolver->getArguments($request, $controller);
    }
}
