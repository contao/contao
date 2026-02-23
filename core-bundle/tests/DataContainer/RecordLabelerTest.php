<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DataContainer;

use Contao\CoreBundle\DataContainer\RecordLabeler;
use Contao\CoreBundle\Event\DataContainerRecordLabelEvent;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class RecordLabelerTest extends TestCase
{
    public function testDispatchesEvent(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(
                function (DataContainerRecordLabelEvent $event): DataContainerRecordLabelEvent {
                    $this->assertSame('contao.db.tl_foo.123', $event->getIdentifier());
                    $this->assertSame(['id' => 123], $event->getData());

                    $event->setLabel('My Label');

                    return $event;
                },
            )
        ;

        $labeler = new RecordLabeler($dispatcher);
        $this->assertSame('My Label', $labeler->getLabel('contao.db.tl_foo.123', ['id' => 123]));
    }
}
