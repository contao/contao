<?php

declare(strict_types=1);

namespace Contao\MakerBundle\Tests\Translation;

use Contao\MakerBundle\Translation\XliffMerger;
use PHPUnit\Framework\TestCase;

class XliffMergerTest extends TestCase
{
    public function testReturnsRootNodeIfNoBodyTagIsFound(): void
    {
        $root = new \DOMDocument();
        $root->load(__DIR__ . '/../Fixtures/translations/test-no-body/no-body-tag.xlf');

        $document = new \DOMDocument();
        $document->load(__DIR__ . '/../Fixtures/translations/test-no-body/empty.xlf');

        $merger = new XliffMerger();
        $mergedDocument = $merger->merge($root, $document);

        $this->assertSame($root, $mergedDocument);
    }

    public function testReturnsExpectedMergedDocument(): void
    {
        $root = new \DOMDocument();
        $root->load(__DIR__ . '/../Fixtures/translations/test-merge/root.xlf');

        $document = new \DOMDocument();
        $document->load(__DIR__ . '/../Fixtures/translations/test-merge/document.xlf');

        $expectation = new \DOMDocument();
        $expectation->load(__DIR__ . '/../Fixtures/translations/test-merge/merged.xlf');

        $merger = new XliffMerger();
        $mergedDocument = $merger->merge($root, $document);

        $this->assertSame($expectation->saveXML(), $mergedDocument->saveXML());
    }

    public function testDoesNotOverwriteDuplicates(): void
    {
        $root = new \DOMDocument();
        $root->load(__DIR__ . '/../Fixtures/translations/test-duplicates/root.xlf');

        $document = new \DOMDocument();
        $document->load(__DIR__ . '/../Fixtures/translations/test-duplicates/document.xlf');

        $expectation = new \DOMDocument();
        $expectation->load(__DIR__ . '/../Fixtures/translations/test-duplicates/merged.xlf');

        $merger = new XliffMerger();
        $mergedDocument = $merger->merge($root, $document);

        $this->assertSame($expectation->saveXML(), $mergedDocument->saveXML());
    }

    public function testGetImportNodesForEmptyDocument(): void
    {
        $merger = new XliffMerger();

        $class = new \ReflectionClass(XliffMerger::class);
        $method = $class->getMethod('getImportNodes');
        $method->setAccessible(true);

        $document = new \DOMDocument();
        $document->load(__DIR__ . '/../Fixtures/translations/test-get-import-nodes/empty.xlf');

        $nodes = $method->invoke($merger, $document);

        $this->assertIsArray($nodes);
        $this->assertEmpty($nodes);
    }

    public function testGetImportNodesForSingleNodeDocument(): void
    {
        $merger = new XliffMerger();

        $class = new \ReflectionClass(XliffMerger::class);
        $method = $class->getMethod('getImportNodes');
        $method->setAccessible(true);

        $document = new \DOMDocument();
        $document->load(__DIR__ . '/../Fixtures/translations/test-get-import-nodes/single-element.xlf');

        $nodes = $method->invoke($merger, $document);

        $this->assertIsArray($nodes);
        $this->assertCount(1, $nodes);
        $this->assertContainsOnlyInstancesOf(\DOMElement::class, $nodes);
    }

    public function testGetImportNodesForMultipleNodeDocument(): void
    {
        $merger = new XliffMerger();

        $class = new \ReflectionClass(XliffMerger::class);
        $method = $class->getMethod('getImportNodes');
        $method->setAccessible(true);

        $document = new \DOMDocument();
        $document->load(__DIR__ . '/../Fixtures/translations/test-get-import-nodes/multiple-elements.xlf');

        $nodes = $method->invoke($merger, $document);

        $this->assertIsArray($nodes);
        $this->assertCount(4, $nodes);
        $this->assertContainsOnlyInstancesOf(\DOMElement::class, $nodes);
    }
}
