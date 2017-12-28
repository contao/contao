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
use Contao\CoreBundle\Tests\TestCase;
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
        $connection = $this->createMock(Connection::class);

        $this->container = $this->mockContainerWithContaoScopes();
        $this->container->set('contao.framework', $this->mockContaoFramework());
        $this->container->set('database_connection', $connection);

        $this->command = new UserPasswordCommand();
        $this->command->setContainer($this->container);
        $this->command->setApplication(new Application());
    }

    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $this->assertInstanceOf('Contao\CoreBundle\Command\UserPasswordCommand', $this->command);
        $this->assertSame('contao:user:password', $this->command->getName());
    }

    /**
     * Tests that the command defines username and password.
     */
    public function testDefinesUsernameAndPassword()
    {
        $this->assertNotEmpty($this->command->getDescription());

        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasArgument('username'));
        $this->assertTrue($definition->hasOption('password'));
    }

    /**
     * Tests that a password can be passed as argument.
     */
    public function testTakesAPasswordAsArgument()
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
     * Tests that the password is asked for interactively if not given.
     */
    public function testAsksForThePasswordIfNotGiven()
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
     * Tests that the command fails if the passwords do not match.
     */
    public function testFailsIfThePasswordsDoNotMatch()
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
     * Tests that the command fails if no username is given.
     */
    public function testFailsWithoutUsername()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Please provide the username as argument.');

        (new CommandTester($this->command))->execute([]);
    }

    /**
     * Tests that the command fails without a password if not interactive.
     */
    public function testFailsWithoutPasswordIfNotInteractive()
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
     * Tests that a minimum password length is required.
     */
    public function testRequiresAMinimumPasswordLength()
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
     * Tests that the minimum password length is read from the Config object.
     */
    public function testHandlesACustomMinimumPasswordLength()
    {
        $framework = $this->mockContaoFramework(
            null,
            null,
            [
                Config::class => $this->mockConfigAdapter(16),
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
     * Tests that the command fails if the username is unknown.
     */
    public function testFailsIfTheUsernameIsUnknown()
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
     * Tests that the database is updated on success.
     *
     * @param string $username
     * @param string $password
     *
     * @dataProvider usernamePasswordProvider
     */
    public function testUpdatesTheDatabaseOnSuccess($username, $password)
    {
        $connection = $this->container->get('database_connection');

        $connection
            ->expects($this->once())
            ->method('update')
            ->with(
                'tl_user',
                $this->callback(
                    function ($data) {
                        $this->assertArrayHasKey('password', $data);
                        $this->assertSame(PASSWORD_DEFAULT, password_get_info($data['password'])['algo']);

                        return $data;
                    }
                ),
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
}
