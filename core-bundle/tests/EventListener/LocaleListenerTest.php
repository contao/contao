<?php

declare(strict_types=1);

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

class LocaleListenerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $listener = new LocaleListener($this->mockScopeMatcher(), ['en']);

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\LocaleListener', $listener);
    }

    /**
     * @param string|null $locale
     * @param string      $expected
     *
     * @dataProvider localeTestData
     */
    public function testReadsTheLocaleFromTheRequest(?string $locale, string $expected): void
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
     * @param string|null $locale
     * @param string      $expected
     *
     * @dataProvider localeTestData
     */
    public function testReadsTheLocaleFromTheSession(?string $locale, string $expected): void
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
     * @return array
     */
    public function localeTestData(): array
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
     * @param string|null $locale
     * @param string      $expected
     * @param array       $available
     *
     * @dataProvider acceptLanguageTestData
     */
    public function testReadsTheLocaleFromTheAcceptLanguageHeader(?string $locale, string $expected, array $available): void
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
     * @return array
     */
    public function acceptLanguageTestData(): array
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

    public function testDoesNothingIfThereIsNoRequestScope(): void
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

    public function testFailsIfTheLocaleIsInvalid(): void
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
