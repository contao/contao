<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\EventListener\LocaleListener;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
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
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $listener = new LocaleListener($this->mockScopeMatcher(), ['en']);

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\LocaleListener', $listener);
    }

    /**
     * Tests reading the locale from the request.
     *
     * @param string $locale
     * @param string $expected
     *
     * @dataProvider localeTestData
     */
    public function testReadsTheLocaleFromTheRequest($locale, $expected)
    {
        $request = Request::create('/');
        $request->attributes->set('_locale', $locale);
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $event = new GetResponseEvent($this->mockKernel(), $request, HttpKernelInterface::MASTER_REQUEST);

        $listener = new LocaleListener($this->mockScopeMatcher(), ['en']);
        $listener->onKernelRequest($event);

        $this->assertSame($expected, $request->attributes->get('_locale'));
    }

    /**
     * Tests reading the locale from the session.
     *
     * @param string $locale
     * @param string $expected
     *
     * @dataProvider localeTestData
     */
    public function testReadsTheLocaleFromTheSession($locale, $expected)
    {
        // The session values are already formatted, so we're passing in $expected here
        $session = $this->mockSession();
        $session->set('_locale', $expected);

        $request = Request::create('/');
        $request->setSession($session);
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $event = new GetResponseEvent($this->mockKernel(), $request, HttpKernelInterface::MASTER_REQUEST);

        $listener = new LocaleListener($this->mockScopeMatcher(), ['en']);
        $listener->onKernelRequest($event);

        $this->assertSame($expected, $request->attributes->get('_locale'));
    }

    /**
     * Provides the test data for the locale tests.
     *
     * @return array
     */
    public function localeTestData()
    {
        return [
            [null, 'en'], // see #264
            ['en', 'en'],
            ['de', 'de'],
            ['de-CH', 'de_CH'],
            ['de_CH', 'de_CH'],
            ['zh-tw', 'zh_TW'],
        ];
    }

    /**
     * Tests the onKernelRequest() method with an accept language header.
     *
     * @param string $locale
     * @param string $expected
     * @param array  $available
     *
     * @dataProvider acceptLanguageTestData
     */
    public function testReadsTheLocaleFromTheAcceptLanguageHeader($locale, $expected, array $available)
    {
        $request = Request::create('/');
        $request->headers->set('Accept-Language', $locale);
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $event = new GetResponseEvent($this->mockKernel(), $request, HttpKernelInterface::MASTER_REQUEST);

        $listener = new LocaleListener($this->mockScopeMatcher(), $available);
        $listener->onKernelRequest($event);

        $this->assertSame($expected, $request->attributes->get('_locale'));
    }

    /**
     * Provides the test data for the accept language header tests.
     *
     * @return array
     */
    public function acceptLanguageTestData()
    {
        return [
            [null, 'de', ['de', 'en']], // see #264
            ['de', 'de', ['de', 'en']],
            ['de, en', 'en', ['en']],
            ['de', 'en', ['en']],
            ['de-de, en', 'de', ['de', 'en']],
            ['de, fr, en', 'fr', ['en', 'fr']],
            ['fr, de-ch, en', 'de_CH', ['en', 'de_CH']],
        ];
    }

    /**
     * Tests that the listener does nothing if there is no request scope.
     */
    public function testDoesNothingIfThereIsNoRequestScope()
    {
        $attributes = $this->createMock(ParameterBag::class);

        $attributes
            ->expects($this->never())
            ->method('set')
        ;

        $request = Request::create('/', Request::METHOD_GET, [$attributes]);
        $event = new GetResponseEvent($this->mockKernel(), $request, HttpKernelInterface::MASTER_REQUEST);

        $listener = new LocaleListener($this->mockScopeMatcher(), ['en']);
        $listener->onKernelRequest($event);
    }

    /**
     * Tests the onKernelRequest() method with an invalid locale.
     */
    public function testFailsIfTheLocaleIsInvalid()
    {
        $request = Request::create('/');
        $request->attributes->set('_locale', 'invalid');
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $event = new GetResponseEvent($this->mockKernel(), $request, HttpKernelInterface::MASTER_REQUEST);

        $this->expectException('InvalidArgumentException');

        $listener = new LocaleListener($this->mockScopeMatcher(), ['en']);
        $listener->onKernelRequest($event);
    }
}
