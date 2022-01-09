<?php declare(strict_types=1);

namespace Contao\CoreBundle\Tests\EventListener\DataContainer\Undo;

use Contao\CoreBundle\EventListener\DataContainer\Undo\FromTableOptionsListener;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\MockObject\MockObject;

class FromTableOptionsListenerTest extends TestCase
{
    /**
     * @var Connection&MockObject
     */
    private Connection $connection;

    public function setUp(): void
    {
        parent::setUp();
        $this->connection = $this->createMock(Connection::class);
    }

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

        $this->connection
            ->method('getDatabasePlatform')
            ->willReturn($platform)
        ;

        $this->connection
            ->method('quoteIdentifier')
            ->willReturnArgument(0)
        ;

        $this->connection
            ->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT DISTINCT fromTable FROM tl_undo')
            ->willReturn($result)
        ;

        $listener = new FromTableOptionsListener($this->connection);
        $tables = $listener();

        $this->assertEquals(['tl_form'], $tables);
    }
}
