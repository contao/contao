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

use Contao\CoreBundle\Command\UserListCommand;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Model\Collection;
use Contao\UserModel;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class UserListCommandTest extends TestCase
{
    public function testDefinition(): void
    {
        $command = $this->getCommand();

        $this->assertNotEmpty($command->getDescription());

        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('fields'));
        $this->assertTrue($definition->hasOption('admins'));
    }

    public function testTakesAdminsFlagAsArgument(): void
    {
        $command = $this->getCommand();

        $input = [
            '--admins' => true,
        ];

        $code = (new CommandTester($command))->execute($input);

        $this->assertSame(0, $code);
    }

    public function testReturnsErrorCodeOnEmptyResult(): void
    {
        $command = $this->getCommand();

        $input = [];

        //todo override UserModel::findAll() to return null

        $code = (new CommandTester($command))->execute($input);

        $this->assertSame(0, $code);
    }

    public function testTakesFieldsAsArgument(): void
    {
        $command = $this->getCommand();

        $input = [
            '--fields' => ['username', 'name'],
        ];

        $code = (new CommandTester($command))->execute($input);

        $this->assertSame(0, $code);
    }

    private function getCommand(): UserListCommand
    {
        $userModelAdapter = $this->mockAdapter(['findBy', 'findAll']);
        $userModelAdapter
            //->expects($this->once())
            ->method('findAll')
            ->willReturn(new Collection([$this->mockAdminUser(), $this->mockContaoUser()], 'tl_user'), null)
        ;
        $userModelAdapter
            //->expects($this->once())
            ->method('findBy')
            ->with('admin', '1')
            ->willReturn(new Collection([$this->mockAdminUser()], 'tl_user'), null)
        ;

        $command = new UserListCommand($this->mockContaoFramework([UserModel::class => $userModelAdapter]));
        $command->setApplication(new Application());

        return $command;
    }

    private function mockContaoUser(): UserModel
    {
        /** @var UserModel&MockObject $userModel */
        $userModel = $this->mockClassWithProperties(UserModel::class);

        $userModel->id = '2';
        $userModel->username = 'j.doe';
        $userModel->name = 'John Doe';

        return $userModel;
    }

    private function mockAdminUser(): UserModel
    {
        /** @var UserModel&MockObject $userModel */
        $userModel = $this->mockClassWithProperties(UserModel::class);

        $userModel->id = '1';
        $userModel->username = 'j.doe';
        $userModel->name = 'John Doe';
        $userModel->admin = '1';

        return $userModel;
    }
}
