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

use Contao\ManagerBundle\DependencyInjection\Compiler\ParametersYamlPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ParametersYamlPassTest extends TestCase
{
    public function testUpdatesTheMailerTransport(): void
    {
        $container = $this->getContainer();
        $container->setParameter('mailer_transport', 'mail');

        $pass = new ParametersYamlPass();
        $pass->process($container);

        $this->assertSame('sendmail', $container->getParameter('mailer_transport'));
    }

    public function testSetsTheAppSecret(): void
    {
        $container = $this->getContainer();

        $pass = new ParametersYamlPass();
        $pass->process($container);

        $bag = $container->getParameterBag()->all();

        $this->assertSame('ThisTokenIsNotSoSecretChangeIt', $bag['env(APP_SECRET)']);
    }

    /**
     * @dataProvider getDatabaseParameters
     */
    public function testSetsTheDatabaseUrl(?string $user, ?string $password, ?string $name, string $expected): void
    {
        $container = $this->getContainer();
        $container->setParameter('database_user', $user);
        $container->setParameter('database_password', $password);
        $container->setParameter('database_name', $name);

        $pass = new ParametersYamlPass();
        $pass->process($container);

        $bag = $container->getParameterBag()->all();

        $this->assertSame($expected, $bag['env(DATABASE_URL)']);
    }

    public function getDatabaseParameters(): \Generator
    {
        yield [
            null,
            null,
            null,
            'mysql://localhost:3306',
        ];

        yield [
            null,
            null,
            'contao_test',
            'mysql://localhost:3306/contao_test',
        ];

        yield [
            null,
            'foobar',
            'contao_test',
            'mysql://localhost:3306/contao_test',
        ];

        yield [
            'root',
            null,
            'contao_test',
            'mysql://root@localhost:3306/contao_test',
        ];

        yield [
            'root',
            'foobar',
            'contao_test',
            'mysql://root:foobar@localhost:3306/contao_test',
        ];
    }

    /**
     * @dataProvider getMailerParameters
     */
    public function testSetsTheMailerUrl(string $transport, string $host, ?string $user, ?string $password, int $port, ?string $encryption, string $expected): void
    {
        $container = $this->getContainer();
        $container->setParameter('mailer_transport', $transport);
        $container->setParameter('mailer_host', $host);
        $container->setParameter('mailer_user', $user);
        $container->setParameter('mailer_password', $password);
        $container->setParameter('mailer_port', $port);
        $container->setParameter('mailer_encryption', $encryption);

        $pass = new ParametersYamlPass();
        $pass->process($container);

        $parameters = $container->getParameterBag()->all();

        $this->assertSame($expected, $parameters['env(MAILER_URL)']);
    }

    public function getMailerParameters(): \Generator
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
            null,
            'foobar',
            25,
            null,
            'smtp://127.0.0.1:25',
        ];

        yield [
            'smtp',
            '127.0.0.1',
            'foo@bar.com',
            null,
            25,
            null,
            'smtp://127.0.0.1:25?username=foo%40bar.com',
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

    private function getContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('database_host', 'localhost');
        $container->setParameter('database_port', 3306);
        $container->setParameter('database_user', null);
        $container->setParameter('database_password', null);
        $container->setParameter('database_name', null);
        $container->setParameter('mailer_transport', 'sendmail');
        $container->setParameter('mailer_host', '127.0.0.1');
        $container->setParameter('mailer_user', null);
        $container->setParameter('mailer_password', null);
        $container->setParameter('mailer_port', 25);
        $container->setParameter('mailer_encryption', null);
        $container->setParameter('secret', 'ThisTokenIsNotSoSecretChangeIt');

        return $container;
    }
}
