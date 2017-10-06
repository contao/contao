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

class FragmentRegistry implements FragmentRegistryInterface
{
    /**
     * @var array
     */
    private $fragments = [];

    /**
     * @var array
     */
    private $fragmentOptions = [];

    /**
     * {@inheritdoc}
     */
    public function addFragment(string $identifier, $fragment, array $options): FragmentRegistryInterface
    {
        if (3 !== \count(array_intersect(array_keys($options), ['tag', 'type', 'controller']))) {
            throw new \InvalidArgumentException('Missing the three basic options "tag", "type" and "controller".');
        }

        // Override existing fragments with the same identifier
        $this->fragments[$identifier] = $fragment;
        $this->fragmentOptions[$identifier] = $options;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFragment(string $identifier)
    {
        return $this->fragments[$identifier];
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(string $identifier): array
    {
        return $this->fragmentOptions[$identifier];
    }

    /**
     * {@inheritdoc}
     */
    public function getFragments(callable $filter = null): array
    {
        $matches = [];

        foreach ($this->fragments as $identifier => $fragment) {
            if (null !== $filter && !$filter($identifier, $fragment)) {
                continue;
            }

            $matches[$identifier] = $fragment;
        }

        return $matches;
    }
}
