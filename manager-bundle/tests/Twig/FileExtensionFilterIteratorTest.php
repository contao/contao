<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\Twig;

use Contao\ManagerBundle\Twig\FileExtensionFilterIterator;
use Contao\TestCase\ContaoTestCase;

class FileExtensionFilterIteratorTest extends ContaoTestCase
{
    public function testRemovesPathsWithoutTwigFileExtension(): void
    {
        $input = ['foo.twig', 'bar.twig', 'foobar.sql', 'foo/bar.sql'];
        $expected = ['foo.twig', 'bar.twig'];

        $this->assertSame($expected, $this->applyFilter($input));
    }

    public function testDoesNotAlterNamespacedPaths(): void
    {
        $input = ['@FooBundle/foo.twig', '@FooBundle/foo.sql'];

        $this->assertSame($input, $this->applyFilter($input));
    }

    private function applyFilter(array $input): array
    {
        $iteratorAggregate = new IteratorAggregateStub($input);
        $iteratorAggregate = new FileExtensionFilterIterator($iteratorAggregate);

        $output = iterator_to_array($iteratorAggregate->getIterator());

        // normalize keys
        return array_values($output);
    }
}
