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

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\FormLoginFactory;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ContaoLoginFactory extends FormLoginFactory
{
    public function __construct()
    {
        parent::__construct();

        $this->addOption('lock_period', 300);
        $this->addOption('login_attempts', 3);
        $this->addOption('username_parameter', 'username');
        $this->addOption('password_parameter', 'password');

        unset(
            $this->defaultSuccessHandlerOptions['target_path_parameter'],
            $this->defaultSuccessHandlerOptions['use_referer'],
            $this->defaultFailureHandlerOptions['failure_path_parameter']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getKey(): string
    {
        return 'contao-login';
    }

    protected function getListenerId(): string
    {
        return 'contao.security.authentication_listener';
    }

    protected function getSuccessHandlerId($id): string
    {
        return 'contao.security.authentication_success_handler';
    }

    protected function getFailureHandlerId($id): string
    {
        return 'contao.security.authentication_failure_handler';
    }

    /**
     * {@inheritdoc}
     */
    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId): string
    {
        $provider = 'contao.security.authentication_provider.'.$id;

        $container
            ->setDefinition($provider, new ChildDefinition('contao.security.authentication_provider'))
            ->replaceArgument(0, new Reference($userProviderId))
            ->replaceArgument(1, new Reference('security.user_checker.'.$id))
            ->replaceArgument(2, $id)
            ->addArgument(
                [
                    'lock_period' => $config['lock_period'],
                    'login_attempts' => $config['login_attempts'],
                ]
            )
        ;

        return $provider;
    }
}
