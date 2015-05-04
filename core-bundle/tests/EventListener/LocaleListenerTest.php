<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\EventListener;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\EventListener\LocaleListener;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests the LocaleListener class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class LocaleListenerTest extends TestCase
{
    /**
     * @var LocaleListener
     */
    private $listener;

    public function setUp()
    {
        $this->listener = new LocaleListener(['en']);
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\\CoreBundle\\EventListener\\LocaleListener', $this->listener);
    }

    /**
     * @dataProvider localeTestData
     *
     * @param string $input
     * @param string $expected
     */
    public function testWithRequestAttribute($input, $expected)
    {
        $kernel  = $this->mockKernel();
        $session = $this->mockSession();
        $request = Request::create('/');
        $event   = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $kernel->getContainer()->enterScope(ContaoCoreBundle::SCOPE_FRONTEND);
        $request->setSession($session);
        $this->listener->setContainer($kernel->getContainer());

        $request->attributes->set('_locale', $input);

        $this->listener->onKernelRequest($event);

        $this->assertEquals($expected, $request->attributes->get('_locale'));
        $this->assertEquals($expected, $session->get('_locale'));
    }

    /**
     * @dataProvider localeTestData
     *
     * @param string $input
     * @param string $expected
     */
    public function testWithSessionValue($input, $expected)
    {
        $kernel  = $this->mockKernel();
        $session = $this->mockSession();
        $request = Request::create('/');
        $event   = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $kernel->getContainer()->enterScope(ContaoCoreBundle::SCOPE_FRONTEND);
        $request->setSession($session);
        $this->listener->setContainer($kernel->getContainer());

        // The session values is already formatted, so we're passing in $expected here
        $session->set('_locale', $expected);

        $this->listener->onKernelRequest($event);

        $this->assertEquals($expected, $request->attributes->get('_locale'));
        $this->assertEquals($expected, $session->get('_locale'));
    }

    /**
     * @dataProvider fallbackTestData
     *
     * @param string $input
     * @param string $expected
     * @param array  $available
     */
    public function testFindsFallback($input, $expected, array $available)
    {
        $listener = new LocaleListener($available);
        $kernel   = $this->mockKernel();
        $session  = $this->mockSession();
        $request  = Request::create('/');
        $event    = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $request->headers->set('Accept-Language', $input);

        $kernel->getContainer()->enterScope(ContaoCoreBundle::SCOPE_FRONTEND);
        $request->setSession($session);
        $listener->setContainer($kernel->getContainer());

        $listener->onKernelRequest($event);

        $this->assertEquals($expected, $request->attributes->get('_locale'));
        $this->assertEquals($expected, $session->get('_locale'));

    }

    public function testWithoutContainer()
    {
        $kernel  = $this->mockKernel();
        $session = $this->mockSession();
        $request = Request::create('/');
        $event   = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $request->setSession($session);

        $request->attributes->set('_locale', 'zh-TW');

        $this->listener->onKernelRequest($event);

        $this->assertEquals('zh-TW', $request->attributes->get('_locale'));
        $this->assertFalse($session->has('_locale'));
    }

    public function testWithoutContaoScope()
    {
        $kernel  = $this->mockKernel();
        $session = $this->mockSession();
        $request = Request::create('/');
        $event   = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $request->setSession($session);
        $this->listener->setContainer($kernel->getContainer());

        $request->attributes->set('_locale', 'zh-TW');

        $this->listener->onKernelRequest($event);

        $this->assertEquals('zh-TW', $request->attributes->get('_locale'));
        $this->assertFalse($session->has('_locale'));
    }

    /**
     * @dataProvider localeTestData
     *
     * @param string $input
     * @param string $expected
     */
    public function testWithoutSession($input, $expected)
    {
        $kernel  = $this->mockKernel();
        $request = Request::create('/');
        $event   = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $this->assertNull($request->getSession());

        $kernel->getContainer()->enterScope(ContaoCoreBundle::SCOPE_FRONTEND);
        $request->attributes->set('_locale', $input);

        $this->listener->setContainer($kernel->getContainer());
        $this->listener->onKernelRequest($event);

        $this->assertEquals($expected, $request->attributes->get('_locale'));
    }

    public function localeTestData()
    {
        return [
            ['en', 'en'],
            ['de', 'de'],
            ['de-CH', 'de_CH'],
            ['de_CH', 'de_CH'],
            ['zh-tw', 'zh_TW']
        ];
    }

    public function fallbackTestData()
    {
        return [
            ['de', 'de', ['de', 'en']],
            ['de, en', 'en', ['en']],
            ['de', 'en', ['en']],
            ['de-de, en', 'de', ['de', 'en']],
            ['de, fr, en', 'fr', ['en', 'fr']],
            ['fr, de-ch, en', 'de_CH', ['en', 'de_CH']],
        ];
    }
}
