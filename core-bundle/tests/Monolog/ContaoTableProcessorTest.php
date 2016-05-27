<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Monolog;

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Monolog\ContaoTableProcessor;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the ContaoTableProcessor class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ContaoTableProcessorTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $processor = new ContaoTableProcessor(
            $this->getMock('Symfony\Component\HttpFoundation\RequestStack'),
            $this->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface')
        );

        $this->assertInstanceOf('Contao\CoreBundle\Monolog\ContaoTableProcessor', $processor);
    }

    public function testIgnoresWithoutContext()
    {
        $processor = new ContaoTableProcessor(
            $this->getMock('Symfony\Component\HttpFoundation\RequestStack'),
            $this->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface')
        );

        $this->assertEmpty($processor([]));
        $this->assertEquals(['foo' => 'bar'], $processor(['foo' => 'bar']));
        $this->assertEquals(['context' => ['contao' => false]], $processor(['context' => ['contao' => false]]));
    }

    public function testIpOnEmptyRequest()
    {
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');

        $requestStack
            ->method('getCurrentRequest')
            ->willReturn(null)
        ;
        
        $processor = new ContaoTableProcessor(
            $requestStack,
            $this->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface')
        );

        /** @var ContaoContext $context */
        $context = $processor(['context' => ['contao' => new ContaoContext(__METHOD__)]])['extra']['contao'];

        $this->assertEquals('127.0.0.1', $context->getIp());
    }

    /**
     * @dataProvider anonymizedIpProvider
     */
    public function testAnonymizesIp($input, $expected)
    {
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => $input]);
        $requestStack = new RequestStack();

        $requestStack->push($request);

        $processor = new ContaoTableProcessor(
            $requestStack,
            $this->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface'),
            false
        );

        /** @var ContaoContext $context */
        $context = $processor(['context' => ['contao' => new ContaoContext(__METHOD__)]])['extra']['contao'];

        $this->assertEquals($input, $context->getIp());

        $processor = new ContaoTableProcessor(
            $requestStack,
            $this->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface'),
            true
        );

        /** @var ContaoContext $context */
        $context = $processor(['context' => ['contao' => new ContaoContext(__METHOD__)]])['extra']['contao'];

        $this->assertEquals($expected, $context->getIp());
    }

    public function anonymizedIpProvider()
    {
        return [
            ['127.0.0.1', '127.0.0.1'],
            ['::1', '::1'],
            ['192.168.1.111', '192.168.1.0'],
            ['10.10.10.10', '10.10.10.0'],
            // TODO test with IPv6
        ];
    }
}
