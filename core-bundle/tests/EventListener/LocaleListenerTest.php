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
    public function testCanBeInstantiated(): void
    {
        $listener = new LocaleListener($this->mockScopeMatcher(), ['en']);

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\LocaleListener', $listener);
    }

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

    /**
     * @return array<(string|null)[]>
     */
    public function getLocaleRequestData(): array
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

    /**
     * @return array<(string[]|string|null)[]>
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
