<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Command;

use Contao\CoreBundle\Command\UserPasswordCommand;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UserPasswordCommandTest extends TestCase
{
    /**
     * @var UserPasswordCommand
     */
    private $command;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->container = new ContainerBuilder();
        $this->container->set('contao.framework', $this->mockContaoFramework());
        $this->container->set('database_connection', $this->createMock(Connection::class));

        $this->command = new UserPasswordCommand();
        $this->command->setContainer($this->container);
        $this->command->setApplication(new Application());
    }

    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf('Contao\CoreBundle\Command\UserPasswordCommand', $this->command);
        $this->assertSame('contao:user:password', $this->command->getName());
    }

    public function testDefinesUsernameAndPassword(): void
    {
        $this->assertNotEmpty($this->command->getDescription());

        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasArgument('username'));
        $this->assertTrue($definition->hasOption('password'));
    }

    public function testTakesAPasswordAsArgument(): void
    {
        $input = [
            'username' => 'foobar',
            '--password' => '12345678',
        ];

        $code = (new CommandTester($this->command))->execute($input);

        $this->assertSame(0, $code);
    }

    public function testAsksForThePasswordIfNotGiven(): void
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

    public function testFailsIfThePasswordsDoNotMatch(): void
    {
        $question = $this->createMock(QuestionHelper::class);

        $question
            ->method('ask')
            ->willReturnOnConsecutiveCalls('12345678', '87654321')
        ;

        $this->command->getHelperSet()->set($question, 'question');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The passwords do not match.');

        (new CommandTester($this->command))->execute(['username' => 'foobar']);
    }

    public function testFailsWithoutUsername(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Please provide the username as argument.');

        (new CommandTester($this->command))->execute([]);
    }

    public function testFailsWithoutPasswordIfNotInteractive(): void
    {
        $code = (new CommandTester($this->command))->execute(['username' => 'foobar'], ['interactive' => false]);

        $this->assertSame(1, $code);
    }

    public function testRequiresAMinimumPasswordLength(): void
    {
        unset($GLOBALS['TL_CONFIG']['minPasswordLength']);

        $input = [
            'username' => 'foobar',
            '--password' => '123456',
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The password must be at least 8 characters long.');

        (new CommandTester($this->command))->execute($input, ['interactive' => false]);
    }

    public function testHandlesACustomMinimumPasswordLength(): void
    {
        $GLOBALS['TL_CONFIG']['minPasswordLength'] = 16;

        $input = [
            'username' => 'foobar',
            '--password' => '123456789',
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The password must be at least 16 characters long.');

        (new CommandTester($this->command))->execute($input, ['interactive' => false]);
    }

    public function testFailsIfTheUsernameIsUnknown(): void
    {
        $connection = $this->container->get('database_connection');

        $connection
            ->expects($this->once())
            ->method('update')
            ->willReturn(0)
        ;

        $input = [
            'username' => 'foobar',
            '--password' => '12345678',
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid username: foobar');

        (new CommandTester($this->command))->execute($input, ['interactive' => false]);
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @dataProvider usernamePasswordProvider
     */
    public function testUpdatesTheDatabaseOnSuccess(string $username, string $password): void
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

        $input = [
            'username' => $username,
            '--password' => $password,
        ];

        (new CommandTester($this->command))->execute($input, ['interactive' => false]);
    }

    /**
     * @return array
     */
    public function usernamePasswordProvider(): array
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
