<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DependencyInjection\Compiler;

use Contao\CoreBundle\DependencyInjection\Compiler\CheckCspReportingPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;

class CheckCspReportingPassTest extends TestCase
{
    public function testThrowsExceptionIfReportingIsEnabledAndNelmioSecurityBundleIsNotLoaded(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('contao.csp.reporting.enabled', true);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot enable Contao CSP reporting if the NelmioSecurityBundle was not loaded.');

        (new CheckCspReportingPass())->process($container);
    }
}
