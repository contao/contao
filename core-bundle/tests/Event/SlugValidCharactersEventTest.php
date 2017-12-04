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

use Contao\CoreBundle\Event\SlugValidCharactersEvent;
use PHPUnit\Framework\TestCase;

class SlugValidCharactersEventTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $event = new SlugValidCharactersEvent([]);

        $this->assertInstanceOf('Contao\CoreBundle\Event\SlugValidCharactersEvent', $event);
    }

    public function testReadsAndWritesTheOptions(): void
    {
        $event = new SlugValidCharactersEvent(['a-z' => 'ASCII']);

        $this->assertSame(['a-z' => 'ASCII'], $event->getOptions());

        $event->setOptions(['\pL' => 'Unicode', '0-9' => 'Digits']);

        $this->assertSame(['\pL' => 'Unicode', '0-9' => 'Digits'], $event->getOptions());
    }
}
