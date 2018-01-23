<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @license LGPL-3.0+
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

        unset(
            $this->options['username_parameter'],
            $this->options['password_parameter'],
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

    /**
     * {@inheritdoc}
     */
    protected function createListener($container, $id, $config, $userProvider): string
    {
        $listenerId = parent::createListener($container, $id, $config, $userProvider);

        /* @var ContainerBuilder $container */
        $container
            ->getDefinition($listenerId)
            ->replaceArgument(
                7,
                array_merge(
                    $container->getDefinition($listenerId)->getArgument(7),
                    ['username_parameter' => 'username', 'password_parameter' => 'password']
                )
            )
        ;

        return $listenerId;
    }
}
