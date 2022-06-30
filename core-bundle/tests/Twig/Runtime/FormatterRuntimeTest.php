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

class FormatterRuntimeTest extends TestCase
{
    public function testDelegatesCalls(): void
    {
        $GLOBALS['TL_LANG'] = [
            'MSC' => [
                'decimalSeparator' => '.',
                'thousandsSeparator' => ',',
            ],
            'UNITS' => ['Byte', 'KiB'],
        ];

        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $this->assertSame(
            '1.50 KiB',
            (new FormatterRuntime($framework))->formatBytes(1024 + 512, 2)
        );

        unset($GLOBALS['TL_LANG']);
    }
}
