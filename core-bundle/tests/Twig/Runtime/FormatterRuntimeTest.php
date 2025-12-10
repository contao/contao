<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Runtime;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Runtime\FormatterRuntime;
use Contao\System;
use Symfony\Contracts\Translation\TranslatorInterface;

class FormatterRuntimeTest extends TestCase
{
    public function testDelegatesCalls(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->exactly(5))
            ->method('trans')
            ->willReturnCallback(
                static function (string $id): string {
                    return match ($id) {
                        'MSC.decimalSeparator' => '.',
                        'MSC.thousandsSeparator' => ',',
                        'UNITS.0' => 'Byte',
                        'UNITS.1' => 'KiB',
                        default => throw new \InvalidArgumentException(\sprintf('Unknown translation id: %s', $id)),
                    };
                },
            )
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('translator', $translator);
        System::setContainer($container);

        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->expects($this->exactly(2))
            ->method('initialize')
        ;

        $this->assertSame('1.50 KiB', (new FormatterRuntime($framework))->formatBytes(1024 + 512, 2));
        $this->assertSame('42.00', (new FormatterRuntime($framework))->formatNumber(42, 2));
    }
}
