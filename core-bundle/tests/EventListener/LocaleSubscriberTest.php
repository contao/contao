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

use Contao\CoreBundle\EventListener\LocaleSubscriber;
use Contao\CoreBundle\Intl\Locales;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;

class LocaleSubscriberTest extends TestCase
{
    /**
     * @dataProvider getLocaleRequestData
     */
    public function testReadsTheLocaleFromTheRequest(string|null $locale, string $expected): void
    {
        $request = $this->createMock(Request::class);
        $request->attributes = $this->createMock(ParameterBag::class);
        $request->attributes
            ->expects($this->atLeastOnce())
            ->method('get')
            ->with('_locale')
            ->willReturn($expected)
        ;

        $request->attributes
            ->expects($this->once())
            ->method('set')
            ->with('_locale', $expected)
        ;

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isContaoRequest')
            ->with($request)
            ->willReturn(true)
        ;

        $kernel = $this->createMock(KernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $listener = new LocaleSubscriber(
            $this->createMock(LocaleAwareInterface::class),
            $scopeMatcher,
            $this->mockLocales(['en']),
        );

        $listener->onKernelRequest($event);
    }

    public static function getLocaleRequestData(): iterable
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
    public function testReadsTheLocaleFromTheAcceptLanguageHeader(string|null $locale, string $expected, array $available): void
    {
        $request = $this->createMock(Request::class);
        $request->attributes = new ParameterBag();

        $request
            ->expects($this->once())
            ->method('getPreferredLanguage')
            ->with($available)
            ->willReturn($expected)
        ;

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isContaoRequest')
            ->with($request)
            ->willReturn(true)
        ;

        $event = new RequestEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );

        $listener = new LocaleSubscriber(
            $this->createMock(LocaleAwareInterface::class),
            $scopeMatcher,
            $this->mockLocales($available),
        );

        $listener->onKernelRequest($event);

        $this->assertSame($expected, $request->attributes->get('_locale'));
    }

    public static function acceptLanguageTestData(): iterable
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

        $event = new RequestEvent(
            $this->createMock(KernelInterface::class),
            Request::create('/', Request::METHOD_GET, [$attributes]),
            HttpKernelInterface::MAIN_REQUEST,
        );

        $listener = new LocaleSubscriber(
            $this->createMock(LocaleAwareInterface::class),
            $this->mockScopeMatcher(),
            $this->mockLocales(['en']),
        );

        $listener->onKernelRequest($event);
    }

    public function testSetsTheTranslatorLocale(): void
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getPreferredLanguage')
            ->with(['en', 'de'])
            ->willReturn('de')
        ;

        $event = new RequestEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );

        $translator = $this->createMock(LocaleAwareInterface::class);
        $translator
            ->expects($this->once())
            ->method('setLocale')
            ->with('de')
        ;

        $listener = new LocaleSubscriber($translator, $this->mockScopeMatcher(), $this->mockLocales(['en', 'de']));
        $listener->setTranslatorLocale($event);
    }

    private function mockLocales(array $locales): Locales&MockObject
    {
        $localesService = $this->createMock(Locales::class);
        $localesService
            ->method('getEnabledLocaleIds')
            ->willReturn($locales)
        ;

        return $localesService;
    }
}
