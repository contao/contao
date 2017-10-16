<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Contao\CoreBundle\Fragment\FragmentRegistryInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

class FragmentRegistryPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * @var Definition
     */
    private $fragmentRegistry;

    /**
     * @var array
     */
    private $fragments = [
        FragmentRegistryInterface::CONTENT_ELEMENT_FRAGMENT,
        FragmentRegistryInterface::FRONTEND_MODULE_FRAGMENT,
        FragmentRegistryInterface::PAGE_TYPE_FRAGMENT,
    ];

    /**
     * @var array
     */
    private $renderers = [
        FragmentRegistryInterface::CONTENT_ELEMENT_RENDERER,
        FragmentRegistryInterface::FRONTEND_MODULE_RENDERER,
        FragmentRegistryInterface::PAGE_TYPE_RENDERER,
    ];

    /**
     * Adds the fragments and fragment renderers.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('contao.fragment.registry')) {
            return;
        }

        $this->fragmentRegistry = $container->findDefinition('contao.fragment.registry');

        foreach ($this->fragments as $tag) {
            $this->registerFragment($container, $tag);
        }

        foreach ($this->renderers as $tag) {
            $this->registerFragmentRenderer($container, $tag);
        }
    }

    /**
     * Registers the fragments.
     *
     * @param ContainerBuilder $container
     * @param string           $tag
     */
    private function registerFragment(ContainerBuilder $container, string $tag): void
    {
        $fragments = $this->findAndSortTaggedServices($tag, $container);

        foreach ($fragments as $priority => $reference) {
            $fragment = $container->findDefinition($reference);

            foreach ($fragment->getTag($tag) as $fragmentOptions) {
                $fragmentOptions['tag'] = $tag;

                if (!isset($fragmentOptions['type'])) {
                    throw new RuntimeException(sprintf('A service tagged as "%s" must have a "type" attribute.', $tag));
                }

                $fragmentOptions['controller'] = (string) $reference;

                // Support a specific method on the controller
                if (isset($fragmentOptions['method'])) {
                    $fragmentOptions['controller'] .= ':'.$fragmentOptions['method'];
                    unset($fragmentOptions['method']);
                }

                // Mark all fragments as lazy so they are lazy loaded using the
                // proxy manager (which is why we need to require it in the composer.json,
                // otherwise the lazy definition will just be ignored).
                $fragment->setLazy(true);

                $fragmentIdentifier = $tag.'.'.$fragmentOptions['type'];

                $this->fragmentRegistry->addMethodCall(
                    'addFragment',
                    [$fragmentIdentifier, $reference, $fragmentOptions]
                );
            }
        }
    }

    /**
     * Registers the fragment renderers.
     *
     * @param ContainerBuilder $container
     * @param string           $tag
     */
    private function registerFragmentRenderer(ContainerBuilder $container, string $tag): void
    {
        $renderers = $this->findAndSortTaggedServices($tag, $container);

        foreach ($renderers as $priority => $reference) {
            $renderer = $container->findDefinition($tag);
            $renderer->addMethodCall('addRenderer', [$reference]);
        }
    }
}
