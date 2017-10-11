<?php

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

/**
 * Tests the WebsiteRootsConfigProvider class.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class WebsiteRootsConfigProviderTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $connection = $this->createMock(Connection::class);
        $configProvider = new WebsiteRootsConfigProvider($connection);

        $this->assertInstanceOf('Contao\CoreBundle\Cors\WebsiteRootsConfigProvider', $configProvider);
    }

    /**
     * Tests that a configuration is provided if the host matches.
     */
    public function testProvidesTheConfigurationIfTheHostMatches()
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

        $connection = $this->getConnection($statement);
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

    /**
     * Tests that no configuration is provided if the host does not match.
     */
    public function testDoesNotProvideTheConfigurationIfTheHostDoesNotMatch()
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

        $connection = $this->getConnection($statement);
        $configProvider = new WebsiteRootsConfigProvider($connection);
        $result = $configProvider->getOptions($request);

        $this->assertCount(0, $result);
    }

    /**
     * Tests that no configuration is provided if there is no origin header.
     */
    public function testDoesNotProvideTheConfigurationIfThereIsNoOriginHeader()
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

    /**
     * Tests that no configuration is provided if the origin equals the host.
     */
    public function testDoesNotProvideTheConfigurationIfTheOriginEqualsTheHost()
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

    /**
     * Tests that no configuration is provided if the database is not connected.
     */
    public function testDoesNotProvideTheConfigurationIfTheDatabaseIsNotConnected()
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

    /**
     * Tests that no configuration is provided if the table does not exist.
     */
    public function testDoesNotProvideTheConfigurationIfTheTableDoesNotExist()
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
     * Mocks a database connection object.
     *
     * @param string $statement
     *
     * @return Connection|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getConnection($statement)
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
