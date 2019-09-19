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

class SwiftMailerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('mailer_transport')) {
            return;
        }

        // The "mail" transport has been removed in SwiftMailer 6, so use "sendmail" instead
        if ('mail' === $container->getParameter('mailer_transport')) {
            $container->setParameter('mailer_transport', 'sendmail');
        }

        // Build the mailer URL from the parameters.yml configuration
        if (!isset($_SERVER['MAILER_URL'])) {
            if ('sendmail' === $container->getParameter('mailer_transport')) {
                $container->setParameter('env(MAILER_URL)', 'sendmail://localhost');
            } elseif ('smtp' === $container->getParameter('mailer_transport')) {
                $parameters = [];

                if ($username = $container->getParameter('mailer_user')) {
                    $parameters[] = 'username='.rawurlencode($container->getParameter('mailer_user'));
                }

                if ($username = $container->getParameter('mailer_password')) {
                    $parameters[] = 'password='.rawurlencode($container->getParameter('mailer_password'));
                }

                if ($username = $container->getParameter('mailer_encryption')) {
                    $parameters[] = 'encryption='.rawurlencode($container->getParameter('mailer_encryption'));
                }

                $append = '';

                if (!empty($parameters)) {
                    $append = '?'.implode('&', $parameters);
                }

                $container->setParameter(
                    'env(MAILER_URL)',
                    sprintf(
                        'smtp://%s:%s%s',
                        rawurlencode($container->getParameter('mailer_host')),
                        (int) $container->getParameter('mailer_port'),
                        $append
                    )
                );
            }
        }
    }
}
