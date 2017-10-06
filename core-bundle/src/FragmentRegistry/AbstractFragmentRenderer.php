<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\FragmentRegistry;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;

abstract class AbstractFragmentRenderer
{
    /**
     * @var FragmentRegistryInterface
     */
    protected $fragmentRegistry;

    /**
     * @var FragmentHandler
     */
    protected $fragmentHandler;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param FragmentRegistryInterface $fragmentRegistry
     * @param FragmentHandler           $fragmentHandler
     * @param RequestStack              $requestStack
     */
    public function __construct(FragmentRegistryInterface $fragmentRegistry, FragmentHandler $fragmentHandler, RequestStack $requestStack)
    {
        $this->fragmentRegistry = $fragmentRegistry;
        $this->fragmentHandler = $fragmentHandler;
        $this->requestStack = $requestStack;
    }

    /**
     * Renders a fragment.
     *
     * @param string $fragmentIdentifier
     * @param array  $attributes
     * @param array  $query
     * @param string $forceRenderStrategy
     *
     * @return string|null
     */
    protected function renderFragment(string $fragmentIdentifier, array $attributes = [], array $query = [], string $forceRenderStrategy = ''): ?string
    {
        $options = $this->fragmentRegistry->getOptions($fragmentIdentifier);
        $fragment = $this->fragmentRegistry->getFragment($fragmentIdentifier);
        $request = $this->requestStack->getCurrentRequest();

        if (isset($GLOBALS['objPage'])) {
            $attributes['pageModel'] = $GLOBALS['objPage']->id;
        }

        if ($fragment instanceof SimpleRenderingInformationProviderInterface) {
            $attributes = $fragment->getControllerRequestAttributes($request, $attributes);
            $query = $fragment->getControllerQueryParameters($request, $query);
        }

        $controllerReference = new ControllerReference($options['controller'], $attributes, $query);

        if ('' !== $forceRenderStrategy) {
            $renderStrategy = $forceRenderStrategy;
        } else {
            $renderStrategy = $options['renderStrategy'] ?? 'inline';
        }

        return $this->fragmentHandler->render($controllerReference, $renderStrategy);
    }
}
