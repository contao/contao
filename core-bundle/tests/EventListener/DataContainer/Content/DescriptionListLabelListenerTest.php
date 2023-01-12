<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\DataContainer\Content;

use Contao\CoreBundle\EventListener\DataContainer\Content\DescriptionListLabelListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Symfony\Contracts\Translation\TranslatorInterface;

class DescriptionListLabelListenerTest extends TestCase
{
    public function testUpdatesTheDCA(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->exactly(4))
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $dc = $this->createMock(DataContainer::class);
        $dc
            ->expects($this->once())
            ->method('getCurrentRecord')
            ->willReturn(['type' => 'description_list'])
        ;

        $listener = new DescriptionListLabelListener($translator);

        $attributes = $listener([], $dc);

        $this->assertSame('tl_content.dl_label.0', $attributes['label']);
        $this->assertSame('tl_content.dl_label.1', $attributes['description']);
        $this->assertSame('tl_content.dl_key', $attributes['keyLabel']);
        $this->assertSame('tl_content.dl_value', $attributes['valueLabel']);
        $this->assertTrue($attributes['mandatory']);
        $this->assertTrue($attributes['allowEmptyKeys']);
    }

    public function testDoesNothingWithoutDataContainer(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->never())
            ->method('trans')
        ;

        $listener = new DescriptionListLabelListener($translator);

        $attributes = $listener([]);
        $this->assertEmpty($attributes);
    }

    public function testIgnoresOtherElementTypes(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->never())
            ->method('trans')
        ;

        $dc = $this->createMock(DataContainer::class);
        $dc
            ->expects($this->once())
            ->method('getCurrentRecord')
            ->willReturn(['type' => 'text'])
        ;

        $listener = new DescriptionListLabelListener($translator);

        $attributes = $listener([], $dc);
        $this->assertEmpty($attributes);
    }
}
