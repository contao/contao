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

use Contao\CoreBundle\DependencyInjection\Compiler\MakeServicesPublicPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class MakeServicesPublicPassTest extends TestCase
{
    public function testMakesTheServicesPublic(): void
    {
        $container = new ContainerBuilder();

        // Definitions
        $container->setDefinition('assets.packages', (new Definition())->setPublic(false));
        $container->setDefinition('debug.stopwatch', (new Definition())->setPublic(false));
        $container->setDefinition('fragment.handler', (new Definition())->setPublic(false));
        $container->setDefinition('monolog.logger.contao', (new Definition())->setPublic(false));
        $container->setDefinition('security.authentication_utils', (new Definition())->setPublic(false));
        $container->setDefinition('security.authentication.trust_resolver', (new Definition())->setPublic(false));
        $container->setDefinition('security.authorization_checker', (new Definition())->setPublic(false));
        $container->setDefinition('security.encoder_factory', (new Definition())->setPublic(false));
        $container->setDefinition('security.firewall.map', (new Definition())->setPublic(false));
        $container->setDefinition('security.helper', (new Definition())->setPublic(false));
        $container->setDefinition('security.logout_url_generator', (new Definition())->setPublic(false));
        $container->setDefinition('security.password_hasher_factory', (new Definition())->setPublic(false));
        $container->setDefinition('security.token_storage', (new Definition())->setPublic(false));
        $container->setDefinition('twig', (new Definition())->setPublic(false));
        $container->setDefinition('uri_signer', (new Definition())->setPublic(false));

        // Aliased definitions
        $container->setDefinition('doctrine.dbal.default_connection', (new Definition())->setPublic(false));
        $container->setDefinition('mailer.mailer', (new Definition())->setPublic(false));

        // Aliases
        $container->setAlias('database_connection', 'doctrine.dbal.default_connection');
        $container->setAlias('mailer', 'mailer.mailer');

        $pass = new MakeServicesPublicPass();
        $pass->process($container);

        // Definitions
        $this->assertTrue($container->getDefinition('assets.packages')->isPublic());
        $this->assertTrue($container->getDefinition('debug.stopwatch')->isPublic());
        $this->assertTrue($container->getDefinition('fragment.handler')->isPublic());
        $this->assertTrue($container->getDefinition('monolog.logger.contao')->isPublic());
        $this->assertTrue($container->getDefinition('security.authentication_utils')->isPublic());
        $this->assertTrue($container->getDefinition('security.authentication.trust_resolver')->isPublic());
        $this->assertTrue($container->getDefinition('security.authorization_checker')->isPublic());
        $this->assertTrue($container->getDefinition('security.encoder_factory')->isPublic());
        $this->assertTrue($container->getDefinition('security.firewall.map')->isPublic());
        $this->assertTrue($container->getDefinition('security.helper')->isPublic());
        $this->assertTrue($container->getDefinition('security.logout_url_generator')->isPublic());
        $this->assertTrue($container->getDefinition('security.password_hasher_factory')->isPublic());
        $this->assertTrue($container->getDefinition('security.token_storage')->isPublic());
        $this->assertTrue($container->getDefinition('twig')->isPublic());
        $this->assertTrue($container->getDefinition('uri_signer')->isPublic());

        // Aliases
        $this->assertTrue($container->getAlias('database_connection')->isPublic());
        $this->assertTrue($container->getAlias('mailer')->isPublic());
    }
}
