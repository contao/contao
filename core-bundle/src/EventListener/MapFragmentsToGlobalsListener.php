<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\ContentProxy;
use Contao\CoreBundle\Fragment\FragmentRegistryInterface;
use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\ModuleProxy;
use Contao\PageProxy;

class MapFragmentsToGlobalsListener implements FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    /**
     * @var FragmentRegistryInterface
     */
    private $fragmentRegistry;

    /**
     * @param FragmentRegistryInterface $fragmentRegistry
     */
    public function __construct(FragmentRegistryInterface $fragmentRegistry)
    {
        $this->fragmentRegistry = $fragmentRegistry;
    }

    /**
     * Maps fragments to the globals array.
     */
    public function onInitializeSystem(): void
    {
        $this->framework->initialize();

        $this->mapPageTypes();
        $this->mapFrontendModules();
        $this->mapContentElements();
    }

    /**
     * Maps the page types.
     */
    private function mapPageTypes(): void
    {
        $filter = $this->getTagFilter(FragmentRegistryInterface::PAGE_TYPE_FRAGMENT);

        foreach ($this->fragmentRegistry->getFragments($filter) as $identifier => $fragment) {
            $options = $this->fragmentRegistry->getOptions($identifier);

            $GLOBALS['TL_PTY'][$options['type']] = PageProxy::class;
        }
    }

    /**
     * Maps the front end modules.
     *
     * @throws \RuntimeException
     */
    private function mapFrontendModules(): void
    {
        $filter = $this->getTagFilter(FragmentRegistryInterface::FRONTEND_MODULE_FRAGMENT);

        foreach ($this->fragmentRegistry->getFragments($filter) as $identifier => $fragment) {
            $options = $this->fragmentRegistry->getOptions($identifier);

            if (!isset($options['category'])) {
                throw new \RuntimeException(sprintf(
                    'You tagged a fragment as "%s" but forgot to specify the "category" attribute.',
                    FragmentRegistryInterface::FRONTEND_MODULE_FRAGMENT
                ));
            }

            $GLOBALS['FE_MOD'][$options['category']][$options['type']] = ModuleProxy::class;
        }
    }

    /**
     * Maps the content elements.
     *
     * @throws \RuntimeException
     */
    private function mapContentElements(): void
    {
        $filter = $this->getTagFilter(FragmentRegistryInterface::CONTENT_ELEMENT_FRAGMENT);

        foreach ($this->fragmentRegistry->getFragments($filter) as $identifier => $fragment) {
            $options = $this->fragmentRegistry->getOptions($identifier);

            if (!isset($options['category'])) {
                throw new \RuntimeException(sprintf(
                    'You tagged a fragment as "%s" but forgot to specify the "category" attribute.',
                    FragmentRegistryInterface::CONTENT_ELEMENT_FRAGMENT
                ));
            }

            $GLOBALS['TL_CTE'][$options['category']][$options['type']] = ContentProxy::class;
        }
    }

    /**
     * Returns the tag filter function.
     *
     * @param string $tag
     *
     * @return \Closure
     */
    private function getTagFilter(string $tag): \Closure
    {
        return function ($identifier) use ($tag) {
            $options = $this->fragmentRegistry->getOptions($identifier);

            return $options['tag'] === $tag;
        };
    }
}
