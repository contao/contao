<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle;

use Contao\ManagerBundle\DependencyInjection\Compiler\ContaoManagerPass;
use Contao\ManagerBundle\DependencyInjection\Compiler\SwiftMailerPass;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ContaoManagerBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ContaoManagerPass());
        $container->addCompilerPass(new SwiftMailerPass());
    }

    /**
     * {@inheritdoc}
     */
    public function registerCommands(Application $application): void
    {
        // disable automatic command registration
    }
}
