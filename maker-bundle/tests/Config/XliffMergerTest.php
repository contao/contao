<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\MakerBundle\Tests\Config;

use Contao\MakerBundle\Config\XliffMerger;
use PHPUnit\Framework\TestCase;

class XliffMergerTest extends TestCase
{
    public function testReturnsRootNodeIfNoBodyTagIsFound(): void
    {
        $root = new \DOMDocument();
        $root->load(__DIR__.'/../Fixtures/translations/test-no-body/no-body-tag.xlf');

        $document = new \DOMDocument();
        $document->load(__DIR__.'/../Fixtures/translations/test-no-body/empty.xlf');

        $merger = new XliffMerger();
        $mergedDocument = $merger->merge($root, $document);

        $this->assertSame($root, $mergedDocument);
    }

    public function testReturnsMergedDocument(): void
    {
        $root = new \DOMDocument();
        $root->load(__DIR__.'/../Fixtures/translations/test-merge/root.xlf');

        $document = new \DOMDocument();
        $document->load(__DIR__.'/../Fixtures/translations/test-merge/document.xlf');

        $expectation = new \DOMDocument();
        $expectation->load(__DIR__.'/../Fixtures/translations/test-merge/merged.xlf');

        $merger = new XliffMerger();
        $mergedDocument = $merger->merge($root, $document);

        $this->assertSame($expectation->saveXML(), $mergedDocument->saveXML());
    }

    public function testDoesNotOverwriteDuplicates(): void
    {
        $root = new \DOMDocument();
        $root->load(__DIR__.'/../Fixtures/translations/test-duplicates/root.xlf');

        $document = new \DOMDocument();
        $document->load(__DIR__.'/../Fixtures/translations/test-duplicates/document.xlf');

        $expectation = new \DOMDocument();
        $expectation->load(__DIR__.'/../Fixtures/translations/test-duplicates/merged.xlf');

        $merger = new XliffMerger();
        $mergedDocument = $merger->merge($root, $document);

        $this->assertSame($expectation->saveXML(), $mergedDocument->saveXML());
    }

    public function testGetImportNodesForEmptyDocument(): void
    {
        $class = new \ReflectionClass(XliffMerger::class);
        $method = $class->getMethod('getImportNodes');

        $document = new \DOMDocument();
        $document->load(__DIR__.'/../Fixtures/translations/test-get-import-nodes/empty.xlf');

        $merger = new XliffMerger();
        $nodes = $method->invoke($merger, $document);

        $this->assertIsArray($nodes);
        $this->assertEmpty($nodes);
    }

    public function testGetImportNodesForSingleNodeDocument(): void
    {
        $class = new \ReflectionClass(XliffMerger::class);
        $method = $class->getMethod('getImportNodes');

        $document = new \DOMDocument();
        $document->load(__DIR__.'/../Fixtures/translations/test-get-import-nodes/single-element.xlf');

        $merger = new XliffMerger();
        $nodes = $method->invoke($merger, $document);

        $this->assertIsArray($nodes);
        $this->assertCount(1, $nodes);
        $this->assertContainsOnlyInstancesOf(\DOMElement::class, $nodes);
    }

    public function testGetImportNodesForMultipleNodeDocument(): void
    {
        $class = new \ReflectionClass(XliffMerger::class);
        $method = $class->getMethod('getImportNodes');

        $document = new \DOMDocument();
        $document->load(__DIR__.'/../Fixtures/translations/test-get-import-nodes/multiple-elements.xlf');

        $merger = new XliffMerger();
        $nodes = $method->invoke($merger, $document);

        $this->assertIsArray($nodes);
        $this->assertCount(4, $nodes);
        $this->assertContainsOnlyInstancesOf(\DOMElement::class, $nodes);
    }
}
