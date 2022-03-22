<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DependencyInjection\Security;

use Scheb\TwoFactorBundle\DependencyInjection\Factory\Security\TwoFactorFactory;
use Scheb\TwoFactorBundle\DependencyInjection\Factory\Security\TwoFactorServicesFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ContaoLoginFactory extends AbstractFactory implements AuthenticatorFactoryInterface
{
    public function __construct()
    {
        $this->options = [
            'require_previous_session' => false,
            'auth_code_parameter_name' => 'verify',
        ];
        $this->defaultSuccessHandlerOptions = [];
        $this->defaultFailureHandlerOptions = [];
    }

    public function getPosition(): string
    {
        return 'form';
    }

    public function getKey(): string
    {
        return 'contao-login';
    }

    public function createAuthenticator(ContainerBuilder $container, string $firewallName, array $config, string $userProviderId): string
    {
        $twoFactorAuthenticatorId = TwoFactorFactory::AUTHENTICATOR_ID_PREFIX.$firewallName;
        $twoFactorFirewallConfigId = 'contao.security.two_factor_firewall_config.'.$firewallName;

        $container
            ->setDefinition($twoFactorFirewallConfigId, new ChildDefinition(TwoFactorFactory::FIREWALL_CONFIG_DEFINITION_ID))
            ->replaceArgument(0, $config)
            ->replaceArgument(1, $firewallName)
        ;

        $container
            ->setDefinition($twoFactorAuthenticatorId, new ChildDefinition(TwoFactorFactory::AUTHENTICATOR_DEFINITION_ID))
            ->replaceArgument(0, new Reference($twoFactorFirewallConfigId))
            ->replaceArgument(2, new Reference($this->createAuthenticationSuccessHandler($container, $firewallName, $config)))
            ->replaceArgument(3, new Reference($this->createAuthenticationFailureHandler($container, $firewallName, $config)))
            ->replaceArgument(4, new Reference((new TwoFactorServicesFactory())->createAuthenticationRequiredHandler($container, $firewallName, $config, $twoFactorFirewallConfigId)))
        ;

        $this->createTwoFactorPreparationListener($container, $firewallName);
        $this->createTwoFactorAuthenticationTokenCreatedListener($container, $firewallName);

        $authenticatorId = 'contao.security.login_authenticator.'.$firewallName;
        $options = array_intersect_key($config, $this->options);

        $container
            ->setDefinition($authenticatorId, new ChildDefinition('contao.security.login_authenticator'))
            ->replaceArgument(0, new Reference($userProviderId))
            ->replaceArgument(1, new Reference($this->createAuthenticationSuccessHandler($container, $firewallName, $config)))
            ->replaceArgument(2, new Reference($this->createAuthenticationFailureHandler($container, $firewallName, $config)))
            ->replaceArgument(8, new Reference($twoFactorAuthenticatorId))
            ->replaceArgument(9, $options)
        ;

        return $authenticatorId;
    }

    protected function createAuthProvider(ContainerBuilder $container, string $id, array $config, string $userProviderId): string
    {
        throw new \RuntimeException('The old authentication system is not supported with contao_login.');
    }

    protected function getListenerId(): string
    {
        throw new \RuntimeException('The old authentication system is not supported with contao_login.');
    }

    protected function createListener(ContainerBuilder $container, string $id, array $config, string $userProvider): string
    {
        throw new \RuntimeException('The old authentication system is not supported with contao_login.');
    }

    protected function createEntryPoint(ContainerBuilder $container, string $id, array $config, string|null $defaultEntryPointId): string
    {
        throw new \RuntimeException('The old authentication system is not supported with contao_login.');
    }

    protected function createAuthenticationSuccessHandler(ContainerBuilder $container, string $id, array $config): string
    {
        return 'contao.security.authentication_success_handler';
    }

    protected function createAuthenticationFailureHandler(ContainerBuilder $container, string $id, array $config): string
    {
        return 'contao.security.authentication_failure_handler';
    }

    private function createTwoFactorPreparationListener(ContainerBuilder $container, string $firewallName): void
    {
        $firewallConfigId = TwoFactorFactory::PROVIDER_PREPARATION_LISTENER_ID_PREFIX.$firewallName;
        $container
            ->setDefinition($firewallConfigId, new ChildDefinition(TwoFactorFactory::PROVIDER_PREPARATION_LISTENER_DEFINITION_ID))
            ->replaceArgument(3, $firewallName)
            ->replaceArgument(4, true)
            ->replaceArgument(5, false)
            ->addTag('kernel.event_subscriber')
        ;
    }

    private function createTwoFactorAuthenticationTokenCreatedListener(ContainerBuilder $container, string $firewallName): void
    {
        $listenerId = TwoFactorFactory::AUTHENTICATION_TOKEN_CREATED_LISTENER_ID_PREFIX.$firewallName;
        $container
            ->setDefinition($listenerId, new ChildDefinition(TwoFactorFactory::AUTHENTICATION_TOKEN_CREATED_LISTENER_DEFINITION_ID))
            ->replaceArgument(0, $firewallName)
            // Important: register event only for the specific firewall
            ->addTag('kernel.event_subscriber', ['dispatcher' => 'security.event_dispatcher.'.$firewallName])
        ;
    }
}
