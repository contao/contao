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

        $container
            ->setDefinition($twoFactorProviderId, new ChildDefinition(TwoFactorFactory::PROVIDER_DEFINITION_ID))
            ->replaceArgument(0, $id)
            ->replaceArgument(1, [])
            ->replaceArgument(3, new Reference(BackupCodeManager::class))
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

    protected function createEntryPoint($container, $id, $config, $defaultEntryPoint): string
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
            ->addTag('kernel.event_listener', ['event' => 'security.authentication.success', 'method' => 'onLogin', 'priority' => PHP_INT_MAX])
            ->addTag('kernel.event_listener', ['event' => 'scheb_two_factor.authentication.form', 'method' => 'onTwoFactorForm'])
            ->addTag('kernel.event_listener', ['event' => 'kernel.finish_request', 'method' => 'onKernelFinishRequest'])
        ;
    }
}
