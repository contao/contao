<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Messenger\Message\BackendSearch;

use Contao\CoreBundle\Messenger\Message\BackendSearch\ReindexMessage;
use PHPUnit\Framework\TestCase;

class ReindexMessageTest extends TestCase
{
    public function testGetter(): void
    {
        $message = new ReindexMessage();
        $this->assertNull($message->getUpdateSince());

        $message = new ReindexMessage('2024-01-01 00:00:00');
        $this->assertInstanceOf(\DateTimeInterface::class, $message->getUpdateSince());
    }
}
