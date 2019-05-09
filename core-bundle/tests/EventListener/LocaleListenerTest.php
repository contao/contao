<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\EventListener\LocaleListener;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class LocaleListenerTest extends TestCase
{
    /**
     * @dataProvider getLocaleRequestData
     */
    public function testReadsTheLocaleFromTheRequest(?string $locale, string $expected): void
    {
        $request = Request::create('/');
        $request->attributes->set('_locale', $locale);
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $kernel = $this->createMock(KernelInterface::class);
        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $listener = new LocaleListener($this->mockScopeMatcher(), ['en']);
        $listener->onKernelRequest($event);

        $this->assertSame($expected, $request->attributes->get('_locale'));
    }

    public function getLocaleRequestData(): \Generator
    {
        yield [null, 'en']; // see #264
        yield ['en', 'en'];
        yield ['de', 'de'];
        yield ['de-CH', 'de_CH'];
        yield ['de_CH', 'de_CH'];
        yield ['zh-tw', 'zh_TW'];
    }

    /**
     * @dataProvider acceptLanguageTestData
     */
    public function testReadsTheLocaleFromTheAcceptLanguageHeader(?string $locale, string $expected, array $available): void
    {
        $request = Request::create('/');
        $request->headers->set('Accept-Language', $locale);
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $event = new GetResponseEvent($this->createMock(KernelInterface::class), $request, HttpKernelInterface::MASTER_REQUEST);

        $listener = new LocaleListener($this->mockScopeMatcher(), $available);
        $listener->onKernelRequest($event);

        $this->assertSame($expected, $request->attributes->get('_locale'));
    }

    public function acceptLanguageTestData(): \Generator
    {
        yield [null, 'de', ['de', 'en']]; // see #264
        yield ['de', 'de', ['de', 'en']];
        yield ['de, en', 'en', ['en']];
        yield ['de', 'en', ['en']];
        yield ['de-de, en', 'de', ['de', 'en']];
        yield ['de, fr, en', 'fr', ['en', 'fr']];
        yield ['fr, de-ch, en', 'de_CH', ['en', 'de_CH']];
    }

    public function testDoesNothingIfThereIsNoRequestScope(): void
    {
        $attributes = $this->createMock(ParameterBag::class);
        $attributes
            ->expects($this->never())
            ->method('set')
        ;

        $request = Request::create('/', Request::METHOD_GET, [$attributes]);
        $event = new GetResponseEvent($this->createMock(KernelInterface::class), $request, HttpKernelInterface::MASTER_REQUEST);

        $listener = new LocaleListener($this->mockScopeMatcher(), ['en']);
        $listener->onKernelRequest($event);
    }

    public function testFailsIfTheLocaleIsInvalid(): void
    {
        $request = Request::create('/');
        $request->attributes->set('_locale', 'invalid');
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $event = new GetResponseEvent($this->createMock(KernelInterface::class), $request, HttpKernelInterface::MASTER_REQUEST);
        $listener = new LocaleListener($this->mockScopeMatcher(), ['en']);

        $this->expectException('InvalidArgumentException');

        $listener->onKernelRequest($event);
    }
}
