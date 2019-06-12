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

use Contao\CoreBundle\DependencyInjection\Compiler\RemembermeServicesPass;
use Contao\CoreBundle\Security\Authentication\RememberMe\ExpiringTokenBasedRememberMeServices;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Security\Http\RememberMe\TokenBasedRememberMeServices;

class RemembermeServicesPassTest extends TestCase
{
    public function testCreatesTheRemembermeServiceAndOverridesTheSimpleHashService(): void
    {
        $serviceId = RemembermeServicesPass::TEMPLATE_ID.'.contao_frontend';
        $overrideId = RemembermeServicesPass::OVERRIDE_PREFIX.'.contao_frontend';

        $container = $this->getContainerWithContaoConfiguration();
        $container->setDefinition(
            $overrideId,
            new Definition(TokenBasedRememberMeServices::class, [null, null, null, null])
        );

        $pass = new RemembermeServicesPass('contao_frontend');
        $pass->process($container);

        $this->assertTrue($container->hasDefinition($serviceId));
        $this->assertSame($serviceId, (string) $container->getAlias($overrideId));
    }

    public function testDoesNothingIfRemembermeIsNotEnabledForTheFirewall(): void
    {
        $serviceId = RemembermeServicesPass::TEMPLATE_ID.'.contao_backend';
        $overrideId = RemembermeServicesPass::OVERRIDE_PREFIX.'.contao_backend';

        $container = $this->getContainerWithContaoConfiguration();

        $pass = new RemembermeServicesPass('contao_backend');
        $pass->process($container);

        $this->assertFalse($container->hasDefinition($serviceId));
        $this->assertFalse($container->hasAlias($overrideId));
    }

    public function testInheritsTheArgumentsFromTheSimplehashService(): void
    {
        $serviceId = RemembermeServicesPass::TEMPLATE_ID.'.contao_frontend';
        $overrideId = RemembermeServicesPass::OVERRIDE_PREFIX.'.contao_frontend';

        $container = $this->getContainerWithContaoConfiguration();
        $container->setDefinition(
            $overrideId,
            new Definition(TokenBasedRememberMeServices::class, [1, 2, 3, 4])
        );

        $container->setDefinition(
            RemembermeServicesPass::TEMPLATE_ID,
            new Definition(ExpiringTokenBasedRememberMeServices::class)
        );

        $pass = new RemembermeServicesPass('contao_frontend');
        $pass->process($container);

        /** @var ChildDefinition $def */
        $def = $container->getDefinition($serviceId);

        $this->assertInstanceOf(ChildDefinition::class, $def);
        $this->assertSame(RemembermeServicesPass::TEMPLATE_ID, $def->getParent());
        $this->assertSame(1, $def->getArgument(1));
        $this->assertSame(2, $def->getArgument(2));
        $this->assertSame(3, $def->getArgument(3));
        $this->assertSame(4, $def->getArgument(4));
    }
}
