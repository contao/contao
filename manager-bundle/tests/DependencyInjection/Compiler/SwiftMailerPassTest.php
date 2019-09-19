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

    /**
     * @dataProvider getParameters
     */
    public function testGeneratesTheMailerUrlVariable(string $transport, string $host, ?string $user, ?string $password, int $port, ?string $encryption, string $expected): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('mailer_transport', $transport);
        $container->setParameter('mailer_host', $host);
        $container->setParameter('mailer_user', $user);
        $container->setParameter('mailer_password', $password);
        $container->setParameter('mailer_port', $port);
        $container->setParameter('mailer_encryption', $encryption);

        $pass = new SwiftMailerPass();
        $pass->process($container);

        $parameters = $container->getParameterBag()->all();

        $this->assertSame($expected, $parameters['env(MAILER_URL)']);
    }

    public function getParameters(): \Generator
    {
        yield [
            'sendmail',
            '127.0.0.1',
            null,
            null,
            25,
            null,
            'sendmail://localhost',
        ];

        yield [
            'smtp',
            '127.0.0.1',
            null,
            null,
            25,
            null,
            'smtp://127.0.0.1:25',
        ];

        yield [
            'smtp',
            '127.0.0.1',
            'foo@bar.com',
            'foobar',
            25,
            null,
            'smtp://127.0.0.1:25?username=foo%40bar.com&password=foobar',
        ];

        yield [
            'smtp',
            '127.0.0.1',
            null,
            null,
            587,
            'tls',
            'smtp://127.0.0.1:587?encryption=tls',
        ];

        yield [
            'smtp',
            '127.0.0.1',
            'foo@bar.com',
            'foobar',
            587,
            'tls',
            'smtp://127.0.0.1:587?username=foo%40bar.com&password=foobar&encryption=tls',
        ];
    }
}
