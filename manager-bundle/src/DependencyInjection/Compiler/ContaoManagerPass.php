<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ContaoManagerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $webDir = $container->getParameter('contao.web_dir');
        $managerPath = $container->getParameter('contao_manager.manager_path');

        if (null === $managerPath) {
            if (is_file($webDir.'/contao-manager.phar.php')) {
                $managerPath = 'contao-manager.phar.php';
            }
        } elseif (!is_file($webDir.'/'.$managerPath)) {
            throw new \LogicException(
                sprintf(
                    'You have configured "contao_manager.manager_path" but the file "%s" does not exist',
                    $webDir.'/'.$managerPath
                )
            );
        }

        $container->setParameter('contao_manager.manager_path', $managerPath);
    }
}
