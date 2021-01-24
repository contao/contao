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

use Contao\CoreBundle\Security\TwoFactor\BackupCodeManager;
use Scheb\TwoFactorBundle\DependencyInjection\Factory\Security\TwoFactorFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ContaoLoginFactory extends AbstractFactory
{
    public function __construct()
    {
        $this->options = ['require_previous_session' => false];
        $this->defaultSuccessHandlerOptions = [];
        $this->defaultFailureHandlerOptions = [];
    }

    public function create(ContainerBuilder $container, $id, $config, $userProviderId, $defaultEntryPointId): array
    {
        $ids = parent::create($container, $id, $config, $userProviderId, $defaultEntryPointId);

        $this->createTwoFactorPreparationListener($container, $id);

        return $ids;
    }

    public function getPosition(): string
    {
        return 'form';
    }

    public function getKey(): string
    {
        return 'contao-login';
    }

    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId): string
    {
        $twoFactorProviderId = TwoFactorFactory::PROVIDER_ID_PREFIX.$id;
        $twoFactorFirewallConfigId = 'contao.security.two_factor_firewall_config.'.$id;

        $container
            ->setDefinition($twoFactorFirewallConfigId, new ChildDefinition(TwoFactorFactory::FIREWALL_CONFIG_DEFINITION_ID))
        ;

        $container
            ->setDefinition($twoFactorProviderId, new ChildDefinition(TwoFactorFactory::PROVIDER_DEFINITION_ID))
            ->replaceArgument(0, new Reference($twoFactorFirewallConfigId))
            ->replaceArgument(2, new Reference(BackupCodeManager::class))
        ;

        $provider = 'contao.security.authentication_provider.'.$id;

        $container
            ->setDefinition($provider, new ChildDefinition('contao.security.authentication_provider'))
            ->replaceArgument(0, new Reference($userProviderId))
            ->replaceArgument(1, new Reference('security.user_checker.'.$id))
            ->replaceArgument(2, $id)
            ->replaceArgument(5, new Reference($twoFactorProviderId))
        ;

        return $provider;
    }

    protected function getListenerId(): string
    {
        return 'contao.security.authentication_listener';
    }

    protected function createEntryPoint($container, $id, $config, $defaultEntryPointId): string
    {
        return 'contao.security.entry_point';
    }

    protected function createAuthenticationSuccessHandler($container, $id, $config): string
    {
        return 'contao.security.authentication_success_handler';
    }

    protected function createAuthenticationFailureHandler($container, $id, $config): string
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
}
