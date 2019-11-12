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

use Contao\ManagerBundle\Tests\Fixtures\IteratorAggregateStub;
use Contao\ManagerBundle\Twig\FileExtensionFilterIterator;
use PHPUnit\Framework\TestCase;

class FileExtensionFilterIteratorTest extends TestCase
{
    public function testRemovesPathsWithoutTwigFileExtension(): void
    {
        $input = ['foo.twig', 'bar.twig', 'foobar.php', 'foo/bar'];

        $this->assertSame(['foo.twig', 'bar.twig'], $this->applyFilter($input));
    }

    public function testDoesNotAlterNamespacedPaths(): void
    {
        $input = ['@FooBundle/foo.twig', '@FooBundle/foo.other'];

        $this->assertSame($input, $this->applyFilter($input));
    }

    private function applyFilter(array $input): array
    {
        $iterator = new FileExtensionFilterIterator(new IteratorAggregateStub($input));
        $output = iterator_to_array($iterator->getIterator());

        return array_values($output);
    }
}
