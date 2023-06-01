<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao\Database;

use Contao\CoreBundle\Tests\TestCase;
use Contao\Database\Statement;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Result;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\DependencyInjection\Container;

class StatementTest extends TestCase
{
    use ExpectDeprecationTrait;

    protected function tearDown(): void
    {
        $this->resetStaticProperties([System::class]);

        parent::tearDown();
    }

    /**
     * @dataProvider getDeprecatedSetQueries
     */
    public function testSetThrowsException(string $query): void
    {
        $statement = new Statement($this->createMock(Connection::class));

        if ($query) {
            $statement->prepare($query);
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('is only supported for INSERT and UPDATE queries with the "%s" placeholder');

        $statement->set(['foo' => 'bar']);
    }

    public function getDeprecatedSetQueries(): \Generator
    {
        yield [''];
        yield ['SELECT * FROM %s'];
        yield ["SELECT 'INSERT' FROM %s"];
        yield ['INSERT INTO missing_placeholder'];
        yield ['UPDATE missing_placeholder'];
        yield ['INSERT INTO two_placeholders %s %s'];
        yield ['UPDATE two_placeholders %s %s'];
    }

    /**
     * @dataProvider getQueriesWithParametersAndSets
     */
    public function testReplacesParametersAndSets(string $query, string $expected, array|null $params = null, array|null $set = null): void
    {
        $doctrineResult = $this->createMock(Result::class);
        $doctrineResult
            ->method('columnCount')
            ->willReturn(1)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->willReturnCallback(
                function (string $query, array $params) use ($expected, $doctrineResult) {
                    $params = array_map(
                        static function ($param) {
                            if (\is_bool($param)) {
                                return (int) $param;
                            }

                            if (\is_object($param) || \is_array($param)) {
                                $param = serialize($param);
                            }

                            if (\is_string($param)) {
                                return "'".str_replace("'", "''", $param)."'";
                            }

                            return $param ?? 'NULL';
                        },
                        $params
                    );

                    $builtQuery = '';

                    foreach (explode('?', $query) as $index => $queryPart) {
                        $builtQuery .= $params[$index - 1] ?? '';
                        $builtQuery .= $queryPart;
                    }

                    $this->assertSame($expected, $builtQuery);

                    return $doctrineResult;
                }
            )
        ;

        $connection
            ->method('quoteIdentifier')
            ->willReturnArgument(0)
        ;

        $connection
            ->method('getDatabasePlatform')
            ->willReturn($this->createMock(AbstractPlatform::class))
        ;

        $container = new Container();
        $container->set('database_connection', $connection);

        System::setContainer($container);

        $statement = new Statement($connection);
        $statement->prepare($query);

        if ($set) {
            $statement->set($set);
        }

        $statement->execute(...($params ?? []));
    }

    public function getQueriesWithParametersAndSets(): \Generator
    {
        yield [
            'SELECT id FROM tl_content',
            'SELECT id FROM tl_content',
        ];

        yield [
            'SELECT id FROM tl_content WHERE boolCol = ? AND intCol = ? AND floatCol = ? AND stringCol = ? AND nullCol = ?',
            "SELECT id FROM tl_content WHERE boolCol = 0 AND intCol = 0 AND floatCol = 0 AND stringCol = '' AND nullCol = NULL",
            [false, 0, 0.0, '', null],
        ];

        yield [
            'SELECT id FROM tl_content WHERE boolCol = ? AND intCol = ? AND floatCol = ? AND stringCol = ?',
            "SELECT id FROM tl_content WHERE boolCol = 1 AND intCol = 123456 AND floatCol = 123.456 AND stringCol = 'foo''bar'",
            [true, 123456, 123.456, 'foo\'bar'],
        ];

        yield [
            'SELECT id FROM tl_content WHERE objectCol = ? AND arrayCol = ?',
            "SELECT id FROM tl_content WHERE objectCol = 'O:8:\"stdClass\":0:{}' AND arrayCol = 'a:0:{}'",
            [new \stdClass(), []],
        ];

        yield [
            'SELECT id FROM tl_content WHERE objectCol = ? AND arrayCol = ?',
            "SELECT id FROM tl_content WHERE objectCol = 'O:8:\"stdClass\":2:{s:1:\"a\";i:1;s:1:\"b\";i:1;}' AND arrayCol = 'a:2:{i:0;i:1;i:1;i:2;}'",
            [(object) ['a' => 1, 'b' => 1], [1, 2]],
        ];

        yield [
            'SELECT id FROM tl_content WHERE arrayCol = ? AND objectCol = ?',
            "SELECT id FROM tl_content WHERE arrayCol = 'a:2:{i:0;i:1;i:1;i:2;}' AND objectCol = 'O:8:\"stdClass\":2:{s:1:\"a\";i:1;s:1:\"b\";i:1;}'",
            [[1, 2], (object) ['a' => 1, 'b' => 1]],
        ];

        yield [
            'INSERT INTO tl_content %s',
            "INSERT INTO tl_content (boolCol, intCol, floatCol, stringCol, nullCol) VALUES (1, 123456, 123.456, 'foo''bar', NULL)",
            null,
            [
                'boolCol' => true,
                'intCol' => 123456,
                'floatCol' => 123.456,
                'stringCol' => 'foo\'bar',
                'nullCol' => null,
            ],
        ];

        yield [
            'UPDATE tl_content %s WHERE id = ?',
            "UPDATE tl_content SET boolCol=1, intCol=123456, floatCol=123.456, stringCol='foo''bar', nullCol=NULL WHERE id = 123",
            [123],
            [
                'boolCol' => true,
                'intCol' => 123456,
                'floatCol' => 123.456,
                'stringCol' => 'foo\'bar',
                'nullCol' => null,
            ],
        ];

        yield [
            "SELECT id FROM tl_content WHERE headline = '%%%-special' and type = ?",
            "SELECT id FROM tl_content WHERE headline = '%%%-special' and type = ' BOBBY TABLES -- a'",
            [' BOBBY TABLES -- a', 'b'],
        ];

        yield [
            'SELECT id FROM tl_content WHERE type = ?',
            "SELECT id FROM tl_content WHERE type = 'a'",
            ['a', 'b'],
        ];

        yield [
            'SELECT id FROM tl_content',
            'SELECT id FROM tl_content',
            ['a'],
        ];

        yield [
            'SELECT id FROM tl_content',
            'SELECT id FROM tl_content',
            [null],
        ];
    }
}
