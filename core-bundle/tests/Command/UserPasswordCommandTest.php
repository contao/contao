<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Command;

use Contao\CoreBundle\Command\UserPasswordCommand;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the UserPasswordCommandTest class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class UserPasswordCommandTest extends TestCase
{
    /**
     * @var UserPasswordCommand
     */
    protected $command;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $framework = $this->mockContaoFramework(
            null,
            null,
            [
                'Contao\Encryption' => $this->mockEncryptionAdapter(),
            ]
        );

        $connection = $this->getMock('Doctrine\DBAL\Connection', [], [], '', false);

        $this->container = $this->mockContainerWithContaoScopes();
        $this->container->set('contao.framework', $framework);
        $this->container->set('database_connection', $connection);

        $this->command = new UserPasswordCommand();
        $this->command->setContainer($this->container);
        $this->command->setApplication(new Application());
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CoreBundle\Command\UserPasswordCommand', $this->command);
        $this->assertEquals('contao:user:password', $this->command->getName());
    }

    /**
     * Tests the command configuration.
     */
    public function testConfiguration()
    {
        $this->assertNotEmpty($this->command->getDescription());

        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasArgument('username'));
        $this->assertTrue($definition->hasOption('password'));
    }

    /**
     * Tests the command without a username.
     *
     * @expectedException \Symfony\Component\Console\Exception\InvalidArgumentException
     * @expectedExceptionMessage Please provide the username as argument.
     */
    public function testExceptionWhenMissingUsername()
    {
        (new CommandTester($this->command))->execute([]);
    }

    /**
     * Tests the command without a password.
     */
    public function testExitCodeWithoutPassword()
    {
        $code = (new CommandTester($this->command))
            ->execute(
                ['username' => 'foobar'],
                ['interactive' => false]
            )
        ;

        $this->assertEquals(1, $code);
    }

    /**
     * Tests the minimum password length.
     *
     * @expectedException \Symfony\Component\Console\Exception\InvalidArgumentException
     * @expectedExceptionMessage The password must be at least 8 characters long.
     */
    public function testMinimumPasswordLength()
    {
        (new CommandTester($this->command))
            ->execute(
                [
                    'username' => 'foobar',
                    '--password' => '123456',
                ],
                ['interactive' => false]
            )
        ;
    }

    /**
     * Tests a custom minimum password length.
     *
     * @expectedException \Symfony\Component\Console\Exception\InvalidArgumentException
     * @expectedExceptionMessage The password must be at least 16 characters long.
     */
    public function testCustomPasswordLength()
    {
        $framework = $this->mockContaoFramework(
            null,
            null,
            [
                'Contao\Config' => $this->mockConfigAdapter(16),
                'Contao\Encryption' => $this->mockEncryptionAdapter(),
            ]
        );

        $container = $this->mockContainerWithContaoScopes();
        $container->set('contao.framework', $framework);

        $command = new UserPasswordCommand();
        $command->setContainer($container);
        $command->setApplication(new Application());

        (new CommandTester($command))
            ->execute(
                [
                    'username' => 'foobar',
                    '--password' => '123456789',
                ],
                ['interactive' => false]
            )
        ;
    }

    /**
     * Tests an invalid username.
     *
     * @expectedException \Symfony\Component\Console\Exception\InvalidArgumentException
     * @expectedExceptionMessage foobar
     * @expectedExceptionMessage not found
     */
    public function testDatabaseUserNotFound()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject $connection */
        $connection = $this->container->get('database_connection');
        $connection
            ->expects($this->once())
            ->method('update')
            ->willReturn(0)
        ;

        (new CommandTester($this->command))
            ->execute(
                [
                    'username' => 'foobar',
                    '--password' => '12345678',
                ],
                ['interactive' => false]
            )
        ;
    }

    /**
     * Tests the database update.
     *
     * @param string $username
     * @param string $password
     *
     * @dataProvider usernamePasswordProvider
     */
    public function testDatabaseUpdate($username, $password)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject $connection */
        $connection = $this->container->get('database_connection');

        $connection
            ->expects($this->once())
            ->method('update')
            ->with(
                'tl_user',
                ['password' => 'HA$HED-'.$password.'-HA$HED'],
                ['username' => $username]
            )
            ->willReturn(1)
        ;

        (new CommandTester($this->command))
            ->execute(
                [
                    'username' => $username,
                    '--password' => $password,
                ],
                ['interactive' => false]
            )
        ;
    }

    /**
     * Provides username and password data.
     *
     * @return array
     */
    public function usernamePasswordProvider()
    {
        return [
            [
                'foobar',
                '12345678',
            ],
            [
                'k.jones',
                'kevinjones',
            ],
        ];
    }

    /**
     * Mocks an encryption adapter.
     *
     * @return Adapter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockEncryptionAdapter()
    {
        $encryption = $this->getMock('Contao\CoreBundle\Framework\Adapter', ['hash'], ['Contao\Encryption']);

        $encryption
            ->expects($this->any())
            ->method('hash')
            ->willReturnCallback(
                function ($password) {
                    return 'HA$HED-'.$password.'-HA$HED';
                }
            )
        ;

        return $encryption;
    }
}
