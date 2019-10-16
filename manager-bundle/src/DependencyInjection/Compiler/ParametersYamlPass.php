<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ParametersYamlPass implements CompilerPassInterface
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $this->container = $container;

        // The "mail" transport has been removed in SwiftMailer 6, so use "sendmail" instead
        if ('mail' === $this->container->getParameter('mailer_transport')) {
            $this->container->setParameter('mailer_transport', 'sendmail');
        }

        if (!isset($_SERVER['APP_SECRET'])) {
            $this->container->setParameter('env(APP_SECRET)', $this->getAppSecret());
        }

        if (!isset($_SERVER['DATABASE_URL'])) {
            $this->container->setParameter('env(DATABASE_URL)', $this->getDatabaseUrl());
        }

        if (!isset($_SERVER['MAILER_URL'])) {
            $this->container->setParameter('env(MAILER_URL)', $this->getMailerUrl());
        }
    }

    private function getAppSecret(): string
    {
        return $this->container->getParameter('secret');
    }

    private function getDatabaseUrl(): string
    {
        $userPassword = '';

        if ($user = $this->container->getParameter('database_user')) {
            $userPassword = $user;

            if ($password = $this->container->getParameter('database_password')) {
                $userPassword .= ':'.$password;
            }

            $userPassword .= '@';
        }

        $dbName = '';

        if ($name = $this->container->getParameter('database_name')) {
            $dbName = '/'.$name;
        }

        return sprintf(
            'mysql://%s%s:%s%s',
            $userPassword,
            $this->container->getParameter('database_host'),
            $this->container->getParameter('database_port'),
            $dbName
        );
    }

    private function getMailerUrl(): string
    {
        if ('sendmail' === $this->container->getParameter('mailer_transport')) {
            return 'sendmail://localhost';
        }

        $parameters = [];

        if ($user = $this->container->getParameter('mailer_user')) {
            $parameters[] = 'username='.rawurlencode($user);

            if ($password = $this->container->getParameter('mailer_password')) {
                $parameters[] = 'password='.rawurlencode($password);
            }
        }

        if ($encryption = $this->container->getParameter('mailer_encryption')) {
            $parameters[] = 'encryption='.rawurlencode($encryption);
        }

        $qs = '';

        if (!empty($parameters)) {
            $qs = '?'.implode('&', $parameters);
        }

        return sprintf(
            'smtp://%s:%s%s',
            rawurlencode($this->container->getParameter('mailer_host')),
            (int) $this->container->getParameter('mailer_port'),
            $qs
        );
    }
}
