<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Command;

use Contao\Config;
use Contao\CoreBundle\Command\UserPasswordCommand;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Encryption;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\QuestionHelper;
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
            [Encryption::class => $this->mockEncryptionAdapter()]
        );

        $connection = $this->createMock(Connection::class);

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
        $this->assertSame('contao:user:password', $this->command->getName());
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
     * Tests the execution with a password argument.
     */
    public function testExecutionWithPasswordArgument()
    {
        $code = (new CommandTester($this->command))
            ->execute(
                [
                    'username' => 'foobar',
                    '--password' => '12345678',
                ]
            )
        ;

        $this->assertSame(0, $code);
    }

    /**
     * Tests the execution with the password dialog.
     */
    public function testExecutionWithPasswordDialog()
    {
        $question = $this->createMock(QuestionHelper::class);

        $question
            ->method('ask')
            ->willReturn('12345678')
        ;

        $this->command->getHelperSet()->set($question, 'question');

        $code = (new CommandTester($this->command))->execute(['username' => 'foobar']);

        $this->assertSame(0, $code);
    }

    /**
     * Tests the execution with differing passwords.
     */
    public function testExecutionWithDifferingPasswords()
    {
        $question = $this->createMock(QuestionHelper::class);

        $question
            ->method('ask')
            ->willReturnOnConsecutiveCalls(['12345678', '87654321'])
        ;

        $this->command->getHelperSet()->set($question, 'question');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The passwords do not match.');

        (new CommandTester($this->command))->execute(['username' => 'foobar']);
    }

    /**
     * Tests the command without a username.
     */
    public function testExceptionWhenMissingUsername()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Please provide the username as argument.');

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

        $this->assertSame(1, $code);
    }

    /**
     * Tests the minimum password length.
     */
    public function testMinimumPasswordLength()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The password must be at least 8 characters long.');

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
     */
    public function testCustomPasswordLength()
    {
        $framework = $this->mockContaoFramework(
            null,
            null,
            [
                Config::class => $this->mockConfigAdapter(16),
                Encryption::class => $this->mockEncryptionAdapter(),
            ]
        );

        $container = $this->mockContainerWithContaoScopes();
        $container->set('contao.framework', $framework);

        $command = new UserPasswordCommand();
        $command->setContainer($container);
        $command->setApplication(new Application());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The password must be at least 16 characters long.');

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
     */
    public function testDatabaseUserNotFound()
    {
        $connection = $this->container->get('database_connection');

        $connection
            ->expects($this->once())
            ->method('update')
            ->willReturn(0)
        ;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid username: foobar');

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
        $encryption = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['hash'])
            ->getMock();

        $encryption
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
