<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\Serializer;

use Contao\ApiBundle\DataContainer\DataContainerRelationReference;
use Contao\ApiBundle\Relation\RelationReference;
use Contao\ApiBundle\Serializer\RelationReferenceNormalizer;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

final class RelationReferenceNormalizerTest extends ContaoTestCase
{
    protected function tearDown(): void
    {
        $this->resetStaticProperties([ObjectNormalizer::class]);

        parent::tearDown();
    }

    #[DataProvider('provideFormats')]
    public function testNormalizesReferencesInOtherResources(string $format, array $expected): void
    {
        $reference = new RelationReference('file-id', '/api/files/file-id');

        $resource = new class($reference) {
            public function __construct(public RelationReference $file)
            {
            }
        };

        $serializer = new Serializer([new RelationReferenceNormalizer(), new ObjectNormalizer()]);

        $this->assertSame(['file' => $expected], $serializer->normalize($resource, $format));
        $this->assertSame(['rows' => [[$expected, null]], 'title' => 'Example'], $serializer->normalize(['rows' => [[$reference, null]], 'title' => 'Example'], $format));
    }

    public function testSupportsOnlyRelationReferencesIncludingTheLegacyClass(): void
    {
        $normalizer = new RelationReferenceNormalizer();

        $this->assertTrue($normalizer->supportsNormalization(new RelationReference(12, '/pages/12')));
        $this->assertTrue($normalizer->supportsNormalization(new DataContainerRelationReference(12, '/pages/12')));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass()));
        $this->assertFalse($normalizer->supportsNormalization(['id' => 12, 'iri' => '/pages/12']));
        $this->assertSame([RelationReference::class => true], $normalizer->getSupportedTypes('jsonld'));
    }

    public static function provideFormats(): iterable
    {
        yield 'JSON' => ['json', ['id' => 'file-id', 'iri' => '/api/files/file-id']];
        yield 'JSON-LD' => ['jsonld', ['@id' => '/api/files/file-id', 'id' => 'file-id']];
    }
}
