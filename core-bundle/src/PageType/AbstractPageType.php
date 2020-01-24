<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\PageType;

abstract class AbstractPageType implements PageTypeInterface
{
    /**
     * Map of parameter name and it's requirement.
     *
     * If no special requirement is given
     *
     * @var array
     */
    protected $parameters = [];

    /**
     * List of supported features.
     *
     * @var array
     */
    protected $features = [
        self::FEATURE_ARTICLES
    ];

    /**
     * Computes the name of the page type by using unqualified classname without suffix "PageType" and converts it to
     * camelize.
     *
     * @return string
     */
    public function getName(): string
    {
        return \strtolower(
            \preg_replace(
                '/(?<!^)(?<![0-9])[A-Z0-9]/',
                '_$0',
                \substr(\strrchr(static::class, '\\'), 1, -8)
            )
        );
    }

    public function getAvailableParameters(): array
    {
        return \array_keys($this->parameters);
    }

    public function getRequiredParameters(): array
    {
        return [];
    }

    public function supportFeature(string $feature): void
    {
        if ($this->supportsFeature($feature)) {
            return;
        }

        $this->features[] = $feature;
    }

    public function supportsFeature(string $feature) : bool
    {
        return \in_array($feature, static::$features, true);
    }
}
