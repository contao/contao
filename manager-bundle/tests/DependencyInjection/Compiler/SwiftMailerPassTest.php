<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\DependencyInjection\Compiler;

use Contao\ManagerBundle\DependencyInjection\Compiler\SwiftMailerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SwiftMailerPassTest extends TestCase
{
    public function testUpdatesTheMailerTransport(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('mailer_transport', 'mail');

        $pass = new SwiftMailerPass();
        $pass->process($container);

        $this->assertSame('sendmail', $container->getParameter('mailer_transport'));
    }
}
