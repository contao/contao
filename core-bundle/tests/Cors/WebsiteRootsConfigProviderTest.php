<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test;

use Contao\CoreBundle\Cors\WebsiteRootsConfigProvider;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the WebsiteRootsConfigProvider class.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class WebsiteRootsConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $connection = $this
            ->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $configProvider = new WebsiteRootsConfigProvider($connection);

        $this->assertInstanceOf('Contao\CoreBundle\Cors\WebsiteRootsConfigProvider', $configProvider);
    }

    /**
     * Tests that no configuration is provided if the host does not match.
     */
    public function testNoConfigProvidedIfHostDoesNotMatch()
    {
        $request = Request::create('https://foobar.com');
        $request->headers->set('origin', 'http://origin.com');
        $statement = $this->getMock(Statement::class);

        $statement
            ->expects($this->at(1))
            ->method('bindValue')
            ->with('dns', 'origin.com')
        ;

        $statement
            ->expects($this->once())
            ->method('rowCount')
            ->willReturn(0)
        ;

        $connection = $this->getConnection($statement);
        $configProvider = new WebsiteRootsConfigProvider($connection);
        $result = $configProvider->getOptions($request);

        $this->assertCount(0, $result);
    }

    /**
     * Tests that a configuration is provided if the host matches.
     */
    public function testConfigProvidedIfHostMatches()
    {
        $request = Request::create('https://foobar.com');
        $request->headers->set('origin', 'https://origin.com');
        $statement = $this->getMock(Statement::class);

        $statement
            ->expects($this->at(1))
            ->method('bindValue')
            ->with('dns', 'origin.com')
        ;

        $statement
            ->expects($this->once())
            ->method('rowCount')
            ->willReturn(1)
        ;

        $connection = $this->getConnection($statement);
        $configProvider = new WebsiteRootsConfigProvider($connection);
        $result = $configProvider->getOptions($request);

        $this->assertEquals(
            [
                'allow_methods' => ['HEAD', 'GET'],
                'allow_headers' => ['x-requested-with'],
                'allow_origin' => true
            ],
            $result
        );
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
        $mock = $this
            ->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $mock
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($statement)
        ;

        return $mock;
    }
}
