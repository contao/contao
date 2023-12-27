<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\DataContainer;

use Contao\CoreBundle\EventListener\DataContainer\LayoutOptionsListener;
use Contao\CoreBundle\EventListener\DataContainer\LogoutPageRedirectListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DC_Table;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Service\ResetInterface;

class LogoutPageRedirectListenerTest extends TestCase
{
    /**
     * @dataProvider listenerProvider
     */
    public function testListener(array $attributes, array|null $currentRecord, bool|null $expected): void
    {
        if (null === $currentRecord) {
            $dataContainer = null;
        } else {
            $dataContainer = $this->createMock(DC_Table::class);
            $dataContainer
                ->method('getCurrentRecord')
                ->willReturn($currentRecord)
            ;
        }

        $listener = new LogoutPageRedirectListener();
        $attributes = $listener($attributes, $dataContainer);

        if (null === $expected) {
            $this->assertArrayNotHasKey('mandatory', $attributes);
        } else {
            $this->assertArrayHasKey('mandatory', $attributes);
            $this->assertSame($expected, $attributes['mandatory']);
        }
    }

    public function listenerProvider(): \Generator
    {
        yield 'Keeps true mandatory value for logout pages' => [
            [],
            ['type' => 'logout'],
            true
        ];

        yield 'Overrides false mandatory value for logout pages' => [
            ['mandatory' => false],
            ['type' => 'logout'],
            true
        ];

        yield 'Overrides any mandatory value for logout pages' => [
            ['mandatory' => 'foobar'],
            ['type' => 'logout'],
            true
        ];

        yield 'Does not change true mandatory property for non-logout pages' => [
            ['mandatory' => true],
            ['type' => 'regular'],
            true
        ];

        yield 'Does not change false mandatory property for non-logout pages' => [
            ['mandatory' => false],
            ['type' => 'regular'],
            false
        ];

        yield 'Does not add the mandatory property for non-logout pages' => [
            [],
            ['type' => 'regular'],
            null
        ];

        yield 'Does nothing without DataContainer' => [
            [],
            null,
            null
        ];
    }
}
