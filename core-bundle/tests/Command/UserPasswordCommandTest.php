<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Command;

use Contao\BackendUser;
use Contao\CoreBundle\Command\UserPasswordCommand;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class UserPasswordCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->resetStaticProperties([Terminal::class]);

        parent::tearDown();
    }

    public function testDefinesUsernameAndPassword(): void
    {
        $command = $this->getCommand();

        $this->assertNotEmpty($command->getDescription());

        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasArgument('username'));
        $this->assertTrue($definition->hasOption('password'));
    }

    public function testTakesAPasswordAsArgument(): void
    {
        $command = $this->getCommand();

        $input = [
            'username' => 'foobar',
            '--password' => '12345678',
        ];

        $code = (new CommandTester($command))->execute($input);

        $this->assertSame(0, $code);
    }

    public function testAsksForThePasswordIfNotGiven(): void
    {
        $command = $this->getCommand();

        $question = $this->createMock(QuestionHelper::class);
        $question
            ->method('ask')
            ->willReturn('12345678')
        ;

        $command->getHelperSet()->set($question, 'question');

        $code = (new CommandTester($command))->execute(['username' => 'foobar']);

        $this->assertSame(0, $code);
    }

    public function testFailsIfThePasswordsDoNotMatch(): void
    {
        $command = $this->getCommand();

        $question = $this->createMock(QuestionHelper::class);
        $question
            ->method('ask')
            ->willReturnOnConsecutiveCalls('12345678', '87654321')
        ;

        $command->getHelperSet()->set($question, 'question');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The passwords do not match.');

        (new CommandTester($command))->execute(['username' => 'foobar']);
    }

    public function testFailsWithoutUsername(): void
    {
        $command = $this->getCommand();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Please provide the username as argument.');

        (new CommandTester($command))->execute([]);
    }

    public function testFailsWithoutPasswordIfNotInteractive(): void
    {
        $command = $this->getCommand();
        $code = (new CommandTester($command))->execute(['username' => 'foobar'], ['interactive' => false]);

        $this->assertSame(1, $code);
    }

    public function testRequiresAMinimumPasswordLength(): void
    {
        $command = $this->getCommand();

        unset($GLOBALS['TL_CONFIG']['minPasswordLength']);

        $input = [
            'username' => 'foobar',
            '--password' => '123456',
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The password must be at least 8 characters long.');

        (new CommandTester($command))->execute($input, ['interactive' => false]);
    }

    public function testHandlesACustomMinimumPasswordLength(): void
    {
        $command = $this->getCommand();

        $GLOBALS['TL_CONFIG']['minPasswordLength'] = 16;

        $input = [
            'username' => 'foobar',
            '--password' => '123456789',
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The password must be at least 16 characters long.');

        (new CommandTester($command))->execute($input, ['interactive' => false]);
    }

    public function testFailsIfTheUsernameIsUnknown(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('update')
            ->willReturn(0)
        ;

        $command = $this->getCommand($connection);

        $input = [
            'username' => 'foobar',
            '--password' => '12345678',
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid username: foobar');

        (new CommandTester($command))->execute($input, ['interactive' => false]);
    }

    /**
     * @dataProvider usernamePasswordProvider
     */
    public function testResetsPassword(string $username, string $password): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('update')
            ->with(
                'tl_user',
                [
                    'password' => '$argon2id$v=19$m=65536,t=6,p=1$T+WK0xPOk21CQ2dX9AFplw$2uCrfvt7Tby81Dhc8Y7wHQQGP1HnPC3nDEb4FtXsfrQ',
                    'pwChange' => 0,
                ],
                ['username' => $username]
            )
            ->willReturn(1)
        ;

        $input = [
            'username' => $username,
            '--password' => $password,
        ];

        $command = $this->getCommand($connection, $password);

        (new CommandTester($command))->execute($input, ['interactive' => false]);
    }

    public function usernamePasswordProvider(): \Generator
    {
        yield ['foobar', '12345678'];
        yield ['k.jones', 'kevinjones'];
    }

    public function testResetsPasswordWithRequiredChangeOnNextLogin(): void
    {
        $username = 'foobar';
        $password = '12345678';

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('update')
            ->with(
                'tl_user',
                [
                    'password' => '$argon2id$v=19$m=65536,t=6,p=1$T+WK0xPOk21CQ2dX9AFplw$2uCrfvt7Tby81Dhc8Y7wHQQGP1HnPC3nDEb4FtXsfrQ',
                    'pwChange' => 1,
                ],
                ['username' => $username]
            )
            ->willReturn(1)
        ;

        $input = [
            'username' => $username,
            '--password' => $password,
            '--require-change' => null,
        ];

        $command = $this->getCommand($connection, $password);

        (new CommandTester($command))->execute($input, ['interactive' => false]);
    }

    private function getCommand(Connection $connection = null, string $password = null): UserPasswordCommand
    {
        $connection ??= $this->createMock(Connection::class);
        $password ??= '12345678';

        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $passwordHasher
            ->method('hash')
            ->with($password)
            ->willReturn('$argon2id$v=19$m=65536,t=6,p=1$T+WK0xPOk21CQ2dX9AFplw$2uCrfvt7Tby81Dhc8Y7wHQQGP1HnPC3nDEb4FtXsfrQ')
        ;

        $passwordHasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $passwordHasherFactory
            ->method('getPasswordHasher')
            ->with(BackendUser::class)
            ->willReturn($passwordHasher)
        ;

        $command = new UserPasswordCommand($this->mockContaoFramework(), $connection, $passwordHasherFactory);
        $command->setApplication(new Application());

        return $command;
    }
}
