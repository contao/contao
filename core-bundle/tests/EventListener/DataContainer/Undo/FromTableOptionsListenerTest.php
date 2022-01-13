<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\DataContainer\Undo;

use Contao\CoreBundle\EventListener\DataContainer\Undo\FromTableOptionsListener;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Result;

class FromTableOptionsListenerTest extends TestCase
{
    public function testGetFromTableOptions(): void
    {
        $result = $this->createConfiguredMock(Result::class, [
            'rowCount' => 1,
            'fetchFirstColumn' => ['tl_form'],
        ]);

        $platform = $this->createMock(AbstractPlatform::class);
        $platform
            ->method('getIdentifierQuoteCharacter')
            ->willReturn('\'')
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('getDatabasePlatform')
            ->willReturn($platform)
        ;

        $connection
            ->method('quoteIdentifier')
            ->willReturnArgument(0)
        ;

        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT DISTINCT fromTable FROM tl_undo')
            ->willReturn($result)
        ;

        $listener = new FromTableOptionsListener($connection);
        $tables = $listener();

        $this->assertSame(['tl_form'], $tables);
    }
}
