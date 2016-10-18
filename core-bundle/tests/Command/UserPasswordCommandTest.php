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
use Contao\CoreBundle\Test\TestCase;
use Contao\Encryption;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

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

    public function setUp()
    {
        $framework = $this->mockContaoFramework();
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
    }

    public function testConfiguration()
    {
        $this->assertEquals('contao:user:password', $this->command->getName());
        $this->assertNotEmpty($this->command->getDescription());

        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasArgument('username'));
        $this->assertTrue($definition->hasOption('password'));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage enter a username
     */
    public function testExceptionWhenMissingUsername()
    {
        (new CommandTester($this->command))
            ->execute([])
        ;
    }

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
     * @expectedException \RuntimeException
     * @expectedExceptionMessage 8 characters
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
     * @expectedException \RuntimeException
     * @expectedExceptionMessage 16 characters
     */
    public function testCustomPasswordLength()
    {
        $container = $this->mockContainerWithContaoScopes();
        $framework = $this->mockContaoFramework(null, null, ['Contao\Config' => $this->mockConfigAdapter(16)]);

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
     * @expectedException \RuntimeException
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
                ['password' => 'HA$HED-' . $password . '-HA$HED'],
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

    public function usernamePasswordProvider()
    {
        return [
            [
                'foobar',
                '12345678'
            ],
            [
                'k.jones',
                'kevinjones'
            ],
        ];
    }

    public function mockContaoFramework(RequestStack $requestStack = null, RouterInterface $router = null, array $adapters = [])
    {
        $encryption = $this->getMock('Contao\CoreBundle\Framework\Adapter', ['hash'], ['Contao\Encryption']);
        $encryption
            ->expects($this->any())
            ->method('hash')
            ->willReturnCallback(
                function ($password) {
                    return 'HA$HED-' . $password . '-HA$HED';
                }
            )
        ;

        $adapters['Contao\Encryption'] = $encryption;

        return parent::mockContaoFramework($requestStack, $router, $adapters);
    }

    protected function mockConfigAdapter($minPasswordLength = null)
    {
        $configAdapter = $this
            ->getMockBuilder('Contao\CoreBundle\Framework\Adapter')
            ->setMethods(['isComplete', 'preload', 'getInstance', 'get'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $configAdapter
            ->expects($this->any())
            ->method('isComplete')
            ->willReturn(true)
        ;

        $configAdapter
            ->expects($this->any())
            ->method('preload')
            ->willReturn(null)
        ;

        $configAdapter
            ->expects($this->any())
            ->method('getInstance')
            ->willReturn(null)
        ;

        $configAdapter
            ->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($key) use ($minPasswordLength) {
                switch ($key) {
                    case 'characterSet':
                        return 'UTF-8';

                    case 'timeZone':
                        return 'Europe/Berlin';

                    case 'gdMaxImgWidth':
                    case 'gdMaxImgHeight':
                        return 3000;

                    case 'minPasswordLength':
                        return $minPasswordLength;

                    default:
                        return null;
                }
            })
        ;

        return $configAdapter;
    }
}
