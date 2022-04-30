<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\PageModel;

/**
 * @psalm-type TOverrideDnsConfiguration = array{dns?: string, protocol?: string}
 */
class OverrideDnsSettingsListener
{
    private array $configuration;

    /**
     * @param array<string,array<string,string>> $configuration
     * @psalm-type array<string,TOverrideDnsConfiguration>
     */
    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

    public function __invoke(array $parentModels, PageModel $page): void
    {
        if (!isset($this->configuration[$page->domain])) {
            return;
        }

        $config = $this->configuration[$page->domain];

        if (isset($config['dns'])) {
            $page->domain = $config['dns'];
        }

        if (isset($config['protocol'])) {
            $page->rootUseSSL = 'https' === $config['protocol'];
            $page->useSSL = $page->rootUseSSL;
        }
    }
}
