<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Cors;

use Contao\CoreBundle\Cors\WebsiteRootsConfigProvider;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\MySQLSchemaManager;
use Doctrine\DBAL\Statement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class WebsiteRootsConfigProviderTest extends TestCase
{
    public function testProvidesTheConfigurationIfTheHostMatches(): void
    {
        $request = Request::create('https://foobar.com');
        $request->headers->set('Origin', 'http://origin.com');

        $result = $this->createMock(Result::class);
        $result
            ->expects($this->once())
            ->method('fetchOne')
            ->willReturn('1')
        ;

        $statement = $this->createMock(Statement::class);
        $statement
            ->method('bindValue')
            ->with('dns', 'origin.com')
        ;

        $statement
            ->expects($this->once())
            ->method('executeQuery')
            ->willReturn($result)
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
            $result,
        );
    }

    public function testDoesNotProvideTheConfigurationIfTheHostDoesNotMatch(): void
    {
        $request = Request::create('https://foobar.com');
        $request->headers->set('Origin', 'https://origin.com');

        $result = $this->createMock(Result::class);
        $result
            ->expects($this->once())
            ->method('fetchOne')
            ->willReturn('0')
        ;

        $statement = $this->createMock(Statement::class);
        $statement
            ->method('bindValue')
            ->with('dns', 'origin.com')
        ;

        $statement
            ->expects($this->once())
            ->method('executeQuery')
            ->willReturn($result)
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

    public function testDoesNotProvideTheConfigurationIfTheTableDoesNotExist(): void
    {
        $request = Request::create('https://foobar.com');
        $request->headers->set('Origin', 'https://origin.com');

        $schemaManager = $this->createMock(MySQLSchemaManager::class);
        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->willReturn(false)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('createSchemaManager')
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
     * @return Connection&MockObject
     */
    private function mockConnection(Statement $statement): Connection
    {
        $schemaManager = $this->createMock(MySQLSchemaManager::class);
        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->willReturn(true)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($statement)
        ;

        $connection
            ->expects($this->once())
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;

        return $connection;
    }
}
