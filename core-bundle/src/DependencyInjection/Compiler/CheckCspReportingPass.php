<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;

/**
 * @internal
 */
class CheckCspReportingPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->getParameter('contao.csp.reporting.enabled') && !$container->hasDefinition('nelmio_security.csp_reporter_controller')) {
            throw new LogicException('Cannot enable Contao CSP reporting if the NelmioSecurityBundle was not loaded.');
        }
    }
}
