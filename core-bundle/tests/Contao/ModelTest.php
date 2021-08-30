<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\CoreBundle\Tests\TestCase;
use Contao\System;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @group legacy
 */
class ModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = $this->getContainerWithContaoConfiguration();
        $container->setParameter('contao.resources_paths', $this->getTempDir());

        (new Filesystem())->mkdir($this->getTempDir().'/languages/en');

        System::setContainer($container);
    }

    public function testThrowsExceptionForNonExistantRelations(): void
    {
        $this->expectException(\Exception::class);

        // ContentModel could be any other Model class extending from \Contao\Model
        $model = new \Contao\ContentModel();
        $model->getRelated('foo');
    }

    /**
     * @dataProvider brokenRelationsProvider
     */
    public function testIncludesTableAndRelationNameForNonExistantRelationsInException(string $table, string $relation): void
    {
        // We expect to find the table name and the relation in the exception,
        // e.g. tl_foo.bar for the relation "bar" of table "tl_foo".
        $expectedExceptionContent = $table.'.'.$relation;
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/'.preg_quote($expectedExceptionContent).'/');

        // Sadly, we cannot use a mock of \Contao\Model for abstract testing
        // since PHPUnit mocks cannot call or mock static methods (in this case,
        // static::getTable() within the Model), so use a real model
        // class and dynamically set the table name via reflection.

        // ContentModel could be any other Model class extending from \Contao\Model
        $mock = new \Contao\ContentModel();
        $reflection = new \ReflectionClass($mock);
        $reflection->setStaticPropertyValue('strTable', $table);

        $mock->getRelated($relation);
    }

    public function brokenRelationsProvider(): \Generator
    {
        yield 'tl_foo.bar' => [
            'tl_foo',
            'bar',
        ];

        yield 'tl_bar.foo' => [
            'tl_bar',
            'foo',
        ];
    }
}
