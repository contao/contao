<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DependencyInjection\Security;

use Contao\CoreBundle\DependencyInjection\Security\ContaoLoginFactory;
use Contao\CoreBundle\Tests\TestCase;
use Scheb\TwoFactorBundle\DependencyInjection\Factory\Security\TwoFactorFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ContaoLoginFactoryTest extends TestCase
{
    public function testReturnsTheCorrectKey(): void
    {
        $this->assertSame('contao-login', (new ContaoLoginFactory())->getKey());
    }

    public function testConfiguresTheContainerServices(): void
    {
        $container = new ContainerBuilder();
        $factory = new ContaoLoginFactory();

        [$authProviderId, $listenerId, $defaultEntryPoint] = $factory->create(
            $container,
            'contao_frontend',
            ['remember_me' => true],
            'contao.security.frontend_user_provider',
            null
        );

        $twoFactorProviderId = TwoFactorFactory::PROVIDER_ID_PREFIX.'contao_frontend';
        $twoFactorListenerId = TwoFactorFactory::PROVIDER_PREPARATION_LISTENER_ID_PREFIX.'contao_frontend';
        $twoFactorFirewallConfigId = 'contao.security.two_factor_firewall_config.contao_frontend';

        $this->assertSame('contao.security.authentication_provider.contao_frontend', $authProviderId);
        $this->assertSame('contao.security.login_authentication_listener.contao_frontend', $listenerId);
        $this->assertSame('contao.security.authentication_entry_point', $defaultEntryPoint);

        $this->assertTrue($container->hasDefinition($authProviderId));

        $arguments = $container->getDefinition($authProviderId)->getArguments();

        $this->assertIsArray($arguments);
        $this->assertCount(4, $arguments);
        $this->assertEquals(new Reference('contao.security.frontend_user_provider'), $arguments['index_0']);
        $this->assertEquals(new Reference('security.user_checker.contao_frontend'), $arguments['index_1']);
        $this->assertSame('contao_frontend', $arguments['index_2']);
        $this->assertEquals(new Reference($twoFactorProviderId), $arguments['index_5']);

        $this->assertTrue($container->hasDefinition($twoFactorFirewallConfigId));

        $arguments = $container->getDefinition($twoFactorFirewallConfigId)->getArguments();

        $this->assertIsArray($arguments);
        $this->assertSame(['remember_me' => true], $arguments['index_0']);
        $this->assertSame('contao_frontend', $arguments['index_1']);

        $this->assertTrue($container->hasDefinition($twoFactorProviderId));

        $arguments = $container->getDefinition($twoFactorProviderId)->getArguments();

        $this->assertIsArray($arguments);
        $this->assertCount(2, $arguments);
        $this->assertEquals(new Reference($twoFactorFirewallConfigId), $arguments['index_0']);
        $this->assertEquals(new Reference('contao.security.two_factor.backup_code_manager'), $arguments['index_2']);

        $this->assertTrue($container->hasDefinition($twoFactorListenerId));

        $arguments = $container->getDefinition($twoFactorListenerId)->getArguments();

        $this->assertIsArray($arguments);
        $this->assertCount(3, $arguments);
        $this->assertSame('contao_frontend', $arguments['index_3']);
        $this->assertTrue($arguments['index_4']);
        $this->assertFalse($arguments['index_5']);

        $this->assertTrue($container->getDefinition($twoFactorListenerId)->hasTag('kernel.event_subscriber'));

        $this->assertTrue($container->hasDefinition('security.authorization_checker'));
        $this->assertFalse($container->getDefinition('security.authorization_checker')->getArgument(3));
    }
}
