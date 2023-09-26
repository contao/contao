<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\InstallationBundle;

use Contao\InstallationBundle\Event\ContaoInstallationEvents;
use Contao\InstallationBundle\Event\InitializeApplicationEvent;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\DependencyInjection\AddEventAliasesPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ContaoInstallationBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(
            new AddEventAliasesPass([
                InitializeApplicationEvent::class => ContaoInstallationEvents::INITIALIZE_APPLICATION,
            ])
        );
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
