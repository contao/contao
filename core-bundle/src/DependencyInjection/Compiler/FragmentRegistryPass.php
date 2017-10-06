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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

class FragmentRegistryPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public const FRAGMENT_REGISTRY = 'contao.fragment.registry';

    public const TAG_FRAGMENT_FRONTEND_MODULE = 'contao.fragment.frontend_module';
    public const TAG_FRAGMENT_PAGE_TYPE = 'contao.fragment.page_type';
    public const TAG_FRAGMENT_CONTENT_ELEMENT = 'contao.fragment.content_element';

    public const RENDERER_FRONTEND_MODULE = 'contao.fragment.renderer.frontend_module.delegating';
    public const RENDERER_PAGE_TYPE = 'contao.fragment.renderer.page_type.delegating';
    public const RENDERER_CONTENT_ELEMENT = 'contao.fragment.renderer.content_element.delegating';

    public const TAG_RENDERER_FRONTEND_MODULE = 'contao.fragment.renderer.frontend_module';
    public const TAG_RENDERER_PAGE_TYPE = 'contao.fragment.renderer.page_type';
    public const TAG_RENDERER_CONTENT_ELEMENT = 'contao.fragment.renderer.content_element';

    /**
     * @var Definition
     */
    private $fragmentRegistry;

    /**
     * @var array
     */
    private $tags = [
        self::TAG_FRAGMENT_FRONTEND_MODULE,
        self::TAG_FRAGMENT_PAGE_TYPE,
        self::TAG_FRAGMENT_CONTENT_ELEMENT,
    ];

    /**
     * @var array
     */
    private $renderers = [
        self::RENDERER_FRONTEND_MODULE => self::TAG_RENDERER_FRONTEND_MODULE,
        self::RENDERER_PAGE_TYPE => self::TAG_RENDERER_PAGE_TYPE,
        self::RENDERER_CONTENT_ELEMENT => self::TAG_FRAGMENT_CONTENT_ELEMENT,
    ];

    /**
     * Adds the fragments and fragment renderers.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(self::FRAGMENT_REGISTRY)) {
            return;
        }

        $this->fragmentRegistry = $container->findDefinition(self::FRAGMENT_REGISTRY);

        foreach ($this->tags as $tag) {
            $this->registerFragments($container, $tag);
        }

        foreach ($this->renderers as $renderer => $tag) {
            $this->registerFragmentRenderers($container, $renderer, $tag);
        }
    }

    /**
     * Registers the fragments.
     *
     * @param ContainerBuilder $container
     * @param string           $tag
     */
    private function registerFragments(ContainerBuilder $container, string $tag): void
    {
        $fragments = $this->findAndSortTaggedServices($tag, $container);

        foreach ($fragments as $priority => $reference) {
            $fragment = $container->findDefinition($reference);

            foreach ($fragment->getTag($tag) as $fragmentOptions) {
                $fragmentOptions['tag'] = $tag;

                if (!isset($fragmentOptions['type'])) {
                    throw new RuntimeException(sprintf(
                        'A service tagged as "%s" must have a "type" attribute set.',
                        $tag
                    ));
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
     * @param string           $renderer
     * @param string           $tag
     */
    private function registerFragmentRenderers(ContainerBuilder $container, string $renderer, string $tag): void
    {
        if (!$container->has($renderer)) {
            return;
        }

        $renderer = $container->findDefinition($renderer);
        $renderer->setArgument(0, $this->findAndSortTaggedServices($tag, $container));
    }
}
