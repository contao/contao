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

use Contao\CoreBundle\EventListener\DataContainer\AccordionListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;

class AccordionListenerTest extends TestCase
{
    private const PALETTE = '{type_legend},type,headline;{template_legend},customTpl';

    public function testAddsTheSectionHeadlineToThePalette(): void
    {
        $currentRecord = [
            'pid' => 2,
            'ptable' => 'tl_content',
        ];

        $parentRecord = [
            'type' => 'accordion',
        ];

        $dc = $this->createMock(DataContainer::class);
        $dc
            ->expects($this->exactly(2))
            ->method('getCurrentRecord')
            ->willReturnCallback(
                static fn (int|null $pid = null) => match ($pid) {
                    2 => $parentRecord,
                    default => $currentRecord,
                },
            )
        ;

        $this->assertSame(
            '{type_legend},type,headline;{section_legend},sectionHeadline;{template_legend},customTpl',
            (new AccordionListener())(self::PALETTE, $dc),
        );
    }

    public function testDoesNotAddTheSectionHeadlineIfNotANestedElement(): void
    {
        $currentRecord = [
            'pid' => 2,
            'ptable' => 'tl_article',
        ];

        $dc = $this->createMock(DataContainer::class);
        $dc
            ->expects($this->once())
            ->method('getCurrentRecord')
            ->willReturn($currentRecord)
        ;

        $this->assertSame(self::PALETTE, (new AccordionListener())(self::PALETTE, $dc));
    }

    public function testDoesNotAddTheSectionHeadlineIfTheParentIsNotAnAccordion(): void
    {
        $currentRecord = [
            'pid' => 2,
            'ptable' => 'tl_content',
        ];

        $parentRecord = [
            'type' => 'slider',
        ];

        $dc = $this->createMock(DataContainer::class);
        $dc
            ->expects($this->exactly(2))
            ->method('getCurrentRecord')
            ->willReturnCallback(
                static fn (int|null $pid = null) => match ($pid) {
                    2 => $parentRecord,
                    default => $currentRecord,
                },
            )
        ;

        $this->assertSame(self::PALETTE, (new AccordionListener())(self::PALETTE, $dc));
    }
}
