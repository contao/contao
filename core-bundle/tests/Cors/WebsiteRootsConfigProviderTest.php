<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Cors;

use Contao\CoreBundle\Cors\WebsiteRootsConfigProvider;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Mysqli\MysqliException;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Schema\MySqlSchemaManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class WebsiteRootsConfigProviderTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $configProvider = new WebsiteRootsConfigProvider($this->createMock(Connection::class));

        $this->assertInstanceOf('Contao\CoreBundle\Cors\WebsiteRootsConfigProvider', $configProvider);
    }

    public function testProvidesTheConfigurationIfTheHostMatches(): void
    {
        $request = Request::create('https://foobar.com');
        $request->headers->set('Origin', 'http://origin.com');

        $statement = $this->createMock(Statement::class);

        $statement
            ->method('bindValue')
            ->with('dns', 'origin.com')
        ;

        $statement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn('1')
        ;

        $connection = $this->mockConnection($statement);

        $configProvider = new WebsiteRootsConfigProvider($connection);
        $result = $configProvider->getOptions($request);

        $this->assertSame(
            [
                'allow_origin' => true,
                'allow_methods' => ['HEAD', 'GET'],
                'allow_headers' => ['x-requested-with'],
            ],
            $result
        );
    }

    public function testDoesNotProvideTheConfigurationIfTheHostDoesNotMatch(): void
    {
        $request = Request::create('https://foobar.com');
        $request->headers->set('Origin', 'https://origin.com');

        $statement = $this->createMock(Statement::class);

        $statement
            ->method('bindValue')
            ->with('dns', 'origin.com')
        ;

        $statement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn('0')
        ;

        $connection = $this->mockConnection($statement);

        $configProvider = new WebsiteRootsConfigProvider($connection);
        $result = $configProvider->getOptions($request);

        $this->assertCount(0, $result);
    }

    public function testDoesNotProvideTheConfigurationIfThereIsNoOriginHeader(): void
    {
        $request = Request::create('http://foobar.com');
        $request->headers->remove('Origin');

        $connection = $this->createMock(Connection::class);

        $connection
            ->expects($this->never())
            ->method('prepare')
        ;

        $configProvider = new WebsiteRootsConfigProvider($connection);
        $result = $configProvider->getOptions($request);

        $this->assertCount(0, $result);
    }

    public function testDoesNotProvideTheConfigurationIfTheOriginEqualsTheHost(): void
    {
        $request = Request::create('https://foobar.com');
        $request->headers->set('Origin', 'https://foobar.com');

        $connection = $this->createMock(Connection::class);

        $connection
            ->expects($this->never())
            ->method('prepare')
        ;

        $configProvider = new WebsiteRootsConfigProvider($connection);
        $result = $configProvider->getOptions($request);

        $this->assertCount(0, $result);
    }

    public function testDoesNotProvideTheConfigurationIfTheDatabaseIsNotConnected(): void
    {
        $request = Request::create('https://foobar.com');
        $request->headers->set('Origin', 'https://origin.com');

        $connection = $this->createMock(Connection::class);

        $connection
            ->method('isConnected')
            ->willThrowException(new DriverException('Could not connect', new MysqliException('Invalid password')))
        ;

        $connection
            ->expects($this->never())
            ->method('prepare')
        ;

        $configProvider = new WebsiteRootsConfigProvider($connection);
        $result = $configProvider->getOptions($request);

        $this->assertCount(0, $result);
    }

    public function testDoesNotProvideTheConfigurationIfTheTableDoesNotExist(): void
    {
        $request = Request::create('https://foobar.com');
        $request->headers->set('Origin', 'https://origin.com');

        $schemaManager = $this->createMock(MySqlSchemaManager::class);

        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->willReturn(false)
        ;

        $connection = $this->createMock(Connection::class);

        $connection
            ->method('isConnected')
            ->willReturn(true)
        ;

        $connection
            ->method('getSchemaManager')
            ->willReturn($schemaManager)
        ;

        $connection
            ->expects($this->never())
            ->method('prepare')
        ;

        $configProvider = new WebsiteRootsConfigProvider($connection);
        $result = $configProvider->getOptions($request);

        $this->assertCount(0, $result);
    }

    /**
     * Mocks a database connection.
     *
     * @param Statement $statement
     *
     * @return Connection|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockConnection(Statement $statement): Connection
    {
        $schemaManager = $this->createMock(MySqlSchemaManager::class);

        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->willReturn(true)
        ;

        $connection = $this->createMock(Connection::class);

        $connection
            ->expects($this->once())
            ->method('isConnected')
            ->willReturn(true)
        ;

        $connection
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($statement)
        ;

        $connection
            ->expects($this->once())
            ->method('getSchemaManager')
            ->willReturn($schemaManager)
        ;

        return $connection;
    }
}
