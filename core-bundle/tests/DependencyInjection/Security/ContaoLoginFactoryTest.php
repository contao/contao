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

        $authenticatorId = $factory->createAuthenticator(
            $container,
            'contao_frontend',
            ['remember_me' => true],
            'contao.security.frontend_user_provider',
        );

        $twoFactorAuthenticatorId = TwoFactorFactory::AUTHENTICATOR_ID_PREFIX.'contao_frontend';
        $twoFactorListenerId = TwoFactorFactory::PROVIDER_PREPARATION_LISTENER_ID_PREFIX.'contao_frontend';
        $twoFactorFirewallConfigId = 'contao.security.two_factor_firewall_config.contao_frontend';

        $this->assertSame('contao.security.login_authenticator.contao_frontend', $authenticatorId);

        $this->assertTrue($container->hasDefinition($authenticatorId));

        $arguments = $container->getDefinition($authenticatorId)->getArguments();

        $this->assertCount(5, $arguments);
        $this->assertEquals(new Reference('contao.security.frontend_user_provider'), $arguments['$userProvider']);
        $this->assertEquals(new Reference('contao.security.authentication_success_handler'), $arguments['$successHandler']);
        $this->assertEquals(new Reference('contao.security.authentication_failure_handler'), $arguments['$failureHandler']);
        $this->assertEquals(new Reference('security.authenticator.two_factor.contao_frontend'), $arguments['$twoFactorAuthenticator']);

        $this->assertTrue($container->hasDefinition($twoFactorFirewallConfigId));

        $arguments = $container->getDefinition($twoFactorFirewallConfigId)->getArguments();

        $this->assertSame(['remember_me' => true], $arguments['$options']);
        $this->assertSame('contao_frontend', $arguments['$firewallName']);

        $this->assertTrue($container->hasDefinition($twoFactorAuthenticatorId));

        $arguments = $container->getDefinition($twoFactorAuthenticatorId)->getArguments();

        $this->assertCount(4, $arguments);
        $this->assertEquals(new Reference($twoFactorFirewallConfigId), $arguments['$twoFactorFirewallConfig']);
        $this->assertEquals(new Reference('contao.security.authentication_success_handler'), $arguments['$successHandler']);
        $this->assertEquals(new Reference('contao.security.authentication_failure_handler'), $arguments['$failureHandler']);
        $this->assertEquals(new Reference('security.authentication.authentication_required_handler.two_factor.contao_frontend'), $arguments['$authenticationRequiredHandler']);

        $this->assertTrue($container->hasDefinition($twoFactorListenerId));

        $arguments = $container->getDefinition($twoFactorListenerId)->getArguments();

        $this->assertCount(3, $arguments);
        $this->assertSame('contao_frontend', $arguments['$firewallName']);
        $this->assertTrue($arguments['$prepareOnLogin']);
        $this->assertFalse($arguments['$prepareOnAccessDenied']);

        $this->assertTrue($container->getDefinition($twoFactorListenerId)->hasTag('kernel.event_subscriber'));
    }
}
