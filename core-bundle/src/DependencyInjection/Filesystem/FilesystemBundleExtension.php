<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DependencyInjection\Filesystem;

use Symfony\Component\DependencyInjection\Extension\ConfigurableExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\BundleExtension;

/**
 * @experimental
 */
class FilesystemBundleExtension extends BundleExtension implements ConfigureFilesystemInterface
{
    public function __construct(
        private readonly ConfigurableExtensionInterface&ConfigureFilesystemInterface $subject,
        string $alias,
    ) {
        parent::__construct($subject, $alias);
    }

    public function configureFilesystem(FilesystemConfiguration $config): void
    {
        $this->subject->configureFilesystem($config);
    }
}
