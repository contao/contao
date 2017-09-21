<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Event;

use Contao\CoreBundle\Event\PreviewUrlConvertEvent;
use PHPUnit\Framework\TestCase;

class PreviewUrlConvertEventTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $event = new PreviewUrlConvertEvent();

        $this->assertInstanceOf('Contao\CoreBundle\Event\PreviewUrlConvertEvent', $event);
    }

    public function testSupportsReadingAndWritingTheUrl(): void
    {
        $event = new PreviewUrlConvertEvent();

        $this->assertNull($event->getUrl());

        $event->setUrl('http://localhost');

        $this->assertSame('http://localhost', $event->getUrl());
    }
}
