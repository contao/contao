<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\FrontendPreview;

/**
 * This class holds arbitrary toolbar providers, each adding content to the front end preview toolbar.
 */
class FrontendPreviewProviderManager
{
    /**
     * @var array<ToolbarProviderInterface>
     */
    private $providers = [];

    public function addProvider(ToolbarProviderInterface $provider): void
    {
        $this->providers[$provider->getName()] = $provider;
    }

    /**
     * @return array<ToolbarProviderInterface>
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    public function getProviderNames(): array
    {
        return array_keys($this->providers);
    }

    public function getProvider($name): ?ToolbarProviderInterface
    {
        return $this->providers[$name] ?? null;
    }
}
