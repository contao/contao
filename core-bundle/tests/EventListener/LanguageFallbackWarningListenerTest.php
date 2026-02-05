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

use Contao\CoreBundle\EventListener\LanguageFallbackWarningListener;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Message;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class LanguageFallbackWarningListenerTest extends TestCase
{
    #[DataProvider('provideRootRecords')]
    public function testGeneratesMessages(array $records, string $messages): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn($records)
        ;

        $translator = $this->createStub(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnCallback(static fn (string $msg) => $msg)
        ;

        $listener = new LanguageFallbackWarningListener($this->createStub(RequestStack::class), $connection, $translator, $this->createStub(ContaoFramework::class));

        $this->assertSame($listener->getMessages(), $messages);
    }

    public static function provideRootRecords(): iterable
    {
        yield [
            [['fallback' => 1, 'dns' => ''], ['fallback' => 0, 'dns' => '']],
            '',
        ];

        yield [
            [['fallback' => 0, 'dns' => ''], ['fallback' => 0, 'dns' => '']],
            '<p class="tl_error">ERR.noFallbackEmpty</p>',
        ];

        yield [
            [['fallback' => 1, 'dns' => 'example.com'], ['fallback' => 0, 'dns' => 'example.com']],
            '',
        ];

        yield [
            [['fallback' => 0, 'dns' => 'example.com'], ['fallback' => 0, 'dns' => 'example.com']],
            '<p class="tl_error">ERR.noFallbackDns</p>',
        ];

        yield [
            [['fallback' => 1, 'dns' => ''], ['fallback' => 0, 'dns' => ''], ['fallback' => 1, 'dns' => 'example.com'], ['fallback' => 0, 'dns' => 'example.com']],
            '',
        ];

        yield [
            [['fallback' => 0, 'dns' => ''], ['fallback' => 0, 'dns' => ''], ['fallback' => 0, 'dns' => 'example.com'], ['fallback' => 0, 'dns' => 'example.com']],
            '<p class="tl_error">ERR.noFallbackEmpty</p><p class="tl_error">ERR.noFallbackDns</p>',
        ];
    }

    public function testAddsContaoMessage(): void
    {
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(new Request())
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn([['fallback' => 0, 'dns' => '']])
        ;

        $translator = $this->createStub(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnCallback(static fn (string $msg) => $msg)
        ;

        $contaoMessage = $this->createAdapterMock(['addRaw']);
        $contaoMessage
            ->expects($this->once())
            ->method('addRaw')
            ->with('<p class="tl_error">ERR.noFallbackEmpty</p>')
        ;

        $contaoFramework = $this->createContaoFrameworkStub([Message::class => $contaoMessage]);

        $listener = new LanguageFallbackWarningListener($requestStack, $connection, $translator, $contaoFramework);
        $listener->onPageLoad();
    }

    public function testDoesNotAddContaoMessage(): void
    {
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(Request::create('?act=create'))
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method('fetchAllAssociative')
        ;

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->never())
            ->method('trans')
        ;

        $contaoMessage = $this->createAdapterMock(['addRaw']);
        $contaoMessage
            ->expects($this->never())
            ->method('addRaw')
        ;

        $contaoFramework = $this->createContaoFrameworkStub([Message::class => $contaoMessage]);

        $listener = new LanguageFallbackWarningListener($requestStack, $connection, $translator, $contaoFramework);
        $listener->onPageLoad();
    }
}
