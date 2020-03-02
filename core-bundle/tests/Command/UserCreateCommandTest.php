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
use Contao\CoreBundle\Command\UserCreateCommand;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class UserCreateCommandTest extends TestCase
{
    public function testDefinition(): void
    {
        $command = $this->getCommand();

        $this->assertNotEmpty($command->getDescription());

        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('username'));
        $this->assertTrue($definition->hasOption('name'));
        $this->assertTrue($definition->hasOption('email'));
        $this->assertTrue($definition->hasOption('password'));
        $this->assertTrue($definition->hasOption('language'));
        $this->assertTrue($definition->hasOption('admin'));
        $this->assertTrue($definition->hasOption('groups'));
        $this->assertTrue($definition->hasOption('pwChange'));
    }

    public function testAsksForTheUsernameIfNotGiven(): void
    {
        $command = $this->getCommand();

        $question = $this->createMock(QuestionHelper::class);
        $question
            ->method('ask')
            ->willReturn('j.doe')
        ;

        $command->getHelperSet()->set($question, 'question');

        $code = (new CommandTester($command))->execute(['name' => 'John Doe']);

        $this->assertSame(0, $code);
    }

    public function testFailsWithoutParametersIfNotInteractive(): void
    {
        $command = $this->getCommand();
        $code = (new CommandTester($command))->execute(['username' => 'foobar'], ['interactive' => false]);

        $this->assertSame(1, $code);
    }

    public function testFailsIfTheUsernameIsDuplicate(): void
    {
        /** @var Connection&MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('update')
            ->willReturn(0)
        ;

        $command = $this->getCommand($connection);

        $input = [];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid username: foobar');

        (new CommandTester($command))->execute($input, ['interactive' => false]);
    }

    /**
     * @dataProvider usernamePasswordProvider
     */
    public function testUpdatesTheDatabaseOnSuccess(string $username, string $password): void
    {
        /** @var Connection&MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('update')
            ->with(
                'tl_user',
                ['password' => '$argon2id$v=19$m=65536,t=6,p=1$T+WK0xPOk21CQ2dX9AFplw$2uCrfvt7Tby81Dhc8Y7wHQQGP1HnPC3nDEb4FtXsfrQ'],
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

    /**
     * @param Connection&MockObject $connection
     */
    private function getCommand(Connection $connection = null, string $password = null): UserCreateCommand
    {
        if (null === $connection) {
            $connection = $this->createMock(Connection::class);
        }

        if (null === $password) {
            $password = '12345678';
        }

        $encoder = $this->createMock(PasswordEncoderInterface::class);
        $encoder
            ->method('encodePassword')
            ->with($password, null)
            ->willReturn('$argon2id$v=19$m=65536,t=6,p=1$T+WK0xPOk21CQ2dX9AFplw$2uCrfvt7Tby81Dhc8Y7wHQQGP1HnPC3nDEb4FtXsfrQ')
        ;

        $encoderFactory = $this->createMock(EncoderFactoryInterface::class);
        $encoderFactory
            ->method('getEncoder')
            ->with(BackendUser::class)
            ->willReturn($encoder)
        ;

        $command = new UserCreateCommand($this->mockContaoFramework(), $connection, $encoderFactory, ['en', 'de', 'ru']);
        $command->setApplication(new Application());

        return $command;
    }
}
