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

use Contao\Database\Result;
use Doctrine\DBAL\Cache\ArrayResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result as DoctrineResult;
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{
    public function testEmptyResult(): void
    {
        $results = $this->createResults([]);

        foreach ($results as $result) {
            foreach ([null, 'first', 'last', 'reset'] as $methodName) {
                if ($methodName) {
                    $this->assertSame($result, $result->$methodName());
                }
                $this->assertFalse($result->isModified);
                $this->assertSame(0, $result->numFields);
                $this->assertSame(0, $result->numRows);
                $this->assertSame('SELECT * FROM test', $result->query);
                $this->assertSame(0, $result->count());
                $this->assertSame([], $result->fetchAllAssoc());
                $this->assertFalse($result->fetchAssoc());
                $this->assertSame([], $result->fetchEach('test'));
                $this->assertFalse($result->fetchRow());
                $this->assertSame([], $result->row());
                $this->assertSame([], $result->row(true));
                $this->assertFalse(isset($result->modifiedKey));
                $this->assertNull($result->modifiedKey);
                $result->modifiedKey = 'value';
                $this->assertSame(['modifiedKey' => 'value'], $result->row());
                $this->assertSame(['value'], $result->row(true));
                $this->assertTrue(isset($result->modifiedKey));
                $this->assertSame('value', $result->modifiedKey);
            }
        }

        PHP_MAJOR_VERSION < 8 ? $this->expectNotice() : $this->expectWarning();

        $results[1]->fetchField();
    }

    public function testSingleRow(): void
    {
        $data = [
            ['field' => 'value1'],
        ];

        $results = $this->createResults($data);

        foreach ($results as $result) {
            $this->assertFalse($result->isModified);
            $this->assertSame(1, $result->numFields);
            $this->assertSame(1, $result->numRows);
            $this->assertSame('SELECT * FROM test', $result->query);
            $this->assertSame(1, $result->count());
            $this->assertSame($data, $result->fetchAllAssoc());
            $this->assertSame($data[0], $result->reset()->fetchAssoc());
            $this->assertFalse($result->fetchAssoc());
            $this->assertSame(['value1'], $result->fetchEach('field'));
            $this->assertSame(array_values($data[0]), $result->reset()->fetchRow());
            $this->assertFalse($result->fetchRow());

            $this->assertSame($data[0], $result->reset()->row());
            $this->assertSame(array_values($data[0]), $result->last()->row(true));

            $this->assertSame('value1', $result->first()->field);
            $this->assertFalse($result->prev());
            $this->assertSame('value1', $result->last()->field);
            $this->assertFalse($result->next());

            $result->field = 'new value';
            $this->assertSame('new value', $result->field);
            $this->assertSame(['field' => 'new value'], $result->row());
            $this->assertSame(['new value'], $result->row(true));
            $this->assertSame('value1', $result->fetchField());
            $this->assertSame('value1', $result->fetchField());
        }

        PHP_MAJOR_VERSION < 8 ? $this->expectNotice() : $this->expectWarning();

        $results[1]->fetchField(1);
    }

    public function testMultipleRows(): void
    {
        $data = [
            ['field' => 'value1'],
            ['field' => 'value2'],
        ];

        $results = $this->createResults($data);

        foreach ($results as $result) {
            $this->assertFalse($result->isModified);
            $this->assertSame(1, $result->numFields);
            $this->assertSame(2, $result->numRows);
            $this->assertSame('SELECT * FROM test', $result->query);
            $this->assertSame(2, $result->count());
            $this->assertSame($data, $result->fetchAllAssoc());
            $this->assertSame($data[0], $result->reset()->fetchAssoc());
            $this->assertSame($data[1], $result->fetchAssoc());
            $this->assertSame(['value1', 'value2'], $result->fetchEach('field'));
            $this->assertSame(array_values($data[0]), $result->reset()->fetchRow());
            $this->assertSame(array_values($data[1]), $result->fetchRow());
            $this->assertFalse($result->fetchRow());

            $this->assertSame($data[0], $result->reset()->row());
            $this->assertSame(array_values($data[1]), $result->last()->row(true));

            $this->assertSame('value1', $result->first()->field);
            $this->assertFalse($result->prev());
            $this->assertSame('value2', $result->next()->field);
            $this->assertInstanceOf(Result::class, $result->prev());
            $this->assertSame('value1', $result->field);
            $this->assertSame('value2', $result->last()->field);
            $this->assertFalse($result->next());

            $result->field = 'new value';
            $this->assertSame('new value', $result->field);
            $this->assertSame(['field' => 'new value'], $result->row());
            $this->assertSame(['new value'], $result->row(true));
            $this->assertSame('value2', $result->fetchField());
            $this->assertSame('value2', $result->fetchField());
        }

        PHP_MAJOR_VERSION < 8 ? $this->expectNotice() : $this->expectWarning();

        $results[1]->fetchField(1);
    }

    public function testFetchRowAndAssoc(): void
    {
        $data = [
            ['field' => 'value1'],
            ['field' => 'value2'],
        ];

        $results = $this->createResults($data);

        foreach ($results as $result) {
            $this->assertSame(['field' => 'value1'], $result->fetchAssoc());
            $this->assertSame(['field' => 'value1'], $result->row());
            $this->assertSame('value1', $result->field);
            $this->assertNull($result->{'0'});

            $this->assertSame(['value2'], $result->fetchRow());
            $this->assertSame(['field' => 'value2'], $result->row());
            $this->assertSame('value2', $result->field);
            $this->assertNull($result->{'0'});
        }
    }

    public function testResultStatementInterface(): void
    {
        $resultStatement = $this->createMock(DoctrineResult::class);
        $resultStatement
            ->expects($this->exactly(3))
            ->method('fetchAssociative')
            ->willReturnOnConsecutiveCalls(['field' => 'value1'], ['field' => 'value2'], false)
        ;

        $result = new Result($resultStatement, 'SELECT * FROM test');

        $this->assertSame(2, $result->count());
    }

    /**
     * @dataProvider getInvalidStatements
     */
    public function testInvalidStatements(array|object|string $statement): void
    {
        $this->expectException('InvalidArgumentException');

        new Result($statement, 'SELECT * FROM test');
    }

    public function getInvalidStatements(): \Generator
    {
        yield 'String' => ['foo'];
        yield 'Object' => [new \stdClass()];
        yield 'Single Array' => [['foo' => 'bar']];
        yield 'Mixed Array' => [[['foo' => 'bar'], 'baz']];
    }

    /**
     * @param array<array<string,string>> $data
     *
     * @return array<Result|object>
     */
    private function createResults(array $data): array
    {
        return [
            new Result(
                new DoctrineResult(new ArrayResult($data), $this->createMock(Connection::class)),
                'SELECT * FROM test'
            ),
            new Result($data, 'SELECT * FROM test'),
        ];
    }
}
