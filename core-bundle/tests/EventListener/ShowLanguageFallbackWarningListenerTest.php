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

use Contao\CoreBundle\EventListener\ShowLanguageFallbackWarningListener;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class ShowLanguageFallbackWarningListenerTest extends TestCase
{
    #[DataProvider('provideRequestAndDirty')]
    public function testGeneratesMessages(array $records, string $messages): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn($records)
        ;

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnCallback(static fn (string $msg) => $msg)
        ;

        $listener = new ShowLanguageFallbackWarningListener($this->createMock(RequestStack::class), $connection, $translator);

        $this->assertSame($listener->onGetSystemMessages(), $messages);
    }

    public static function provideRequestAndDirty(): iterable
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
}
